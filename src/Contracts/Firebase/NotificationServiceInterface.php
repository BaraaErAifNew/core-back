<?php

namespace ApiCore\Contracts\Firebase;

interface NotificationServiceInterface
{
    public function sendToTopic(
        string $topic,
        array $title,
        array $body,
        array $data = []
    ): void;
}
