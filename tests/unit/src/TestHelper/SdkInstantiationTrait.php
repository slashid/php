<?php

namespace SlashId\Test\Php\TestHelper;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use SlashId\Php\SlashIdSdk;

/**
 * Helper trait to instantiate a SlashIdSdk class with a GuzzlePHP mock.
 */
trait SdkInstantiationTrait
{
    protected function sdk(?array &$historyContainer = null, ?array $mockCalls = null, string $environment = SlashIdSdk::ENVIRONMENT_PRODUCTION): SlashIdSdk
    {
        $handlerStack = null;
        if (!empty($mockCalls)) {
            $mockHandler = new MockHandler(
                array_map(fn($mockCall) => new Response(
                    $mockCall[0],
                    [
                        'Content-Type' => 'application/json',
                    ],
                    $mockCall[1],
                ), $mockCalls),
            );

            $handlerStack = HandlerStack::create($mockHandler);

            if (is_array($historyContainer)) {
                $history = Middleware::history($historyContainer);
                $handlerStack->push($history);
            }
        }

        return new SlashIdSdk($environment, 'org_id', 'api_key', $handlerStack);
    }
}
