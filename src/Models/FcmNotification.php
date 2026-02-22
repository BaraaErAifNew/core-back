<?php

namespace ApiCore\Models;

class FcmNotification extends BaseModel
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'topic',
        'title',
        'body',
        'notifiable_type',
        'notifiable_id',
        'read_at',
        'payload',
        'type',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'title' => 'array',
        'body' => 'array',
        'payload' => 'array',
    ];

    protected $translatable = ['title', 'body'];

    protected static function booted(): void
    {
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }
}
