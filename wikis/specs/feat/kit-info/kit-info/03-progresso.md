# Progresso — `kit:info`

> Wiki criada e implementada em 2026-09-02. **Fidelidade do requisito: baixa** (uma linha de chat) —
> as premissas A1–A6 e P1 do `00-requisito.md` seguiram como assumidas, com a autorização do
> mantenedor para implementar. Cada uma traz o "Se negado" no `00`.
>
> **Testes: 37 de 37 verdes, 140 asserções** (`tests/Kit/KitInfoTest.php`,
> `tests/Tenancy/KitInfoTenancyTest.php` e a guarda `tests/Kit/HelpersDeTesteTest.php`).

## 0. Antes de implementar

- [x] Premissas: o mantenedor autorizou a implementação **sem responder uma a uma** ("pode
      implementar", 2026-09-02). Todas seguiram como assumidas no `00-requisito.md` — A1 (as duas
      camadas), A2 (seção de divergência), A3 (`kit:info`), A4 (e-mail mascarado), A5 (sem
      `--json`), A6 (docs pt/en) e P1 (textos de estado). **Cada uma continua reversível**: o `00`
      registra o "Se negado" de todas, e nenhuma se espalhou por mais de um passo.
- [ ] Decidir a branch: esta wiki nasceu em `feat/paleta-do-filament-na-organizacao`, que tem outra
      feature **não commitada**. Opções: fechar aquela entrega primeiro, ou `git checkout -b feat/kit-info`
      + `git mv wikis/specs/feat/paleta-do-filament-na-organizacao/kit-info wikis/specs/feat/kit-info/kit-info`
      — **pendente, é decisão sua**; nenhum arquivo desta feature colide com os nove da outra

## 1. `ConfiguracoesDoKit::gravadoNoBanco()`

- [x] Método estático novo em `app/Settings/ConfiguracoesDoKit.php` + import de `Schema`
- [x] `KitServiceProvider::configureSettingsDoKit()` passa a chamá-lo (o `try/catch` fica); remover
      o import de `Schema` do provider se ficar sem uso (hoje só `:180` o usa em código)
- [x] `CustomizadorDaInstalacao::propagarParaOSettings()` passa a chamá-lo
- [x] `php artisan test --compact tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/CustomizadorDaInstalacaoTest.php` verde

## 2. `ConfiguracoesDoKit::valoresDosArquivos()`

- [x] Extraído de `devolverConfigAoEnv()`; este vira `config(self::valoresDosArquivos())`
- [x] Docblock do porquê (`kit:install --force`, `KIT_SOCIALITE_GOOGLE`) permanece em `devolverConfigAoEnv()`
- [x] `ConfiguracoesDoKitTest.php:637` continua verde sem edição

## 3. `App\Console\Commands\KitInfo`

- [x] `php artisan make:command KitInfo --no-interaction` e reescrita conforme o PRD
- [x] Cabeçalho: versão + fonte
- [x] Seção *Instalação*: nome, banco, administrador(es) mascarado(s), senha (aponta `kit:admin`), cor por `CorPrimaria::paleta()`, multi-organização
- [x] Seção *Configurações do kit*: `mapaDeConfiguracao()` iterado, `exibir()` com segredos de `encrypted()`
- [x] Seção *divergências*: `valoresDosArquivos()` × `config()`, `normalizar()`, só quando houver e só com banco
- [x] Seção *manuais*: `CustomizadorDaInstalacao::itensManuais()` + rodapé "Para mudar"
- [x] `noBanco()` nas três consultas (`gravadoNoBanco`, `todos()`, `Tenant::count()`)
- [x] Um `Log::channel('configuracoes')->debug('[KitInfo@handle] …')` com **chaves**, nunca valores
- [x] `php artisan kit:info` à mão: com banco migrado; com `DB_DATABASE` inexistente

## 4. Documentação e changelog

- [x] `docs/pt/comecar/instalacao-avancada.md` — linha no bloco *Comandos* + frase antes da tabela *Personalize*
- [x] `docs/en/comecar/instalacao-avancada.md` — as mesmas duas, em inglês
- [x] `README.md` e `README.en.md` — linha no bloco de comandos (`:298-309`)
- [x] `CHANGELOG.md` → `[Unreleased]` → *Adicionado*
- [x] **Não** tocar `docs/*/recursos/configuracoes-do-kit.md` (em edição pela outra feature da branch)

