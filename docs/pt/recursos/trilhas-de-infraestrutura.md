---
title: "Trilhas do `/infra`: exceções, e-mails e lixeira"
description: "O painel de infraestrutura já mostrava saúde (Health), desempenho (Pulse), arquivo de log (Logs Explorer) e filas (Jobs Monitor) — e nenhum deles respondia…"
sidebar:
  label: "Trilhas do /infra: exceções, e-mails e lixeira"
  order: 4
---
O painel de infraestrutura já mostrava **saúde** (Health), **desempenho** (Pulse), **arquivo de
log** (Logs Explorer) e **filas** (Jobs Monitor) — e nenhum deles respondia "qual exception está
estourando, e quantas vezes", "o convite chegou?" ou "dá para desfazer aquele delete?". Três telas
respondem cada uma dessas perguntas:

| Tela | Onde | O que responde |
|---|---|---|
| **Exceções** | `/infra`, grupo *Observabilidade* | as exceptions agrupadas por tipo e frequência, com badge de contagem no menu |
| **Trilha de e-mails** | `/infra`, grupo *Trilhas* | todo e-mail que o kit enviou — separa "não foi enviado" de "foi enviado e caiu no spam" |
| **Lixeira** | `/infra`, grupo *Sistema* | restaura registro apagado com `SoftDeletes` |

## As duas trilhas guardam dado sensível

É por isso que elas só são **alcançáveis** no `/infra`, onde entrar já exige papel `master_global` ou
`infra` — no `/app` qualquer papel do painel as veria. A rota do `ExceptionResource` existe nos três
painéis (`/admin/exceptions`, `/app/{tenant}/exceptions`, `/infra/exceptions`); a barreira é a
subtração de permissão em `database/seeders/PapeisSeeder.php`, não a ausência da tela:

- o **stack trace** da exceção pode carregar parâmetro de request, logo pode carregar dado pessoal;
- o **corpo do e-mail** é gravado, e o convite de acesso carrega o link de aceite.

## Retenção: o número é a intenção, o agendador é a execução

As duas tabelas crescem por evento — um bug em laço enche o disco em horas. Por isso a poda tem
prazo, em `config/kit.php`:

| Chave | `.env` | Padrão |
|---|---|---|
| `kit.retencao.excecoes_em_dias` | `KIT_RETENCAO_EXCECOES_DIAS` | 14 |
| `kit.retencao.emails_em_dias` | `KIT_RETENCAO_EMAILS_DIAS` | 14 |

Os 14 dias acompanham o `days` da rotação em `config/logging.php`: a trilha morre junto com o log
que a originou, não depois dele. **Zero ou negativo desliga a poda** daquela trilha — e aí a tabela
cresce sem teto, o que é uma escolha, não um esquecimento.

> ⚠️ **Quem aplica a retenção é o agendador.** As rotinas estão em `routes/console.php`; sem
> `php artisan schedule:work` (ou o serviço `scheduler` do docker compose) o número no config é só
> intenção declarada.

## A Lixeira lista o que você declarar

O `RevivePlugin` recebe uma **lista explícita** de models em
`app/Providers/Filament/InfraPanelProvider.php` — hoje `App\Models\Projeto` e `App\Models\User`,
as duas models do kit com `SoftDeletes` (`InfraPanelProvider.php:models:581`):

```php
RevivePlugin::make()
    ->navigationGroup('Sistema')
    ->navigationLabel('Lixeira')
    ->navigationSort(250)
    ->authorize(fn (): bool => auth()->check()
        && PermissaoDaTela::permite(RecycleBin::class))
    ->models([
        Projeto::class,
        User::class,
    ])
    ->withoutScoping(),
```

O `->authorize()` não é enfeite: o default do pacote é `true`, e esta tela lista tudo o que foi
apagado na **instalação inteira**. A allow-list de `->models()` é a primeira trava; a permissão da
tela é a segunda.

**Model nova com `SoftDeletes` precisa entrar nessa lista**, senão fica apagada sem tela para
restaurar — e precisa também usar `Promethys\Revive\Concerns\Recyclable`, que é quem grava em
`recycle_bin_items` no evento `deleted`; sem a trait a Lixeira lista vazio. A varredura automática
de `app/Models` foi evitada de propósito: alcançaria `Role` e `Tenant`, que não têm `SoftDeletes` e
não têm o que restaurar. A trava é a lista, como na allow-list do Command Center.

`User` entrou na lista junto com a exclusão lógica de usuário, e é por aqui que se restaura uma
conta excluída (ver [estados do usuário](/pt/autenticacao/estados-de-usuario/)). A recusa antiga
— *"um usuário volta com papel numa organização que pode nem existir mais"* — pressupunha
exclusão **física** com cascata; com `SoftDeletes` as pivots `tenant_user` e `model_has_roles`
ficam de pé, restaurar devolve exatamente o que havia, e `Tenant` nunca é apagado (tem `ativo`).

