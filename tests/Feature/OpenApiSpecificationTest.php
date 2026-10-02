<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

function openApiDocument(): array
{
    $contents = file_get_contents(base_path('docs/openapi.json'));

    expect($contents)->not->toBeFalse();

    return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
}

it('contains a valid versioned OpenAPI document with unique operation ids', function () {
    $document = openApiDocument();
    $operationIds = [];

    foreach ($document['paths'] as $path) {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (isset($path[$method])) {
                $operationIds[] = $path[$method]['operationId'];
            }
        }
    }

    expect($document['openapi'])->toBe('3.1.0')
        ->and($document['info']['version'])->toBe('1.0.0')
        ->and($operationIds)->not->toBeEmpty()
        ->and(array_unique($operationIds))->toHaveCount(count($operationIds));
});

it('documents every application API route and method', function () {
    $document = openApiDocument();
    $documented = collect($document['paths'])
        ->flatMap(function (array $path, string $uri): array {
            return collect(['get', 'post', 'put', 'patch', 'delete'])
                ->filter(fn (string $method): bool => isset($path[$method]))
                ->map(fn (string $method): string => strtoupper($method).' '.$uri)
                ->all();
        })
        ->sort()
        ->values();

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/'))
        ->flatMap(function ($route): array {
            $uri = '/'.substr($route->uri(), 4);

            return collect($route->methods())
                ->reject(fn (string $method): bool => $method === 'HEAD')
                ->map(fn (string $method): string => $method.' '.$uri)
                ->all();
        })
        ->sort()
        ->values();

    expect($documented->all())->toBe($routes->all());
});

it('declares bearer authentication and common API errors', function () {
    $document = openApiDocument();

    expect($document['components']['securitySchemes']['bearerAuth']['scheme'])
        ->toBe('bearer');

    foreach ($document['paths'] as $uri => $path) {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (! isset($path[$method])) {
                continue;
            }

            $operation = $path[$method];

            if (in_array($uri, ['/register', '/login'], true)) {
                expect($operation['security'])->toBe([]);
            } else {
                expect($operation['security'])->toContain(['bearerAuth' => []])
                    ->and($operation['responses'])->toHaveKey('401')
                    ->and($operation['responses'])->toHaveKey('429');
            }
        }
    }

    expect($document['components']['responses'])
        ->toHaveKeys(['Unauthorized', 'Forbidden', 'NotFound', 'ValidationError', 'TooManyRequests']);
});
