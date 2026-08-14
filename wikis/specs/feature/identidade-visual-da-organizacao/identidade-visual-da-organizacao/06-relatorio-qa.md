# Relatório de QA — Identidade visual da organização

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **focado** — dimensões A (cobertura), B (validação visual), C (fronteiras da
> cor) e D (segurança da superfície nova). As demais ficaram fora, declaradas em "Não Verificado".
> Natureza da wiki: **evolução** · Regressão: **sim** (`tests/Tenancy/*`, `BloqueioDeSessaoTest`)

## Limitação estrutural, declarada primeiro

O mesmo agente capturou o requisito, escreveu o PRD e implementou. Mitigação: as dimensões
dinâmicas foram delegadas a um **avaliador independente**, instruído a ser cético e proibido de
alterar código. Os achados dele foram **reverificados por reprodução própria** antes de entrar
aqui — três confirmados lendo o vendor, um rebaixado, um aceito como decisão.

## Veredito — Ciclo 1

**REPROVADO → implementação**, e **corrigido na mesma rodada**.

- **Blocker: 0 · Major: 4 · Minor: 3 · Cosmético: 1**
- Suítes após as correções: `--group=kit` **verde**, `--testsuite=Browser` verde, PHPStan 0 erros

Ao contrário da wiki anterior — onde o que reprovou foi documentação errada — aqui **três dos
quatro Major eram código**, e dois deles em validação de entrada numa superfície que esta entrega
criou. Isso não vira dívida: validação em fronteira de confiança não é cortável.

## Achados

### QA-01 — `FileUpload` da logo aceitava SVG · **Major** · dimensão D · **CORRIGIDO**

- **Esperado**: nenhuma superfície nova de execução de script.
- **Observado**: `FileUpload::image()` gera `acceptedFileTypes(['image/*'])`
  (`vendor/filament/forms/src/Components/FileUpload.php:130-134`), que vira a regra
  `mimetypes:image/*` — e `image/svg+xml` **casa** com ela. (A regra `image` do Laravel, que é
  outra coisa, recusa SVG.) Com `->disk('public')->visibility('public')`, o arquivo é servido pelo
  **mesmo origin da aplicação**: abrir a URL direto executa `<script>` com acesso ao cookie de
  sessão.
- **Repro (verificada por mim)**: `grep -A5 "function image()" FileUpload.php` mostra o
  `acceptedFileTypes(['image/*'])` literal.
- **Atenuante**: exige quem já tem `Create:Tenant`/`Update:Tenant` — escalada de insider, não porta
  anônima.
- **Destino**: 2 implementação. **Corrigido**: `->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])`
  no lugar de `->image()`, com o motivo no comentário.

### QA-02 — `cor_primaria` entrava sem validação nenhuma · **Major** · dimensão C · **CORRIGIDO**

- **Esperado**: coluna `string(7)` para `#RRGGBB`; o helperText promete que "o Filament deriva as
  11 tonalidades desta cor".
- **Observado**: `ColorPicker::hex()` **não valida** — só troca o formato do picker
  (`vendor/filament/forms/src/Components/ColorPicker.php:31-36`). Persistiram sem um erro de form:
  `roxo`, `#ZZZZZZ`, `rgb(124,58,237)` (15 caracteres) e `"><script>alert(1)</script>` (27) — todos
  acima dos 7 da coluna. Em sqlite passa; em MySQL/Postgres é erro no save.
- **A degradação é o que torna isto Major**: `Color::generatePalette('roxo')` **não estoura**. O
  `sscanf` falha, tudo vira 0, o chroma cai abaixo de 0.03 e a paleta inteira sai **acromática** —
  o painel do cliente fica **cinza, sem erro em lugar nenhum**.
- **Repro (verificada por mim)**: `hex()` tem corpo de duas linhas, `format('hex')` e `return`.
- **Destino**: 2 implementação. **Corrigido**: `->regex('/^#[0-9A-Fa-f]{6}$/')` + mensagem própria.
  O regex é âncorado, então cobre também o comprimento — `ColorPicker` não tem `maxLength()`.
- **Teste novo**: CT-08, dataset com os quatro valores acima. Antes da correção, **os quatro
  passavam**.

### QA-03 — A logo não renderizava: URL presa ao `APP_URL` · **Major** · dimensão B · **CORRIGIDO**

