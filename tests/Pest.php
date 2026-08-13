<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenancyTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Testes do SEU projeto
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Testes do KIT
|--------------------------------------------------------------------------
| Ficam isolados em tests/Kit para você conseguir rodar só eles — é o que
| você quer depois de um `kit:update`, para saber se a atualização quebrou
| a fundação sem esperar a suíte inteira do seu negócio:
|
|   composer test:kit
|   php artisan test --testsuite=Kit
|   php artisan test --group=kit
|
| Eles cobrem o que o kit promete: acesso aos três painéis, telas de infra
| e admin de pé, invariantes da fundação (uuid, gates, auditoria) e o
| contrato da camada de IA.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Kit');

/*
|--------------------------------------------------------------------------
| Testes do KIT — multi-tenancy
|--------------------------------------------------------------------------
| Suíte separada por uma razão de bootstrap, não de organização: a migration
| de permissões do spatie lê `config('permission.teams')` em tempo de execução
| para decidir se cria as colunas de team. Ligar a flag num beforeEach seria
| tarde — o RefreshDatabase já teria migrado sem elas.
|
| O Tests\TenancyTestCase fixa a config em createApplication(), que roda antes
| das migrations; e o Pest não permite dois TestCases na mesma pasta, daí o
| diretório próprio.
|
| Mesmo grupo `kit`, então continua entrando em:
|
|   composer test:kit
|   php artisan test --group=kit
*/

pest()->extend(TenancyTestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Tenancy');
