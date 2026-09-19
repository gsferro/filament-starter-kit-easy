# Plano de Ação — Laravel Data como padrão de DTO

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: o kit não tem DTO hoje; esta é a primeira adoção do padrão.
- **Toca infra compartilhada?**: **sim** — `composer.json`, `.ai/rules/`, `app/Console/Commands/KitUpdate.php`
  e três fluxos existentes (IA, login social, convite em massa). A regressão é
  **obrigatória** mesmo sendo wiki nova: os CTs das features que consomem esses fluxos precisam
  continuar verdes.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Pacote no kit | 1 | `^4.23`, dry-run limpo |
| RQ-02 | Rule de DTO | 9 (rule) + 8 (enforço) | gravada só com aprovação (step 9 da skill) |
| RQ-03 | Consumo de API tem Data | 2, 3 | os dois pontos de API externa que ficaram nesta entrega (o terceiro, A3, foi cortado) |
| RQ-04 | Resposta de API tem Data | 8 | sem sujeito hoje (P1) — vale por enforço |
| RQ-05 | Revisão do código-fonte | — | **feita antes deste plano**: varredura completa de `app/`, `routes/`, `config/`, `database/`; resultado na tabela "Alvos" abaixo |
| RQ-06 | Implementar o que a revisão achou | 2, 3, 5 | **3 Data** nesta entrega (decisão do usuário, 2026-09-15); os outros 3 e os rejeitados estão nas tabelas abaixo |
| RQ-07 | Documentações | 10 | docs pt/en, READMEs, CHANGELOG, e a rule no lugar do `CLAUDE.md` (ver nota) |

> **Nota sobre `CLAUDE.md`/`AGENTS.md`** *(refina P3 do `00`)*: os dois arquivos são **gerados pelo
> Laravel Boost** — idênticos byte a byte, envolvidos em `<laravel-boost-guidelines>`, reescritos
> por `php artisan boost:update`. Editar à mão é perder na próxima atualização. A convenção de DTO
> entra em `.ai/rules/dto.md`, que as próprias guidelines do Boost mandam o agente ler
> (`CLAUDE.md:75`). É o mesmo alcance, com durabilidade.

## Objetivo

Adotar `spatie/laravel-data` como a forma única de DTO no kit, converter para Data os pontos onde
hoje trafega array de shape fixo — com prioridade para os que cruzam a fronteira com API externa —
e deixar a regra enforçada por teste, de modo que a cláusula "toda API tem Data" continue valendo
para o código que ainda não existe.

## Contexto

O kit não tem DTO. Onde precisa de estrutura, usa `array` com o shape escrito em PHPDoc. A
varredura de `app/` encontrou 8 ocorrências, e em duas delas o **mesmo** shape está redocumentado
em classes diferentes — produtor e consumidor concordando por comentário, sem nada que os obrigue
a continuar concordando.

Na fronteira com o mundo externo o problema é maior: a resposta do classificador de IA chega como
`array<string, mixed>` e é lida com `?? false` / `?? ''`; o perfil do provedor social chega como
objeto do Socialite e é lido por `getRaw()` com três nomes possíveis para a mesma informação.

## Análise dos Arquivos Existentes

### Alvos (o resultado de RQ-05)

| # | Alvo | Hoje | Vira | RQ |
|---|---|---|---|---|
| A1 | `app/Ai/Guardrails/GarantirPromptSeguroMiddleware.php:classificar():66` | `array<string,mixed>\|null` vindo de `StructuredAgentResponse::toArray()` | `VeredictoDoGuardrailData` | RQ-03 |
| A2 | `app/Http/Controllers/Auth/LoginSocialController.php:retorno():137` + `app/Support/ProvedorSocial.php:booleanoDoBruto():219` | `AbstractUser` do Socialite + `getRaw()` cru | `PerfilSocialData` | RQ-03 |
| ~~A3~~ | `app/Ai/Listeners/RegistrarAiRun.php:handle():30` | array `$tokens` remontado à mão (`:37-42`) | **fora desta entrega** — ganho baixo: o array é montado e consumido num método só | RQ-03 |
| A4 | `app/Models/Convite.php:convidarEmMassa():281` **e** `app/Filament/Concerns/ConvidaEmMassa.php:notificarResultadoDoLote():145` | `array{enviados, falhas}` redocumentado nas duas classes | `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` | RQ-05/06 |
| ~~A5~~ | `app/Support/CustomizadorDaInstalacao.php:aplicarSemBanco():239` **e** `propagarParaOSettings():350` | `array{nome, cor}` com PHPDoc idêntico | **fora desta entrega** — dois campos, e o instalador roda no `create-project`: risco sem ganho | RQ-05/06 |
| ~~A6~~ | `app/Services/Dashboard/CriadorDeDashboardPadrao.php:widgets():75` | `array<int, array{type, name, section_slug, x, y, w, h}>` | **fora desta entrega** — `widgets()` é contrato público publicado na v0.33.0; mexer nele agora é risco sem ganho | RQ-05/06 |

