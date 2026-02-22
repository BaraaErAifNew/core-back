<?php

namespace ApiCore\Traits;

use ApiCore\Http\Requests\BaseRequest;
use ApiCore\Http\Resources\BaseResource;
use ApiCore\Models\BaseModel;
use ApiCore\Repositories\BaseRepository;
use Illuminate\Support\Str;

trait ControllerBootstrap
{

    public function register()
    {
        // Initialize repository and model instances via the container
        $this->initClass('repository', BaseRepository::class);
        $this->initClass('model', BaseModel::class);
        $this->initClass('resource', BaseResource::class);
        $this->initClass('request', BaseRequest::class);
        $this->modelName = $this->getModelName();
    }

    /**
     * Resolve a default class or fallback from the container
     */
    protected function initClass(string $type, string $fallbackClass): void
    {
        if ($this->$type) {
            $this->$type = app($this->$type);
            return;
        }

        $method = "getDefault{$type}";
        $called = method_exists($this, $method) && class_exists($this->$method())
            ? $this->$method()
            : $fallbackClass;

        $this->$type = $type != 'resource' ? app($called) : $called;
    }

    /**
     * Generate class name based on controller namespace
     */
    protected function generateClassName(string $folder, string $suffix): string
    {
        return Str::replace(
            ['Controllers', 'Controller'],
            [$folder, $suffix],
            static::class
        );
    }

    /**
     * Default Request class
     */
    public function getDefaultRequest(): string
    {
        return $this->generateClassName('Requests', 'Request');
    }

    /**
     * Default Resource class
     */
    public function getDefaultResource(): string
    {
        return $this->generateClassName('Resources', 'Resource');
    }

    /**
     * Default Model class
     */
    public function getDefaultModel(): string
    {
        return models_path(
            Str::remove('Controller', class_basename(static::class))
        );
    }

    /**
     * Default Repository class with BaseRepository fallback
     */
    public function getDefaultRepository(): string
    {
        $repository = Str::replace(
            ['Http\\', 'Controllers', 'Controller'],
            ['', 'Repositories', 'Repository'],
            static::class
        );

        return class_exists($repository)
            ? $repository
            : Str::replace(
                class_basename($repository),
                'BaseRepository',
                $repository
            );
    }

    /**
     * Get the model name for translation.
     *
     * @return string
     */
    protected function getModelName(): string
    {
        if (is_object($this->model)) {
            return class_basename($this->model);
        }

        if (is_string($this->model)) {
            return class_basename($this->model);
        }

        return 'Resource';
    }

}
