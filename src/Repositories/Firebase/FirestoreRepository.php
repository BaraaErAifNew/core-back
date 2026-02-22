<?php

namespace ApiCore\Repositories\Firebase;

use ApiCore\Contracts\Firebase\FirestoreRepositoryInterface;
use ApiCore\Exceptions\FirebaseException;
use App\Helpers\Classes\AppCore;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Throwable;

class FirestoreRepository implements FirestoreRepositoryInterface
{
    protected $db;

    public function __construct()
    {
        AppCore::configureFirebaseSettings(tenant()->id);
        $serviceAccountPath = config('api-core.firebase.credentials');

        // Ensure the path is absolute and file exists
        if (empty($serviceAccountPath) || !file_exists($serviceAccountPath)) {
            throw new \RuntimeException("Firebase service account file not found at: {$serviceAccountPath}");
        }

        // Read the JSON file and pass as array for more reliable path resolution
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in Firebase service account file: " . json_last_error_msg());
        }

        $this->db = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->createFirestore()
            ->database();
    }

    /**
     * Resolve Firestore collection path (tenant-aware)
     */
    protected function resolveCollection(string $collection): string
    {
        $tenantId = tenant()->id ?? null;

        return $tenantId
            ? "countries/{$tenantId}/{$collection}"
            : $collection;
    }

    /**
     * Normalize data to Firestore-safe primitives only. Prevents objects (e.g. Enums) from
     * being passed to the Firestore/gRPC client. If you still see "Maximum call stack size"
     * from Google\ApiCore\CredentialsWrapper, it is a known gRPC/gax-php bug; try pinning
     * "google/gax" to "1.34.0" in composer.json or use a REST-based Firestore client.
     */
    protected function normalizeToPrimitives(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeToPrimitives($v), $value);
        }
        if (is_object($value)) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }
            if ($value instanceof \UnitEnum) {
                return $value->name;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format(\DateTimeInterface::ATOM);
            }
            return (string) $value;
        }
        return is_scalar($value) || $value === null ? $value : (string) $value;
    }

    /**
     * Get document data
     *
     * @throws FirebaseException
     */
    public function get(string $collection, string $document): array
    {
        try {
            $snapshot = $this->db
                ->collection($this->resolveCollection($collection))
                ->document($document)
                ->snapshot();

            return $snapshot->exists() ? $snapshot->data() : [];
        } catch (Throwable $e) {
            throw new FirebaseException('Firestore get failed', 0, $e);
        }
    }

    /**
     * Create or replace document
     *
     * @throws FirebaseException
     */
    public function set(string $collection, string $document, array $data): void
    {
        try {
            $this->db
                ->collection($this->resolveCollection($collection))
                ->document($document)
                ->set($this->normalizeToPrimitives($data));
        } catch (Throwable $e) {
            Log::error('Firestore set failed', [
                'collection' => $this->resolveCollection($collection),
                'document' => $document,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            throw new FirebaseException('Firestore set failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set document with merge: create if missing, merge fields if exists (upsert).
     *
     * @throws FirebaseException
     */
    public function setMerge(string $collection, string $document, array $data): void
    {
        if ($data === []) {
            return;
        }

        try {
            $this->db
                ->collection($this->resolveCollection($collection))
                ->document($document)
                ->set($this->normalizeToPrimitives($data), ['merge' => true]);
        } catch (Throwable $e) {
            Log::error('Firestore setMerge failed', [
                'collection' => $this->resolveCollection($collection),
                'document' => $document,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            throw new FirebaseException('Firestore setMerge failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update document fields
     *
     * @throws FirebaseException
     */
    public function update(string $collection, string $document, array $data): void
    {
        if ($data === []) {
            return;
        }

        $normalized = $this->normalizeToPrimitives($data);
        try {
            $this->db
                ->collection($this->resolveCollection($collection))
                ->document($document)
                ->update(
                    array_map(
                        fn ($key, $value) => ['path' => $key, 'value' => $value],
                        array_keys($normalized),
                        $normalized
                    )
                );
        } catch (Throwable $e) {
            Log::error('Firestore update failed', [
                'collection' => $this->resolveCollection($collection),
                'document' => $document,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            throw new FirebaseException('Firestore update failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