## Testes

- [x] `tests/Kit/KitInfoTest.php` — CT-01…CT-14, CT-16, CT-17
- [x] `tests/Tenancy/KitInfoTenancyTest.php` — CT-15
- [x] Nenhum helper novo fora de `tests/Pest.php` se for usado por mais de um arquivo

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --compact tests/Kit/KitInfoTest.php tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/CustomizadorDaInstalacaoTest.php`
- [x] `php artisan test --compact tests/Tenancy/KitInfoTenancyTest.php`
- [x] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` — 53 verdes
- [x] `vendor/bin/phpstan analyse` — **0 erros**
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] `vendor/bin/pest tests/Kit/KitInfoTest.php --mutate --path=app/Console/Commands/KitInfo.php` (`pest-plugin-mutate ^5.0` declarado em `composer.json:94`)
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "comandos do kit são descobertos automaticamente" | `routes/console.php` registra só `inspire` e agendamentos; os sete `kit:*` não têm registro manual — `php artisan list kit` os lista | confirmado; nenhuma correção |
| "`Schema` no provider pode ficar sem uso" | `KitServiceProvider.php` usa `Schema` só em `:180` (código) e `:159` (docblock) | passo 1 manda remover o import se ficar sem uso |
| "`Str::headline('login_linkedin_openid_client_id')` é legível" | devolve `Login Linkedin Openid Client Id`; `nome_da_aplicacao` → `Nome Da Aplicacao` (sem acento, como a propriedade) | aceito na ADR-02; nenhuma correção |
| "`KIT_LOGIN_RODAPE=\"\"` dá `''` no arquivo" | `config/kit.php:526` é `env('KIT_LOGIN_RODAPE')` cru, e `phpunit.xml:102` força `""` → `''` | CT-13 linha 2 é determinística; confirmado |
| "`vinculo_confirmar` e `pontuacao_minima` têm coerção própria" | `:533` `filter_var(..., FILTER_VALIDATE_BOOLEAN)`; `:581` `is_numeric ? (float) : 0.5` | reforça a ADR-05: comparar pelos **arquivos**, não por `env()` (M30) |
| "testes do customizador existem" | `tests/Kit/CustomizadorDaInstalacaoTest.php` existe; não há `KitInstallTest` | PRD *Impacto* corrigido para citar só o arquivo que existe |
| "suíte `Kit` = `TestCase` + `RefreshDatabase` + grupo `kit`" | `tests/Pest.php:53-56` | confirmado; cenários alocados em `Feature` (console) |
| "`tenant()` cria organização com `nome`, `slug`, `ativo`" | `tests/Pest.php:381-384` | confirmado |
| "`{N} cadastrada(s)` no PRD" | o `04` (CT-15) espera o texto literal `3 cadastrada` | PRD lê-se como texto literal `{N} cadastrada(s)` — sem pluralização por código |
| "o banco nasce igual aos arquivos" (CT-09 seria só ausência de seção) | a migration semeia texto com `textoOuNulo()` (`create_kit_settings.php:84`): banco `null`, arquivo `''` — já no primeiro `migrate` | CT-09 passou a matar M27; nota adicionada no `04` (R6) |
| "`pest-plugin-mutate` talvez só transitivo" | declarado em `composer.json:94` (`^5.0`) | hedge removido do `03` e do `04` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | **Nenhuma.** Revisor independente (sem o contexto de quem derivou) auditou os cinco arquivos e devolveu `Lean already. Ship.` | — | — |

Verificado ponto a ponto pelo revisor: os passos 1 e 2 têm dois chamadores cada (não são extração
especulativa); os quatro helpers privados do comando têm mais de um chamador ou encapsulam
formatação condicional; as cinco seções derivam do requisito; os 17 cenários não se sobrepõem em
mutante; os 42 mutantes são plausíveis; as 6 ADRs registram alternativa concreta. Reuso: nada de
novo foi escrito onde o kit já tinha (`mapaDeConfiguracao`, `encrypted`, `itensManuais`,
`AdministradorDaInstalacao::todos`, `CorPrimaria::paleta`, o visual do `resumoDaCustomizacao`).

## Blockers

- [x] **Requisito de fidelidade baixa** — resolvido pela autorização do mantenedor. As premissas
      A1 e A2, que eram as de maior alcance, seguiram como assumidas; se qualquer uma for negada, o
      "Se negado" do `00` diz exatamente o que sai (A1 negada remove os passos 3-4 da seção de
      configurações; A2 negada remove `divergencias()`, `normalizar()` e três cenários).
