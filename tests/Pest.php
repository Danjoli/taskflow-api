<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Os testes da pasta Feature utilizarão a classe-base do Laravel.
| Isso permite acessar a aplicação, as rotas, o banco de dados
| e as configurações de teste.
|
*/

pest()
    ->extend(TestCase::class)
    ->in('Feature');
