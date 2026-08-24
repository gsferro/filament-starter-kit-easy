# Progresso — W7: validação de e-mail editável

## 1. O middleware que decide por request

- [ ] `app/Http/Middleware/ExigirEmailVerificado.php` criado, estendendo `EnsureEmailIsVerified`
- [ ] guarda `if (! RegistroAberto::exigirVerificacaoDeEmail()) return $next($request);`
- [ ] log de barramento no channel `autenticacao`, no formato `[Classe@Método]`, com e-mail mascarado
- [ ] nenhum log no caminho liberado (decisão, não esquecimento)

## 2. O painel aplica sempre e delega a decisão

- [ ] `AppPanelProvider`: `->emailVerification(EmailVerification::class)` sem condição
- [ ] `->emailVerifiedMiddlewareName(ExigirEmailVerificado::class)`
- [ ] import novo
- [ ] bloco de comentário reescrito (o anterior descreve mecanismo que deixou de existir)

## 3. A propriedade e a linha do mapa

- [ ] `public bool $registro_verificar_email;` em `App\Settings\ConfiguracoesDoKit`
- [ ] `'registro_verificar_email' => 'kit.registro.verificar_email'` em `mapaDeConfiguracao()`
- [ ] comentário que justificava a ausência substituído pela justificativa da presença

## 4. A migration de settings

- [ ] `database/settings/2026_08_25_000000_add_registro_verificar_email_to_kit_settings.php`
- [ ] `up()` semeia de `config('kit.registro.verificar_email')`
- [ ] `down()` com `deleteIfExists`

## 5. O toggle na tela

- [ ] `TextEntry::make('aviso_verificacao_email')` removido de `abaRegistro()`
- [ ] `Toggle::make('registro_verificar_email')` acrescentado, com `->visible($aberto)`
- [ ] `helperText` diz as duas coisas: vale para todo usuário do `/app`, e convite não é afetado
- [ ] import de `TextEntry` removido se ficou sem uso

## 6. Os docblocks que passam a mentir

- [ ] `app/Support/RegistroAberto.php` — o bloco da dívida
- [ ] `app/Support/ConfiguracaoDoLogin.php` — a citação do contraexemplo
- [ ] `config/kit.php` — o bloco de `'verificar_email'`

## 7. Testes

- [ ] `tests/Kit/VerificacaoDeEmailTest.php` — CT-01…CT-12, CT-14
- [ ] `tests/Kit/RegistroAbertoTest.php` — as duas **inversões** (ver *Desvios do Plano*)
- [ ] `tests/Kit/ConfiguracoesDoKitTest.php` — o caso de desfazer/refazer generalizado para
      todas as migrations de settings
- [ ] CT-13 confirmado como coberto pelo caso invariante existente

## 8. README (pt e en) e proposta de rule

