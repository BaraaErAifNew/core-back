<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ApiCore\Contracts\Firebase\FcmRepositoryInterface;
use Illuminate\Support\Str;

const SUCCESS_STATUS = true;
const ERROR_STATUS = false;

const CUSTOMER_GUARD = 'customer-api';
const CUSTOMER_GUARD_MIDDLEWARE = 'auth:' . CUSTOMER_GUARD;

/*
|--------------------------------------------------------------------------
| General Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('models_path')) {
    function models_path(string $model): string
    {
        return 'App\\Models\\' . ltrim($model, '\\');
    }
}
/* -----------------------------------------------------------------
  | Helpers
  |-----------------------------------------------------------------*/

function arrayContains(?array $haystack, $needle): bool
{
    return !empty($haystack) && $needle && in_array($needle, $haystack, true);
}

function arrayIntersects(?array $a, array $b): bool
{
    return !empty($a) && !empty(array_intersect($a, $b));
}

function locale(): string
{
    return app()->getLocale();
}

/*
|--------------------------------------------------------------------------
| API Responses
|--------------------------------------------------------------------------
*/

function errorResponse(
    string $message,
    int    $statusCode = 400
): JsonResponse
{
    return \Illuminate\Support\Facades\Response::api(
        ERROR_STATUS,
        $message,
        null,
        null,
        $statusCode,
        $statusCode
    );
}

function successResponse(
    string $message,
    mixed  $data = null,
    mixed  $extra = null
): JsonResponse
{
    return \Illuminate\Support\Facades\Response::api(
        SUCCESS_STATUS,
        $message,
        $data,
        $extra
    );
}

/*
|--------------------------------------------------------------------------
| Utilities
|--------------------------------------------------------------------------
*/

function flag(?string $countryCode): ?string
{
    return $countryCode
        ? "https://flagcdn.com/" . strtolower($countryCode) . ".svg"
        : null;
}

function generateOtp(int $digits = 4): int
{
    $min = 10 ** ($digits - 1);
    $max = (10 ** $digits) - 1;

    return random_int($min, $max);
}

function data_get_local(mixed $value, string $path, mixed $default = null): mixed
{
    return data_get($value, "{$path}." . locale(), $default);
}

/*
|--------------------------------------------------------------------------
| Settings Serializer
|--------------------------------------------------------------------------
*/

function settingSerializer(
    string    $settingClass,
    string    $key,
    ?callable $transformCallback = null
)
{
    $settings = app($settingClass)?->$key;

    if (empty($settings)) {
        return null;
    }

    $locale = locale();

    return collect($settings)->map(function ($item) use ($locale, $transformCallback) {
        $result = [];

        foreach ($item as $itemKey => $value) {
            if (
                is_array($value) &&
                !array_is_list($value) &&
                (isset($value['en']) || isset($value['ar']))
            ) {
                $result[$itemKey] =
                    $value[$locale]
                    ?? $value['en']
                    ?? $value['ar']
                    ?? reset($value);
            } else {
                $result[$itemKey] = $value;
            }
        }

        return $transformCallback
            ? $transformCallback($result, $item)
            : $result;
    });
}

/*
|--------------------------------------------------------------------------
| Country Data (Cached)
|--------------------------------------------------------------------------
*/

if (!function_exists('fetch_country_data')) {
    function fetch_country_data(string $callingCode): ?array
    {
        if ($callingCode === '') {
            return null;
        }

        return Cache::remember(
            "country_calling_code_{$callingCode}",
            now()->addDay(),
            function () use ($callingCode) {
                try {
                    $response = Http::get(
                        "https://www.apicountries.com/callingcode/{$callingCode}"
                    );

                    if ($response->successful()) {
                        $data = $response->json();
                        return is_array($data) ? ($data[0] ?? null) : $data;
                    }
                } catch (\Throwable $e) {
                    Log::warning(
                        "Country fetch failed [calling_code={$callingCode}]: {$e->getMessage()}"
                    );
                }

                return null;
            }
        );
    }
}

function get_country_data(string $code): ?array
{
    return Cache::remember(
        "country_alpha_{$code}",
        now()->addDay(),
        function () use ($code) {
            $response = Http::get("https://restcountries.com/v3.1/alpha/{$code}");

            if (!$response->successful()) {
                return null;
            }

            $country = $response->json()[0] ?? null;

            if (!$country) {
                return null;
            }

            $root = data_get($country, 'idd.root', '');
            $suffix = data_get($country, 'idd.suffixes.0', '');
            $currencies = data_get($country, 'currencies', []);

            return [
                'name' => data_get($country, 'name.common'),
                'phone_prefix' => $root . $suffix,
                'lat' => data_get($country, 'latlng.0'),
                'lng' => data_get($country, 'latlng.1'),
                'flag' => data_get($country, 'flags.png'),
                'currencies' => is_array($currencies) ? $currencies : [],
            ];
        }
    );
}

/*
|--------------------------------------------------------------------------
| FCM Notifications
|--------------------------------------------------------------------------
*/
function send_fcm_notification(
    $notifiable,
    array $title,
    array $body,
    array $extra = [],
    bool $saved = true
): void
{
    $topic = fcm_topic($notifiable);

    $payload = [
        'title' => $title,
        'body' => $body,
        'extra' => $extra,
    ];

    send_fcm_to_topic($topic, $title, $body, $extra);

    if ($saved && data_get($extra, 'save', true)) {
        logNotification($topic, $payload, $notifiable);
    }
}

if (!function_exists('logNotification')) {
    function logNotification(string $topic, array $payload, $notifiable): void
    {
        \ApiCore\Models\FcmNotification::create(transform_fcm_notification($topic, $payload, $notifiable));
    }
}

function transform_fcm_notification(string $topic, array $payload, $notifiable): array
{
    // Handle both object instances and class name strings
    $notifiableType = is_string($notifiable) ? $notifiable : get_class($notifiable);
    $notifiableId = is_object($notifiable) ? data_get($notifiable, 'id') : null;

    return [
        'notifiable_type' => $notifiableType,
        'notifiable_id' => $notifiableId,
        'payload' => data_get($payload, 'extra'),
        'topic' => $topic,
        'title' => data_get($payload, 'title'),
        'body' => data_get($payload, 'body'),
    ];
}

function fcm_topic($notifiable): string
{
    // Example: use class name and id to generate topic
    $class = strtolower(class_basename($notifiable)); // e.g. 'User'
    return "{$class}-{$notifiable->id}";
}

function send_fcm_to_topic($topic, $title, $body, $data = [])
{
    Log::info('send_fcm_to topic: ' . $topic,[
        'title' => $title,
        'body' => $body,
        'data' => $data,
    ]);
    $notifier = app(\ApiCore\Contracts\Firebase\NotificationServiceInterface::class);
    $notifier->sendToTopic($topic, $title, $body, $data);
}
