<?php

declare(strict_types=1);

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

/**
 * @return array<string, mixed>
 */
function postmanJson(string $path): array
{
    $contents = file_get_contents(base_path($path));

    expect($contents)->not->toBeFalse();

    return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @param  array<int, array<string, mixed>>  $items
 * @return array<int, array<string, mixed>>
 */
function postmanRequests(array $items): array
{
    return collect($items)
        ->flatMap(function (array $item): array {
            if (isset($item['request'])) {
                return [$item];
            }

            return postmanRequests($item['item'] ?? []);
        })
        ->values()
        ->all();
}

function normalizedApiOperation(string $method, string $url): string
{
    $path = parse_url(str_replace('{{base_url}}', 'http://taskflow.test/api', $url), PHP_URL_PATH);
    $normalized = preg_replace('/\{\{[^}]+}}|\{[^}]+}/', '{id}', (string) $path);

    return strtoupper($method).' '.$normalized;
}

it('contains valid Postman collection and environment documents', function () {
    $collection = postmanJson('docs/postman/TaskFlow-API.postman_collection.json');
    $environment = postmanJson('docs/postman/TaskFlow-Local.postman_environment.json');

    expect($collection['info']['schema'])
        ->toBe('https://schema.getpostman.com/json/collection/v2.1.0/collection.json')
        ->and($collection['item'])->toHaveCount(4)
        ->and($environment['_postman_variable_scope'])->toBe('environment');

    $values = collect($environment['values'])->keyBy('key');

    expect($values['token']['value'])->toBe('')
        ->and($values['token']['type'])->toBe('secret')
        ->and($values['base_url']['value'])->toBe('http://localhost:8000/api');
});

it('covers every application API route and the health check', function () {
    $collection = postmanJson('docs/postman/TaskFlow-API.postman_collection.json');
    $requests = postmanRequests($collection['item']);

    $documented = collect($requests)
        ->filter(fn (array $item): bool => str_contains($item['request']['url'], '{{base_url}}'))
        ->map(fn (array $item): string => normalizedApiOperation(
            $item['request']['method'],
            $item['request']['url'],
        ))
        ->unique()
        ->sort()
        ->values();

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), 'api/'))
        ->flatMap(function (IlluminateRoute $route): array {
            $uri = preg_replace('/\{[^}]+}/', '{id}', '/'.$route->uri());

            return collect($route->methods())
                ->reject(fn (string $method): bool => $method === 'HEAD')
                ->map(fn (string $method): string => $method.' '.$uri)
                ->all();
        })
        ->sort()
        ->values();

    expect($documented->all())->toBe($routes->all())
        ->and(collect($requests)->pluck('request.url'))->toContain('{{app_url}}/up');
});

it('generates runtime credentials and clears sensitive variables', function () {
    $contents = file_get_contents(base_path('docs/postman/TaskFlow-API.postman_collection.json'));

    expect($contents)->not->toBeFalse()
        ->and($contents)->toContain(
            "pm.environment.set('email'",
            "pm.environment.set('token'",
            "pm.environment.unset('token'",
            '@example.test',
        )
        ->and($contents)->not->toContain('COLE_AQUI', 'Bearer eyJ');
});
