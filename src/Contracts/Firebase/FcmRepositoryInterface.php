<?php

namespace ApiCore\Contracts\Firebase;

use Illuminate\Database\Eloquent\Model;

interface FcmRepositoryInterface
{
    /**
     * Eloquent model instance
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Store notification log
     */
    public function create(array $data): Model;
}