- **Esperado**: RQ-06 — a logo do cliente aparece na lock-screen.
- **Observado**: o `<img>` chegava com `src="http://localhost:8000/storage/..."` e
  `naturalWidth: 0` — imagem quebrada, só o `alt` visível. A mídia **base**, no mesmo layout,
  carregava (`naturalWidth: 120`).
- **Causa**: `Storage::disk('public')->url()` segue `config('filesystems.disks.public.url')`, que é
  a string congelada `env('APP_URL').'/storage'` (`config/filesystems.php:44`). O `asset()` segue o
  host do **request**. Os dois divergem sempre que o host efetivo não é o `APP_URL` — domínio
  próprio de organização, staging, `config:cache` com `APP_URL` velho, e a porta aleatória do
  servidor de teste.
- **Destino**: 2 implementação. **Corrigido**: `asset('storage/'.$this->logo)`, que é o mesmo
  `asset()` que os painéis já usam para a mídia base.

### QA-04 — O CT-B da logo passava verde com a logo quebrada · **Major** · dimensão B · **CORRIGIDO**

- **Esperado**: o `05` justifica usar disk **real** dizendo que "o navegador faz request HTTP à URL
  da logo" — a premissa é que ele de fato busca e exibe.
- **Observado**: a única asserção era `assertAttributeContains('.fi-auth-media', 'src', $caminho)`.
  O atributo contém o path **mesmo com o host errado**, então o caso ficava verde enquanto a tela
  mostrava imagem quebrada. `assertNoJavaScriptErrors()` não vê 404 de imagem.
- **É o achado mais instrutivo da rodada**: um teste que existe justamente para provar que a logo
  aparece, e que passava com ela quebrada.
- **Destino**: 3 teste. **Corrigido** junto com QA-03.

### QA-05 — Sem degradação quando o arquivo da logo sumiu · **Minor** · dimensão B · **CORRIGIDO**

- **Esperado**: o `00-requisito` e o docblock do `TelaBloqueio` prometem **degradar para a mídia
  base** quando não houver logo confiável.
- **Observado**: a degradação só cobria logo **nula**. Path órfão — arquivo apagado, restore de
  banco sem storage, `migrate:fresh` com uploads antigos — renderizava `<img>` quebrado.
- **Destino**: 2 implementação. **Corrigido**: `Storage::disk('public')->exists()` no
  `Tenant::urlDaLogo()`, e o CT-02 ganhou a terceira persona (path órfão).

### QA-06 — RQ-07 entregou metade do que o `00` assumiu · **Minor** · dimensão A · **ACEITO COM RETRATAÇÃO**

- **Esperado**: o `00-requisito` assumiu *"uma Section no form **e uma coluna que aceita chaves
  novas sem migration**"*.
- **Observado**: entregue a Section e o docblock. A outra metade foi **rejeitada em ADR-01 e
  ADR-05** (coluna JSON descartada) **sem reconciliar com a assunção do `00`**. Na prática,
  acrescentar campo hoje custa o mesmo que custaria sem a feature.
- **Destino**: 1 especificação. **Retratado**: a seção "Assumido" de RQ-07 no `00-requisito.md`
  passa a dizer o que de fato se entrega. O texto original permanece intocado — só a decomposição,
  que é derivada, foi corrigida.

### QA-07 — `ViewAction` e a tela `view` nunca exercitados em navegador · **Minor** · dimensão A

- **Observado**: o `ViewAction` é justamente o que o comentário do `TenantsTable` argumenta que
  "navega em vez de abrir modal", e nenhum CT clica nele. O CT-03 faz `GET` direto na rota, o que
  não distingue modal de tela cheia.
- **Destino**: 3 teste. **Registrado como débito** — o CT-B01 já prova a mecânica para o
  `EditAction`, e `Page.php:382-389` é simétrico. Custo/benefício não justifica outro cenário de
  navegador nesta rodada.

### QA-08 — Logo legível sem autenticação · **Cosmético** · dimensão D · **NÃO-DEFEITO**

- Disk público + `visibility('public')`: `GET /storage/organizacoes/logos/{hash}.png` responde a
  qualquer um. Nome aleatório (`hashName`), então não é enumerável.
- **Avaliação**: aceitável — logo é a marca **pública** do cliente, e é o mesmo tratamento do avatar
  do Breezy. **Vira defeito no dia em que a mesma Section receber documento ou contrato**, e é por
  isso que está escrito aqui.
- **Destino**: 5 não-defeito.

## O que o gate confirmou que estava certo