### Recusados, com motivo

| Alvo | Por que não vira Data |
|---|---|
| `ImportadorDoKit` / `ExportadorDoKit` | o array é shape do `Importer`/`Exporter` do Filament; converter na entrada e desconverter na saída a cada linha de CSV não paga |
| `CorPrimaria::paleta():64` | formato ditado pelo `ColorManager` do Filament |
| `ConfiguracoesDoKit::mapaDeConfiguracao():317` | dicionário de ~44 chaves, largura variável — não é shape fixo |
| `KitAdmin::coletar():105` | tupla local de método privado, destructurada uma linha abaixo |
| `GuardrailRegistry::MAPA:23` | mapa de configuração, já resolvido como const + validação |
| `FiltroSaidaSensivelMiddleware::redigir():73` | tupla interna à própria classe; não cruza fronteira pública |
| `{name, email, password}` (4 pontos) | **ADR-04** — senha não entra em Data |
| `Paineis::mapa():120` | shape aninhado de 3 níveis com consumidor único interno; o Data aninhado custaria mais que o comentário que ele substituiria |

## Autorização

Nada. A feature não cria tela, rota nem ação — não há policy, gate, middleware ou guard novo.

## Rotas

Nenhuma rota nova. O kit continua **sem** `routes/api.php` (P1).

## Superfície de UI

**Sem superfície de UI.** Nenhum Data desta entrega chega à tela por caminho novo: o
`ResultadoDoConviteEmMassaData` alimenta a mesma `Notification` de hoje
(`ConvidaEmMassa::notificarResultadoDoLote():148`), com o mesmo texto.

Consequência: **não haverá `05-casos-de-teste-browser.md`** — o motivo fica registrado no `04`.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum novo, e nenhum alterado: o `RegistrarAiRun` ficou **fora desta entrega** junto com o A3.

## Jobs / Queues

Nenhum. O kit não tem `app/Jobs` nem `app/Events`; as três Notifications `ShouldQueue` já usam
construtor tipado. **Nenhum Data desta entrega é serializado em fila** — quando o kit ganhar o
primeiro job com Data, ADR-06 do CMS (array cru reconstruído dentro do `handle()` para fila de vida
longa) é o ponto de partida.

## Impacto em Features Existentes

| Feature | O que pode quebrar |
|---|---|
| Guardrails de IA (`agentes-de-ia`) | `classificar()` muda de tipo de retorno; o fail-open de `handle()` precisa continuar idêntico |
| Login social (`login-social-*`) | `PerfilSocialData` entra no caminho de criação de conta e de vínculo — é o fluxo mais coberto do kit |
| Convite em massa (`convite-em-massa`) | produtor e consumidor mudam juntos; a `Notification` tem de sair com o mesmo texto |
| ~~Instalador (`kit:install`)~~ | **fora desta entrega** — `CustomizadorDaInstalacao` não é tocado |
| ~~Dashboard dinâmico (v0.33.0)~~ | **fora desta entrega** — `widgets()` não é tocado |

## Rollback

- `composer remove spatie/laravel-data` + reverter o commit. Não há migration, não há dado
  persistido em formato novo, não há `.env`.
- Com o corte de escopo, o rollback ficou trivial: nenhum contrato público é tocado, e os três
  fluxos alterados voltam ao array com o `git revert` do commit.

## Dependências

- **Composer**: `spatie/laravel-data ^4.23` (traz `spatie/php-structure-discoverer ^2.4`)
- **NPM**: nenhuma

