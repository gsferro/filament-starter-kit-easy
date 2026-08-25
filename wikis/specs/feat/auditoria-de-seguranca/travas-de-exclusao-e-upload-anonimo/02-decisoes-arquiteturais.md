# Decisões Arquiteturais — Auditoria de segurança com o Blueprint

## ADR-01: A trava é `getDeleteAuthorizationResponse()`, e os `can*()` ficam

**Status**: Aceita
**Data**: 2026-08-25

### Contexto

O kit negava exclusão de usuário no `/app` sobrescrevendo `canDelete()` e `canDeleteAny()`. No
Filament v5 esses métodos não autorizam nada: são invólucros que **leem** a resposta
(`HasAuthorization.php:154-162`), e quem a produz é `getDeleteAuthorizationResponse()`
(`:94-102`). Quem decide a ação chama a resposta direto —
`Resources/Pages/Page.php:313` para `DeleteAction`, `:329` para `DeleteBulkAction` — e o framework
**nunca** chama `canDelete()`: `grep` por chamadores em `vendor/filament/filament/src/` devolve zero.

### Decisão

Sobrescrever `getDeleteAuthorizationResponse()` e `getDeleteAnyAuthorizationResponse()`, devolvendo
`Response::deny()` com mensagem. **Manter** os `can*()` existentes.

### Alternativas Consideradas

1. **Trocar os `can*()` pelo par novo, removendo os antigos** — descartada. Os `can*()` continuam
   gateando navegação, badge e busca global, que é caminho de request real. Removê-los faria a
   entrada aparecer no menu de quem não pode agir: trocaria um defeito por outro, menos grave e mais
   visível.
2. **Negar na policy (`UserPolicy::delete()`)** — descartada, e é a alternativa mais tentadora. A
   policy é **global**: negar ali proibiria a exclusão também no `/admin`, onde ela é legítima e
   desejada. A assimetria por painel é a feature, não um acidente.
3. **Confiar na ausência de `DeleteAction`** — descartada. É o estado atual, e é o que a auditoria
   flagrou: barreira por ausência de superfície não é barreira, e o gerador do Filament recria a
   superfície por default no próximo `make:filament-resource`.

### Consequências

- **Positivas**: a negação passa a valer para `DeleteAction`, `DeleteBulkAction` e para qualquer ação
  futura que resolva autorização pelo caminho padrão. A mensagem do `deny()` explica ao usuário em
  vez de dar 403 mudo.
- **Negativas**: agora há **dois** pares de métodos com nomes parecidos no mesmo arquivo, e a
  diferença entre eles não é óbvia. Mitigado pelo docblock, que é o único lugar onde essa distinção
  pode morar.
- **Riscos**: negar no método errado derruba edição. Coberto por caso de regressão que salva a tela
  de edição.

### Referências

- `vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php:94,154,159`
- `vendor/filament/filament/src/Resources/Pages/Page.php:309-325`
- `05-security.md` §2, F-01

---

## ADR-02: O trait entra na página pública, apesar de o catálogo excluir páginas

**Status**: Aceita
**Data**: 2026-08-25

### Contexto

O check A5 do Blueprint manda buscar componentes que compõem `InteractsWithSchemas`
*"excluding classes that extend Filament's Resource / Page / RelationManager"*, e justifica:
*"Panel resources/pages re-authorize every request, so the trait isn't needed there."*

`BoasVindas` **estende** `Page` — pela regra literal, sairia da busca. Mas ela é servida em
`routes/web.php:22` por `Route::get('/', BoasVindas::class)->middleware('panel:app')`, sem `auth` e
sem `canAccess()`. O `panel:app` é o alias de `SetUpPanel`: boota o painel, não autentica.

### Decisão

Tratar como achado e aplicar `RestrictsFileUploadsToSchemaComponents`. A exclusão do catálogo é
condicionada a uma **premissa** — "reautoriza todo request" — e a premissa é falsa neste caso.

### Alternativas Consideradas

1. **Seguir a regra literal e marcar `N/A`** — descartada. Seria obedecer à letra do catálogo contra
   a razão que ele mesmo declara. O "Flag if" do próprio check nomeia o caso: *"Chief cases:
   unauthenticated pages, or components whose schema has no upload field"* — e aqui **os dois** valem.
2. **Pôr `auth` na rota `/`** — descartada. A rota é pública por desenho (ADR-04 da wiki
   `pagina-boas-vindas`); autenticá-la mataria a feature para consertar o upload.
