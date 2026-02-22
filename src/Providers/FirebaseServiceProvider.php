<?php

namespace ApiCore\Providers;

use ApiCore\Contracts\Firebase\FcmRepositoryInterface;
use ApiCore\Contracts\Firebase\FirestoreRepositoryInterface;
use ApiCore\Contracts\Firebase\NotificationServiceInterface;
use ApiCore\Repositories\Firebase\FcmRepository;
use ApiCore\Repositories\Firebase\FirestoreRepository;
use ApiCore\Services\FirebaseNotificationService;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(
            FirestoreRepositoryInterface::class,
            FirestoreRepository::class
        );

        $this->app->bind(
            NotificationServiceInterface::class,
            FirebaseNotificationService::class
        );
        $this->app->bind(
            FcmRepositoryInterface::class,
            FcmRepository::class);

    }

}