## Riscos

| Risco | Mitigação |
|---|---|
| ~~`widgets()` é contrato público~~ | eliminado pelo corte de escopo |
| ~~Data em ponto crítico do instalador~~ | eliminado pelo corte de escopo |
| Data criado e não consumido em produção | CT-22, CT-23 e CT-24 provam o ponto de uso com valor que só o Data produz |
| Adoção parcial vira dois padrões no kit | o teste do passo 8 reprova array de shape fixo novo em fronteira pública |

## Channel de Log da Feature

**Nenhum channel novo** — decisão registrada, não esquecimento. A feature não tem fluxo de execução
próprio: cada Data vive dentro de um fluxo que **já** tem channel e já loga
(`ai` em `GarantirPromptSeguroMiddleware` e `RegistrarAiRun`; `autenticacao` em
`LoginSocialController`). Um channel `dto` não teria evento nenhum para registrar.

O que muda nos logs existentes: onde o log hoje lê do array (`$veredito['categoria'] ?? '…'`), passa
a ler da propriedade tipada (`$veredito->categoria`), **sem alterar mensagem, nível nem contexto** —
os CTs de log das features existentes são o oráculo disso.

## Estrutura de Implementação

### 1. Instalar o pacote e reservar o diretório

> Skills: `laravel-best-practices`, `ponytail`

- `composer require spatie/laravel-data` (resolve `^4.23`; dry-run já conferido, sem conflito)
- **Não** publicar `config/data.php` (ADR-08)
- `app/Console/Commands/KitUpdate.php`: acrescentar `'app/Data'` à lista de paths do kit, junto de
  `'app/Support'` (`KitUpdate.php:148`) — sem isso o diretório novo não viaja para quem atualiza
- Criar `app/Data/.gitkeep`? **Não** — o diretório nasce com o primeiro Data do passo 2

