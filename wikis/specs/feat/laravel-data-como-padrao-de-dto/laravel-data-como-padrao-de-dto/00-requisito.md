# Requisito — Laravel Data como padrão de DTO do kit

## Fonte

- **Origem**: pedido do mantenedor no chat, via `/feature-wiki`
- **Data**: 2026-09-15
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> implemente o pacote "https://spatie.be/docs/laravel-data/v4/introduction" no kit e crie a rule para que sempre que precisar um DTO, utilize o pacote como padrão.
> - todo consumo e resposta de uma api, deve obrigatoriamente ter um Data(DTO)
> - faça uma revisão de todo o codigo fonte e veja se já tem alguma parte do projeto que um Data seja necessário e implemente
> - atualize as documentações

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `spatie/laravel-data` passa a fazer parte do kit | "implemente o pacote "https://spatie.be/docs/laravel-data/v4/introduction" no kit" | funcional |
| RQ-02 | Existe uma Project Rule dizendo que DTO no kit é `spatie/laravel-data` | "crie a rule para que sempre que precisar um DTO, utilize o pacote como padrão" | restrição |
| RQ-03 | Todo **consumo** de API tem um Data | "todo consumo e resposta de uma api, deve obrigatoriamente ter um Data(DTO)" | restrição |
| RQ-04 | Toda **resposta** de API tem um Data | "todo consumo e resposta de uma api, deve obrigatoriamente ter um Data(DTO)" | restrição |
| RQ-05 | O código-fonte inteiro é revisado à procura de pontos onde um Data é necessário | "faça uma revisão de todo o codigo fonte e veja se já tem alguma parte do projeto que um Data seja necessário" | funcional |
| RQ-06 | Os pontos encontrados na revisão são implementados | "e implemente" | funcional |
| RQ-07 | As documentações são atualizadas | "atualize as documentações" | funcional |

> `RQ-03` e `RQ-04` saem da mesma frase e foram separadas porque falham em separado: o kit
> **consome** API hoje e **não expõe** nenhuma. Fundidas, a matriz de rastreabilidade marcaria ✅
> com metade entregue.

## Ambiguidades e Perguntas Abertas

Todas levadas ao solicitante antes de escrever o plano. As respostas viram premissas numeradas.

- **P1 — `RQ-04` não tem sujeito no kit.** Não existe `routes/api.php`, nenhum controller devolve
  `response()->json(...)` e não há `app/Http/Resources/`. Confirmado por varredura (Explore, 39
  ferramentas): categoria "resposta de API exposta" = **0 achados**.
  - **Resposta do solicitante**: *regra + enforço, sem criar API*. O kit não ganha rota de API
    nesta entrega; a cláusula vale por **teste de arquitetura**, que fica vermelho no dia em que
    alguém criar a primeira rota de API, controller JSON ou `Http\Resources` sem Data.
  - **Se negado**: `RQ-04` viraria "criar uma API de referência no kit" — superfície nova,
    autenticação de API, versionamento, docs e CTs próprios.

- **P2 — Qual a régua de "necessário" em `RQ-05`/`RQ-06`?** Sem critério, "necessário" vai de 3 a
  20 Data, e alguns nasceriam de uso único.
  - **Resposta do solicitante**: **fronteira pública + shape fixo**. Vira Data o array de shape
    fixo que atravessa fronteira de classe pública (retorno ou parâmetro de método público,
    payload de job/evento, resposta de API externa consumida). **Não** viram Data: array local de
    um método, `config/*.php`, opções de comando artisan, array de opções de `Select` do Filament.
  - **Se negado**: a lista de alvos do passo 5 do PRD muda, e os CTs de cada Data junto.

- **P3 — Quais documentações?**
  - **Resposta do solicitante**: as quatro superfícies — `docs/pt` **e** `docs/en` (página nova em
    `recursos/`), `CLAUDE.md`/`AGENTS.md`, `README.md`/`README.en.md` e `CHANGELOG.md`.

- **P4 — "Consumo de API" inclui SDK de terceiro?** O kit consome provedores sociais pelo
  Socialite, o `siteverify` do captcha pelo `ddr/filament-captcha` e modelos de IA pelo
  `laravel/ai` — os três já devolvem objeto ou array próprio do pacote.
  - **Premissa assumida**: sim, inclui. O Data entra na **fronteira do kit com o pacote** — logo
    após a chamada que traz o dado de fora do processo —, não dentro do vendor. Quando o pacote já
    devolve objeto tipado e estável (`Laravel\Ai` com resposta estruturada tipada), o Data existe
    para fixar o shape que o **kit** consome, não para reembrulhar o do vendor.
  - **Se negado**: os Data de consumo caem de 3 para 1 (só o que chega como array cru).

- **P5 — Senha dentro de um Data.** O shape `{name, email, password}` aparece em quatro pontos e
  hoje trafega como `array` marcado com `#[SensitiveParameter]` (`RegistroAberto::registrar()`).
  Um Data tem `toArray()`/`toJson()` públicos e é serializável — o risco de a senha vazar em log,
  em `dd()` ou numa fila é **maior** que o do array atual.
  - **Premissa assumida**: o Data de registro existe, mas a senha **não** é propriedade comum —
    ver ADR-04. Se a mitigação não couber, `RQ-06` entrega os outros Data e este fica declarado
    como fora desta entrega, nunca implementado sem a proteção.
  - **Se negado**: o shape de registro continua array e a duplicação em 4 pontos permanece.

- **P6 — o `bruto` do perfil social preserva todas as chaves do provedor?** Levantada pela
  derivação dos casos de teste.
  - **Premissa assumida** (falha fechado): preserva o array de perfil **menos** credenciais
    (`token`, `refresh_token`, `access_token`, `secret`). Assumir o contrário criaria estrutura
    serializável com credencial dentro — o que ADR-04 proíbe.
  - **Invariante afirmado junto**, válido qualquer que seja a resposta: nenhum token do provedor
    aparece em `toArray()` do Data (CT-16).
  - **Se negado**: CT-16 passa de "não contém token" para "contém apenas as chaves listadas", e a
    rule ganha a lista explícita.

## Fora de Escopo (declarado)

- **Criar API no kit** (rota, versionamento, autenticação de API, endpoint de exemplo) — decisão
  de P1.
- **Transformar em Data todo `array{...}` do código.** A régua de P2 exclui explicitamente array
  local, config, opções de comando e array ditado pela API de um pacote (`ColorManager` do
  Filament, `Importer`/`Exporter` do Filament).
- **Geração de tipos TypeScript** (`spatie/typescript-transformer`), que o pacote suporta e o kit
  não usa.
- **Substituir os enums existentes** (`ProvedorSocial`, `ProvedorAntiRobo`) por Data — eles já são
  o value object certo para o problema deles.
