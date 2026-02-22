<?php

namespace ApiCore\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface RepositoryInterface
{
    public function all(array $columns = ['*']);

    public function paginate(
        array $columns = ['*'],
        int $perPage = 15
    ): LengthAwarePaginator|AnonymousResourceCollection;

    public function find(int|string $id, array $columns = ['*']);

    public function findOrFail(int|string $id, array $columns = ['*']);

    public function findBy(
        string $field,
        mixed $value,
        array $columns = ['*']
    ): Collection;

    public function findOneBy(
        string $field,
        mixed $value,
        array $columns = ['*']
    );

    public function create(array $data);

    public function update(int|string $id, array $data);

    public function updateOrCreate(array $attributes, array $values = []);

    public function delete(int|string $id): bool;

    public function deleteWhere(array $conditions): int;
}