A pergunta central da feature — **a cor chega à tela?** — foi respondida **olhando**, com
screenshot dos dois painéis:

| Painel | `--primary-500` | O que se vê |
|---|---|---|
| `/app/acme` (`#7c3aed`) | `oklch(0.6827 0.1701 293.009)` | roxo real: item ativo da sidebar, ícones, topbar |
| `/app/globex` (`#059669`) | `oklch(0.6827 0.1701 163.225)` | verde real, mesmos elementos |
| `/admin` | `oklch(0.769 0.188 70.08)` | âmbar default — **não vazou** |

Não é só a CSS var mudando: dezenas de elementos com `color`/`border` computados no hue da
organização. **RQ-05 e a guarda de painel estão visualmente comprovados** — que é exatamente o que
ADR-02 previu e o que nenhum teste HTTP poderia mostrar.

E `#ffffff` **não** deixa a tela ilegível: `generatePalette()` fixa a luminosidade nas 11
constantes e só herda o hue, então branco vira rampa cinza. A afirmação da wiki sobre contraste se
sustenta.

## Matriz de Rastreabilidade — só as linhas que tinham lacuna

| RQ | Lacuna encontrada | Situação |
|----|-------------------|----------|
| RQ-02 | upload sem restrição de tipo (QA-01) | **corrigido** |
| RQ-03 | formato da cor sem validação (QA-02) | **corrigido** + CT-08 novo |
| RQ-06 | logo não renderizava (QA-03); sem fallback para arquivo ausente (QA-05); CT verde com a tela quebrada (QA-04) | **corrigidos** |
| RQ-07 | mecanismo prometido no `00` foi rejeitado sem retratação (QA-06) | **retratado no `00`** |
| RQ-08/09 | `ViewAction` sem cenário de navegador (QA-07) | **débito aceito** |

As demais (RQ-01, RQ-04, RQ-05) fecham sem lacuna, e RQ-05 com evidência visual.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | RQ-07 era racionalização (QA-06); RQ-08/09 com débito de teste |
| B | Validação visual | ⚠️→✅ | cor **confirmada olhando** nos dois tenants e no isolamento do `/admin`. Logo: 3 achados, todos corrigidos |
| C | Fronteiras da cor | ❌→✅ | entrada sem validação, degradando em silêncio para cinza. Corrigido |
| D | Segurança da superfície nova | ⚠️→✅ | SVG era defeito real e foi corrigido; logo pública é decisão registrada |
| E–J | demais dimensões | ⏭️ | fora do perfil focado — ver "Não Verificado" |

## Débitos Aceitos

- **QA-07** (Minor): `ViewAction` sem cenário de navegador. O CT-B01 prova a mecânica para o
  `EditAction`, e `Page.php:382-389` é simétrico.
- **QA-08** (Cosmético): logo pública sem autenticação. Decisão, com o gatilho de revisão nomeado.

## Suspeitas Não Confirmadas

- **31 elementos em âmbar default no `/app/globex`**, com `--primary-600` verde. O avaliador não
  fechou o rastro e inclina-se a artefato do arnês (servidor in-process, render hook do Spotlight
  acumulando por visita — o mesmo padrão do DT-08 da wiki anterior). **Se for produto, é um pedaço
  do `/app` que não segue a cor do cliente** — vale um olhar na próxima rodada.
- **`ColorManager` sob Octane**: o binding é `scoped`, que o Octane descarta, mas
  `Facade::$resolvedInstance` guarda outra referência. Octane não está instalado; não deu para
  verificar.

## Não Verificado

- **Dimensões E (performance), F (UX de erro), H (acessibilidade), I (regressão adjacente por
  RCRCRC)** — fora do perfil focado desta execução, por decisão de custo. A regressão foi coberta
  pelo caminho barato: `tests/Tenancy/*` e `BloqueioDeSessaoTest` verdes.
- **Cor do tenant em dark mode**, e contraste do texto sobre a cor escolhida.
- **`string(7)` em MySQL/Postgres** — só sqlite no ambiente; o estouro está inferido do schema.
- **Upload de SVG ponta a ponta pelo Livewire** — provado no nível da regra de validação que o
  Filament gera, que é onde a decisão acontece.

## Ciclo 2 — necessário?

**Não.** Os quatro Major foram corrigidos e cobertos por teste na mesma rodada; os dois Minor
restantes viraram débito com justificativa; o não-defeito ficou registrado. Um segundo ciclo
reencontraria os mesmos.
