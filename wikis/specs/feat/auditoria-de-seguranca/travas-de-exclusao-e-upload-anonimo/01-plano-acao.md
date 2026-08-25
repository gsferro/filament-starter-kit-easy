# Plano de Ação — Travas de exclusão e upload anônimo

> Requisito: `00-requisito.md`
> Relatório da auditoria: `05-security.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/` (a matriz de permissões
  e o desenho de autorização por painel) e `wikis/specs/feat/pagina-boas-vindas/pagina-boas-vindas/`
  (a rota pública `/`)
- **Motivo**: a auditoria do Blueprint achou duas travas que a aplicação acredita ter e não tem. As
  duas foram introduzidas pelas wikis ancestrais, e nenhum teste delas mede a coisa certa.
- **Toca infra compartilhada?**: **não**. Duas classes de resource e duas de página; nenhum seeder,
  middleware global, `tests/Pest.php` ou migration.

> Tipo `correção` ⇒ o `feature-quality-gate` roda **regressão** contra os CT/CT-B das duas wikis
> ancestrais. É o que garante que negar a exclusão por autorização não derrube a edição de usuário
> no `/app`, e que o trait na `BoasVindas` não quebre a tela pública.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Rodar a auditoria do Blueprint | — | Concluída antes desta wiki; saída em `05-security.md` |
| RQ-02 | F-01 no `UserResource` do `/app` | 1 | — |
| RQ-03 | F-01 no `ConviteResource` do `/app` | 2 | — |
| RQ-04 | Corrigir o docblock que aponta trava inexistente | 1, 3 | O `EditUser` é o passo 3 |
| RQ-05 | F-02 na `BoasVindas` (rota pública) | 4 | — |
| RQ-06 | F-02 na `ConvitesRecebidos` | 5 | — |
| RQ-07 | Teste que falha sem a correção | 6 | Casos em `04-casos-de-teste.md`; mutação obrigatória |
| RQ-08 | Rodada em worktree | — | `.claude/worktrees/auditoria-seguranca` |
| RQ-09 | Commits individualizados | 7 | Um por achado, mais o da wiki |
| RQ-10 | Documentar bem | — | Esta wiki + `05-security.md` + docblocks dos passos 1–5 |
| RQ-11 | PR, merge e release | 8 | — |

## Objetivo

Trocar duas barreiras decorativas por barreiras reais. A primeira é uma negação de exclusão que o
Filament v5 não consulta; a segunda é a exposição do RPC de upload do Livewire na única rota pública
e anônima do kit. Nenhuma das duas muda comportamento visível: depois da correção a tela faz
exatamente o que hoje parece fazer.

## Contexto

O kit passou por sete rodadas de features nas últimas versões e ganhou, junto, duas afirmações de
segurança que não se sustentam. Não é descuido de quem escreveu: as duas são exatamente o tipo de
armadilha que só aparece lendo o `vendor/`.

- `canDelete()` **parece** ser o ponto de autorização — é público, estático, tem o nome certo, e em
  versões anteriores do Filament era consultado. No v5 ele virou invólucro de leitura, e quem decide
  é `getDeleteAuthorizationResponse()`.
- `BoasVindas` **parece** ser uma página inofensiva de texto — e é, quanto ao que ela renderiza. O
  problema não está no que ela mostra, está no que ela herda: a cadeia até `BasePage` compõe
  `InteractsWithSchemas`, que compõe o `WithFileUploads` do Livewire.

## Análise dos Arquivos Existentes

### `app/Filament/App/Resources/Users/UserResource.php`

`canDelete()` (linha 98) e `canDeleteAny()` (linha 103) devolvem `false` com um docblock que explica
bem **por que** a exclusão deve ser negada — a razão continua correta e é reaproveitada. O que muda é
o método sobrescrito. Os `can*()` ficam: eles gateiam navegação e busca global, e removê-los seria
troca de um defeito por outro.

### `app/Filament/App/Resources/Convites/ConviteResource.php`

