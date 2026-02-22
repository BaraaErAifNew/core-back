<?php

namespace ApiCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeApiResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-resource
                            {name : The name of the resource}
                            {--model= : The model class name}
                            {--model-namespace= : The full model namespace (e.g., App\\Models\\Product\\Product)}
                            {--module= : The module name (e.g., Customer)}
                            {--namespace= : The namespace for the generated Classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource (Controller, Repository, Resource, Request)';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $modelName = $this->option('model') ?: $name;
        $moduleName = $this->option('module');

        // Determine base namespace and paths based on whether it's a module or app
        if ($moduleName) {
            $moduleName = Str::studly($moduleName);
            $modulePath = base_path("Modules/{$moduleName}");

            // Validate module exists
            if (!$this->files->isDirectory($modulePath)) {
                $this->error("Module '{$moduleName}' does not exist in Modules/{$moduleName}");
                return Command::FAILURE;
            }

            $namespace = $this->option('namespace') ?: "Modules\\{$moduleName}\\app";
            $basePath = $modulePath . '/app';
        } else {
            $namespace = $this->option('namespace') ?: 'App';
            $basePath = app_path();
        }

        // Determine paths
        $controllerPath = $basePath . '/Http/Controllers';
        $repositoryPath = $basePath . '/Repositories';
        $resourcePath = $basePath . '/Http/Resources';
        $requestPath = $basePath . '/Http/Requests';

        // Generate class names
        $className = Str::studly($name);
        $modelClass = Str::studly($modelName);
        $controllerClass = $className . 'Controller';
        $repositoryClass = $className . 'Repository';
        $resourceClass = $className . 'Resource';
        $requestClass = $className . 'Request';

        // Generate namespaces
        $controllerNamespace = $namespace . '\\Http\\Controllers';
        $repositoryNamespace = $namespace . '\\Repositories';
        $resourceNamespace = $namespace . '\\Http\\Resources';
        $requestNamespace = $namespace . '\\Http\\Requests';

        // Model namespace - use provided or default to App\Models\{ModelClass}
        // For modules, models are typically in App\Models, not in the module
        $modelNamespace = $this->option('model-namespace')
            ?: 'App\\Models\\' . $modelClass;

        // Create directories if they don't exist
        $this->ensureDirectoryExists($controllerPath);
        $this->ensureDirectoryExists($repositoryPath);
        $this->ensureDirectoryExists($resourcePath);
        $this->ensureDirectoryExists($requestPath);

        // Generate files
        $this->generateController($controllerPath, $controllerClass, $controllerNamespace, $repositoryClass, $repositoryNamespace, $resourceClass, $resourceNamespace, $requestClass, $requestNamespace, $modelClass, $modelNamespace, $modelName);
        $this->generateRepository($repositoryPath, $repositoryClass, $repositoryNamespace, $modelClass, $modelNamespace);
        $this->generateResource($resourcePath, $resourceClass, $resourceNamespace);
        $this->generateRequest($requestPath, $requestClass, $requestNamespace);

        $this->info("API Resource files created successfully!");
        $this->line("Controller: {$controllerNamespace}\\{$controllerClass}");
        $this->line("Repository: {$repositoryNamespace}\\{$repositoryClass}");
        $this->line("Resource: {$resourceNamespace}\\{$resourceClass}");
        $this->line("Request: {$requestNamespace}\\{$requestClass}");

        return Command::SUCCESS;
    }

    /**
     * Generate the controller file.
     *
     * @param string $path
     * @param string $className
     * @param string $namespace
     * @param string $repositoryClass
     * @param string $repositoryNamespace
     * @param string $resourceClass
     * @param string $resourceNamespace
     * @param string $requestClass
     * @param string $requestNamespace
     * @param string $modelClass
     * @param string $modelNamespace
     * @param string $modelName
     * @return void
     */
    protected function generateController($path, $className, $namespace, $repositoryClass, $repositoryNamespace, $resourceClass, $resourceNamespace, $requestClass, $requestNamespace, $modelClass, $modelNamespace, $modelName)
    {
        $stub = $this->files->get(__DIR__ . '/../../../stubs/controller.stub');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ repositoryClass }}', $repositoryClass, $stub);
        $stub = str_replace('{{ repositoryNamespace }}', $repositoryNamespace, $stub);
        $stub = str_replace('{{ resourceClass }}', $resourceClass, $stub);
        $stub = str_replace('{{ resourceNamespace }}', $resourceNamespace, $stub);
        $stub = str_replace('{{ requestClass }}', $requestClass, $stub);
        $stub = str_replace('{{ requestNamespace }}', $requestNamespace, $stub);
        $stub = str_replace('{{ modelClass }}', $modelClass, $stub);
        $stub = str_replace('{{ modelNamespace }}', $modelNamespace, $stub);
        $stub = str_replace('{{ modelName }}', $modelName, $stub);

        $this->files->put($path . '/' . $className . '.php', $stub);
    }

    /**
     * Generate the repository file.
     *
     * @param string $path
     * @param string $className
     * @param string $namespace
     * @param string $modelClass
     * @param string $modelNamespace
     * @return void
     */
    protected function generateRepository($path, $className, $namespace, $modelClass, $modelNamespace)
    {
        $stub = $this->files->get(__DIR__ . '/../../../stubs/repository.stub');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ modelClass }}', $modelClass, $stub);
        $stub = str_replace('{{ modelNamespace }}', $modelNamespace, $stub);

        $this->files->put($path . '/' . $className . '.php', $stub);
    }

    /**
     * Generate the resource file.
     *
     * @param string $path
     * @param string $className
     * @param string $namespace
     * @return void
     */
    protected function generateResource($path, $className, $namespace)
    {
        $stub = $this->files->get(__DIR__ . '/../../../stubs/resource.stub');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);

        $this->files->put($path . '/' . $className . '.php', $stub);
    }

    /**
     * Generate the request file.
     *
     * @param string $path
     * @param string $className
     * @param string $namespace
     * @return void
     */
    protected function generateRequest($path, $className, $namespace)
    {
        $stub = $this->files->get(__DIR__ . '/../../../stubs/request.stub');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);

        $this->files->put($path . '/' . $className . '.php', $stub);
    }

    /**
     * Ensure the directory exists.
     *
     * @param string $path
     * @return void
     */
    protected function ensureDirectoryExists($path)
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }
}

