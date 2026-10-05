<?php

use App\Support\DetalheDoUsuario;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * As cinco opções do cabeçalho dos painéis (feat/cabecalho-do-painel).
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação
 * e nunca mais roda. Os defaults vêm de `config/kit.php`, que por sua vez lê o `.env` — é
 * assim que o `.env` "semeia a primeira gravação" (docs, seção "Quem manda") — e todos
 * preservam o comportamento anterior: tudo desligado, detalhe em `perfil`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('cabecalho_nome_do_projeto', (bool) config('kit.cabecalho.nome_do_projeto', false));
            $blueprint->add('cabecalho_nome_do_painel', (bool) config('kit.cabecalho.nome_do_painel', false));
            $blueprint->add('cabecalho_logo_da_marca', (bool) config('kit.cabecalho.logo_da_marca', false));
            $blueprint->add('cabecalho_usuario', (bool) config('kit.cabecalho.usuario', false));
            $blueprint->add(
                'cabecalho_detalhe_do_usuario',
                DetalheDoUsuario::coagir(config('kit.cabecalho.detalhe_do_usuario'))->value,
            );
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('cabecalho_nome_do_projeto');
            $blueprint->delete('cabecalho_nome_do_painel');
            $blueprint->delete('cabecalho_logo_da_marca');
            $blueprint->delete('cabecalho_usuario');
            $blueprint->delete('cabecalho_detalhe_do_usuario');
        });
    }
};