Mesmo par nas linhas 174 e 179. O docblock cita que "sem página de edição também não há rota para
alcançar" — verdadeiro e independente da correção, então permanece.

### `app/Filament/App/Resources/Users/Pages/EditUser.php`

O docblock (linhas 9-11) afirma: *"A trava de verdade é `UserResource::canDelete()`"*. É a frase que
o F-01 desmente, e é a mais perigosa das três porque **instrui**.

### `app/Filament/Pages/BoasVindas.php` e `app/Filament/App/Pages/ConvitesRecebidos.php`

Duas classes de página sem o trait. A primeira é servida em `routes/web.php:22` sem `auth`.

## Autorização

- **Policies**: nenhuma mudança. `UserPolicy::delete()` continua respondendo à matriz do Shield — a
  negação desta entrega é **do resource**, por painel, e é assim de propósito: a mesma pessoa pode
  legitimamente excluir usuário pelo `/admin`.
- **Gates / Middleware / Guards**: nenhuma mudança.

## Rotas

Nenhuma rota criada, alterada ou removida.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ListUsers` / `EditUser` do `/app` | Filament | `/app/{org}/users`, `/app/{org}/users/{id}/edit` | nenhuma nova — a tela não ganha nem perde botão | Não |
| `BoasVindas` | Filament (rota própria) | `/` | nenhuma nova — a página renderiza igual | Não |

**Gate de CT-B**: **nenhum cenário vai para o browser.** Os dois achados são de autorização, e
autorização na tela é teste de componente Livewire, que pertence ao `04`. Não há afirmação sobre
JavaScript executado, console, acessibilidade, cor/tema ou layout. A regressão visual da rota `/`
já está coberta por `tests/Browser/BoasVindasTest.php`, que continua valendo sem alteração.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` nova.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`/app` — edição de usuário**: a negação é só de `delete`. Se ela vazar para `edit`, a
  administração da organização quebra. Coberto por caso de regressão explícito.
- **`/admin` — exclusão de usuário**: precisa continuar **permitida**. A correção é por resource, e
  o `/admin` tem o seu próprio; um erro aqui seria negar globalmente. Coberto por caso.
- **Rota `/`**: o trait fecha o RPC de upload. A página não tem campo de upload, então o risco é
  zero por construção — mas a tela precisa continuar respondendo `200` e mostrando os cartões.
- **`/app` — convites recebidos**: mesma coisa; a página não tem upload.

## Rollback

Reverter o commit. Não há migration, dado migrado, cache ou config publicada. As quatro mudanças são
métodos e um `use` de trait.

## Dependências

Nenhuma nova. O `filament/blueprint` é ferramenta de desenvolvimento e **não** entra no
`composer.json` publicado — ver `tests/Kit/BlueprintForaDoPacoteTest.php`.

## Riscos

- **Negar demais**: `Response::deny()` no método errado derruba edição ou listagem. Mitigação: caso
  de regressão que abre a tela de edição e salva.
- **Trait sem efeito**: já mitigado na pesquisa — a aplicação do flag foi confirmada em
  `SchemasServiceProvider.php:63-77`, e os dois métodos que a guarda exige existem na cadeia da
  página (`InteractsWithSchemas.php:505`). Sem essa verificação, a correção repetiria o defeito que
  ela conserta.
- **Correção envelhecer**: página pública nova nasce sem o trait. Mitigação: caso que percorre as
  páginas do kit.

## Channel de Log da Feature

**Nenhum channel novo, e nenhum log novo.** Decisão deliberada, e o motivo importa:

- a negação de exclusão acontece **antes** de qualquer efeito, e o Filament já responde com a
  mensagem do `Response::deny()`. Logar aqui geraria linha em toda renderização de tabela que
  avalia a autorização por registro — ruído por linha listada, não sinal;
- o 403 do upload é emitido pelo `abort_unless()` do próprio Filament, dentro de um hook do
  Livewire. Não há ponto nosso na pilha para logar sem envolver o vendor.

