# Decisões Arquiteturais — Laravel Data como padrão de DTO

> Muitas destas decisões nasceram da leitura de um projeto **real do mesmo autor** que já usa o
> pacote há tempo (`D:/PROJECTS/GSFERRO/FM2S/CMS/cms`, ~20 Data em produção). Onde a decisão veio
> de lá, a evidência é citada como `cms:arquivo:linha` — é aprendizado medido, não preferência.

## Superfície do Pacote

Inventário exigido pela `feature-wiki` quando a feature monta sobre pacote de terceiro. A fonte é o
código do pacote (4.23.0, extraído do cache do Composer antes da instalação), não a documentação.

| Ponto de entrada (vendor) | Alcançável pelo cliente? | Fronteira aplicada pelo projeto | Evidência |
|---|---|---|---|
| `Data::from($payload)` | **não** — só o código do kit chama, com payload que ele mesmo buscou | a fábrica nomeada de cada Data (ADR-03) é o único chamador | `src/Data.php`, `src/Concerns/BaseData.php` |
| `LivewireDataSynth::hydrate()` → `->from($payload)` com o payload **do navegador** | **sim**, se um Data virar propriedade pública de componente Livewire | **nenhum Data desta entrega é propriedade de componente** — e a rule proíbe sem `#[Locked]` | `src/Support/Livewire/LivewireDataSynth.php:73,102` |
| `WireableData` (concern que liga o Data ao Livewire) | idem acima | não usado pelo kit | `src/Concerns/WireableData.php` |
| Comandos artisan `data:make`, `data:cache-structures` | não (CLI) | — | `src/Commands/DataStructuresCacheCommand.php:14` |
| Rotas / controllers próprios | **não existem** — o pacote é biblioteca, não plugin de painel | — | `ls src/` não tem `Http/`, `Routes/`, nem `routes/` |

**Models persistidos pelo pacote**: nenhum. O pacote não traz migration nem Eloquent model — não há
tabela a escopar, ao contrário do que o inventário costuma achar em plugin de Filament.

**A linha que importa** é a segunda: a mesma classe de defeito da v0.33.0 (propriedade pública de
componente Livewire reconstruída a partir do payload do cliente) existe aqui, e a decisão é
mantê-la fora do alcance — nenhum Data em propriedade de componente nesta entrega, e a rule do
passo 9 registra a proibição para quem vier depois. O cenário que falsifica essa negativa está no
`04` (ver R6).

---

## ADR-01: `spatie/laravel-data` 4.x é o padrão de DTO do kit

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

`RQ-01` e `RQ-02` pedem o pacote e a regra. O kit hoje não tem DTO nenhum: onde precisa de
estrutura, usa `array` com shape documentado em PHPDoc — 8 ocorrências confirmadas em `app/`,
duas delas com o **mesmo** shape redocumentado em classes diferentes
(`Convite::convidarEmMassa():279` e `ConvidaEmMassa::notificarResultadoDoLote():145`).

### Decisão

Adotar `spatie/laravel-data` ^4.23 como a forma única de DTO. Todo Data do kit estende
`Spatie\LaravelData\Data`.

### Alternativas Consideradas

1. **Continuar com `array{...}` em PHPDoc** — grátis, mas o contrato só existe no comentário:
   nada impede o produtor e o consumidor divergirem, que é exatamente o estado atual.
2. **`readonly class` de PHP puro** — resolve tipo, não resolve criação a partir de payload
   externo, validação, nem `toArray()` — que é o que o kit precisa na fronteira com Socialite,
   captcha e SDK de IA.
3. **`Spatie\LaravelData\Dto`** (classe base mínima do mesmo pacote) — descartada como padrão
   geral para não ter duas classes base no kit; `Data` é superset e o CMS usa só ela.

### Consequências

- **Positivas**: contrato verificável pelo PHPStan e pelo runtime; um lugar só para traduzir
  payload externo; `toArray()` de graça.
- **Negativas**: mais duas dependências (`spatie/laravel-data` + `spatie/php-structure-discoverer`).
- **Riscos**: nenhum de compatibilidade — `composer require --dry-run` resolve limpo em Laravel 13
  (`illuminate/contracts ^10|^11|^12|^13`) e Filament 5.

---

## ADR-02: `app/Data/{Contexto}/`, sufixo `Data` **reservado**, classe `final` com `readonly`

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

Onde os Data moram e como se chamam decide se, daqui a um ano, `grep '*Data.php'` encontra os DTOs
ou lixo junto.

### Decisão

- Diretório `app/Data/`, subpasta por contexto (`app/Data/Ia/`, `app/Data/Social/`,
  `app/Data/Convite/`…), como no CMS (`cms:app/Data/Pagarme/Order/OrderData.php:10`).
- **Sufixo `Data` é reservado** para classes que estendem `Spatie\LaravelData\Data`. Nenhum model,
  serviço ou enum do kit pode terminar em `Data`.
- Toda classe: `final`, construtor com **promoted properties `readonly`**
  (`cms:app/Data/Pagarme/Order/OrderData.php:12-30`).
