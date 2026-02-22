<?php

namespace ApiCore\Traits;

use Illuminate\Database\Eloquent\Collection;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait HasQuery
{

    public function getQuery()
    {
        return $this->query;
    }

    public function setProcessedQuery()
    {
        $this->query = $this->model->newQuery()
            ->with($this->with);
        $this->query = $this
            ->orderByArray();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteWhere(array $conditions): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $deleted = $query->delete();
        $this->resetModel();
        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function where(array $conditions, array $columns = ['*']): Collection
    {
        $query = $this->model->select($columns);

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $results = $query->get();
        $this->resetModel();
        return $results;
    }


    public function orderByArray()
    {
        // Cache fillable and timestamp columns to avoid repeated computation
        static $fillableCache = [];
        static $timestampCache = [];
        
        $modelClass = get_class($this->model);
        
        if (!isset($fillableCache[$modelClass])) {
            $fillableCache[$modelClass] = $this->model->getFillable();
        }
        
        if (!isset($timestampCache[$modelClass])) {
            $timestampCache[$modelClass] = $this->timestamps();
        }
        
        $allowedColumns = array_merge($fillableCache[$modelClass], $timestampCache[$modelClass]);
        
        foreach ($this->orderBy as $field => $direction) {
            if (in_array($field, $allowedColumns, true)) {
                $this->query = $this->query->orderBy($field, $direction);
            }
        }
        return $this->query;
    }

    public function timestamps()
    {
        return [$this->model->getCreatedAtColumn(), $this->model->getUpdatedAtColumn()];
    }

    /**
     * Set a custom query builder instance.
     *
     * @param Closure(Builder): void $callback
     * @return static
     */
    public function customQuery(Closure $callback): static
    {
        $this->query = $callback($this->query);

        return $this;
    }

}