### 2. `VeredictoDoGuardrailData` — A1 (RQ-03)

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Data/Ia/VeredictoDoGuardrailData.php`
- Assinatura:

  ```php
  final class VeredictoDoGuardrailData extends Data
  {
      public function __construct(
          public readonly bool $seguro,
          public readonly string $categoria,
          public readonly string $motivo,
      ) {}

      /** O contrato do schema de `GuardaPrompt::schema()` — a resposta estruturada do modelo. */
      public static function daRespostaEstruturada(StructuredAgentResponse $resposta): self
  }
  ```

- `daRespostaEstruturada()` faz o mapeamento explícito (ADR-03) com os mesmos defaults que o código
  de hoje aplica: `categoria` ausente → `'fora_de_escopo'`, `motivo` ausente → `''`
- `GarantirPromptSeguroMiddleware::classificar()` passa a devolver `?VeredictoDoGuardrailData`;
  `handle()` lê `$veredito->seguro` e `$veredito->categoria`
- **O fail-open não muda**: `null` continua significando "o classificador não pôde opinar", e
  resposta fora do schema continua virando `null` (ADR-06)
- **Logs**: os dois logs existentes de `handle()` e `classificar()` ficam **idênticos** em canal,
  nível, mensagem e chaves de contexto — só a origem do valor muda

### 3. `PerfilSocialData` — A2 (RQ-03)

> Skills: `laravel-best-practices`, `socialite-development`, `pest-testing`

- **Path**: `app/Data/Social/PerfilSocialData.php`
- Campos: `id` (o `sub` do provedor), `email`, `nome`, `avatar` (`?string`),
  `emailVerificado` (`?bool` — `null` = o provedor não disse), e `bruto` (`array<string, mixed>`,
  o `getRaw()` preservado para quem precisar de chave específica)
- Construtor nomeado `doSocialite(ProvedorSocial $provedor, AbstractUser $usuario): self`, que
  concentra o que hoje está espalhado: `getId()`, `getEmail()`, `getName()` e a leitura de
  "email verificado" com os três nomes possíveis (`ProvedorSocial::booleanoDoBruto():217`)
- `ProvedorSocial::booleanoDoBruto()` e `naoDesmentidoNoBruto()` passam a receber o Data (ou a
  lógica migra para o construtor nomeado — decidir no passo, mantendo os CTs de login social verdes)
- **Segurança**: `bruto` **não** inclui `token`/`refreshToken` do Socialite — o construtor nomeado
  copia só o array de perfil (ADR-04 vale aqui também)
- **Logs**: `autenticacao`, sem mensagem nova

### 4. `UsoDeTokensData` — A3 — **FORA DESTA ENTREGA**

> Cortado na escalação de 2026-09-15, depois da rodada 2 da revisão adversarial: cada Data exige um
> cenário provando que produção o consome, e este não paga o custo. O alvo continua mapeado acima.

<details><summary>Plano original, para a entrega seguinte</summary>

> Skills: `ai-sdk-development`, `pest-testing`

- **Path**: `app/Data/Ia/UsoDeTokensData.php`
- Campos: `entrada`, `saida`, `leituraDeCache`, `escritaDeCache` (todos `int`, default `0`)
- Construtor nomeado `doEvento(AgentPrompted|AgentStreamed $evento): self`
- `RegistrarAiRun::handle()` usa o Data para montar as colunas de `ai_runs`, mantendo os nomes de
  coluna atuais
- **Logs**: nenhum novo

</details>

### 5. `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` — A4 (RQ-05/06)

> Skills: `laravel-best-practices`, `eloquent-best-practices`, `pest-testing`

- **Paths**: `app/Data/Convite/ResultadoDoConviteEmMassaData.php`, `app/Data/Convite/FalhaDoConviteData.php`
- `ResultadoDoConviteEmMassaData`: `list<string> $enviados` + `list<FalhaDoConviteData> $falhas`
  (array tipado, ADR-05)
- `FalhaDoConviteData`: `string $email` + `string $motivo`
- `Convite::convidarEmMassa()` muda o tipo de retorno; `ConvidaEmMassa::notificarResultadoDoLote()`
  muda o parâmetro. **Os dois PHPDoc de shape são apagados** — é o contrato que some do comentário
  e vira tipo
- A `Notification` sai **com o mesmo texto de hoje** (contagem, corpo, `persistent()`, `success` ×
  `warning`)

### 6. `MarcaDaInstalacaoData` — A5 — **FORA DESTA ENTREGA**

> Mesmo motivo do passo 4. O alvo continua mapeado.

<details><summary>Plano original, para a entrega seguinte</summary>

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Data/Instalacao/MarcaDaInstalacaoData.php`
- Campos: `string $nome`, `string $cor`
- `CustomizadorDaInstalacao::aplicarSemBanco()` e `propagarParaOSettings()` passam a recebê-lo; o
  PHPDoc duplicado sai das duas assinaturas
- **Cuidado**: `perguntar()` continua devolvendo array (respostas do instalador, shape variável com
  tenancy — fora da régua de P2). A conversão para o Data acontece na chamada

### 7. `WidgetDoDashboardData` — A6 — **FORA DESTA ENTREGA**

> Cortado na escalação de 2026-09-15: `widgets()` é contrato público publicado na v0.33.0, e a
> compatibilidade de dois formatos por um ciclo custaria mais que o ganho. Volta na entrega
> seguinte, quando houver motivo além da uniformidade.

