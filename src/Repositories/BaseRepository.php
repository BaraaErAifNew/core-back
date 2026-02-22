<?php

namespace ApiCore\Repositories;

use ApiCore\Contracts\RepositoryInterface;
use ApiCore\Exceptions\RepositoryException;
use ApiCore\Traits\HasHook;
use ApiCore\Traits\HasQuery;
use ApiCore\Traits\HasTransform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BaseRepository implements RepositoryInterface
{
    use HasTransform;
    use HasQuery;
    use HasHook;

    /**
     * Model instance or query builder
     */
    protected Model|Builder $model;

    /**
     * Resource class
     */
    protected ?string $resourceClass = null;

    /**
     * Active query
     */
    protected Builder $query;

    protected array $with = [];
    protected array $withFind = [];
    protected array $withAll = [];

    protected array $orderBy = [
        'created_at' => 'desc',
        'ordered' => 'asc',
    ];

    /**
     * Must return model class name
     */
    protected function model(): string
    {
        return $this->model;
    }

    /**
     * Set model
     */
    public function setModel(Model|Builder $model): static
    {
        $this->model = $model;
        $this->setProcessedQuery();
        return $this;
    }

    /**
     * Reset query builder
     */
    public function resetModel(): static
    {
        $this->query = $this->model instanceof Builder
            ? clone $this->model
            : $this->model->newQuery();

        return $this;
    }

    /**
     * Get resource class
     */
    protected function resource(): ?string
    {
        return $this->resourceClass;
    }

    public function setResource(?string $resource): static
    {
        $this->resourceClass = $resource;
        return $this;
    }

    public function getResource(): ?string
    {
        return $this->resource();
    }

    /**
     * Index
     */
    public function index($index_paginate = true)
    {
        $request = request();
        
        if ($request->boolean('no_paginate') || !$index_paginate) {
            return $this->all();
        }

        return $this->paginate(['*'], $request->integer('per_page', 15));
    }

    /**
     * All
     */
    public function all(array $columns = ['*'])
    {
        return $this->transformCollection(
            $this->getQuery()
                ->with($this->withAll)
                ->select($columns)
                ->get()
        );
    }

    /**
     * Paginate
     */
    public function paginate(array $columns = ['*'], int $perPage = 15): LengthAwarePaginator|AnonymousResourceCollection
    {
        return $this->transformPaginator(
            $this->getQuery()
                ->with($this->withAll)
                ->paginate($perPage, $columns)
        );
    }

    /**
     * Find
     */
    public function find(int|string $id, array $columns = ['*'])
    {
        return $this->transformModel(
            $this->getQuery()
                ->with($this->withFind)
                ->select($columns)
                ->find($id),
            $this->serializeShowMethod
        );
    }

    /**
     * Find or fail
     */
    public function findOrFail(int|string $id, array $columns = ['*'])
    {
        return $this->transformModel(
            $this->getQuery()
                ->with($this->withFind)
                ->select($columns)
                ->findOrFail($id),
            $this->serializeShowMethod
        );
    }

    /**
     * Find by field
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->transformCollection(
            $this->getQuery()
                ->with($this->withFind)
                ->select($columns)
                ->where($field, $value)
                ->get()
        );
    }

    /**
     * Find one by field
     */
    public function findOneBy(string $field, mixed $value, array $columns = ['*'])
    {
        return $this->transformModel(
            $this->getQuery()
                ->with($this->withFind)
                ->select($columns)
                ->where($field, $value)
                ->first()
        );
    }

    /**
     * Create
     * @throws \Exception
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $this->creating($data);
            $data = $this->getCreateAttributes($data);

            $model = $this->getQuery()->create(
                $this->saving($data)
            );

            // Reload relationships if any are defined for find operations
            if (!empty($this->withFind)) {
                $model->load($this->withFind);
            }

            $request = request();
            $this->created($request, $model);
            $this->saved($request, $model);

            $this->resetModel();
            DB::commit();
            return $this->transformModel($model, $this->serializeForStoreMethod);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Update
     */
    public function update(int|string $id, array $data)
    {
        DB::beginTransaction();
        try {
            $model = $this->getQuery()->findOrFail($id);

            $data = $this->getUpdateAttributes($data, $model);
            $model->update($this->saving($data));

            // Refresh to get latest attributes from DB (handles triggers, timestamps, etc.)
            $model->refresh();

            // Reload relationships if any are defined for find operations
            if (!empty($this->withFind)) {
                $model->load($this->withFind);
            }

            $request = request();
            $this->updated($request, $model);
            $this->saved($request, $model);

            $this->resetModel();
            DB::commit();
            return $this->transformModel($model, $this->serializeForUpdateMethod);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Update or create
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        DB::beginTransaction();
        try {
            $model = $this->model->updateOrCreate($attributes, $values);

            // Reload relationships if any are defined for find operations
            if (!empty($this->withFind)) {
                $model->load($this->withFind);
            }

            $this->resetModel();
            DB::commit();
            return $this->transformModel($model);
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Delete
     */
    public function delete(int|string $id): bool
    {
        $model = $this->getQuery()->findOrFail($id);

        $model = $this->deleting($model);
        $deleted = $model->delete();
        $model = $this->deleted($model);

        $this->resetModel();
        return $deleted;
    }
}
