<?php

namespace ApiCore\Services;

use ApiCore\Contracts\Firebase\NotificationServiceInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService implements NotificationServiceInterface
{

    protected $messaging;

    public function __construct()
    {
        $serviceAccountPath = config('api-core.firebase.credentials');

        // Ensure the path is absolute and file exists
        if (!file_exists($serviceAccountPath)) {
            throw new \RuntimeException("Firebase service account file not found at: {$serviceAccountPath}");
        }

        // Read the JSON file and pass as array for more reliable path resolution
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in Firebase service account file: " . json_last_error_msg());
        }

        $this->messaging = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->createMessaging();
    }

    public function sendToTopic(
        string $topic,
        array  $title,
        array  $body,
        array  $data = []
    ): void
    {
        // Merge title and body into the data payload
        $payload = $this->normalizeData(array_merge($data, [
            'title' => json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'body'  => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]));

        $message = CloudMessage::withTarget('topic', $topic)
//            ->withNotification($payload)
            ->withData($payload);

        $this->messaging->send($message);
    }

    /**
     * Normalize data array to ensure all values are strings (or stringable)
     * as required by Firebase Cloud Messaging
     *
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            // Ensure key is a string
            $stringKey = (string)$key;

            // Skip non-stringable keys (though this shouldn't happen in practice)
            if (!is_string($stringKey) && !is_numeric($stringKey)) {
                continue;
            }

            // Convert value to string
            if (is_array($value)) {
                // JSON encode arrays (handles nested arrays recursively)
                $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $normalized[$stringKey] = $json !== false ? $json : '';
            } elseif (is_object($value)) {
                // Handle Laravel Collections
                if ($value instanceof \Illuminate\Support\Collection) {
                    $json = json_encode($value->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $normalized[$stringKey] = $json !== false ? $json : '';
                } elseif (method_exists($value, 'toArray')) {
                    // Laravel models and resources
                    $arrayValue = $value->toArray();
                    // Ensure the result is properly serialized
                    $json = json_encode($arrayValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $normalized[$stringKey] = $json !== false ? $json : '';
                } elseif (method_exists($value, '__toString')) {
                    // Objects with __toString method
                    $normalized[$stringKey] = (string)$value;
                } elseif ($value instanceof \JsonSerializable) {
                    // Objects implementing JsonSerializable
                    $json = json_encode($value->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $normalized[$stringKey] = $json !== false ? $json : '';
                } else {
                    // Try to JSON encode, fallback to empty string if it fails
                    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $normalized[$stringKey] = $json !== false ? $json : '';
                }
            } elseif (is_bool($value)) {
                // Convert boolean to string
                $normalized[$stringKey] = $value ? '1' : '0';
            } elseif (is_null($value)) {
                // Convert null to empty string
                $normalized[$stringKey] = '';
            } elseif (is_resource($value)) {
                // Resources cannot be serialized
                $normalized[$stringKey] = '';
            } elseif (is_scalar($value)) {
                // Convert scalar types (int, float, string) to string
                $normalized[$stringKey] = (string)$value;
            } else {
                // Fallback for any other type
                $normalized[$stringKey] = '';
            }
        }

        return $normalized;
    }
}
