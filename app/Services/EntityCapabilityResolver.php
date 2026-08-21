<?php

namespace UasDashboard\WebCurator\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EntityCapabilityResolver
{
    public function resolve(?int $entityId, ?string $accessToken): array
    {
        if (!$entityId) {
            return ['programs' => false];
        }

        $sessionKey = "web_curator_entity_capabilities.{$entityId}";
        $fallback = (array) session($sessionKey, ['programs' => false]);

        try {
            $capabilities = Cache::remember(
                "web-curator:entity-capabilities:{$entityId}",
                now()->addMinutes(10),
                function () use ($entityId, $accessToken) {
                    $response = Http::acceptJson()
                        ->withToken((string) $accessToken)
                        ->connectTimeout(2)
                        ->timeout(5)
                        ->retry(2, 100, fn (\Throwable $exception) => $exception instanceof ConnectionException)
                        ->get(rtrim((string) config('web-api.api_base_url'), '/') . '/entity/profile', [
                            'entity_id' => $entityId,
                        ]);

                    if (!$response->successful()) {
                        throw new \RuntimeException('Entity capability request failed.');
                    }

                    return [
                        'programs' => strcasecmp((string) $response->json('data.entity_category'), 'Academic') === 0,
                    ];
                }
            );

            session()->put($sessionKey, $capabilities);

            return $capabilities;
        } catch (\Throwable $exception) {
            \Log::warning('Failed to resolve Web Curator entity capabilities.', [
                'entity_id' => $entityId,
                'exception' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }
}