<details><summary>Plano original, para a entrega seguinte</summary>

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Data/Dashboard/WidgetDoDashboardData.php`
- Campos: `class-string $tipo`, `string $nome`, `string $secao`, `int $x`, `int $y`, `int $w`, `int $h`
- `CriadorDeDashboardPadrao::widgets()` passa a declarar `@return list<WidgetDoDashboardData>`
- **Compatibilidade (risco declarado acima)**: por um ciclo, o laço de criação aceita **os dois**
  formatos — item que já é `WidgetDoDashboardData` segue direto; item `array` é convertido com
  `WidgetDoDashboardData::from()` e a linha fica marcada com `ponytail:` + a versão em que sai
- CHANGELOG avisa a quem sobrescreveu `widgets()`

</details>

</details>

### 8. O guarda do padrão (RQ-02, RQ-03, RQ-04)

> Skills: `pest-testing`, `testing-best-practices`

- **Paths**: `app/Support/GuardaDoPadraoDeDto.php` (o guarda) + `tests/Kit/DtoComLaravelDataTest.php`
- **O guarda expõe as raízes padrão como dado** (constante ou config), recebe uma raiz opcional por
  argumento e devolve a lista de violações com o motivo de cada uma. Foi a decisão do usuário na
  escalação: sem isso, o teste precisaria escrever arquivo dentro de `app/` e `routes/` para provar
  o escopo — com quatro riscos medidos (paralelismo, interrupção deixando classe defeituosa no
  working tree, cache de rota, `git status` sujo).
- **A mensagem de cada violação é discriminante**: cita o motivo daquele defeito e **não** os
  outros. Mensagem única listando todos os motivos satisfaz qualquer asserção de "cita X" e não
  distingue nada (achado I8 da rodada 2).
- No estilo de varredura que o kit já usa (`PermissoesDeTelasTest`, `InventarioDeTelasTest`) —
  **não** `pest --arch`, que o kit não usa em lugar nenhum:
  1. toda classe em `app/Data/**` estende `Spatie\LaravelData\Data`, é `final` e só tem propriedade
     `readonly`
  2. nenhuma classe **fora** de `app/Data/**` termina em `Data` (sufixo reservado, ADR-02)
  3. nenhum Data tem propriedade com nome de credencial — `senha`, `password`, `token`, `secret`,
     `api_key` (ADR-04)
  4. se existir `routes/api.php`, `app/Http/Resources/**` ou `response()->json(` em controller, o
     payload correspondente é um Data (ADR-07) — hoje passa por vacuidade, e o CT registra isso
     explicitamente para o caso não parecer falso verde
- **Logs**: n/a

### 9. Rule de projeto (RQ-02)

> Skills: `requirement-to-rule`

- Candidato único: **"DTO no kit é `spatie/laravel-data`"**, glob `app/**`
- Conteúdo curto, apontando para o enforço do passo 8 e para as ADR-02 a ADR-05 (onde moram, como
  se chamam, fábrica nomeada, coleção, senha fora)
- **Gravada só com aprovação explícita do usuário**, via `record-rule` do Boost (nunca escrevendo o
  arquivo à mão — o Boost regenera o `index.md`)

### 10. Documentação (RQ-07)

> Skills: `laravel-best-practices`

- `docs/pt/recursos/dto-com-laravel-data.md` e `docs/en/recursos/dto-com-laravel-data.md` — página
  nova com `parent: Recursos`, `nav_order` seguinte ao maior existente; explica o padrão, onde
  moram, a fábrica nomeada, a regra da senha e como o enforço reprova
- `docs/{pt,en}/recursos/index.md` — a frase de índice cita a página nova
- `README.md` / `README.en.md` — `spatie/laravel-data` na tabela de pacotes; números objetivos
  recontados se mudarem
- `CHANGELOG.md` — entrada em `[Unreleased]`, com o aviso de `widgets()` do passo 7
- `CLAUDE.md`/`AGENTS.md`: **não editar** (gerados pelo Boost) — a convenção vive na rule do passo 9

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**: cada Data precisa de um segundo consumidor ou de uma fronteira
> externa para existir. Data de uso único é o que a auditoria do step 6 deve cortar.
>
> **Caveman `ultra`** na conversa; arquivos wiki, código e commits em prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. **Sem `05-casos-de-teste-browser.md`** — a feature não tem superfície
> de UI nova e nenhum cenário depende de JavaScript, tema ou acessibilidade.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check` (PHPStan level 7 — os Data precisam passar com generics corretos)
- [ ] `vendor/bin/filacheck` (tocou `app/Filament/Concerns`)
- [ ] `php artisan test --compact tests/Kit/DtoComLaravelDataTest.php`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — regressão obrigatória
      (infra compartilhada)
- [ ] `diff` dos IDs de CT entre o `04` e os arquivos de teste — saída vazia
- [ ] Revisão de código do diff (step 7.5)

## Commits

- `✨ feat(dto): spatie/laravel-data como padrão de DTO do kit`
- `♻️ refactor(dto): {alvo} passa a trafegar {XData}` (um por alvo, ou agrupados por fluxo)
- `✅ test(dto): enforço do padrão de DTO`
- `📝 docs(dto): página do padrão de DTO em pt e en`
- `📝 docs(wiki): especificação do padrão de DTO`
