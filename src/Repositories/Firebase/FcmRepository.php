<?php

namespace ApiCore\Repositories\Firebase;

use ApiCore\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class FcmRepository extends BaseRepository
{

    public function create(array $data): Model
    {
        return $this->model->create([
            'payload' => $data,
            'type' => data_get($data, 'status'),
        ]);
    }
}