- **Uma classe por arquivo**, contra o padrão do CMS de agrupar pai e filhos no mesmo `.php`
  (`cms:app/Data/Curseduca/MemberByEmailData.php:7,20,27` — três classes num arquivo).

### Alternativas Consideradas

1. **Sufixo livre / sem sufixo** — descartada por evidência direta: no CMS,
   `cms:app/Models/MbaReportStudentData.php:11` é um **Eloquent Model** terminado em `Data`, e
   quem varre por `*Data.php` atrás de DTO encontra um falso positivo. O kit começa limpo (zero
   classes `*Data` hoje), então a reserva custa nada agora e é impossível depois.
2. **Agrupar Data relacionados num arquivo** (padrão do CMS) — descartada: o kit é lido por agentes
   de IA, que abrem arquivo por FQCN; classe escondida em arquivo de outro nome é invisível para
   eles e para o PSR-4.

### Consequências

- **Positivas**: `app/Data/**` é a resposta completa para "quais DTOs existem"; a reserva do sufixo
  mantém o grep confiável para sempre.
- **Negativas**: mais arquivos que o padrão do CMS.

---

## ADR-03: fábrica nomeada na fronteira, mapeamento explícito — sem mapper mágico

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

O dado externo chega em `snake_case`, com chaves que variam por provedor
(`ProvedorSocial::booleanoDoBruto():219` lê `email_verified`, `verified_email` ou `confirmed_email`
conforme o provedor). O pacote oferece `#[MapInputName]` e mappers automáticos de casing.

### Decisão

Cada Data expõe um **construtor nomeado estático** que recebe o objeto/array externo e faz o
mapeamento **campo a campo, explícito**, chamando `self::from([...])` por dentro. `Data::from()`
não é chamado direto pelo código consumidor.

### Alternativas Consideradas

1. **`#[MapInputName(SnakeCaseMapper::class)]`** — menos linhas, mas o algoritmo de casing erra
   justamente nas chaves irregulares que o kit tem (três nomes diferentes para "email verificado"),
   e o erro é silencioso: a propriedade fica nula e ninguém sabe por quê. O CMS chegou à mesma
   conclusão e **não usa nenhum atributo de mapeamento** em ~20 Data
   (`cms:app/Data/Pagarme/PagarmeChargeData.php:39-63`), pagando boilerplate em troca de
   explicitude testável.
2. **`Data::from()` espalhado no consumidor** — espalha a tradução do payload por N call-sites;
   o CMS centraliza em `make*()` e testa por lá (`cms:app/Data/Charges/ClientCheckoutData.php:51,73`).

### Consequências

- **Positivas**: a tradução vive num lugar testável; a assinatura diz de onde o dado vem.
- **Negativas**: boilerplate por Data.
- **Armadilha herdada**: `new MeuData(...)` **não** roda casts — só o pipeline de `::from()` roda.
  O CMS foi mordido por isso e escreveu teste de regressão
  (`cms:tests/Unit/Data/Charges/ClientCheckoutDataTest.php:154-163`). O kit repete esse teste.

---

## ADR-04: senha não entra em Data

**Status**: Aceita
**Data**: 2026-09-15
**Relacionada**: P5 do `00-requisito.md`

### Contexto

O shape `{name, email, password}` é montado à mão em quatro pontos do kit (`Convite::aceitar():601`,
`RegistroAberto::registrar():160` e dois literais em `LoginSocialController.php:399,636`) e seria,
pela régua de P2, um candidato forte a Data.

Um Data, porém, tem `toArray()`/`toJson()` públicos, é serializável em fila e aparece inteiro em
`dd()`. No CMS, `cms:app/Data/Charges/ClientCheckoutData.php:16` carrega `password` em texto puro
**sem** `#[Hidden]`; a única rede é um processor do Monolog que mascara por nome de chave
(`cms:app/Providers/LogMaskingServiceProvider.php:88-101`) — que não cobre `toArray()` fora de log
nem payload de fila. E **não há teste** provando que a senha não vaza.

### Decisão

Senha (e qualquer credencial) **não** vira propriedade de Data neste kit. O shape de registro
continua como está: `#[SensitiveParameter] string $senha` como parâmetro à parte, fora de qualquer
estrutura serializável.

Consequentemente, o candidato "`DadosDeRegistroData`" fica **fora desta entrega**, registrado aqui
com o motivo — não é esquecimento.

### Alternativas Consideradas

1. **Data com `#[Hidden]` na senha** — `#[Hidden]` tira de `toArray()`, mas **não** de `var_dump`,
   `dd()` nem da serialização de fila. Mitigação parcial vendida como completa.
2. **Data só com `{nome, email}` e senha por fora** — tecnicamente seguro, mas um Data de dois
   campos não paga a troca em quatro call-sites; a duplicação que ele resolveria é de duas chaves.

### Consequências

- **Positivas**: nenhuma superfície nova por onde credencial vaze.
- **Negativas**: os quatro call-sites continuam concordando "à mão" sobre `{name, email, password}`.
- **Enforço**: o teste do ADR-07 reprova qualquer Data com propriedade chamada `senha`, `password`,
  `token`, `secret` ou `api_key`.