3. **Sobrescrever `shouldRestrictFileUploadsToSchemaComponents()` à mão** — descartada por preguiça
   bem aplicada: o trait é exatamente esse método devolvendo `true`. Uma linha de `use` contra
   quatro de método.

### Consequências

- **Positivas**: o RPC fecha por completo nas duas páginas, sem configuração, porque nenhuma tem
  campo de upload. Zero impacto em fluxo existente.
- **Negativas**: a proteção é opt-in por classe, então página pública nova nasce desprotegida.
  Mitigado por um caso de teste que percorre as páginas.
- **Riscos**: nenhum identificado. O trait não altera renderização.

### Referências

- `vendor/filament/schemas/src/Concerns/RestrictsFileUploadsToSchemaComponents.php`
- `vendor/filament/schemas/src/SchemasServiceProvider.php:63-77`
- `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:505`
- `05-security.md` §2, F-02

---

## ADR-03: Verificar o consumidor do flag antes de confiar no trait

**Status**: Aceita
**Data**: 2026-08-25

### Contexto

O F-01 é, na essência, um defeito de **fé**: alguém sobrescreveu um método com o nome certo e
assumiu que o framework o chamava. Corrigir esse defeito adicionando um trait — sem verificar quem
lê o trait — seria cometer o mesmo erro na mesma entrega.

O trait tem uma linha: `shouldRestrictFileUploadsToSchemaComponents(): bool { return true; }`. Por
si só não faz nada.

### Decisão

Antes de escrever o plano, localizar e ler o consumidor. Feito: `SchemasServiceProvider.php:63-77`
registra um hook `on('call')` do Livewire que intercepta `_startUpload`, `_finishUpload`,
`_uploadErrored` e `_removeUpload`, e faz
`abort_unless($component->isFileUploadForSchemaComponent($params[0] ?? ''), 403)`.

### Alternativas Consideradas

1. **Confiar na documentação do Filament** — descartada. A doc estava certa neste caso, mas a doc
   também não desmentia o `canDelete()`. Documentação descreve intenção; `vendor/` descreve
   comportamento.

### Consequências

- **Positivas**: descobrimos que a guarda exige **dois** métodos (`shouldRestrict...` e
  `isFileUploadForSchemaComponent`) e que, faltando um, ela **retorna sem abortar** — falha **aberta**.
  Confirmado que o segundo existe em `InteractsWithSchemas:505`, que a `BasePage` compõe. Se algum dia
  uma classe usar o trait sem essa cadeia, a proteção será silenciosamente nula.
- **Negativas**: nenhuma.

### Referências

- `vendor/filament/schemas/src/SchemasServiceProvider.php:69-77`
- Refine: ADR-02

---

## ADR-04: O oráculo do teste é a resposta de autorização, não a ausência do botão

**Status**: Aceita
**Data**: 2026-08-25

### Contexto

O modo natural de testar "não pode excluir" numa tela Filament é abrir a tabela e afirmar que a ação
não está lá. Esse teste fica **verde com o defeito presente** — porque o defeito É a ausência do
botão fazendo o papel de autorização. Ele mediria a barreira errada.

### Decisão

Os casos afirmam sobre `UserResource::getDeleteAuthorizationResponse($record)->denied()` e sobre
`getDeleteAnyAuthorizationResponse()->denied()`. E cada caso é validado por **mutação**: reverter a
correção tem de deixá-lo vermelho.

### Alternativas Consideradas

1. **`assertTableActionDoesNotExist('delete')`** — descartada pelo motivo acima. Fica no conjunto
   como caso de regressão de UI, nunca como oráculo do achado.
2. **Registrar uma `DeleteAction` só no teste, para exercitar o caminho real** — descartada. Exigiria
   uma subclasse de resource só para teste, e mediria o Filament em vez do kit. O mapeamento de
   `DeleteAction` → `getDeleteAuthorizationResponse()` está em `Page.php:313` e é do framework, não
   nosso para testar.

### Consequências

- **Positivas**: o conjunto falha quando a trava sai, que é a única propriedade que interessa.
- **Negativas**: o caso lê como "teste de implementação" — ele nomeia um método do Filament. É o
  preço de ter oráculo correto num ponto que a UI não expõe.

### Referências

- `04-casos-de-teste.md`
- `05-security.md` §4