- [ ] `README.md` §"Validação de e-mail (opcional)" + linha `F-03c` da matriz
- [ ] `README.en.md` — as duas seções equivalentes
- [ ] proposta de atualização de `.ai/rules/settings.md` escrita abaixo (**não gravada**)
- [ ] `CHANGELOG.md`

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --testsuite=Kit --filter="VerificacaoDeEmail|RegistroAberto|ConfiguracoesDoKit" --compact`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base **1016**, não cair
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] `composer test:browser`
- [ ] `git commit` por bloco concluído

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `emailVerifiedMiddlewareName()` aceita a classe do middleware | confirmado: `HasAuth.php:174-178`, e `getEmailVerifiedMiddleware()` (`:367-370`) concatena `"{nome}:{rota}"` — o Laravel resolve FQCN com parâmetro | nenhuma; ADR-01 já cita as linhas |
| `emailVerification()` com um argumento entra com `isRequired: true` | confirmado: assinatura em `HasAuth.php:110` tem `bool \| Closure $isRequired = true` | nenhuma |
| o helper `usuarioDoKit()` produz usuário **validado** | **falso.** Ele usa `usuario()`, que é `User::create()`, e `email_verified_at` está fora do `$fillable` → nasce **não** validado | `04` → `## Setup Global` corrigido: a persona "com e-mail validado" precisa de `forceFill` explícito |
| basta acrescentar o `add()` na migration de settings existente | **falso.** O docblock dela proíbe (`2026_08_24_000000_create_kit_settings.php:34-38`) e instalação existente ficaria sem a propriedade → `MissingSettings` no boot | ADR-05 escrita; passo 4 do plano é migration **nova** |
| a migration nova não afeta teste existente | **falso.** `ConfiguracoesDoKitTest` → *"desfaz e refaz a migration de settings sem quebrar"* fixa o nome de uma migration e afirma `count(linhas) === count(mapa)`. Com duas migrations o `up()` de uma só devolve 24 linhas contra 25 no mapa | passo 7 do plano ganhou o item; ver *Desvios do Plano* |
| `/admin` e `/infra` não pedem verificação | confirmado: nenhum dos dois providers chama `emailVerification()`, e o default é `isEmailVerificationRequired = false` (`HasAuth.php:56`) | nenhuma |
| a rota de prompt é a única afetada pela decisão | **incompleto.** A rota `verify` (`{id}/{hash}`) também passa a existir sempre; ela é protegida por `signed` + `throttle:6,1` (`vendor/filament/filament/routes/web.php:79-82`) | ADR-03 → *Riscos* registra a superfície |
| o `Authenticate` do painel roda antes do nosso middleware | confirmado: as rotas de página nascem dentro de `Route::middleware($panel->getAuthMiddleware())->group(...)` (`routes/web.php:60`), então visitante anônimo é mandado ao login antes; o ramo `! $request->user()` do pai é inalcançável no painel | `04` não gera cenário para visitante anônimo — registrado em *Cogitado e Cortado* |
| `Register::sendEmailVerificationNotification()` pula quem já validou | confirmado: `vendor/filament/filament/src/Auth/Pages/Register.php:161-167` | nenhuma |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar channel de log novo — reusar `autenticacao` | sim | ADR-04, passo 1 |
| 2 | não criar classe/interface de "política de verificação"; a decisão é um `if` | sim | ADR-02, *Filosofia de Implementação* |
| 3 | não reimplementar `EnsureEmailIsVerified` — herdar | sim | ADR-02 |
| 4 | não criar `05-casos-de-teste-browser.md` — nada aqui exige navegador | sim | `04` → `## Sem CT-B` |
| 5 | não criar subclasse da tela de prompt só para redirecionar com a opção desligada | sim — aceito como trade-off | ADR-03, e premissa registrada no `00` |
| 6 | não registrar alias de middleware em `bootstrap/app.php` — o Filament injeta a classe | sim | passo 2 |

### Revisão adversarial (perfil completo)

Delegada a agente independente, com acesso **apenas** ao `00-requisito.md` e ao
`04-casos-de-teste.md`. Achados e fechamento: preenchido abaixo durante a execução.

## Blockers

<!-- preenchido durante a execução -->

## Desvios do Plano

### As duas inversões de asserção, e por que elas não são regressão

Dois casos existentes **guardavam a dívida**. Invertê-los não é afrouxar teste; é o oposto — eles
afirmavam o mecanismo que impedia a chave de ser editável.

**Inversão 1 — `tests/Kit/RegistroAbertoTest.php` → *"exige validacao de email no painel de negocio
somente com a opcao ligada"* (CT-22b da wiki ancestral).**
Ele montava o painel pelo provider e afirmava
`hasEmailVerification() === false` e `isEmailVerificationRequired() === false` com a opção
desligada. Ambos passam a ser **sempre `true`** — é exatamente o que tira a decisão do array da
rota (ADR-01) e o que garante que a rota de destino exista (ADR-03, RQ-09). O caso é **reescrito**,
não removido: passa a afirmar que a rota nomeada existe nos dois estados e que o middleware
declarado pelo painel é o do kit. Cobre CT-08.

