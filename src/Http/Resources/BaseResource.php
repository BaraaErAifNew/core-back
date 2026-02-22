<?php

namespace ApiCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

  class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        // By default, just delegate to the parent implementation.
        // Concrete resources can override this method to customize the payload.
        return parent::toArray($request);
    }

    public function serializeForShow()
    {
        return $this->toArray(\request());
    }

      public function serializeForStore()
      {
          return $this->toArray(\request());
      }

      public function SerializeForUpdate()
      {
          return $this->toArray(\request());
      }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request)
    {
        return parent::with($request);
    }
}


