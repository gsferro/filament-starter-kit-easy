---
paths:
  - 'app/**'
---

# App

## Papel se atribui dentro de ContextoDePapeis, nunca com assignRole cru
Com `permission.teams` ligado, `assignRole()` grava em `model_has_roles.team_id` o contexto **corrente do request** — e a relação `roles` do spatie é filtrada pelo team do request na leitura. Atribuir no contexto errado produz o pior sintoma possível: a pessoa autentica, o painel responde **200**, e ela não vê nada.

Foi Blocker na v0.19.1. `User::aprovar()` chamava `assignRole()` cru, e quem aprova está no /admin, cujo contexto é o global: o papel ia para `team_id = 0` com a organização em `id = 1`. E o estado era sem saída pela própria tela — a ação tem `visible = aprovacao_pendente` e a pendência é baixada antes, então a mesma tela não conserta o que acabou de fazer.

Use `App\Support\ContextoDePapeis::em($contexto, $usuario, $callback)`. Ele fixa o team, limpa o cache da relação nas duas pontas e restaura no `finally`.

O contexto certo: papel de painel COM tenancy (`app`) vai no `team_id` da organização — um por organização, se houver várias. Papel de painel SEM tenancy (`admin`, `infra`) vai em `Tenant::CONTEXTO_GLOBAL`, porque ser admin dentro de uma organização não é credencial para administrar a instalação. Ver `Convite::contextoDoPapel()` para o precedente.

E dentro do callback use `assignRole()`, NUNCA `sync()` na relação: o sync escreve só as colunas da chave e estoura `NOT NULL constraint failed: model_has_roles.team_id`.

Sinal de alerta na revisão: um `assignRole()` ou `syncRoles()` que não esteja dentro de um `ContextoDePapeis::em()`. Nesta rodada, `aprovar()` era o único caminho novo que escapava — o convite, o cadastro pelo registro aberto e o gerenciador de usuários da organização já passavam por lá.

## DTO no kit é spatie/laravel-data, em app/Data
Todo DTO estende `Spatie\LaravelData\Data`, mora em `app/Data/{Contexto}/`, é `final` e tem só propriedades `readonly` promovidas. O sufixo `Data` é RESERVADO para DTO: nenhum model, serviço ou enum pode terminar nele (num projeto irmão um Eloquent `*Data` convivia com os DTO e quebrou toda varredura).

A criação passa por FÁBRICA NOMEADA estática que chama `self::from()` — `new MeuData(...)` não roda os casts do pacote, e o defeito é silencioso. Mapeamento de payload externo é explícito, campo a campo; sem `#[MapInputName]`, porque o casing automático erra nas chaves irregulares dos provedores e falha em silêncio.

Credencial (`senha`, `password`, `token`, `secret`, `api_key`) NUNCA vira propriedade de Data: o objeto é serializável em `toArray()`, log e fila. Data também não é propriedade pública de componente Livewire/Filament — o `LivewireDataSynth` do pacote o reconstrói a partir do payload do navegador.

Coleção de sub-Data é array tipado (`list<XData>`); `DataCollection` só com a necessidade escrita no docblock. Consumo e resposta de API exigem Data.

Enforçado por `App\Support\GuardaDoPadraoDeDto` + `tests/Kit/DtoComLaravelDataTest.php` — o guarda expõe as raízes padrão como dado e o kit publicado passa verde nele. Ver `wikis/specs/feat/laravel-data-como-padrao-de-dto/`.