O kit já tem o channel `autenticacao` para eventos de fronteira de acesso (`canAccessTenant`,
`canAccessPanel`, `EditRole@afterSave`). Se um dia a exclusão passar a ser **tentada** de verdade —
isto é, se alguma `DeleteAction` for registrada no `/app` —, o log entra lá, não em channel novo.

## Estrutura de Implementação

### 1. `UserResource` do `/app`: negar exclusão pelo método que o Filament consulta

> Skills: `laravel-best-practices`, `ponytail`
> Atende: RQ-02, RQ-04

- **Path**: `app/Filament/App/Resources/Users/UserResource.php`
- Acrescentar `getDeleteAuthorizationResponse(Model $record): Response` e
  `getDeleteAnyAuthorizationResponse(): Response`, ambos devolvendo
  `Response::deny('Excluir usuário é ato global e não se faz a partir de uma organização.')`.
- Import: `Illuminate\Auth\Access\Response`.
- Manter `canDelete()`/`canDeleteAny()`, e reescrever o docblock deles para dizer o que eles
  **de fato** fazem (navegação e busca global) e apontar para o par novo como a trava.
- **Logs**: nenhum (ver a seção acima).

### 2. `ConviteResource` do `/app`: o mesmo par

> Atende: RQ-03

- **Path**: `app/Filament/App/Resources/Convites/ConviteResource.php`
- Mesma correção, mensagem própria: convite não se exclui da organização; reenvio e revogação
  vivem no `/admin`.

### 3. `EditUser`: o docblock passa a dizer a verdade

> Atende: RQ-04

- **Path**: `app/Filament/App/Resources/Users/Pages/EditUser.php`
- Trocar *"A trava de verdade é `UserResource::canDelete()`"* pela trava real, com a nota de que o
  gerador do Filament inclui `DeleteAction` por default — que é o motivo de a trava precisar existir
  mesmo sem ação registrada.

### 4. `BoasVindas`: fechar o RPC de upload na rota pública

> Atende: RQ-05

- **Path**: `app/Filament/Pages/BoasVindas.php`
- `use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;` na classe.
- Docblock curto explicando o que o trait fecha e por que uma página **pública** precisa dele
  mesmo estendendo `Page` — a premissa de "página de painel reautoriza todo request" não vale numa
  rota sem `auth`.

### 5. `ConvitesRecebidos`: o mesmo trait

> Atende: RQ-06

- **Path**: `app/Filament/App/Pages/ConvitesRecebidos.php`

### 6. Testes

> Skills: `pest-testing`
> Atende: RQ-07

- **Path**: `tests/Kit/TravaDeExclusaoTest.php`
- Cenários em `04-casos-de-teste.md`. Cada correção tem **mutação verificada**: com a linha
  revertida, o caso correspondente tem de ficar vermelho.

### 7. Commits

> Atende: RQ-09

Um por assunto, cada um com o seu teste:

- `:lock: fix(app): a negacao de exclusao passa pelo metodo que o Filament consulta`
- `:lock: fix(paginas): a rota publica nao expoe mais o RPC de upload do Livewire`
- `:memo: docs(wiki): auditoria de seguranca com o catalogo do Blueprint`

### 8. PR, merge e release

> Atende: RQ-11

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada aqui é curta e o topo dela importa: as duas correções
> são **um método existente do framework** e **um trait existente do framework**. Nada de classe
> nova, nada de abstração, nada de helper. Se a correção precisar de mais de quatro linhas por
> arquivo, ela está errada.
>
> Arquivos wiki (00-06) são boundary do Caveman — prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. Sem CT-B — ver o gate na `## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test tests/Kit/TravaDeExclusaoTest.php --compact`
- [ ] Mutação: reverter cada correção e confirmar o caso vermelho
- [ ] `php artisan test --testsuite=Kit --compact` (regressão do grupo)
- [ ] `php artisan test --testsuite=Unit,Feature,Tenancy --compact`
- [ ] `composer bp:off` antes do commit final — o Blueprint não pode viajar no pacote
