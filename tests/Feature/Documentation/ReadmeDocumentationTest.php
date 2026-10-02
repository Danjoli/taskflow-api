<?php

declare(strict_types=1);

it('documents the project setup, usage, quality checks, and production flow', function () {
    $contents = file_get_contents(base_path('README.md'));

    expect($contents)->not->toBeFalse()
        ->and($contents)->toContain(
            '## Arquitetura',
            '## Ambiente Docker',
            '## Instalação rápida',
            '## Endpoints principais',
            '## Demonstração rápida',
            '## Testes e qualidade',
            '## Produção',
        )
        ->and($contents)->not->toContain('serão implementadas nas próximas etapas');
});

it('keeps every referenced project document available', function (string $path) {
    expect(file_exists(base_path($path)))
        ->toBeTrue("The README reference [{$path}] does not exist.");
})->with([
    'OpenAPI specification' => 'docs/openapi.json',
    'API examples' => 'docs/api-examples.md',
    'HTTP collection' => 'docs/taskflow-api.http',
    'Postman documentation' => 'docs/postman/README.md',
    'Postman collection' => 'docs/postman/TaskFlow-API.postman_collection.json',
    'Postman environment' => 'docs/postman/TaskFlow-Local.postman_environment.json',
    'deployment runbook' => 'docs/deployment.md',
    'production environment example' => '.env.production.example',
    'CI workflow' => '.github/workflows/ci.yml',
    'Docker image' => 'Dockerfile',
    'Docker Compose environment' => 'compose.yaml',
    'Nginx configuration' => 'docker/nginx/default.conf',
    'PHP configuration' => 'docker/php/php.ini',
    'PostgreSQL initialization' => 'docker/postgres/init-databases.sql',
]);
