<?php

namespace ApiCore\Traits;

use ApiCore\Http\Resources\BaseResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasTransform
{

    protected $serializeMethod = 'toArray';
    protected $serializeShowMethod = 'serializeForShow';
    protected $serializeForStoreMethod = 'serializeForStore';
    protected $serializeForUpdateMethod = 'SerializeForUpdate';

    protected $additional = [];

    /**
     *
     *
     * a model using the resource class.
     *
     * @param Model|null $model
     * @param Request|null $request
     * @return BaseResource|Model|null
     */
    protected function transformModel(?Model $model, $serializeShowMethod = null)
    {
        if (!$model) {
            return null;
        }

        $resourceClass = $this->resource();
        if (!$resourceClass) {
            return $model;
        }

        $resource = new $resourceClass($model);

        if (!empty($this->additional)) {
            $resource->additional($this->additional);
        }

        // Cache request to avoid repeated helper calls
        $request = request();

        return $resource->{$serializeShowMethod ?? $this->serializeShowMethod}($request);
    }

    /**
     * Transform a collection using the resource class.
     *
     * @param Collection $collection
     * @param Request|null $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|Collection
     */
    protected function transformCollection(Collection $collection, $serialize_method = null)
    {
        $resourceClass = $this->resource();
        if (!$resourceClass) {
            return $collection;
        }

        // Cache request and additional data to avoid repeated access
        $request = request();
        $additional = $this->additional;
        $serializeMethod = $serialize_method ?? $this->serializeMethod;

        $resources = $resourceClass::collection($collection)
            ->map(function ($resource) use ($additional, $serializeMethod, $request) {
                if (!empty($additional)) {
                    $resource->additional($additional);
                }
                return $resource->{$serializeMethod}($request);
            });

        return $resources;
    }

    /**
     * Transform paginated results using the resource class.
     *
     * @param LengthAwarePaginator $paginator
     * @param Request|null $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|LengthAwarePaginator
     */
    protected function transformPaginator(LengthAwarePaginator|AnonymousResourceCollection $paginator): LengthAwarePaginator|AnonymousResourceCollection
    {
        $resourceClass = $this->resource();
        if (!$resourceClass) {
            return $paginator;
        }

        // Cache request and additional data to avoid repeated access
        $request = request();
        $additional = $this->additional;
        $serializeMethod = $this->serializeMethod;

        return $paginator->setCollection(
            $paginator->getCollection()->map(function ($item) use ($resourceClass, $additional, $serializeMethod, $request) {
                $resource = new $resourceClass($item);
                if (!empty($additional)) {
                    $resource->additional($additional);
                }
                return $resource->{$serializeMethod}($request);
            })
        );
    }

    public function setSerializeMethod($serialize_method)
    {
        $this->serializeMethod = $serialize_method;
        return $this;
    }

    /**
     * Set additional data to be included with the resource.
     *
     * @param array $additional
     * @return static
     */
    public function setAdditional(array $additional): static
    {
        $this->additional = $additional;
        return $this;
    }

    /**
     * Add additional data to the existing additional data.
     *
     * @param array $additional
     * @return static
     */
    public function addAdditional(array $additional): static
    {
        $this->additional = array_merge($this->additional, $additional);
        return $this;
    }

    /**
     * Get the additional data.
     *
     * @return array
     */
    public function getAdditional(): array
    {
        return $this->additional;
    }

    /**
     * Clear the additional data.
     *
     * @return static
     */
    public function clearAdditional(): static
    {
        $this->additional = [];
        return $this;
    }

}