---

## ADR-05: coleção de sub-Data é **array tipado**; `DataCollection` só com necessidade declarada

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

O pacote oferece as duas formas. O CMS usa **as duas, para o mesmo problema**, em domínios
diferentes — `DataCollection` + `#[DataCollectionOf]` no Curseduca
(`cms:app/Data/Curseduca/AssessmentsAverageData.php:15-18`) e array tipado com `@var X[]` no
Pagarme (`cms:app/Data/Pagarme/Order/OrderData.php:26-28`) — e **nenhum comentário explica a
escolha**.

### Decisão

Padrão do kit: **array tipado** com `/** @var list<XData> */`. `DataCollection` só quando o
cenário precisar de `include()`/`exclude()`/lazy **na coleção**, e a necessidade fica escrita no
docblock da propriedade.

### Consequências

- **Positivas**: uma resposta só para quem contribuir depois; PHPStan entende `list<XData>`.
- **Negativas**: perde-se o açúcar do `DataCollection` no caso raro em que ele seria útil.

---

## ADR-06: falha de Data ≠ falha de infraestrutura

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

No CMS, o controller de webhook engloba `json_decode` + criação do Data + dispatch num único
`try/catch (Exception)` que devolve 500 (`cms:app/Http/Controllers/Integrations/PagarmeWebhookController.php:26-77`).
Payload malformado do provedor e banco fora do ar ficam **indistinguíveis** na resposta.

### Decisão

Onde o kit criar Data a partir de payload externo, a exceção de criação/validação do pacote é
capturada **separadamente** da exceção de infraestrutura, e cada uma tem o seu log e o seu
desfecho. O padrão já existente no kit é o alvo: `GarantirPromptSeguroMiddleware::classificar():66`
já separa "resposta fora do schema" (devolve `null`, fail-open deliberado) de "container fora do
ar" (`catch (Throwable)` com `warning`) — o Data entra **sem** apagar essa distinção.

### Consequências

- **Positivas**: a trilha continua dizendo qual dos dois aconteceu.
- **Negativas**: mais um `catch` por fronteira.

---

## ADR-07: a regra é enforçada por teste, não por prosa

**Status**: Aceita
**Data**: 2026-09-15
**Relacionada**: P1 do `00-requisito.md`, `.ai/rules/specs.md` ("prefere enforço automático a prosa")

### Contexto

`RQ-04` ("toda resposta de API tem Data") não tem sujeito hoje: o kit não expõe API. Uma rule
escrita sem enforço morre no dia em que alguém criar o primeiro endpoint sem ler `.ai/rules/`.

O CMS mostra o custo de não enforçar: o padrão "final + readonly + extends Data" é respeitado em
100% dos Data **por convenção**, e o teste de arquitetura de lá
(`cms:tests/Arch/ProjectTest.php:94-108`) cobre Controllers, Models, Enums e Traits — e **nenhuma**
regra para `App\Data`.

### Decisão

`tests/Kit/DtoComLaravelDataTest.php` varre o código e reprova:

1. classe em `app/Data/**` que não estenda `Spatie\LaravelData\Data`, não seja `final` ou tenha
   propriedade não-`readonly`;
2. classe fora de `app/Data/**` cujo nome termine em `Data` (o sufixo é reservado — ADR-02);
3. propriedade de Data com nome de credencial (ADR-04);
4. **existência de `routes/api.php`, de `app/Http/Resources/**` ou de `response()->json(` em
   controller** sem que o payload correspondente seja um Data — é este item que faz `RQ-04` valer
   no futuro em vez de virar comentário.

O estilo segue o que o kit já usa para enforço estrutural (varredura do sistema de arquivos, como
`PermissoesDeTelasTest::paginasDePainelDoKit()` e `InventarioDeTelasTest`), e não `pest --arch` —
o kit não tem nenhum teste `arch()` hoje.

### Consequências

- **Positivas**: a cláusula sem sujeito vira uma guarda que acorda sozinha.
- **Negativas**: o item 4 precisa de manutenção se o kit ganhar uma API com formato próprio.

---

## ADR-08: sem `data:cache-structures` e sem publicar `config/data.php` agora

**Status**: Aceita
**Data**: 2026-09-15

### Contexto

O pacote oferece cache de estruturas e um `config/data.php` publicável. No CMS, **nenhum dos dois**
é usado — e a ausência lá é por omissão, não por decisão registrada: não há `config/data.php`, nem
chamada a `data:cache-structures` em script de deploy.

### Decisão

O kit não publica a config nem adiciona o cache de estruturas. O default do pacote vale, e a
decisão fica registrada **com gatilho**: se o kit passar a criar Data em laço sobre coleção grande
(importação de CSV, relatório), o cache entra no `kit:install` e no `optimize` — e volta para cá
como ADR nova.

### Consequências

- **Positivas**: menos um arquivo publicado que o `kit:update` teria de manter em dia.
- **Negativas**: perde-se um ganho de performance que ninguém mediu precisar.