**Inversão 2 — `tests/Kit/RegistroAbertoTest.php` → *"mantem as tres chaves de registro no mapa de
configuracao"*.**
A linha `->and($mapa)->not->toHaveKey('registro_verificar_email')` era o guardião explícito da
dívida — ela existia para *"impedir alguém de 'completar' o mapa achando que faltou uma linha"*. A
dívida foi paga, então a asserção negativa vira **positiva**. Cobre CT-12.

Em ambos os casos o comentário do teste é reescrito apontando para esta wiki, para que a próxima
pessoa não leia a inversão como afrouxamento.

**Desvio 3 — `tests/Kit/ConfiguracoesDoKitTest.php` → *"desfaz e refaz a migration de settings sem
quebrar"*.**
Ele fixava o nome de uma migration e afirmava `count(linhas) === count(mapa)` depois do `up()`.
Com duas migrations de settings isso é aritmeticamente impossível. Generalizado para percorrer
**todas** as migrations de `database/settings/` — `down()` em ordem inversa, `up()` em ordem —, o
que mata o mesmo mutante (`add()` sem `deleteIfExists()` no `down()`) para toda migration presente
e futura, e remove um nome de arquivo fixado.

## Notas de Implementação

<!-- preenchido durante a execução -->

## Proposta de atualização de `.ai/rules/settings.md` (NÃO gravada)

A rule afirma hoje, no fim da seção *"Chave lida no boot não pode virar Settings"*:

> Para tornar uma chave de boot editável de verdade, o caminho é um middleware próprio que decida
> por request — aí a decisão sai do array da rota. **Não foi feito; é dívida conhecida.**

Com esta entrega a última frase é falsa. Proposta de substituição, para decisão do dono do
repositório:

```markdown
## Chave lida no boot não pode virar Settings — a menos que um middleware próprio decida por request

O critério não é sobre a chave, é sobre QUANDO ela é lida — e sobre existir ou não um ponto de
extensão que aceite trocar a decisão por um decisor.

- **Lida por request**: pode ir para o Settings. Basta a linha no `mapaDeConfiguracao()`.
- **Lida no boot** (montar painel, registrar rota, decidir middleware): não pode **diretamente**.
- **Lida no boot, mas com um ponto de extensão que aceita uma classe nossa**: pode, invertendo a
  condição de lugar. É o caso de `registro_verificar_email`, resolvido em
  `wikis/specs/feat/verificacao-de-email-editavel/`:
  o middleware de e-mail verificado continua fixado no array da rota
  (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), mas o painel passou a
  aplicá-lo **sempre** e a declarar a classe do kit em `emailVerifiedMiddlewareName()`
  (`app/Providers/Filament/AppPanelProvider.php`). O array da rota deixou de guardar uma decisão
  e passou a guardar um decisor: `App\Http\Middleware\ExigirEmailVerificado` lê a opção a cada
  request.

  O preço tem nome: a rota de destino do redirecionamento precisa nascer **sempre**, senão ligar
  a opção produz `RouteNotFoundException` em vez de tela.

**Toggle que grava e não faz efeito até o próximo deploy continua sendo pior que campo ausente.**
A saída, quando não há ponto de extensão, continua sendo deixar no `.env` e dizer na tela onde a
chave mora. O que mudou é que "chave de boot" deixou de ser veredito automático: antes de aceitar
a dívida, procure o `...MiddlewareName()` (ou equivalente) do componente.

Ao acrescentar propriedade, são TRÊS lugares, sempre: a propriedade na classe, a linha em
`mapaDeConfiguracao()` e o `add()`/`addEncrypted()` numa migration de settings — **nova**, não a
existente, que já rodou em instalação de terceiro. Esquecer a linha do mapa é o defeito
silencioso: o campo aparece, grava, e não governa nada. Há caso de teste guardando o mapa por isso.
```

Gates avaliados: **durável** ✅ (vale para toda chave de boot futura) · **escopável** ✅
(`app/Settings/**`, e talvez `app/Providers/Filament/**`) · **não-inferível** ✅ (ninguém acha
`emailVerifiedMiddlewareName()` lendo o código do kit) · **não-redundante** ✅ (atualiza rule
existente em vez de criar outra, que é o preferido).

## Retrospectiva

<!-- preenchido no fim -->
