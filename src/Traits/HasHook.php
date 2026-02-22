<?php

namespace ApiCore\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

trait HasHook
{
    public function creating(array $data): array
    {
        return $this->saving($data);
    }

    public function updating(array $data): array
    {
        return $this->saving($data);
    }

    public function saving(array $data): array
    {
        return $data;
    }

    public function getCreateAttributes(array $data): array
    {
        return $this->getAttributes($data);
    }

    public function getUpdateAttributes(array $data, $model): array
    {
        return $this->getAttributes($data);
    }

    public function getAttributes(array $data): array
    {
        return $data;
    }

    public function created(Request $request, Model $model): Model
    {
        return $this->saved($request, $model);
    }

    public function updated(Request $request, Model $model): Model
    {
        return $this->saved($request, $model);
    }

    public function saved(Request $request, Model $model): Model
    {
        return $model;
    }

    public function deleting(Model $model): Model
    {
        return $model;
    }

    public function deleted(Model $model): Model
    {
        return $model;
    }
}