- [ ] **Branch** — esta wiki e o código nasceram em `feat/paleta-do-filament-na-organizacao`, que
      tem outra feature não commitada. Nenhum arquivo colide, mas os dois trabalhos estão no mesmo
      diff. **Decisão sua** antes do commit.

## Desvios do Plano

- **O separador da linha de divergência não é a seta.** O PRD escrevia `.env: X → banco: Y`. A seta
  não sobrevive à saída (ver a nota abaixo), então a linha ficou `no .env: X · no banco: Y`. O `·`
  foi escolhido em vez do `—` porque `—` já é como o comando escreve "vazio" em toda linha: com ele
  no meio, uma chave vazia sairia `no .env: — — no banco: X`.
- **Dois imports órfãos removidos** que o PRD não previa: `Illuminate\Support\Facades\Schema` no
  `KitServiceProvider` e no `CustomizadorDaInstalacao` ficaram sem uso depois do passo 1, porque
  `gravadoNoBanco()` levou a única chamada de cada um. O docblock do provider que citava
  `Schema::hasTable()` foi reescrito para citar `ConfiguracoesDoKit::gravadoNoBanco()`.
- **CT-04 ganhou uma correção de oráculo antes de rodar**: a versão do `04` esperava o prefixo `adm`
  na saída, e `adm` aparece em `/admin/configuracoes-do-kit` e em outras linhas — a asserção
  passaria com o mascaramento **removido**. O caso passou a esperar a máscara inteira
  (`Str::mask($email, '*', 3)`, isto é `adm` mais catorze asteriscos).
- **CT-08 virou dois casos.** A contagem de seis segredos não cabia dentro de um `it()` que roda uma
  vez por linha do dataset — ela ficou em `CT-08b`, com o mesmo propósito: segredo novo na classe
  sem entrada no dataset faz o caso reprovar em vez de passar em silêncio.

### Primeira execução: 25 verdes, 9 vermelhos, 1 erro — **todos de arranjo, nenhum do comando**

A classificação foi feita **antes** de tocar em qualquer coisa, e nenhuma das dez apontava defeito
na implementação. As quatro causas, e o que mudou:

| Casos | Sintoma | Causa | Correção |
|---|---|---|---|
| CT-02, CT-03 | `Output "Starter Kit" was printed` · `Output "#zz" was printed` | **oráculo sobre a saída inteira.** O comando exibe 50 linhas, e o mesmo texto aparece legitimamente em mais de uma: `Starter Kit` é o nome do projeto **e** o remetente de e-mail (`mail_from_name`, semeado de `${APP_NAME}`); `#zz` está na linha da cor **e** na linha `Cor Primaria Hex`, que mostra o valor vigente de propósito. A asserção reprovava o comando **correto** | novo helper `linhaDoKitInfo()`: o oráculo passou a ser **a linha do rótulo**, não a saída |
| CT-08 (6 linhas), CT-13b | `Output does not contain "definida"` | **`gravarConfiguracao()` não serve para propriedade cifrada.** O `payload` de segredo é criptograma; gravar texto claro direto na tabela faz a decifragem da leitura falhar e o valor nunca chega ao comando. O projeto já documenta isso — é o que separa CT-01 de CT-02 em `ConfiguracoesDoKitTest.php:85-88` | arranjo por `$settings->{$p} = ...; $settings->save()` |
| CT-14 | `cannot VACUUM from within a transaction` | **`Schema::dropAllTables()` emite `vacuum` em SQLite**, e sob `RefreshDatabase` a suíte roda dentro de uma transação | `Schema::drop()` nas três tabelas que o comando consulta |

O helper `linhaDoKitInfo()` é o achado que vale registrar: **"não contém em lugar nenhum" é oráculo
errado para saída de comando de resumo**, e erra na direção pior — reprova a implementação certa.

### Segunda execução: 33 verdes, 2 vermelhos, 1 erro — de novo tudo arranjo

