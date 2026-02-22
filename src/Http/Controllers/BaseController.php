<?php

namespace ApiCore\Http\Controllers;

use ApiCore\Traits\ControllerBootstrap;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

abstract class BaseController extends Controller
{
    use ControllerBootstrap;

    /**
     * @var $model
     */
    protected $model;
    /**
     * @var $repository
     */
    protected $repository;
    /**
     * @var $resource
     */
    protected $resource;

    protected $request;

    protected $modelName;

    protected bool $indexPaginate = true;

    public function __construct()
    {
        $this->register();

        $this->repository
            ->setModel($this->model)
            ->setResource($this->resource);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->repository->index($this->indexPaginate);

        return Response::api(
            true,
            trans('apicore::messages.resources_retrieved_successfully', ['model' => $this->modelName]),
            $data
        );
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->repository->create(
            $request->all()
        );

        return Response::api(
            SUCCESS_STATUS,
            trans('apicore::messages.resource_created_successfully', ['model' => $this->modelName]),
            $data,
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id): JsonResponse
    {
        return Response::api(
            SUCCESS_STATUS,
            trans('apicore::messages.resource_retrieved_successfully', ['model' => $this->modelName]),
            $this->repository->findOrFail($id),
            $this->showExtraData(),
        );
    }

    public function showExtraData($data = [])
    {
        return $data;
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $data = $this->repository->update(
            $id,
            $request->all()
        );

        return Response::api(
            SUCCESS_STATUS,
            trans('apicore::messages.resource_updated_successfully', ['model' => $this->modelName]),
            $data
        );
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $this->repository->delete($id);

        return Response::api(
            SUCCESS_STATUS,
            trans('apicore::messages.resource_deleted_successfully', ['model' => $this->modelName])
        );
    }
}
