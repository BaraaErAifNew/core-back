<?php

namespace ApiCore\Contracts\Firebase;

interface FirestoreRepositoryInterface
{
    public function get(string $collection, string $document): array;

    public function set(string $collection, string $document, array $data): void;

    /**
     * Set document with merge: create if missing, merge fields if exists.
     */
    public function setMerge(string $collection, string $document, array $data): void;

    public function update(string $collection, string $document, array $data): void;
}