| Casos | Sintoma | Causa | Correção |
|---|---|---|---|
| CT-13b, CT-15 | `Output does not contain "valores não exibidos"` · `Output does not contain "3 cadastrada"` — **com o texto na tela**, confirmado por sondagem | **`expectsOutputToContain()` casa no máximo UMA substring esperada por linha impressa.** `PendingCommand::createABufferedOutputMock()` registra uma expectativa de Mockery por substring (`vendor/laravel/framework/src/Illuminate/Testing/PendingCommand.php:615-622`), e o Mockery satisfaz **uma** expectativa por chamada de `doWrite`: a primeira que casa. As duas substrings de CT-13b estavam na mesma linha, e as três de CT-15 também | oráculo passou a ser a linha, via `linhaDoKitInfo()`; CT-05 recebeu o mesmo tratamento por precaução (as três afirmações dele também compartilham linha) |
| CT-14 | `no such table: main.tenants (SQL: drop table "users")` | **`PRAGMA foreign_keys` é no-op dentro de uma transação** no SQLite, então `Schema::disableForeignKeyConstraints()` não desliga nada sob `RefreshDatabase` — dropar `tenants` antes de `users` deixou uma referência pendente | dropa só `settings` e o pivô de papéis: nada as referencia, e as duas cobrem os dois ramos que o comando precisa sobreviver |

**O helper virou compartilhado.** `saidaDoKitInfo()` e `linhaDoKitInfo()` passaram para `tests/Pest.php`
porque `tests/Tenancy/KitInfoTenancyTest.php` também os usa — helper cruzado declarado dentro de um
arquivo de teste vaza para o vizinho e só estoura sob `--parallel`, `--tia` ou arquivo isolado
(`.ai/rules/testes.md`, guardado por `tests/Kit/HelpersDeTesteTest.php`).

## Notas de Implementação

- **A seta `→` (U+2192) desaparece da saída de um comando artisan neste projeto**, inclusive num
  `$this->line()` cru, enquanto `—` (U+2014) e `·` (U+00B7) passam. Reprodução mínima:

  ```php
  Artisan::command('probe', fn () => $this->line("a \xE2\x86\x92 b"));
  Artisan::call('probe');   // a saída vem "a  b"
  ```

  Fora do comando a seta sobrevive — testado em `BufferedOutput`, em `OutputStyle::writeln()` e no
  próprio `TwoColumnDetail` instanciado à mão com a string exata. **A causa não foi localizada no
  vendor, e por isso não está afirmada em lugar nenhum** (`.ai/rules/specs.md`: justificativa de
  comportamento de pacote se escreve depois de ler o vendor, com `file:line`).

  **O sintoma é anterior a esta feature**: os rótulos de `CustomizadorDaInstalacao::itensManuais()`
  usam `→` e já saem sem ele no `kit:install` de hoje — o `kit:info` só passou a exibir a mesma
  lista num segundo lugar. Consertar aquele texto mudaria a saída do `kit:install`, fora do escopo
  desta entrega. **Fica como decisão do mantenedor.**

- **CT-09 é discriminante sem arranjo nenhum, e isso não estava previsto.** A migration de settings
  semeia texto com `textoOuNulo()` (`create_kit_settings.php:84`), então **toda instalação
  recém-semeada** tem `null` no banco onde o arquivo de config diz `''`. Uma comparação `!==` crua
  acusaria divergência logo depois do `migrate`, em toda instalação — a seção que existe para
  apontar problema real nasceria como ruído permanente. É o mutante M27, morto pelo caminho feliz.

- **A instalação de desenvolvimento desta máquina tem cinco divergências reais** (as chaves do
  anti-robô: `habilitado`, `provedor`, `chave_do_site`, `chave_secreta` e `local`), com o banco
  dizendo Turnstile ligado e o `.env` dizendo reCAPTCHA desligado. Foi a primeira coisa que o
  comando revelou ao rodar — que é exatamente o que ele existe para fazer.

## Retrospectiva

- **Funcionou bem**: derivar o `04` do requisito, e não do plano, pegou dois oráculos fracos antes
  de existir código — o `adm` não-discriminante de CT-04 e a necessidade de CT-11 (a implementação
  preguiçosa chamaria `devolverConfigAoEnv()` e devolveria a config do processo ao `.env` em
  silêncio). O passo 2 do PRD existe por causa desse cenário, não o contrário.
- **Faltou no plano**: o PRD escreveu um caractere na saída sem verificar que ele sobrevive. Custou
  seis sondagens para isolar. A lição generaliza: **caractere não-ASCII em saída de comando se
  confirma rodando**, não escrevendo — e o kit já carregava a evidência disso em `itensManuais()`,
  sem ninguém ter notado.
