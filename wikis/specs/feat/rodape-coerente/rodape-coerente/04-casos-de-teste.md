# Casos de Teste — Rodapé coerente entre os painéis e a tela de login

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação
> desta feature. Do `01` vieram apenas rotas, nomes de campo e os fatos verificados no vendor
> (`## Verificações do Blueprint`, V1–V7) — usados como **contexto de mecanismo**, nunca como
> oráculo. O que foi recusado está em [`## Fronteira com o Plano`](#fronteira-com-o-plano).

## Perfil de Derivação

| Área | P | I | P×I | Perfil | Por quê |
|---|---|---|---|---|---|
| **A** — visibilidade da versão (a guarda) | 3 | 3 | **9** | **completo** | um controle de segurança **já existente** muda de forma: deixa de envolver o bloco e passa a envolver só a versão. Impacto 3 pela decisão registrada no `00` ("versão exata = mapa de CVEs aplicáveis a quem ainda não autenticou") |
| **B** — composição da linha (`©`, nome, versão, toggle do kit) | 3 | 2 | 6 | padrão | quatro condições independentes governando uma string; retrabalho manual se sair errada |
| **C** — escopo do render hook e ordem na tela de login | 3 | 2 | 6 | padrão | herança de classe de página (V6/V7) + dois hooks no mesmo ponto (V4); errar o escopo para o outro lado põe texto do admin em **toda** tela do sistema |
| **D** — prefixo `v` no formulário | 2 | 2 | 4 | padrão | recurso nativo isolado, mas o valor gravado alimenta o rodapé — prefixo que vaze para o dado é retrabalho em dado existente |
| **E** — superfície pública do nome (escape) | 2 | 3 | 6 | padrão | campo editável por admin passa a renderizar em tela **pública e anônima** |

- Técnicas aplicadas: **EP**, **tabela de decisão** (4 condições), **rastreio de efeito de render**
  (presente / ausente / uma vez só), **matriz superfície × audiência**, **normalização/escape**.
- **Revisão adversarial: obrigatória e disparada** — perfil completo na área A **e** Impacto 3 em A e E.
  Resultado em [`## Revisão Adversarial`](#revisão-adversarial).
- Cenários: **21** (CT-01…CT-22, com CT-06 fundido em CT-04) · Regras: **7** · Mutantes previstos: **57** · Sem matador: **4** (todos declarados: M33, M35, M38, M51).

> **Para quem for implementar**: o `--filter` do Pest casa a **descrição do `it()`**, não o
> `[CT-nn]`. Depois de rodar, confira o campo `tests:` da saída — `--filter=CT-04` pode selecionar
> zero casos e o run sair verde por não ter rodado nada.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | ponto único de composição do rodapé; a blade do `FOOTER`; a blade/registro do recado de login; o `TextInput` "Versão do sistema"; as propriedades `nome_da_aplicacao`, `versao_do_sistema`, `login_rodape`, `exibir_versao_do_kit` e as chaves `app.name`, `app.version`, `kit.login.rodape`, `kit.exibir_versao`, `kit.version` | CT-01…CT-20 |
| **F** | compor a linha; decidir a visibilidade por autenticação; escopar o hook por classe de página; exibir o afixo; escapar o nome | CT-01…CT-20 |
| **D** | **nome** (`string` obrigatória no formulário → vazio só alcançável por `.env`/banco); **versão do sistema** (`?string`: nula, vazia, limpa, com `v` já gravado, com marcação HTML); **recado** (`?string`, Markdown); **toggle do kit** (bool); **versão do kit** (sempre presente) | CT-15, CT-16, CT-17, CT-18, CT-19 |
| **I** | `GET` de qualquer tela dos três painéis; `GET` das quatro telas de login (`/admin/login`, `/app/login`, `/infra/login`, `/login`) e das demais telas do layout `simple`; componente Livewire `ConfiguracoesDoKit` | CT-01, CT-04, CT-11, CT-13, CT-14 |
| **P** | **o kit não usa `viteTheme()`** — utilitária Tailwind emitida por blade do kit **não existe** na folha compilada do Filament (`.ai/rules/css-filament.md`): faixa sem estilo com todo teste verde. SQLite `:memory:`. `phpunit.xml` **força** `APP_VERSION=""`, `KIT_LOGIN_RODAPE=""`, `KIT_EXIBIR_VERSAO=false` e **não força `APP_NAME`** | M35 (lacuna declarada) · ver `## Setup Global` |
| **O** | visitante anônimo numa tela pública e indexável; usuário autenticado nos três painéis; admin que edita a identidade; **instalação com dado sujo** (`v1.2.3` já gravado) | CT-04, CT-05, CT-15 |
| **T** | **não se aplica**: nada na entrega depende de instante, fuso, agendamento, expiração ou ordem temporal. A única escrita é a da tela de configurações, cuja concorrência não muda de regra com esta entrega e já tem cobertura própria em `tests/Kit/ConfiguracoesDoKitTelaTest.php` | — |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a assinatura `© {ano corrente} {Nome}` aparece no rodapé para **todo mundo**, autenticado ou não, com o ano calculado no render | B (padrão) | RQ-01, RQ-02 + decisões de 2026-09-22 e **Q1 de 2026-09-23** | matriz superfície × audiência + **valor limite temporal** | CT-01, CT-02, CT-03, CT-22 |
| **R2** — **nenhuma** versão aparece para quem não autenticou — nem a do sistema, nem a do kit | A (**completo**) | RQ-06 + decisão de 2026-09-22 ("sim, e a versão não") | EP sobre a audiência + rastreio de render | CT-04, CT-05, CT-06 |
| **R3** — a linha automática é composta num **só ponto** e sai igual nas duas superfícies | B (padrão) | RQ-05, RQ-06, RQ-07 | comparação cruzada de superfícies | CT-07, CT-08, CT-09 |
| **R4** — na tela de login o recado do admin é linha **adicional, abaixo** da assinatura, e **só** nas telas de login | C (padrão) | RQ-06 + decisão de 2026-09-22 | tabela de decisão classe × escopo + ordem no documento | CT-10, CT-11, CT-12 |
| **R5** — o prefixo `v` é **de exibição**: o formulário o mostra, o valor gravado não o carrega, o rodapé continua compondo o `v` | D (padrão) | RQ-03, RQ-04 | criação × edição × uso | CT-13, CT-14, CT-15 |
| **R6** — valor vazio não deixa sobra: sem nome não há `©` órfão, sem versão não há separador solto, sem nada não há faixa vazia | B (padrão) | RQ-01, RQ-02 + taxonomia *nulo/vazio/ausente* | tabela de decisão (4 condições) | CT-16, CT-17, CT-18 |
| **R7** — o nome da aplicação sai **escapado** na superfície pública | E (padrão) | RQ-01, RQ-02 (o campo passa a renderizar em tela anônima) + taxonomia | normalização/escape | CT-19, CT-20 |

**Técnica escalada acima do perfil da área**: R6 usa tabela de decisão completa (produto cartesiano
fechado de nome × versão × toggle) numa área `padrão`, porque EP sobre um campo de cada vez não
distingue `implode` de partes não vazias de concatenação cega — o defeito mora na **combinação**,
não no campo.

**Cobertura das `RQ`:**

| `RQ` | Onde foi parar |
|---|---|
| RQ-01, RQ-02 | R1, R6, R7 |
| RQ-03, RQ-04 | R5 |
| RQ-05 | R3 (o ponto único só é observável como *"a mesma linha sai nas duas superfícies"*) |
| RQ-06 | R2, R3, R4 |
| RQ-07 | **sem cenário, e isto é declarado** — *"a solução para RQ-06 é avaliada, não presumida"* é cláusula de **processo**: ela é cumprida pela seção `## Ambiguidades` do `00`, que registra as três opções e por que duas caíram. A primeira redação deste arquivo a mapeava para R2/R3/R4 e a dava por coberta em silêncio, que é o falso ✅ mais barato de produzir. Quem a verifica é o quality gate |
| RQ-08 | **sem cenário próprio** — restrição de *como* usar o Filament; o único pedaço observável é o afixo nativo, e ele é CT-13 (`getPrefixLabel()`) |
| RQ-09 | **sem cenário** — restrição de processo de release, não comportamento. Vai para o quality gate. E asserção sobre a seção do topo do `CHANGELOG.md` **expira sozinha** (`.ai/rules/testes.md`), então nem como caso indireto vale a pena |

---

## Fronteira com o Plano

| Item do `01`/`02` | Recusado como oráculo porque | Destino |
|---|---|---|
| V1–V7 (`## Verificações do Blueprint`) | são fatos do **vendor instalado**, não comportamento pedido | contexto de mecanismo — moldam *qual* cenário escrever (CT-10, CT-11), nunca o `Então` |
| "Recado do rodapé, na tela de login — `FOOTER` **escopado**" | escolha de implementação (qual ponto de extensão) | detalhe do cenário. O `Então` é *"aparece na tela de login e não na tela autenticada"*, que é o `00` |
| ADR-03 — a blade é renomeada | escolha de implementação | detalhe — mas **acopla o arnês existente**: `segmentoDaVersao()` casa `<div class="kit-versao">` por regex. Renomear o elemento deixa CT-46/CT-47 de `VersaoNoRodapeTest.php` **verdes sobre nada**. Ver `## Regressão em suíte existente` |
| ~~ADR-04 — o `©` vai sem ano~~ | era achado: comportamento visível que só o plano determinava. Virou pergunta **Q1**, e o usuário **reverteu a direção em 2026-09-23**: o `©` leva o **ano corrente**, calculado no render | **fechado** — o ADR-04 foi reescrito e registra a reversão. Deixa de ser fronteira e passa a ser oráculo, vindo da decisão do solicitante e não do plano |
| ADR-05 — "a assinatura sai escapada" | o `00` não pede escape; quem o pede é o checklist de taxonomia | R7 foi derivada da **taxonomia**, não do ADR. O ADR concorda, e isso é coincidência, não fonte |
| Nomes de rota (`/admin/login`, `/login`, …) | o `00` fala em "tela de login"; os paths são do plano | superfície — entra no `Dado`, não no oráculo |
| Separador ` · ` | **não recusado**: está no próprio `00` (`## O que existe hoje`, "unidos por ` · `") | é oráculo legítimo em CT-16 |

### Decisões do solicitante (2026-09-23) — as cinco perguntas foram fechadas

> As perguntas que esta derivação devolveu foram respondidas. **Nenhuma continua bloqueando**, e
> **nenhum cenário permanece marcado `@premissa`** — o que era suposição virou oráculo. Registrado
> aqui porque a rastreabilidade de *por que este cenário afirma isto* é o que impede a próxima
> pessoa de "corrigir" um oráculo que está certo.

| # | Pergunta | Decisão | O que mudou no conjunto |
|---|---|---|---|
| **Q1** | o `©` leva ano? | **SIM — ano corrente, `now()->year`, calculado no render.** Sem campo novo, sem persistência | **reversão da premissa**. Todos os literais de assinatura passaram de `© Acme` para `© 2026 Acme`; nasceu a regra do tempo no `## Setup Global`, o cenário **CT-22** e os mutantes **M53–M57** |
| **Q2** | em quais telas de autenticação o recado aparece? | **só nas telas de login** | confirma a direção por falha fechado que o conjunto já adotara: **nenhum cenário muda**. **M34 deixa de ser lacuna** e vira mutante com matador |
| **Q3** | quem já digitou `© Nome` no textarea? | **convivem** — é visível, e o admin remove a dele | confirma a premissa: CT-02 já monta esse mundo desde a rodada 2, e continua valendo |
| **Q4** | nome vazio: omitir o `©` ou emitir `©` sozinho? | **omite**, pelo mesmo `filled()` das outras partes | confirma a direção por falha fechado: CT-16 e CT-18 mantêm as linhas de nome vazio como estão |
| **Q5** | há superfície Livewire nova? | **não** — o diff não cria nem toca método público de componente | fecha a dispensa do checklist de taxonomia, que estava "a confirmar" |

**Q1 é a única que mudou comportamento**, e a mudança tem custo de teste próprio: o ano no render
transforma toda asserção de assinatura numa asserção **dependente do relógio**. A regra nº 3 do
`## Setup Global` existe por isso, e CT-22 é o cenário que separa *ano dinâmico* de *ano cravado*.

---

## Setup Global

### Suíte e camada

- Tudo em **`tests/Kit/RodapeCoerenteTest.php`**, suíte `Kit`, grupo `kit`
  (`pest()->extend(TestCase::class)->use(RefreshDatabase::class)->group('kit')->in('Kit')`).
- **IDs `CT-nn` são por wiki.** `tests/Kit/VersaoNoRodapeTest.php` já usa `CT-01…CT-47` de
  **outra** wiki. Não reaproveite aquele arquivo para os casos desta: o teste de arquitetura que
  casa `[CT-nn]` ↔ pasta da wiki passa a apontar para o lugar errado.
- `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`, como no
  arquivo vizinho.

### Personas

- `usuarioDoKit('admin')` — autenticado, com a permissão de configuração.
- **visitante** — nenhuma chamada a `actingAs()`. É persona, não ausência de arranjo.

### Fixtures e helpers

Quatro helpers já existem em `tests/Kit/VersaoNoRodapeTest.php` e passam a ser usados por **dois**
arquivos. Por `.ai/rules/testes.md`, **mover para `tests/Pest.php`** — helper cruzado declarado num
arquivo de teste some quando o Pest carrega um subconjunto (`--parallel`, `--tia`, um arquivo só),
e o sintoma é `Call to undefined function`, que não aponta a causa:

| Helper | O que faz | Onde está hoje |
|---|---|---|
| `comVersoes(?string $sistema, bool $exibirKit)` | fixa `app.version` e `kit.exibir_versao` | `VersaoNoRodapeTest.php:40` → **mover** |
| `rodapeDe(string $html)` | tudo depois do último `</main>` — ~~vale nos dois layouts (V5)~~ **NÃO vale no layout do Auth Designer, que não tem `<main>`**; a implementação precisou de um segundo âncora (`rodapeDoLayoutDeAutenticacao()`). V5 estava errada — QA-02 | `:61` → **mover** |
| `segmentoDaVersao(string $html, string $versao)` | o segmento entre ` · ` em que a versão aparece | `:90` → **mover** |
| `temRotulo(string $segmento)` | o segmento tem palavra de 2+ letras | `:116` → **mover** |
| `ligarLoginUnificado(bool)` | `kit.login.unificado` | já em `tests/Pest.php:507` |
| `gravarConfiguracao(string, mixed)` / `configuracaoGravada(string)` | escrita/leitura direta da settings, sem `save()` | já em `tests/Pest.php` |

**Dois helpers novos**, também em `tests/Pest.php`:

```
emJunhoDe2026()
    $this->travelTo('2026-06-15 12:00:00')   // instante neutro: longe das duas bordas do ano
                                              // (o teardown do Laravel desfaz; não chame travelBack)

comIdentidade(?string $nome, ?string $versao, bool $exibirKit, ?string $recado = null)
    config()->set('app.name', $nome)
    config()->set('app.version', $versao)
    config()->set('kit.exibir_versao', $exibirKit)
    config()->set('kit.login.rodape', $recado)

assinaturaDoRodape(string $html): string
    o conteúdo TEXTUAL e normalizado do elemento de composição do rodapé:
    strip_tags → html_entity_decode → colapsar espaços → trim
```

**Por que `html_entity_decode` não é detalhe**: `{{ }}` do Blade não converte `©` (o `e()` mexe em
`& < > " '`), mas uma implementação que escreva `&copy;` literal no HTML produz o **mesmo pixel** e
uma string diferente. Sem a normalização, um oráculo escrito com `©` fica vermelho contra uma
implementação correta — e alguém "conserta" o teste trocando por `assertSee` frouxo.

**Acoplamento declarado de `assinaturaDoRodape()`**: ele precisa recortar o **elemento** que a
feature emite, não a cauda da página. `rodapeDe()` devolve menu do usuário, scripts e texto de
sobra; asserção de composição exata sobre aquilo é impossível, e asserção de presença ali é frouxa
demais (o nome da aplicação aparece no `<title>` e na barra do topo — `assertSee('Acme')` **não é
oráculo desta feature em lugar nenhum**). O recorte por elemento é o mesmo acoplamento que
`segmentoDaVersao()` já assume e documenta.

### Configuração — o `Dado` afirma o valor efetivo, nunca o ambiente

- `phpunit.xml` **força** `APP_VERSION=""`, `KIT_LOGIN_RODAPE=""` e `KIT_EXIBIR_VERSAO=false`.
  Toda asserção de ausência de versão ou de recado seria **vácuo** sem `Dado` que os grave.
- `phpunit.xml` **não força `APP_NAME`**: `config('app.name')` vem do `.env` da máquina.
  Nenhum cenário pode depender do nome de fábrica — **todo** `Dado` fixa o nome explicitamente,
  inclusive quando o testa vazio.
- As chaves são lidas **por request** dentro da blade, então `config()->set()` no corpo do caso é
  o arranjo fiel. Com `RefreshDatabase` o `KitServiceProvider::boot()` roda antes das migrations e
  o alinhamento da settings é no-op — quem quer exercitar o caminho *banco → config* chama
  `alinharConfiguracoesDoKit()` (é o que CT-03 faz).

### Duas regras que valem em TODO cenário deste arquivo

Vieram da revisão adversarial, que provou que sem elas dez cenários mediam outra coisa.

1. **`Então o rodapé contém X` significa `assinaturaDoRodape()`, nunca `rodapeDe()`.**
   `rodapeDe()` devolve a cauda inteira da página — menu do usuário, scripts e o **snapshot do
   Livewire**, onde `app.name` e o recado aparecem serializados. Presença medida ali é satisfeita
   por payload de JS, com a faixa do rodapé inexistente. `rodapeDe()` só serve de entrada para
   `segmentoDaVersao()` e para as asserções de **ausência**, onde um recorte largo é mais forte.
2. **Todo `Dado` fixa as QUATRO chaves da composição**, e não só a que o cenário discute:
   `app.name`, `app.version`, `kit.exibir_versao` e `kit.login.rodape`. É o que `comIdentidade()`
   existe para tornar barato. Um cenário que fala de nome e cala sobre o toggle mede o que o
   `phpunit.xml` forçou — exatamente o que a seção *"o `Dado` afirma o valor efetivo, nunca o
   ambiente"* proíbe, e o Gherkin acima escreve só as chaves relevantes por legibilidade **com o
   resto fixado pelo helper**, nunca por omissão.
3. **Todo cenário que afirma a assinatura congela o tempo.** A composição passa a incluir o
   **ano corrente**, calculado no render (`now()->year`). Um `© 2026 Acme` escrito no oráculo sem
   congelar o relógio fica verde hoje e **vermelho em 1º de janeiro**: a suíte quebraria sozinha, de
   madrugada, sem commit — e o defeito pareceria ser da feature. Todo cenário que afirma a
   assinatura chama `$this->travelTo('2026-06-15 12:00:00')` no `Dado`; os literais deste arquivo
   são escritos contra esse instante. **Sem `travelBack()` explícito**: o Laravel o faz no teardown,
   e um `travel()` solto vaza para o vizinho e vira flake em `--parallel`.
4. **Todo cenário que abre uma rota de login fixa `kit.login.unificado` no `Dado`.**
   `ligarLoginUnificado(true)` para `/login`; `ligarLoginUnificado(false)` para `/admin/login`,
   `/app/login`, `/infra/login`. Com a chave no estado errado a rota **redireciona**, e a asserção
   mede o redirect com a feature inteira revertida. O conjunto declarava isso em nota de dois
   cenários e dependia do default do ambiente nos outros dez.

### Armadilhas de asserção nesta base

| Não escrever | Escrever | Por quê |
|---|---|---|
| `expect($html)->not->toContain($x, 'mensagem')` | `$this->assertStringNotContainsString($x, $html, 'mensagem')` | `toContain()` é **variádica**: o 2º argumento vira outra agulha. Na forma negativa a cláusula extra é sempre verdadeira e **o caso nunca falha** (`.ai/rules/testes.md`) |
| `assertSee('Acme')` | `assinaturaDoRodape($html)` | o nome está no `<title>` e na topbar; o oráculo desta feature é o rodapé |
| `assertDontSee(...)` sozinho | `assertDontSee(...)` **+ a assinatura presente na mesma resposta** | ausência sem controle positivo passa com página em branco, 404 e redirect |
| `assertOk()` como oráculo único | qualquer asserção sobre o conteúdo do rodapé | 200 com o conteúdo errado |

---

## Regra R1 — a assinatura `© {ano corrente} {Nome}` aparece para todo mundo

> `RQ-01`, `RQ-02` + decisões de 2026-09-22 e **Q1 de 2026-09-23** · área **B**, perfil **padrão** ·
> técnica: **matriz superfície × audiência** + **valor limite temporal (virada de ano)**

```gherkin
# language: pt
Funcionalidade: Rodapé coerente entre os painéis e a tela de login

  Regra: a assinatura © {ano corrente} {Nome} aparece no rodapé de toda tela, autenticado ou não

    Esquema do Cenário: [CT-01] a assinatura sai em toda superfície, para as duas audiências
      Dado o nome da aplicação gravado como "Acme"
      E a versão do sistema gravada como "2.4.0"
      Quando <audiencia> abre "<rota>"
      Então o rodapé da resposta contém a assinatura "© 2026 Acme"

      Exemplos:
        | audiencia                        | rota                           | # partição                                      |
        | a administradora (admin)         | /admin                         | autenticado, painel admin                       |
        | a usuária do painel (panel_user) | /app                           | autenticado, painel COM tenancy                 |
        | o operador de infra (infra)      | /infra                         | autenticado, terceiro painel                    |
        | o visitante                      | /admin/login                   | anônimo, login de painel                        |
        | o visitante                      | /app/login                     | anônimo, login do painel com tenancy            |
        | o visitante                      | /login                         | anônimo, página única de login (classe filha)   |
        | o visitante                      | /admin/password-reset/request  | **anônimo, layout simple que NÃO é login** — e aqui o rodapé **não** contém o recado (Q2) |

    Esquema do Cenário: [CT-02] a assinatura sai uma vez só, em toda superfície
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como "Fale com o suporte"
      Quando <audiencia> abre "<rota>"
      Então a assinatura "© 2026 Acme" aparece exatamente uma vez no documento

      Exemplos:
        | audiencia                | rota         | # partição                                          |
        | o visitante              | /admin/login | classe de login de painel — a classe mãe            |
        | o visitante              | /login       | classe filha — casa mãe E filha se escoparem as duas|
        | a administradora (admin) | /admin       | hook global — duplica se a assinatura for emitida duas vezes |
        | o visitante              | /admin/login | **recado do admin contendo `© Acme`** — o mundo de Q3 |

    Cenário: [CT-03] a assinatura reflete o nome gravado pela tela de configurações
      Dado a administradora na tela de configurações da aplicação
      E o nome da aplicação gravado como "Acme"
      Quando ela grava o nome da aplicação como "Marca Nova"
      Então a propriedade "nome_da_aplicacao" vale "Marca Nova"
      E o rodapé de "/admin/login" contém "© 2026 Marca Nova" e não contém "© 2026 Acme"
      E o rodapé de "/admin" contém "© 2026 Marca Nova" e não contém "© 2026 Acme"
```

**Notas de implementação**
- **CT-22 é o cenário que separa *ano dinâmico* de *ano cravado*, e ele não é opcional.** Sem ele,
  `'© 2026 '` escrito literalmente na blade passa em **todos** os outros dezenove cenários, porque
  todos congelam o relógio em 2026 — a regra do tempo que protege a suíte de quebrar em 1º de
  janeiro é, ela mesma, o que cega o conjunto para o ano fixo. As linhas de **2027** são o
  antídoto: só elas mudam de observável entre as duas implementações.
- **CT-22 escolhe os instantes onde as implementações divergem, não instantes bonitos.**
  `23:59:59` de 31/12 e `00:00:00` de 1º/01 são borda−1 e borda com o incremento do tipo certo
  (1 segundo). A quarta linha é a que o checklist de taxonomia cobra: com o app em
  `America/Sao_Paulo`, o instante `2027-01-01 02:30 UTC` ainda é **31/12/2026** lá — é a janela de
  três horas em que *ler o ano em UTC* e *ler no fuso da aplicação* dão respostas diferentes.
  Escolher `12:00` de um dia qualquer não distinguiria nada. O arnês suporta: o projeto já usa
  `$this->travelTo()` (`tests/Kit/ConviteTest.php:940`, `SituacaoDaContaTest.php:230`) e
  `config(['app.timezone' => ...])` é injeção de config comum — não é lacuna de arnês, foi tentado.
- **O ano não entra em CT-19/CT-20.** Aquelas duas asserem sobre HTML **bruto** e caçam a forma
  escapada do nome; acrescentar o ano ali só tornaria o literal mais frágil sem matar mutante novo.
  Elas continuam afirmando a presença da forma escapada, que é o que R7 mede.
- CT-01: as personas dos três painéis **não são intercambiáveis** — `usuarioDoKit('admin')` para
  `/admin`, `'panel_user'` para `/app`, `'infra'` para `/infra` (é a matriz que
  `VersaoNoRodapeTest.php` CT-03 já fixa). E **o `/app` não pode faltar**: é o painel com tenancy,
  o mais provável de resolver o hook de forma diferente; omiti-lo deixaria
  `scopes: ['admin', 'infra']` passar verde. O caso vizinho registra esse achado como erro cometido
  na primeira redação dele.
- CT-01: a linha `/login` exige `ligarLoginUnificado(true)`; as demais, `ligarLoginUnificado(false)`
  — com a chave no estado errado a rota **redireciona** e a asserção mediria o redirect, não a
  assinatura (é a mesma armadilha que o `VersaoNoRodapeTest.php` documenta em CT-05).
- CT-02: contar ocorrências (`substr_count`) no **documento inteiro**, não no recorte. Duas
  emissões podem cair em pontos diferentes da página, e um recorte esconderia a segunda. É a única
  asserção do arquivo que não usa `assinaturaDoRodape()` por escolha, e não por esquecimento —
  contar num recorte seria contar menos. **Atenção ao snapshot do Livewire**: se `app.name`
  aparecer serializado nele, o piso da contagem sobe; nesse caso conte dentro do `<body>` menos os
  `wire:snapshot`, e escreva o motivo no caso.
- CT-04, linha `/admin/register`: exige o registro aberto (`kit.registro.habilitado`), que o
  `phpunit.xml` força `false`. Se abrir o registro no caso for caro ou mexer em fronteira alheia,
  **corte esta linha e mantenha a de recuperação de senha** — ela sozinha já mata M37. Não a
  substitua por outra tela de login, que é o que o mutante já atravessa.
- CT-03 é o **gate de tela de escrita** de `nome_da_aplicacao`: `fillForm` → `->call('save')` →
  `configuracaoGravada('nome_da_aplicacao')` → `alinharConfiguracoesDoKit()` → `GET`. Uma tela
  aberta não é uma tela que grava (`.ai/rules/testes.md`).

    Esquema do Cenário: [CT-22] o ano da assinatura acompanha o relógio, e vem do fuso da aplicação
      Dado o nome da aplicação gravado como "Acme"
      E o fuso da aplicação em "<fuso>"
      E o instante congelado em "<instante>" UTC
      Quando o visitante abre "/admin/login"
      Então o conteúdo do rodapé é exatamente "© <ano> Acme"

      Exemplos:
        | fuso                | instante            | ano  | # partição                                  |
        | UTC                 | 2026-12-31 23:59:59 | 2026 | último segundo do ano — borda−1             |
        | UTC                 | 2027-01-01 00:00:00 | 2027 | primeiro segundo do ano seguinte — borda    |
        | UTC                 | 2027-06-15 12:00:00 | 2027 | ano seguinte, longe da borda                |
        | America/Sao_Paulo   | 2027-01-01 02:30:00 | 2026 | **ainda 31/12 em São Paulo** — o ano é o do fuso do app, não o do UTC |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M01 | a assinatura é acrescentada **só** à blade do painel — a opção que o `00` registra como recusada ("deixa RQ-06 sem resposta") | CT-01 (as três linhas de login) |
| M02 | a assinatura é acrescentada **só** ao rodapé do login | CT-01 (`/admin`, `/infra`) |
| M03 | a assinatura é emitida pelo hook global **e** pelo escopado do login, e duplica na tela de login (V4: sem escopo renderiza antes de com escopo — os dois saem) | CT-02 |
| M04 | o nome vem de `env('APP_NAME')` ou de um literal, e não do valor gravado pela tela | CT-03 |
| M46 — *adversarial, rodada 2* | a composição é memoizada (`once()`, cache, leitura de `env`) **no rodapé do painel**, e só a superfície de login relê: depois de gravar, `/admin` segue exibindo o nome antigo | CT-03 (as duas superfícies, e o nome antigo **ausente**) |
| M47 — *adversarial, rodada 2* | a composição trunca o nome (`Str::limit`) para "não quebrar o layout" da tela pública | CT-16 (linha de 120 caracteres) |
| M48 — *adversarial, rodada 2* | a assinatura é **deduplicada** contra o recado, e some quando o admin já escreveu `© Nome` no textarea — o mundo que Q3 nomeia e que nada montava | CT-02 (linha do recado com `© Acme`) |
| M05 | o `©` é posposto ao nome (`Acme ©`) | CT-16, CT-07 (oráculo de composição exata) |
| M53 — *Q1* | o ano é **cravado no código** (`'© 2026 '`) em vez de calculado no render | CT-22 (linhas de 2027) |
| M54 — *Q1* | o ano vem de **outra fonte** — `created_at` de um registro, uma constante de release, o ano da versão gravada | CT-22 (as três linhas UTC, cujo único parâmetro livre é o relógio) |
| M55 — *Q1* | o ano é formatado com `y` em vez de `Y` (`© 26 Acme`), ou com a data inteira | CT-16, CT-07, CT-22 (oráculos de igualdade exata) |
| M56 — *Q1* | o separador entre ano e nome é o mesmo ` · ` das versões (`© 2026 · Acme`), ou não há separador (`© 2026Acme`) | CT-16, CT-07, CT-22 (igualdade exata) |
| M57 — *Q1* | o ano é lido em UTC enquanto a aplicação roda em outro fuso — na virada, a tela mostra o ano errado por até um dia | CT-22 (linha `America/Sao_Paulo`) |
| M39 — *revisão adversarial* | a assinatura para o anônimo é emitida pelo hook **escopado nas classes de login**, e o `FOOTER` global mantém a guarda envolvendo o bloco inteiro: o `© Nome` some de recuperação de senha e registro, e R1 diz "toda tela" | CT-01 (linha `/admin/password-reset/request`) |
| M40 — *revisão adversarial* | `getRenderHookScopes()` é escopado na classe mãe **e** na filha — a leitura defensiva de V6/V7 —, e em `/login` a filha casa os dois escopos: assinatura e recado saem **duas vezes** | CT-02 (linha `/login`) |

---

## Regra R2 — nenhuma versão aparece para quem não autenticou

> `RQ-06` + decisão de 2026-09-22 · área **A**, perfil **completo** ·
> técnica: **EP sobre a audiência + rastreio de render (presente / ausente)**

```gherkin
# language: pt
  Regra: nem a versão do sistema nem a do kit aparecem para quem não autenticou

    Esquema do Cenário: [CT-04] o visitante vê a assinatura e nenhuma versão
      Dado o nome da aplicação gravado como "Acme"
      E a versão do sistema gravada como "9.9.9-secreta"
      E a exibição da versão do kit <toggle>
      Quando o visitante abre "<rota>"
      Então o rodapé da resposta contém a assinatura "© 2026 Acme"
      E o DOCUMENTO INTEIRO não contém "9.9.9-secreta"
      E o documento inteiro não contém o valor de "kit.version"

      Exemplos:
        | rota                          | toggle    | # partição                                         |
        | /admin/login                  | desligada | guarda no caminho comum                            |
        | /admin/login                  | ligada    | a versão do KIT também é guardada                  |
        | /infra/login                  | ligada    | outro painel, mesma guarda                         |
        | /login                        | ligada    | página única — não há painel corrente              |
        | /admin/password-reset/request | ligada    | **superfície pública que NÃO é tela de login**     |
        | /admin/register               | ligada    | idem, com o registro aberto                        |

    Esquema do Cenário: [CT-05] quem autenticou vê, nos três painéis, a versão que o visitante não vê
      Dado o nome da aplicação gravado como "Acme"
      E a versão do sistema gravada como "9.9.9-secreta"
      E a exibição da versão do kit ligada
      Quando <persona> abre "<rota>"
      Então o rodapé contém "v9.9.9-secreta"
      E o segmento da versão do kit contém o valor de "kit.version"
      E esse segmento contém rótulo, e o segmento de "9.9.9-secreta" não contém

      Exemplos:
        | persona                          | rota   | # partição        |
        | a administradora (admin)         | /admin | painel admin      |
        | a usuária do painel (panel_user) | /app   | painel c/ tenancy |
        | o operador de infra (infra)      | /infra | terceiro painel   |

    # [CT-06] ~~a versão do kit não aparece em nenhum lugar do documento do visitante~~
    # FUNDIDO EM CT-04 na rodada 2 da revisão adversarial. O achado foi de RECORTE
    # INCONSISTENTE: CT-04 media a ausência no rodapé e CT-06 no documento inteiro, para a
    # mesma regra — e a versão vazada em <meta>, em comentário HTML ou na topbar passava por
    # CT-04. Unificado no recorte mais forte (documento inteiro), CT-06 deixa de matar mutante
    # próprio. O ID fica registrado, não reaproveitado.
```

**Por que estes cenários discriminam**

- **As duas últimas linhas de CT-04 são as que impedem a guarda por rota.** Um
  `! request()->routeIs('*login*')` passa em todas as outras — e em produção entrega a versão exata
  em recuperação de senha e registro, que é o dano que a decisão de segurança do `00` existe para
  evitar. O eixo do conjunto era *audiência*; faltava o eixo *superfície pública que não é login*.
- **A ausência tem destinatário, e o destinatário é duplo.** O `Dado` **grava** a versão (o
  `phpunit.xml` a força vazia, então sem isso a asserção passaria com a feature inteira revertida),
  e a assinatura presente **na mesma resposta** prova que a página é a certa, que o ponto de
  composição rodou e que o rodapé alcança aquele layout. É um controle positivo mais forte do que o
  marcador de `CT-37` de `VersaoNoRodapeTest.php`, porque não depende de nomear o ponto de extensão.
- **O toggle ligado é o valor discriminante**, não decoração: é a única configuração em que a
  implementação que estreita a guarda *só em volta de `v{versão}`* produz observável diferente da
  correta. Com o toggle desligado, o mutante e a implementação certa são indistinguíveis.
- CT-06 assere sobre o **documento inteiro** de propósito: com o toggle ligado e sem sessão, a
  versão do kit não pode aparecer em lugar nenhum, e o recorte do rodapé seria uma asserção mais
  fraca do que a regra pede (mesma escolha do CT-02 de `VersaoNoRodapeTest.php`).
- CT-05 é **o par positivo do estreitamento**, e não um caminho feliz redundante: é o único cenário
  que fica vermelho se a guarda for invertida ou se ela passar a esconder a versão de todo mundo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M06 | a guarda desaparece na refatoração — o bloco composto renderiza para todo mundo | CT-04 (todas as linhas), CT-06 |
| M07 | a guarda **não foi estreitada**: continua envolvendo o bloco inteiro, e a assinatura some para o visitante | CT-01 (linhas de login), CT-04 (`Então ... contém "© 2026 Acme"`) |
| M08 | a guarda foi estreitada **demais**: envolve só `v{versão}` e deixa a versão do kit de fora | CT-04 (linhas com toggle ligado), CT-06 |
| M09 | a guarda é invertida — esconde do autenticado e mostra ao visitante | CT-05 (fica vermelho), CT-04 (fica vermelho) |
| M10 | a guarda passa a ler o painel corrente (`getCurrentPanel()->auth()`) em vez de `filament()->auth()->check()`; na página única `/login` não há painel corrente e a condição desanda | CT-04 (linha `/login`) |
| M11 | a condição de exibição vira o **toggle do kit** em vez da autenticação | CT-04 (linhas com toggle ligado), CT-05 |
| M37 — *revisão adversarial* | a visibilidade é decidida **pela rota**, não pela autenticação (`! request()->routeIs('*login*')` ou `! $this->ehTelaDeLogin()`). A versão vaza em recuperação de senha e registro — que é literalmente o "mapa de CVEs a quem ainda não autenticou" do `00` | CT-04 (linhas `/admin/password-reset/request` e `/admin/register`) |
| M38 — *revisão adversarial* | `auth()->check()` (guarda default) no lugar de `filament()->auth()->check()` | ⚠️ **sem matador** — ver `## Mutantes sem matador` |

---

## Regra R3 — a linha automática é composta num só ponto

> `RQ-05`, `RQ-06`, `RQ-07` · área **B**, perfil **padrão** ·
> técnica: **comparação cruzada de superfícies**

O ponto único é mecanismo; o que o requisito torna observável é a **consequência**: a mesma linha
sai nas duas superfícies, e continua saindo quando o admin mexe no texto livre. Cenário que
assertasse "existe uma classe `ComposicaoDoRodape`" testaria o plano, não o requisito.

```gherkin
# language: pt
  Regra: a assinatura é a mesma nas duas superfícies, qualquer que seja o texto livre do admin

    Esquema do Cenário: [CT-07] a assinatura é a mesma string literal em toda superfície
      Dado o nome da aplicação gravado como "Acme & Filhos"
      E a versão do sistema gravada como "2.4.0"
      E a exibição da versão do kit desligada
      Quando <audiencia> abre "<rota>"
      Então o conteúdo do rodapé é exatamente "<esperado>"

      Exemplos:
        | audiencia                        | rota         | esperado                       | # partição        |
        | a administradora (admin)         | /admin       | © 2026 Acme & Filhos · v2.4.0       | painel admin      |
        | o operador de infra (infra)      | /infra       | © 2026 Acme & Filhos · v2.4.0       | outro painel      |
        | a usuária do painel (panel_user) | /app         | © 2026 Acme & Filhos · v2.4.0       | painel c/ tenancy |
        | o visitante                      | /admin/login | © 2026 Acme & Filhos           | login de painel   |
        | o visitante                      | /login       | © 2026 Acme & Filhos           | página única      |

    Cenário: [CT-08] o recado preenchido não substitui a assinatura
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como "Fale com o suporte"
      Quando o visitante abre "/admin/login"
      Então o rodapé contém a assinatura "© 2026 Acme"
      E o rodapé contém "Fale com o suporte"

    Esquema do Cenário: [CT-09] o recado sem conteúdo não apaga a assinatura
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como <recado>
      Quando o visitante abre "/admin/login"
      Então o rodapé contém a assinatura "© 2026 Acme"
      E o rodapé não contém linha de recado

      Exemplos:
        | recado    | # partição        |
        | nulo      | ausente           |
        | ""        | vazio             |
        | "   "     | só espaços        |
```

**Notas**
- **CT-07 não compara as duas superfícies entre si — compara cada uma com o literal.** A primeira
  redação dizia *"as duas assinaturas são a mesma string, não vazia"*, e a revisão adversarial
  mostrou que aquilo é **igualdade que não discrimina**: duas composições igualmente erradas
  (`Acme & Filhos ©`, `(c) Acme & Filhos`, `© Acme & Filhos ·`) são idênticas e não vazias, e o
  caso ficava verde. Comparar cada superfície com o **valor literal esperado** mata o mesmo mutante
  de divergência (duas superfícies erradas de formas diferentes falham, cada uma na sua linha) e
  mata também o mutante de **convergência errada**, que a comparação cruzada não via.
- **O `&` do nome não é mais o eixo de escape**, e a redação anterior estava errada ao dizer que
  era: `assinaturaDoRodape()` faz `html_entity_decode`, então `&amp;` e `&` colapsam na mesma
  string e a diferença desaparece. O `&` permanece porque é um caractere que **a composição precisa
  atravessar sem perder** — e o eixo de escape mora inteiro em R7, sobre o HTML **bruto**.
- CT-07 cobre **cinco** superfícies, e não duas, porque é o único cenário que sustenta RQ-05: uma
  composição duplicada que divirja só em `/app` ou em `/login` não seria vista por um par.
- CT-08 é o cenário que mata a alternativa que o `00` **registra como recusada** ("o login herda
  quando o textarea está vazio"): ela ficaria verde em CT-09 e vermelha aqui.
- CT-09 e CT-08 são as duas metades da partição do recado (`sem conteúdo` × `preenchido`), e nenhuma
  delas é redundante: uma mata "a assinatura só aparece quando não há recado", a outra mata "o
  rodapé do login só é registrado quando há recado".
- **A linha `"   "` de CT-09 é a discriminante**, e não enchimento: ela separa `filled()` de uma
  comparação com `null`, e é a que impede uma faixa de recado vazia ao lado da assinatura. A
  partição vem da taxonomia (*ausente ≠ `null` ≠ `""`*) e coincide com a que o caso pré-existente
  de `LoginSocialGoogleTest.php` já protegia pelo lado antigo — por isso ela precisa continuar
  viva em algum lugar depois desta entrega (ver `## Regressão em suíte existente`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | duas composições independentes que divergem (`© Acme` numa, `(c) Acme` ou `Acme ©` na outra) | CT-07 |
| M13 | a assinatura no login só aparece quando o textarea está vazio — a opção recusada no `00` | CT-08 |
| M14 | o rodapé do login inteiro só é registrado quando há recado | CT-09 |
| M36 | a condição do recado troca `filled()` por comparação com `null`, e `"   "` passa a render uma faixa de recado vazia ao lado da assinatura | CT-09 (linha `"   "`) |

---

## Regra R4 — o recado é linha adicional, abaixo, e só nas telas de login

> `RQ-06` + decisão de 2026-09-22 · área **C**, perfil **padrão** ·
> técnica: **tabela de decisão classe de página × escopo + ordem no documento**

```gherkin
# language: pt
  Regra: na tela de login o recado do admin é uma linha adicional abaixo da assinatura

    Esquema do Cenário: [CT-10] a assinatura vem antes do recado, em toda tela de login
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como "Fale com o suporte"
      Quando o visitante abre "<rota>"
      Então o rodapé tem dois blocos irmãos
      E o primeiro é exatamente "© 2026 Acme" e o segundo exatamente "Fale com o suporte"

      Exemplos:
        | rota         | # partição                                       |
        | /admin/login | login de painel — a classe mãe                   |
        | /login       | página única — a classe filha, registro por outro caminho |

    Esquema do Cenário: [CT-11] o recado aparece em todas as telas de login
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como "Fale com o suporte"
      Quando o visitante abre "<rota>"
      Então o rodapé contém "Fale com o suporte"
      E o rodapé contém a assinatura "© 2026 Acme"

      Exemplos:
        | rota         | # partição                                          |
        | /admin/login | página de login de painel (a classe mãe)            |
        | /app/login   | página de login de outro painel, mesma classe mãe   |
        | /infra/login | terceiro painel — a linha que um escopo enumerado painel a painel esquece |
        | /login       | página única de login — a classe FILHA (V6/V7)      |

    Esquema do Cenário: [CT-12] o recado aparece na tela de login e não na tela autenticada
      Dado o nome da aplicação gravado como "Acme"
      E o recado do rodapé de login gravado como "Fale com o suporte"
      Quando <audiencia> abre "<rota>"
      Então o documento <espera> "Fale com o suporte"
      E o rodapé contém a assinatura "© 2026 Acme"

      Exemplos:
        | audiencia                        | rota         | espera     | # partição                |
        | o visitante                      | /admin/login | contém     | o destinatário existe     |
        | a administradora (admin)         | /admin       | não contém | a fronteira, painel admin |
        | a usuária do painel (panel_user) | /app         | não contém | painel c/ tenancy         |
        | o operador de infra (infra)      | /infra       | não contém | terceiro painel           |
```

**Por que estes cenários discriminam**

- **CT-10 tem uma armadilha de oráculo que precisa estar no código**: comparar posições com
  `strpos` **sem antes afirmar a presença dos dois** é falso ✅ — `strpos` devolve `false` para a
  agulha ausente, e `false < 40` é verdadeiro em PHP. O cenário afirma **primeiro** que ambos
  existem (`assertStringContainsString` nos dois), e só então compara as posições com
  `strpos(...) < strpos(...)`, ambas já sabidas inteiras. Sem isso, a implementação que **apaga a
  assinatura** passa no cenário de ordem.
- **CT-11 é a linha `/login` que importa.** V6/V7 registram que `getRenderHookScopes()` devolve a
  classe **concreta** e que a página de login unificada **estende** a do painel — escopar só na mãe
  **não pega a filha**. A linha `/login` é o único ponto do conjunto em que essa implementação
  produz observável diferente. As linhas `/admin/login` e `/app/login` matam o erro simétrico
  (escopar só na filha).
- **CT-12 tem destinatário, e ele é a linha irmã — não um passo de arranjo.** A primeira redação
  punha *"E o recado aparecendo no rodapé de `/admin/login`"* no `Dado`, o que é uma requisição e
  uma asserção travestidas de arranjo: com duas requisições num cenário só, a falha não diz qual
  delas quebrou. Como `Esquema`, cada linha tem **um** `Quando`, e a linha do visitante é o controle
  positivo da linha da administradora. Sem ela, "o recado não aparece em `/admin`" ficaria verde com
  o recado nunca renderizado em lugar nenhum — e o mutante que mais importa aqui (registro sem
  escopo) é justamente o que o `00` documenta como a razão pela qual o rodapé do login **não** usa
  o `FOOTER`.
- **CT-10 mede a ordem em `/login` também.** A ordem pode ser garantida pela ordem de registro no
  provider do painel enquanto a página única registra por outro caminho e sai invertida — e medir
  ordem só em `/admin/login` deixaria isso passar com CT-11 verde, que ali só afirma presença.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o recado é registrado no `FOOTER` **sem escopo** e passa a aparecer em toda tela do sistema | CT-12 |
| M16 | o escopo cita só a classe mãe (`TelaLogin`) — `/login` fica sem o recado | CT-11 (linha `/login`) |
| M17 | o escopo cita só a classe da página única — os logins de painel ficam sem o recado | CT-11 (linhas `/admin/login`, `/app/login`) |
| M18 | o recado continua no `AUTH_LOGIN_FORM_AFTER`: sai **dentro** do cartão e, por V5, **acima** do `FOOTER` | CT-10 |
| M19 | assinatura e recado saem no mesmo hook, com a concatenação invertida | CT-10 |
| M42 — *revisão adversarial* | a ordem é garantida pela ordem de registro no provider do painel, e a página única `/login` registra por outro caminho, saindo com o recado acima | CT-10 (linha `/login`) |
| M45 — *adversarial, rodada 2* | assinatura e recado saem **concatenados no mesmo nó** (`© Acme Fale com o suporte`) — a ordem dos bytes fica certa e "linha adicional abaixo" nunca acontece | CT-10 (dois blocos irmãos, cada um com conteúdo exato) |
| M43 — *revisão adversarial* | o escopo enumera as classes painel a painel e esquece a do `/infra` | CT-11 (linha `/infra/login`) |
| M34 | o recado passa a aparecer também nas telas de registro e de recuperação de senha | **CT-01 e CT-04** (linha `/admin/password-reset/request`). Era lacuna enquanto Q2 estava aberta; com a decisão de 2026-09-23 (*só nas telas de login*) a linha que já existia para a assinatura e para a versão passa a ser oráculo também para o recado: **o `Então` daquela linha ganha `e o rodapé não contém o recado`** |

> **Estouro do teto declarado**: R4 tem 6 mutantes num perfil `padrão`, cujo teto é 5. Dois deles
> (M42, M43) vieram da revisão adversarial e, pela regra do gate, **não contam para o teto**.

---

## Regra R5 — o prefixo `v` é de exibição

> `RQ-03`, `RQ-04` · área **D**, perfil **padrão** ·
> técnica: **criação × edição × uso** (o mesmo campo nos três pontos)

```gherkin
# language: pt
  Regra: o prefixo v é exibido no formulário e não entra no valor gravado

    Cenário: [CT-13] o campo Versão do sistema exibe o afixo nativo "v" sem contaminar o estado
      Dado a versão do sistema gravada como "2.4.0"
      E a administradora na tela de configurações da aplicação
      Quando o campo "versao_do_sistema" é inspecionado
      Então o rótulo de PREFIXO do campo é "v" e o de sufixo é nulo
      E o estado carregado do campo é "2.4.0"
      E o afixo "v" aparece no HTML renderizado do campo
      E nenhum outro campo do formulário tem prefixo "v"

    Esquema do Cenário: [CT-14] o que se digita é o que se grava, e o rodapé compõe o "v"
      Dado a administradora na tela de configurações da aplicação
      E a versão do sistema gravada como "1.0.0"
      Quando ela grava a versão do sistema como "<digitado>"
      Então a propriedade "versao_do_sistema" vale "<gravado>"
      E o rodapé de "/admin" contém "<exibido>"

      Exemplos:
        | digitado | gravado  | exibido   | # partição                                      |
        | 2.4.0    | 2.4.0    | v2.4.0    | caminho comum — e o rodapé NÃO contém "vv"      |
        | v2-beta  | v2-beta  | vv2-beta  | **versão que legitimamente começa por `v`**     |

    Cenário: [CT-21] apagar a versão pela tela esvazia o rodapé, sem o campo virar obrigatório
      Dado o nome da aplicação gravado como "Acme"
      E a versão do sistema gravada como "2.4.0"
      E a administradora na tela de configurações da aplicação
      Quando ela apaga a versão do sistema e grava
      Então o formulário não acusa erro no campo "versao_do_sistema"
      E a propriedade "versao_do_sistema" vale vazio
      E o conteúdo do rodapé de "/admin" é exatamente "© 2026 Acme"

    Cenário: [CT-15] a versão já gravada com "v" não é migrada nem normalizada
      Dado a versão do sistema gravada como "v1.2.3"
      E a administradora na tela de configurações da aplicação
      Quando ela grava a tela alterando apenas o nome da aplicação
      Então a propriedade "versao_do_sistema" continua valendo "v1.2.3"
      E o estado carregado do campo é exatamente "v1.2.3", com o prefixo "v" ao lado
      E o rodapé de "/admin" contém "vv1.2.3"
```

**Por que estes cenários discriminam**

- **CT-13 é o que separa "prefixo exibido" de "prefixo qualquer".** O oráculo é
  `getPrefixLabel()` do componente (`vendor/filament/forms/.../HasAffixes.php:225`, confirmado na
  fonte instalada), alcançado como o projeto já alcança componentes:
  `Livewire::test(ConfiguracoesDoKit::class)->instance()->getSchemaComponent('form.versao_do_sistema')`
  (padrão de `tests/Tenancy/AdminDaOrganizacaoTest.php:157`). Um `assertSee('v')` seria vácuo — a
  letra `v` está em qualquer página — e um prefixo escrito no **label** (`"Versão do sistema (v)"`)
  passaria por ele. É também o único observável de RQ-04.
- **CT-14 é o que separa "prefixo exibido" de "prefixo gravado".** A asserção `não contém "vv"` é o
  detector: se o prefixo entrasse no estado desidratado, o valor gravado seria `v2.4.0`, e o rodapé
  — que compõe o `v` (`00`, RQ-03) — emitiria `vv2.4.0`. Nenhuma das duas asserções sozinha pega
  isso: a do banco pega o prefixo gravado, a do `vv` pega também a variante em que o rodapé para de
  antepor o `v` porque "agora o valor já vem com ele".
- **CT-15 é o efeito colateral que o `00` nomeia** ("quem hoje digitou `v1.2.3` passa a ver
  `vv1.2.3` no campo — o prefixo torna o erro visível, mas o dado sujo continua lá"). O `Quando`
  grava **outro** campo de propósito: é o único jeito de provar que a versão não é tocada por um
  `save` que passa por ela. `vv1.2.3` não é defeito aqui — é o oráculo, e afirmá-lo impede que
  alguém "conserte" o dado sujo em silêncio sem que o `00` tenha decidido migrar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | o prefixo é aplicado ao estado (`formatStateUsing` / `mutateDehydratedStateUsing`) e entra no valor gravado | CT-14 |
| M21 | o prefixo não chega ao campo (esquecido, ou aplicado em outro campo) | CT-13 |
| M22 | o rodapé deixa de antepor o `v`, assumindo que o valor já o traz | CT-14 (`contém "v2.4.0"`) |
| M23 | o campo passa a normalizar, removendo o `v` do valor já gravado | CT-15 |
| M52 — *adversarial, rodada 3* | o campo "ajuda" o usuário **removendo um `v` inicial** no salvamento (`ltrim($s,'v')`, `preg_replace('/^v/','',$s)`): quem digita uma versão que legitimamente começa por `v` (`v2-beta`, `vNext`) tem o dado mutilado, e o prefixo deixou de ser de exibição | CT-14 (linha `v2-beta`) |
| M49 — *adversarial, rodada 2* | o campo vira `required` junto com o afixo, ou o `save` mantém o valor anterior ao esvaziar, ou grava `"v"` — o eixo de **gravação** de vazio não existia: todas as partições de vazio entravam por `config()->set()` | CT-21 |
| M24 | o `v` vira texto do `label()` em vez do afixo nativo — RQ-04 ignorado | CT-13 |

---

## Regra R6 — valor vazio não deixa sobra

> `RQ-01`, `RQ-02` + taxonomia *nulo / vazio / ausente* · área **B**, perfil **padrão** ·
> técnica: **tabela de decisão — produto cartesiano de nome × versão × toggle**

As linhas com `nome vazio` eram `@premissa` por **Q4** e as colunas `esperado` eram `@premissa`
quanto ao ano por **Q1**. **As duas foram decididas em 2026-09-23** e deixam de ser premissa: Q4
confirmou a direção por falha fechado (omitir), Q1 **reverteu** a sua (o ano entra). Nenhuma linha
desta regra permanece marcada.

**O invariante que sustentava as duas leituras continua afirmado, e agora vale como oráculo**: em
nenhuma combinação o rodapé emite `©` sem nome, separador com um lado só, ou elemento vazio.

```gherkin
# language: pt
  Regra: nenhuma combinação de valores vazios deixa marca solta no rodapé

    Esquema do Cenário: [CT-16] a composição exata, com a versão do kit desligada
      Dado o nome da aplicação gravado como "<nome>"
      E a versão do sistema gravada como "<versao>"
      E a exibição da versão do kit desligada
      Quando a administradora abre "/admin"
      Então o conteúdo do rodapé é exatamente "<esperado>"

      Exemplos:
        | nome    | versao  | esperado           | # partição                                   |
        | Acme    | 2.4.0   | © 2026 Acme · v2.4.0    | tudo preenchido                              |
        | Acme    | (vazia) | © 2026 Acme        | sem versão — sem separador solto             |
        | Acme    | (nula)  | © 2026 Acme        | nulo ≠ vazio, mesmo `Então`                  |
        | Acme    | "   "   | © 2026 Acme        | **branco** — separa `filled()` de `! empty()` |
        | Acme    | 0       | © 2026 Acme · v0   | **`"0"`** — `empty("0")` é `true` em PHP     |
        | (vazio) | 2.4.0   | v2.4.0             | sem nome — sem © órfão                       |
        | (vazio) | (vazia) | (elemento ausente) | nada a mostrar — **o elemento não ocorre no HTML** |
        | (nome de 120 caracteres) | 2.4.0 | © 2026 {os 120 caracteres} · v2.4.0 | **sem truncamento silencioso** |

    Esquema do Cenário: [CT-17] com a versão do kit ligada, nenhum segmento nasce vazio
      Dado o nome da aplicação gravado como "<nome>"
      E a versão do sistema gravada como "<versao>"
      E a exibição da versão do kit ligada
      Quando a administradora abre "/admin"
      Então o rodapé contém o valor de "kit.version"
      E o rodapé tem exatamente <segmentos> segmentos separados por " · ", nenhum deles vazio
      E o rodapé não começa nem termina pelo separador
      E a ordem dos segmentos é assinatura, versão do sistema, versão do kit

      Exemplos:
        | nome    | versao  | segmentos | # partição                             |
        | Acme    | 2.4.0   | 3         | assinatura + versão + kit              |
        | Acme    | (vazia) | 2         | o buraco no meio                       |
        | (vazio) | 2.4.0   | 2         | o buraco na frente                     |
        | (vazio) | (vazia) | 1         | só a do kit — segmento único           |

    Esquema do Cenário: [CT-18] para o visitante, a assinatura é a linha inteira
      Dado o nome da aplicação gravado como "<nome>"
      E a versão do sistema gravada como "9.9.9-secreta"
      E a exibição da versão do kit <toggle>
      Quando o visitante abre "/admin/login"
      Então o conteúdo do rodapé automático é exatamente "<esperado>"
      E o rodapé não contém "9.9.9-secreta" nem a versão do kit

      Exemplos:
        | nome    | toggle    | esperado                           | # partição                        |
        | Acme    | desligada | © 2026 Acme                        | caminho comum                     |
        | Acme    | ligada    | © 2026 Acme                        | o toggle não abre nada ao anônimo |
        | (vazio) | desligada | (o elemento não ocorre no HTML)    | sem nome e sem versão visível     |
        | (vazio) | ligada    | (o elemento não ocorre no HTML)    | idem, com o toggle ligado         |
```

**Notas**

- **`(elemento ausente)` é asserção sobre o HTML, não sobre a string normalizada.**
  `assinaturaDoRodape()` devolve `""` tanto para *elemento ausente* quanto para *elemento presente e
  vazio* — comparar com `""` deixaria M27 vivo no cenário que existe para matá-lo. A asserção é
  `assertDontSee` do **marcador do elemento** no HTML, como o caso pré-existente
  `[CT-01] nao renderiza o elemento quando nao ha o que mostrar` já faz com `'kit-versao'`.
- **`"   "` e `"0"` são as duas fronteiras de PHP que separam `filled()` de `! empty()`**, e nenhuma
  das duas é enchimento: `empty("0") === true` faz a versão `0` **desaparecer** junto com seu
  separador, e `! empty("   ")` faz o branco virar um segmento `v` sozinho. Um conjunto que só
  exercita `null` e `""` não distingue as duas funções — e `array_filter()` sem callback tem
  exatamente a semântica de `empty()`.
- CT-16 é o cenário do conjunto com oráculo de **igualdade exata** sobre a string composta — e é por
  isso que ele mata `Acme ©`, `© Acme ·` e `· v2.4.0` de uma vez. Os demais usam presença, que não
  distingue sobra. **CT-07 é o segundo**, sobre cinco superfícies. Antes da decisão de Q1 os dois
  existiam para impedir que um `©` **com** ano atravessasse o conjunto; com o ano agora exigido, eles
  mudaram de lado sem mudar de forma: são o que impede o ano **ausente**, **mal formatado** (`© 26`)
  ou **mal separado** (`© 2026Acme`, `© 2026 · Acme`) de passar, porque presença por substring não
  distingue nenhuma dessas três.
- **CT-17 ganhou um controle positivo** e a contagem de segmentos. Sem eles, as três cláusulas eram
  **vacuamente verdadeiras** com o rodapé ausente: sem segmento não há segmento vazio, string vazia
  não começa nem termina por separador, e *"a assinatura, quando existe"* dispensava a assinatura.
  Exigir o valor de `kit.version` presente (o toggle está ligado nas quatro linhas) e o número
  exato de segmentos obriga a linha a existir.
- CT-17 não usa igualdade exata **de propósito**: o rótulo da versão do kit não é requisito
  (RQ-20 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/` deixa a redação livre, e o conjunto
  existente registra três correções de oráculo por tentar casá-la). O oráculo é estrutural —
  segmento vazio, separador nas bordas, posição da assinatura — e continua discriminante.
- CT-18 corre em `/admin/login`, onde o `Dado` grava uma versão que **existe** e não pode sair:
  fecha a partição de vazios **e** a guarda, no mesmo mundo.
- As linhas `(vazio)` são alcançadas por `config()->set('app.name', '')` — o caminho do `.env`
  vazio e o da escrita direta no banco. **Pelo formulário o estado é irreproduzível**: o campo é
  `required`, e isso já está coberto em `tests/Kit/ConfiguracoesDoKitTelaTest.php`
  (*"recusa campo fora do domínio sem gravar nada"*, linha `nome obrigatório ausente`). O ponto de
  **criação** está fechado lá; aqui se fecha o ponto de **uso**, que é o que esta feature muda.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | o `©` é emitido mesmo sem nome (`'© '.config('app.name')` sem guarda) | CT-16 (linhas de nome vazio), CT-18 |
| M26 | o separador é emitido com um lado vazio (`© Acme · `) | CT-16 (linha de versão vazia) |
| M27 | o elemento renderiza vazio quando não há nada a mostrar | CT-16 (linha `(elemento ausente)`), CT-18 |
| M28 | a composição usa concatenação cega em vez de `implode` sobre as partes não vazias — segmento vazio entre separadores | CT-17 |
| M50 — *adversarial, rodada 2* | com o toggle ligado, a versão do kit sai **antes** da do sistema, ou antes da assinatura: CT-16 só é exato com o toggle desligado, e nada fixava a ordem das três partes juntas — que é a adjacência que RQ-01 pede | CT-17 (ordem dos segmentos) |
| M41 — *revisão adversarial* | o filtro das partes usa `array_filter()` / `! empty()` em vez de `filled()`: a versão `"0"` **desaparece** com seu separador, e `"   "` vira um segmento `v` sozinho | CT-16 (linhas `0` e `"   "`) |
| M29 | a assinatura é **acrescentada ao fim** das partes, e sai depois das versões | CT-16 (igualdade exata), CT-17 (posição do primeiro segmento) |

---

## Regra R7 — o nome da aplicação sai escapado na superfície pública

> `RQ-01`, `RQ-02` (o campo passa a renderizar em tela anônima) + taxonomia *texto livre* ·
> área **E**, perfil **padrão** · técnica: **normalização / escape**

Esta regra não sai de uma cláusula do `00`: ela sai do **checklist de taxonomia**, aplicado ao fato
que o `00` **cria** — um campo editável por admin, que até agora só era lido em superfície
autenticada, passa a renderizar em tela pública e anônima. O conjunto existente já trata a versão
assim (`CT-04` de `VersaoNoRodapeTest.php`); o nome herda a mesma exigência, com uma superfície a
mais.

```gherkin
# language: pt
  Regra: o nome da aplicação chega escapado ao rodapé, nas duas superfícies

    Esquema do Cenário: [CT-19] a marcação gravada no nome sai escapada, não crua nem descartada
      Dado o nome da aplicação gravado como "<script>alert(1)</script>Acme"
      E a versão do sistema gravada como "2.4.0"
      Quando <audiencia> abre "<rota>"
      Então o HTML BRUTO do rodapé contém "&lt;script&gt;alert(1)&lt;/script&gt;Acme"
      E o documento não contém "<script>alert(1)" em forma executável

      Exemplos:
        | audiencia                | rota         | # partição                    |
        | o visitante              | /admin/login | superfície pública e anônima  |
        | a administradora (admin) | /admin       | superfície autenticada        |

    Cenário: [CT-20] a assinatura não passa pelo renderizador de Markdown do recado
      Dado o nome da aplicação gravado como "<script>alert(1)</script>Acme"
      E o recado do rodapé de login gravado como "**Fale com o suporte**"
      Quando o visitante abre "/admin/login"
      Então o HTML BRUTO do rodapé contém "&lt;script&gt;alert(1)&lt;/script&gt;Acme"
      E o HTML bruto do rodapé contém "<strong>Fale com o suporte</strong>"
      E o documento não contém "<script>alert(1)" em forma executável
```

**Notas**

- **O oráculo positivo é a forma escapada literal, e isso não é preciosismo — é o que torna a
  asserção de três vias.** O recado já é renderizado como Markdown com `html_input: strip`
  (coberto por *"renderiza o rodapé como Markdown e descarta HTML cru e link inseguro"* em
  `tests/Kit/LoginSocialGoogleTest.php`), ou seja, HTML no recado é **descartado**, não escapado.
  Então há três saídas possíveis para o nome, e só uma é correta:

  | Caminho da implementação | O que sai no HTML | O `Então` |
  |---|---|---|
  | escapado por `{{ }}` (correto) | `&lt;script&gt;alert(1)&lt;/script&gt;Acme` | ✅ |
  | cru (`{!! !!}`) | `<script>alert(1)</script>Acme` executável | ❌ pela negativa |
  | pelo Markdown do recado (`strip`) | só `Acme` — o nome chega **mutilado** | ❌ pela positiva |

  Um oráculo que só dissesse "não contém `<script>`" daria **verde nas duas últimas linhas**, e a
  terceira é justamente o furo que CT-20 existe para pegar.
- **R7 é a única regra que NÃO usa `assinaturaDoRodape()`.** O helper faz `strip_tags` e
  `html_entity_decode`, e essas duas operações **anulam exatamente o que esta regra mede**: a forma
  escapada e a forma crua colapsam na mesma string depois delas. Aqui a asserção é sobre o **HTML
  bruto** do elemento. A revisão adversarial achou o conjunto medindo o próprio helper em vez do
  escape — um oráculo que não podia falhar.
- A negativa sozinha fica verde se a assinatura simplesmente não renderizar — é asserção de ausência
  sem destinatário. A positiva é o controle, e ela **também** é o destinatário.
- **CT-20 exige a assinatura E o recado na mesma resposta.** Só a negativa deixaria verde a
  implementação que **apaga** a assinatura no caminho do recado — a forma mais fácil de "resolver" o
  conflito entre escapar e renderizar Markdown.
- CT-20 é a variante que **só existe porque as duas linhas passam a conviver**. Uma implementação
  que concatene assinatura e recado numa string só antes de renderizar o Markdown deixaria CT-19
  verde — porque na tela **sem** recado a assinatura continua saindo pelo caminho escapado. A
  segunda asserção (a ênfase aplicada) impede a "correção" preguiçosa de escapar tudo e quebrar o
  recado, cujo comportamento é pré-existente e está fora de escopo.
- `<script>alert(1)</script>Acme` é discriminante de propósito: o sufixo `Acme` dá âncora não vazia
  à metade positiva, a tag é executável (`<b>` não provaria risco), e a tag fica **no começo** do
  nome — o que no CommonMark faz a linha inteira virar bloco HTML, maximizando o estrago da
  terceira linha da tabela acima.
- **`assertDontSee(..., escape: false)` com `<script>alert(1)` e não com `<script>`**: a página tem
  scripts legítimos (Livewire, tema). É a mesma escolha que o caso vizinho já documenta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | a assinatura sai por `{!! !!}` / `Htmlable` para "deixar o `©` bonito" | CT-19 (as duas linhas) |
| M31 | a assinatura é escapada no painel e crua no login — duas emissões, um cuidado só | CT-19 (linha `/admin/login`) |
| M32 | assinatura e recado são concatenados e passam pelo mesmo renderizador de Markdown | CT-20 |

---

## Mutantes sem matador (lacunas declaradas)

> **Duas saíram desta tabela em 2026-09-23.** **M34** (recado nas telas de registro e recuperação)
> tinha como causa a pergunta **Q2**, que o solicitante decidiu — *só nas telas de login* —, e o
> matador passou a ser a linha `/admin/password-reset/request` que CT-01 e CT-04 já visitam.
> **M44** (afixo declarado e não renderizado) foi fechado por asserção nova em CT-13. Registrado
> porque lacuna que some sem explicação é indistinguível de lacuna esquecida.

| # | Implementação errada plausível | Por que não há cenário | O que foi tentado / para onde vai |
|---|---|---|---|
| M33 | o CSS reordena visualmente as duas linhas (ex.: `flex-col-reverse`), e o recado aparece **acima** da assinatura apesar da ordem correta no DOM | CT-10 prova a ordem **no documento**; ordem **visual** depois do CSS só o navegador prova, medindo geometria (`.ai/rules/testes-browser.md`, *"`assertVisible` não prova posição"*) | **Atenuante medido**: o kit não usa `viteTheme()`, e utilitária Tailwind emitida por blade do kit **não existe** na folha compilada (`.ai/rules/css-filament.md`) — um `flex-col-reverse` sairia inerte. Candidato a **CT-B01** (geometria por `script()` em `/login`); ver `## Gate de CT-B` |
| M38 | `auth()->check()` (guarda default) no lugar de `filament()->auth()->check()` | `usuarioDoKit()` autentica na guarda default, e os três painéis do kit usam essa mesma guarda: **não há, nesta instalação, configuração em que as duas expressões divirjam** | **Tentado**: autenticar numa guarda e visitar painel de outra — o kit não tem segunda guarda configurada; registrar painel de teste com guarda própria via `painelRegistradoEmTeste()` exigiria criar guarda, provider e papéis só para o caso. Lacuna **real**, e seu risco é de regressão futura (o dia em que um painel ganhar guarda própria), não de hoje |
| M51 — *adversarial, rodada 2* | a tela **autenticada do layout `simple`** (verificação de e-mail, confirmação de senha) fica sem assinatura, ou sem versão, ou com as duas — é a única célula em que R1 e R2 mandam **ambas** aparecer, e a guarda por rota (M37) e o escopo por classe de login (M39) divergem ali do correto em direções opostas | **sem matador**. **Tentado**: as rotas do Breezy para verificação de e-mail e confirmação de senha exigem montar um usuário não verificado e atravessar middleware próprio, o que arrasta fronteira de outra feature para dentro deste conjunto. **Decisão**: declarar, e apontar o par como o primeiro cenário a escrever se M37 ou M39 aparecerem vivos no `pest --mutate` |
| M35 | o elemento do rodapé emite utilitárias Tailwind que não existem na folha compilada — a faixa sai **sem estilo nenhum**, com HTML byte a byte correto | nenhum cenário de HTML distingue: `assertSee`, `assertOk` e teste de componente ficam todos verdes | **Tentado**: um caso no molde de `tests/Kit/SpotlightCssTest.php` (extrair as classes do elemento e exigir que existam no CSS do kit). Recusado aqui porque o requisito não determina estilo nenhum — vira **achado para o quality gate** (dimensão tema/UX), não CT derivado do `00` |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a feature não recebe `{id}` de recurso; o rodapé é global e não tem dono |
| Autorização exercida na ação (não só `can()`) | **coberto fora desta wiki**: `tests/Kit/VersaoNoRodapeTest.php` CT-11 e CT-41 (a tela e o `save`) — esta entrega não muda a permissão |
| **Fronteira pública × autenticada** (o item que substitui a autorização aqui) | CT-04, CT-05, CT-06, CT-18 — e **ao menos um por fora do componente**: todos entram por `GET` HTTP, não por `Livewire::test()` |
| **Superfície pública que NÃO é tela de login** | CT-01 e CT-04 (linhas `/admin/password-reset/request` e `/admin/register`) — é o que impede que a guarda seja escrita por rota em vez de por autenticação |
| **Default de `kit.exibir_versao` (o toggle nasce desligado)** | **coberto fora desta wiki**: `VersaoNoRodapeTest.php` CT-07 (*"nasce com a exibição da versão do kit desligada no arquivo de configuração"*). Todo cenário deste arquivo fixa o toggle explicitamente, então nenhum deles protege o default — e o `00` o declara como invariante em `## Fora de Escopo` |
| Idempotência | **não se aplica**: a feature não tem escrita própria. A escrita do nome/versão é a da tela de configurações, coberta em `tests/Kit/ConfiguracoesDoKitTelaTest.php` |
| Concorrência | **não se aplica**: sem contador, saldo, estoque ou limite |
| **Fronteira no ponto de entrada** (gravação) | CT-03 (nome), CT-14 (versão) — `fillForm` → `save` → asserção no valor gravado |
| **Criação × edição × uso** | criação/edição: CT-03, CT-14, CT-15 (+ `nome obrigatório ausente` em `ConfiguracoesDoKitTelaTest`); uso: CT-16, CT-17, CT-18 |
| Domínio condicionado (o valor de um campo muda a fronteira de outro) | CT-16, CT-17 — a presença do nome muda o que a versão precisa emitir (separador) |
| **Ausente ≠ `null` ≠ `""` (≠ só espaços)** | recado: **CT-09** (as três linhas); nome e versão: CT-16 e CT-18 exercitam **vazio** e **nulo** (`comIdentidade(null, …)` e `('', …)`) com o mesmo `Então`; **ausente** (sem linha na tabela `settings`) está coberto por CT-12 de `VersaoNoRodapeTest.php`, cuja semântica esta entrega não muda |
| Estado × operação de escrita (registro desativado ainda funciona?) | **não se aplica**: a feature não tem entidade removível nem desativável |
| **Ponto de gravação do recado (`login_rodape`)** | **lacuna declarada**: o recado entra em todo cenário por `config()->set()`, nunca pelo formulário. O campo e sua validação são pré-existentes, mas esta entrega **muda com o que ele convive** (a assinatura acima dele), o que torna o ponto de gravação relevante de novo. Nada de teto, nada de vazio-por-escrita, nada de Markdown malformado gravado pela tela |
| Paginação / ordenação | **não se aplica**: sem listagem |
| **Timezone / virada de ano** | **CT-22** — e o item **reabriu exatamente como estava previsto**: a redação anterior dizia *"se Q1 for negada e o ano entrar, este item reabre"*, e Q1 foi negada em 2026-09-23. As quatro linhas cobrem borda−1, borda, longe da borda e **o fuso do app divergindo do UTC** (a única configuração em que *ler o ano em UTC* e *ler no fuso da aplicação* produzem observáveis diferentes). DST não se aplica: o oráculo é o **ano**, e nenhuma transição de horário de verão atravessa 1º de janeiro nos fusos do projeto |
| **Unicode / texto livre / limite de `varchar`** | acento e `&`: CT-07 (`"Acme & Filhos"`); marcação: CT-19. **Lacuna declarada**: nome de **255 caracteres** (o teto declarado no campo) e emoji de 4 bytes não têm cenário. O requisito não fixa limite, o campo e sua validação são pré-existentes, e a versão já tem o dela coberta por CT-44 de `VersaoNoRodapeTest.php` (*"grava a versão longa inteira ou recusa, nunca truncada"*). **O que muda com esta entrega**: o teto passa a ter consequência de **layout** numa tela pública — um nome de 255 caracteres agora é renderizado no rodapé do login. Destino: quality gate (dimensão layout/UX), não CT derivado do `00` |
| Unicidade + soft delete | **não se aplica**: sem unicidade e sem `SoftDeletes` |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: a entrega não acrescenta campo ao formulário nem coluna à tabela — só um afixo a um campo existente |
| Upload | **não se aplica**: sem upload |
| Precisão monetária | **não se aplica**: sem valor monetário |
| **Escape em superfície pública** | CT-19, CT-20 |
| **Superfície Livewire** (método público, propriedade pública, estado do framework) | **não se aplica — confirmado pelo solicitante em 2026-09-23**: o diff não cria nem toca método público de componente. A entrega é blade, registro de hook e `prefix()` num campo existente. A dispensa deixa de ser hipótese |
| Estado do framework usado sem validar (índice, `parse`, coluna) | **não se aplica**: a composição não indexa array por valor de entrada nem faz `parse` |
| IDOR por entidade | **não se aplica**: a feature não persiste entidade nova |
| Escopo com discriminante nulo | **não se aplica**: sem query escopada |
| **Saída do estado de erro** (4xx/redirect tem destino) | **não se aplica**: nenhum cenário termina em 4xx ou redirect. As rotas cujo estado da chave `kit.login.unificado` produziria redirect são fixadas no `Dado` de CT-01 e CT-11 justamente para que a asserção não meça um redirect |
| **Asserção de ausência com destinatário** | CT-04, CT-06, CT-12, CT-18, CT-19 — todos gravam o valor que não pode aparecer **e** afirmam a assinatura presente na mesma resposta |
| **Não-efeito de render duplicado** | CT-02 (a assinatura sai uma vez só) |

---

## Regressão em suíte existente

Derivar do requisito expõe duas colisões com a suíte que já existe. Nenhuma é defeito desta
entrega, e nenhuma é opcional.

| Caso existente | O que acontece | O que fazer |
|---|---|---|
| `tests/Kit/VersaoNoRodapeTest.php` — `[CT-01] nao renderiza o elemento quando nao ha o que mostrar` (`assertDontSee('kit-versao')` com as duas versões vazias) | **fica vermelho**: com o nome preenchido (e no ambiente de teste `APP_NAME` **não** é forçado vazio), o elemento passa a renderizar com a assinatura. O caso mede a regra antiga | a intenção sobrevive e migra para a linha `(vazio)/(vazia)` de **CT-16**, que exige nome **e** versão vazios. O caso antigo é atualizado, com o motivo escrito — não apagado |
| `[CT-46]`/`[CT-47]` do mesmo arquivo, via `segmentoDaVersao()`, que casa `<div class="kit-versao">` por regex | se a implementação **renomear o elemento** (ADR-03), o helper devolve `''` e os dois casos ficam **verdes sobre nada** — é o modo de falha que o próprio docblock do helper documenta | se houver renome, atualizar o regex **e** conferir que os dois casos continuam vermelhos contra os mutantes M63/M65 que eles nomeiam. Um caso verde depois de um renome não é prova de nada |
| `tests/Kit/LoginSocialGoogleTest.php` — *"exibe o rodapé da tela de login só quando há texto configurado"* (`assertDontSee('fi-login-rodape')` com o recado vazio ou só espaços) | **fica vermelho se a implementação fundir as duas linhas num elemento só**: a tela de login passa a ter rodapé sempre (a assinatura), e o elemento do recado deixaria de ser condicional | **é restrição de desenho, não ajuste de teste**: o elemento do **recado** continua condicional ao texto do recado; a assinatura sai em elemento próprio. CT-09 é o par desta restrição pelo lado novo (o recado vazio não apaga a assinatura). Se a fusão for deliberada, o caso antigo é reescrito **com o motivo**, e a partição "só espaços" — que distingue `filled()` de comparação com `null` — precisa sobreviver em algum lugar |

---

## Achados adjacentes (não viram CT, mas ninguém mais vai vê-los)

Dois textos de interface deixam de ser verdadeiros com esta entrega. Não são caso de teste — o
requisito não os determina — mas são **omissão documental** que o quality gate cobra, e nascem
aqui, na derivação, porque é derivando que se percebe o que mudou de superfície:

| Onde | O que diz hoje | Por que deixa de valer |
|---|---|---|
| `helperText` de `nome_da_aplicacao` | *"Aparece no topo dos três painéis, no título da aba e como remetente padrão."* | passa a aparecer também no **rodapé de toda tela pública e anônima**. É a única frase que diz ao admin onde o valor dele é publicado, e ela passa a subdeclarar a exposição |
| `helperText` de `versao_do_sistema` | *"A versão do SEU produto, exibida no rodapé dos painéis."* | continua verdadeira, mas o campo ganha um afixo `v` e nada diz ao admin que **não** é para digitar o `v` — que é exatamente o dado sujo de CT-15 |

---

## Gate de CT-B

**Não foi criado `05-casos-de-teste-browser.md`** — e há um desvio a declarar, porque o gate tem
duas leituras aqui:

- **O que o gate reprova**: composição de texto, presença, ausência, ordem no documento e escape
  são todos prováveis por `GET` HTTP. Empurrar isso para o navegador seria o desperdício que a
  skill proíbe.
- **O que o gate aprovaria**: **M33** — a ordem **visual** das duas linhas depois do CSS. É a única
  afirmação do conjunto que o navegador prova e o HTTP não. O candidato é um **CT-B01** em `/login`
  medindo geometria por `script()` (`getBoundingClientRect().top` da assinatura < a do recado), no
  molde de `.ai/rules/testes-browser.md`.
- **Por que ele não foi escrito**: o escopo desta tarefa limita a saída ao `04`. **Decisão do
  solicitante**, não do derivador. O atenuante está declarado em M33 (sem `viteTheme()`, utilitária
  Tailwind emitida pela blade do kit é inerte), e ele reduz o risco — não o zera, porque o CSS do
  kit em `resources/css/filament/` pode declarar a regra escapando do atenuante.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B: console limpo nas telas de login | já coberto pela suíte de browser do kit; esta entrega não acrescenta JS |
| CT-B: a assinatura legível em dark mode | o requisito não determina cor nem contraste; vira achado do quality gate, não CT |
| CT: `©` presente no comando `kit:info` | o requisito fala em **rodapé**; o comando não é superfície desta entrega |
| CT: a assinatura no `<title>`/topbar | mesma razão — o `00` diz "ao exibir no rodapé" |
| CT: o recado renderiza Markdown | comportamento **pré-existente**, não muda com esta entrega; a única metade nova é CT-20, que só afirma que o Markdown **continua** funcionando ao lado da assinatura |
| CT: a assinatura na tela de configurações (autenticada, painel admin) | mesma partição de CT-01 `/admin`; mata o mesmo conjunto de mutantes |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | a assinatura sai em toda superfície, para as duas audiências | R1 | matriz superfície × audiência | Feature (HTTP) | `tests/Kit/RodapeCoerenteTest.php` | M01, M02, M07, M34, M39 |
| CT-02 | a assinatura sai uma vez só, em toda superfície | R1 | rastreio de render (uma vez) | Feature | idem | M03, M40, M48 |
| CT-03 | a assinatura reflete o nome gravado pela tela, nas duas superfícies | R1 | gate de tela de escrita | Livewire + Feature | idem | M04, M46 |
| CT-04 | o visitante vê a assinatura e nenhuma versão | R2 | EP audiência × toggle × rota | Feature | idem | M06, M07, M08, M09, M10, M11, M34, M37 |
| CT-05 | quem autenticou vê, nos três painéis, a versão que o visitante não vê | R2 | par positivo × painel | Feature | idem | M09, M11 |
| CT-07 | a assinatura é a mesma string literal em toda superfície | R3 | igualdade exata × 5 superfícies | Feature | idem | M05, M12 |
| CT-08 | o recado preenchido não substitui a assinatura | R3 | EP sobre o recado | Feature | idem | M13 |
| CT-09 | o recado sem conteúdo não apaga a assinatura | R3 | EP (ausente/vazio/espaços) | Feature | idem | M14, M36 |
| CT-10 | a assinatura vem antes do recado, em toda tela de login | R4 | ordem e estrutura × rota | Feature | idem | M18, M19, M42, M45 |
| CT-11 | o recado aparece em todas as telas de login | R4 | tabela de decisão classe × escopo | Feature | idem | M16, M17, M43 |
| CT-12 | o recado aparece na tela de login e não na autenticada | R4 | rastreio de render (presente/ausente) | Feature | idem | M15 |
| CT-13 | o campo exibe o afixo nativo "v" sem contaminar o estado | R5 | inspeção de componente | Livewire | idem | M21, M24, M44 |
| CT-14 | o que se digita é o que se grava, e o rodapé compõe o "v" | R5 | criação/edição × uso | Livewire + Feature | idem | M20, M22, M52 |
| CT-15 | a versão já gravada com "v" não é migrada | R5 | dado sujo (uso) | Livewire + Feature | idem | M23 |
| CT-16 | a composição exata, com a versão do kit desligada | R6 | tabela de decisão + BVA de vazio | Feature | idem | M05, M25, M26, M27, M29, M41, M47 |
| CT-17 | com a versão do kit ligada, nenhum segmento nasce vazio e a ordem é fixa | R6 | tabela de decisão | Feature | idem | M28, M29, M50 |
| CT-18 | para o visitante, a assinatura é a linha inteira | R6 | tabela de decisão × audiência | Feature | idem | M25, M27 |
| CT-19 | a marcação gravada no nome sai escapada | R7 | escape | Feature | idem | M30, M31 |
| CT-20 | a assinatura não é desescapada pelo caminho do recado | R7 | escape | Feature | idem | M32 |
| CT-21 | apagar a versão pela tela esvazia o rodapé | R5 | valor limite **na gravação** | Livewire + Feature | idem | M49 |
| CT-22 | o ano da assinatura acompanha o relógio e o fuso do app | R1 | valor limite temporal (3 valores) + fuso | Feature | idem | M53, M54, M55, M56, M57 |
| ~~CT-06~~ | ~~a versão do kit não aparece no documento do visitante~~ | — | — | — | **fundido em CT-04** | — |

**Casos pré-existentes que este conjunto pressupõe vivos** (não reescrever, não duplicar):
`VersaoNoRodapeTest.php` — `CT-37` (o `FOOTER` é emitido também na tela de login),
`CT-46`/`CT-47` (a versão do kit sai rotulada), `CT-11`/`CT-41` (permissão da tela e da gravação),
`CT-12` (propriedade ausente no banco).

---

## Revisão Adversarial

> Disparo: perfil **completo** na área A **e** Impacto 3 nas áreas A e E.
> Executada por sub-agente `fw-adversario-ct`, que recebeu **apenas** `00-requisito.md` e este
> arquivo — não recebeu o `01`, o `02`, o código nem o raciocínio de quem derivou.

**Rodada 1 executada.** Percorreu as sete regras, as cinco áreas, RQ-01…RQ-09, as três seções de
`## Fora de Escopo`, as cinco ambiguidades decididas em 2026-09-22 e cada linha dos cinco Esquemas.
Produziu **8 implementações erradas que passavam pelo conjunto inteiro**, **10 oráculos fracos**,
**6 defeitos de forma** e **9 pares de audiência × superfície sem cruzamento**.

Dos quatro pontos que o conjunto declarava cobertos, **três foram falseados**: a guarda estreitada
(por I1 e I6), o escopo com herança (por I3 — o conjunto cobria *escopar de menos* e não *escopar de
mais*) e a ordem assinatura × recado (por I4). O quarto — prefixo `v` exibido × gravado — resistiu,
exceto pelo eixo "afixo nativo **renderizado**" de RQ-04.

### Fechamento

| # | Achado | Virou |
|---|---|---|
| I1 | a visibilidade decidida **pela rota** passa tudo, porque toda superfície pública do conjunto era tela de login | **M37** + linhas `/admin/password-reset/request` e `/admin/register` em CT-04 |
| I2 | a assinatura emitida só pelo hook escopado nas classes de login some das demais telas públicas | **M39** + linha `/admin/password-reset/request` em CT-01 |
| I3 | escopo na mãe **e** na filha duplica tudo em `/login`; CT-02 contava numa rota só | **M40** + CT-02 virou `Esquema` sobre `/admin/login`, `/login` e `/admin` |
| I4 | a ordem podia vir da ordem de registro do painel e inverter em `/login` | **M42** + CT-10 virou `Esquema` com `/login` |
| I5 | `empty()` no lugar de `filled()`: `"0"` some, `"   "` vira segmento solto | **M41** + linhas `0`, `"   "` e `(nula)` em CT-16 |
| I6 | `auth()->check()` × `filament()->auth()->check()` | **M38**, **lacuna declarada** com o que foi tentado — não há configuração nesta instalação em que divirjam |
| I7 | ninguém assere o **default** do toggle (invariante do `## Fora de Escopo`) | linha nova no checklist, apontando `VersaoNoRodapeTest.php` CT-07 — **já coberto fora**, e agora dito |
| I8 | `© Acme 2026` atravessa os cenários de presença por substring | CT-07 virou oráculo de **igualdade exata** sobre cinco superfícies, ao lado de CT-16 |
| CT-07 | igualdade cruzada não discrimina: duas composições igualmente erradas são idênticas. E o `&` não media escape nenhum, porque o helper faz `html_entity_decode` | reescrito: cada superfície contra o **literal**; o eixo de escape mudou-se inteiro para R7, sobre HTML bruto |
| CT-17 | as três cláusulas eram **vacuamente verdadeiras** com o rodapé ausente | ganhou controle positivo (`kit.version` presente) e contagem exata de segmentos |
| CT-16/CT-18 | `(elemento ausente)` comparado a `""` não distingue ausente de presente-e-vazio — M27 sobrevivia ao cenário que o matava | a asserção passou a ser sobre o **marcador do elemento no HTML** |
| CT-05 | `temRotulo()` é satisfeito por `© Acme`; não asseria **qual** versão do kit | passou a exigir o valor de `kit.version` no segmento, e a ausência de rótulo no segmento da versão do sistema |
| CT-01/04/08/…/12 | *"o rodapé contém"* via `rodapeDe()` é satisfeito pelo **snapshot do Livewire**, onde `app.name` e o recado vão serializados | regra nº 1 do `## Setup Global`: `Então o rodapé contém` significa `assinaturaDoRodape()` |
| CT-19 | `strip_tags` + `html_entity_decode` colapsam a forma escapada e a crua: o oráculo media o helper, não o escape | R7 passou a asserir sobre o **HTML bruto**, e isso está escrito como a razão de R7 ser a única regra que não usa o helper |
| CT-20 | não exigia a assinatura presente: a implementação que a **apaga** ficava verde | ganhou as duas metades, mais a ênfase do Markdown |
| CT-13 | `Dado` não fixava valor de partida; `getPrefixLabel()` pressupõe o mecanismo sem provar que ele renderiza | ganhou o valor de partida, o estado hidratado, o HTML renderizado e a ausência de sufixo — **M44 fechado** |
| CT-12 | requisição + asserção dentro do `Dado` | virou `Esquema` de duas linhas, um `Quando` cada, a linha do visitante sendo o controle |
| dez cenários | abriam rota de login sem fixar `kit.login.unificado`, dependendo de um default que o próprio arquivo declarava não confiável | regra nº 2 do `## Setup Global` |
| RQ-07 | mapeada para R2/R3/R4 e dada por coberta **em silêncio** | declarada **sem cenário**, como RQ-08 e RQ-09 |
| par `/app` autenticado | apontado como não cruzado | **já fechado antes da revisão** — CT-01 ganhou a linha `/app` com a persona `panel_user` enquanto a revisão corria |
| par `/infra/login` × recado | não cruzado | linha nova em CT-11 |
| teto dos textos livres na gravação | nenhum campo é gravado no teto | **lacuna declarada** no checklist, com o limite real (`255` no nome, `50` na versão) escrito para quem for fechá-la |

### Rodada 2 — executada, e ela pagou o próprio custo

O fechamento da rodada 1 criou cenário novo, então a rodada 2 era obrigatória. Ela achou o que a
rodada 1 não viu, inclusive um erro **introduzido pelo fechamento**:

| # | Achado | Virou |
|---|---|---|
| **CT-07 ficou autocontraditório** | a versão da rodada 1 mandava comparar `/admin` e `/admin/login` por igualdade — mas as duas assinaturas **são legitimamente diferentes** (uma tem a versão). O caso ficaria **vermelho contra a implementação correta**, e o desfecho previsível seria alguém afrouxá-lo para presença, matando RQ-05 de vez | já fechado no próprio fechamento da rodada 1: CT-07 virou igualdade contra o **literal por superfície**. A rodada 2 confirmou a leitura e o motivo |
| W2 | assinatura e recado **concatenados no mesmo nó**: a ordem dos bytes fica certa e a "linha adicional abaixo" nunca acontece. CT-10 media offset, não estrutura | **M45** + CT-10 passou a exigir **dois blocos irmãos**, cada um com conteúdo exato |
| W3 | composição memoizada no painel: CT-03 observava só `/admin/login`, e nada dizia que o nome **antigo** sumiu | **M46** + CT-03 observa as duas superfícies e assere a ausência do nome antigo |
| W4 | **esvaziar a versão pelo formulário nunca era testado** — todas as partições de vazio entravam por `config()->set()`. O eixo criação × edição × uso estava fechado só no uso | **M49** + **CT-21**, cenário novo |
| W5 | espelho de W1: escopar assinatura **e** recado nas classes de login deixa registro e recuperação sem rodapé nenhum | já fechado por CT-01 (linha `/admin/password-reset/request`) |
| extra (b) | com o toggle ligado nada fixava a ordem das três partes — e é a adjacência que **RQ-01** pede | **M50** + CT-17 passou a asserir a ordem dos segmentos |
| extra (c) | truncamento (`Str::limit`) do nome na composição passava 20/20 | **M47** + linha de 120 caracteres em CT-16 |
| CT-02 × Q3 | o mundo que **Q3 nomeia** (admin que já digitou `© Nome` no textarea) nunca era montado, e uma implementação que **deduplica** passava — enquanto Q3 citava CT-02 como prova do invariante | **M48** + linha nova em CT-02 com o recado contendo `© Acme` |
| CT-04 × CT-06 | **recorte inconsistente** para a mesma regra: CT-04 media ausência no rodapé, CT-06 no documento inteiro. Versão vazada em `<meta>`, comentário HTML ou topbar passava por CT-04 | CT-04 unificado no recorte forte; **CT-06 fundido em CT-04**, com o ID registrado e não reaproveitado |
| CT-05, CT-12 | toda a regra positiva de R2 e toda a fronteira de R4 viviam em `/admin` | viraram `Esquema` sobre os três painéis |
| CT-13, CT-15 | o prefixo podia estar em outro campo além deste; e CT-15 não afirmava o **estado hidratado** | asserções acrescentadas nos dois |
| vetor dentro do **recado** | nenhum cenário põe marcação perigosa no recado — o único canal público não escapado | **não se aplica**: é comportamento pré-existente e coberto por `LoginSocialGoogleTest.php` (*"renderiza o rodapé como Markdown e descarta HTML cru e link inseguro"*, com `html_input: strip` e `allow_unsafe_links: false`). Esta entrega não o altera |
| tela **autenticada** do layout `simple` (verificação de e-mail, confirmação de senha) | o par em que R1 e R2 mandam **ambas** aparecer, e que nenhum cenário visita | ⚠️ **M51, sem matador** — ver abaixo |

### Rodada 3 — chegou fora de ordem, e o teto foi estourado de propósito

Uma terceira saída do mesmo sub-agente chegou depois do fechamento da rodada 2, e ela revisou um
**snapshot anterior** do arquivo: aponta CT-06 ainda existindo, CT-10 comparando offsets no
documento, CT-17 sem contagem de segmentos, CT-04 com ausência só no recorte, CT-13 sem situação de
partida e CT-07 na forma de igualdade cruzada — tudo já fechado nas rodadas 1 e 2. Registrar isso
importa: **relatório de adversário não é verdade por ser de adversário**, e conferir contra o
estado corrente do arquivo é parte de fechar o achado.

Dos que sobraram, três eram **novos e reais**, e foram fechados apesar de o teto de 2 rodadas já ter
sido atingido — [o gate vence o teto](#): mutante vivo é pior que cenário a mais.

| Achado novo | Virou |
|---|---|
| o campo "ajuda" removendo um `v` inicial no salvamento: quem digita `v2-beta` ou `vNext` tem o dado mutilado, e CT-14 (que digita `2.4.0`) e CT-15 (que não digita nada) ficam os dois verdes | **M52** + CT-14 virou `Esquema` com a linha `v2-beta` |
| vários `Dado` fixavam só a chave que o cenário discute e calavam sobre as outras três, medindo o que o `phpunit.xml` forçou | **regra nº 2** do `## Setup Global` |
| o recado nunca é gravado **pela tela** em cenário nenhum — sempre entra por `config()->set()`, e esta entrega muda com o que ele convive | **lacuna declarada** no checklist de taxonomia |

Também procedem, e ficam registrados sem virar cenário: `temRotulo()` é satisfeito por qualquer
palavra de duas letras (é o oráculo que o conjunto **herda** de `VersaoNoRodapeTest.php`, e trocá-lo
é mexer em contrato de outra wiki); e RQ-05 continua sem discriminar *um ponto de composição* de
*dois pontos com código idêntico* — o que nenhum teste de comportamento consegue fazer, porque as
duas produzem o mesmo observável. O que CT-07 e CT-16/CT-18 garantem é que elas **não divergem**,
que é o que o requisito pede; a unicidade em si é matéria de revisão de código.

**Parado aqui.** Nenhuma das três rodadas indicou que uma regra deveria ser duas — todas trouxeram
eixos faltando dentro das regras existentes. Uma quarta rodada só faria sentido depois de a
implementação existir, e aí o instrumento é `pest --mutate`, não o adversário.

---

## Divergências entre skill e rules do projeto

| Instrução da skill | Rule que vence | Efeito aqui |
|---|---|---|
| `pest --parallel --tia` como padrão | `.ai/rules/testes.md` + `.ai/rules/testes-browser.md` | helpers cruzados **têm** de estar em `tests/Pest.php`, senão `--parallel`/`--tia`/arquivo isolado quebram com `Call to undefined function`. É o que motiva a mudança de `tests/Pest.php` no `## Setup Global` |
| `expect()->toContain($x, $msg)` como asserção de string | `.ai/rules/testes.md` | toda asserção de conteúdo de HTML usa `assertStringContainsString` / `assertStringNotContainsString` |
| `pest --mutate` fecha o ciclo | `.ai/rules/testes.md` + a seção *Pest 5* da `feature-wiki` | no Windows o plugin dá **100 % falso**; o score só vale pelo lançador `.cmd` e com `Duration` plausível e sobreviventes listados |
