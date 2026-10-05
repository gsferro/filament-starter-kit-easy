# Casos de Teste — feat/logo-dark-mode: Suporte a logo dark / light com unificação de campo

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **não disparada** — nenhuma área com Impacto 3 nem perfil completo.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Resolução por variante (tenant × kit × modo) | 3 | 2 | 6 | padrão |
| Formulários de identidade (settings + organização) | 2 | 2 | 4 | padrão |
| Markup da mídia na lock-screen | 2 | 1 | 2 | mínimo |
| Marca no topo (login/topbar dos painéis) | 1 | 1 | 1 | mínimo |
| Documentação + assets | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP, tabela de decisão, `Esquema do Cenário`
- Cenários: 20 · Regras: 9 · Mutantes previstos: 25 · Sem matador: 1
<!-- derivado do arquivo por grep -c; recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | coluna `logo_dark` em `tenants`; 2 settings novas no grupo `kit`; 1 override de view; helpers `logoEscura()`/`unificaLogo()`/`urlDaLogoEscura()` | CT-01…CT-06 |
| F | resolução de URL por variante, validação obrigatório-entre-si, swap CSS por tema, render do alt | CT-07…CT-19 |
| D | caminho de imagem (string/null/órfão), `logo_dark` de **outra** organização (a resolução só lê a organização resolvida — não há lookup novo por id), toggle bool | CT-01…CT-03, CT-16 |
| I | aba Identidade das settings, formulário do TenantResource, lock-screen, login/topbar | CT-05, CT-07…CT-13, CT-20 |
| P | não se aplica além do disk `public` e do CSS compilado do Filament — ambos já cobertos pelos cenários de markup/resolução | — |
| O | admin configura marca unificada/separada; usuário vê logo na lock-screen e no topo | CT-B01 |
| T | não se aplica: resolução por request, sem concorrência nem expiração | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — `logo_dark` da organização persiste e resolve URL pública com guarda de arquivo | resolução (padrão) | RQ-01, P-01, P-03 | EP | CT-01…CT-04 |
| R2 — `unifica_logo_marca` nasce `true` e persiste | formulários (padrão) | RQ-02, P-02 | EP + valor literal | CT-05, CT-06 |
| R3 — marca unificada torna `logo_dark` inerte em toda superfície | resolução (padrão) | RQ-03, D2 | EP | CT-07…CT-09 |
| R4 — em modo separado, `logo` e `logo_dark` são obrigatórios entre si | formulários (padrão) | RQ-04, Adendo 1 (Q2) | tabela de decisão | CT-10…CT-13 |
| R5 — a logo na lock-screen renderiza `contain` em wrapper, sem `fi-auth-media`; a arte preserva `cover` | markup (mínimo) | RQ-05, RQ-07, Adendo 1 (Q1) | EP | CT-14, CT-15 |
| R6 — a variante resolve por variante com override da organização | resolução (padrão) | RQ-06, P-04, D3, Adendo 1 (Q1, Q3) | tabela de decisão | CT-16 |
| R7 — a marca do topo emite a variante escura em modo separado | marca no topo (mínimo) | RQ-06, Adendo 1 (Q3) | EP | CT-17, CT-18 |
| R8 — a `<img>` da logo carrega `alt` com o nome da organização | markup (mínimo) | RQ-07, P-05 | EP | CT-19 |
| R9 — a documentação (pt + en) descreve o recurso e referencia os assets | documentação (mínimo) | RQ-09, RQ-10, Adendo 1 (Q4) | EP | CT-20 |

<!-- RQ fechadas sem cenário: RQ-08 (branch — já executada, processo), RQ-11 (satisfeita por este
     próprio arquivo), RQ-12 (finalização — processo). Nenhuma RQ aberta. -->

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Resolução e render da lock-screen | R1, R5, R6, R8 | Pest feature HTTP | existente — `tests/Kit/IdentidadeVisualTest.php` (`Storage::fake('public')`, sessão `lockscreen`+`tenant_corrente`, `route('lockscreen.app.page')`) | a saída observável é o HTML da rota — HTTP prova markup sem custo de navegador | sessão, 2026-10-03 |
| Aba Identidade (settings do kit) | R2, R4 | componente Livewire/Filament | existente — `Livewire::test(ConfiguracoesDoKit::class)` + `gravarConfiguracao()`/`configuracaoGravada()` (padrão de `tests/Kit/ConfiguracoesDoKitTest.php`) | a regra mora no formulário; componente é a camada mais barata que a falsifica | sessão, 2026-10-03 |
| Formulário da organização | R1 (persistência), R3 (campo inerte) | componente Livewire/Filament | existente — `Livewire::test(EditTenant::class, ['record' => …])` | idem | sessão, 2026-10-03 |
| Marca do topo (login/topbar) | R7 | Pest feature HTTP | existente — GET `/admin/login` + `assertSee`/`assertDontSee` no HTML (o `page.simple` emite `x-filament-panels::logo` — verificado em `vendor/filament/filament/resources/views/components/header/simple.blade.php:$logo:8`) | o observável é o par de `<img>` no HTML — sem JS | sessão, 2026-10-03 |
| Swap por tema | R5, R6 | browser | nova — só o navegador aplica `.dark` ao `<html>` e mede visibilidade computada | presença no HTML ≠ visível; o swap é o coração da feature | sessão, 2026-10-03 |
| Documentação | R9 | Pest feature HTTP | existente — `documentacaoDoKit($idioma)` (helper de `tests/Pest.php`) | asserção de arquivo/markdown | sessão, 2026-10-03 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `urlsDasLogos()`, `logoEscura()`, `unificaLogo()`, `urlDaLogoEscura()` | nomes de método — escolha de implementação | detalhe do cenário (o `Então` fala em URL/HTML, não em retorno de método) |
| `fi-logo`/`fi-logo-light`/`fi-logo-dark` | classes do mecanismo nativo do Filament — o requisito pede a troca por modo, não a classe | detalhe do cenário; o oráculo é a **URL da logo_dark presente/ausente/visível** |
| `method_exists($livewire, 'urlsDasLogos')` | escolha de implementação do override | detalhe do cenário |
| wrapper `<div style="display:flex…">` e `object-fit:contain` | o requisito (RQ-05/RQ-07) literaliza wrapper flex e `contain` — **é cláusula**, vira oráculo | — |
| "a regra obrigatório-entre-si tem cenário por fora do componente" (gate de camada) | a regra vive **no formulário** por desenho — a camada de domínio (settings/`Tenant`) aceita os campos isoladamente; não há oráculo fora da UI que discrimine | justificativa registrada; a gravação por componente cobre o gate de tela de escrita |

**Perguntas geradas pela derivação**: nenhuma — todas as ambiguidades de comportamento foram respondidas no Adendo 1 do `00`.

## Setup Global

### Personas
- `master_global` — `usuarioDoKit('master_global')` (helper de `tests/Pest.php`)

### Fixtures
- `Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme.png')->create()` — organização com logo (estado de fábrica existente, usado por `IdentidadeVisualTest`)
- `gravarConfiguracao('logo', 'kit/logo.png')` — caminho gravado nas settings do kit; `Storage::disk('public')->put('kit/logo.png', '…')` materializa o arquivo
- Lock-screen alcançável: `$this->actingAs(usuarioDoKit('master_global'))` + `session(['lockscreen' => true])` + `session(['tenant_corrente' => $organizacao->getKey()])` + `route('lockscreen.app.page')`

### Fakes
- `Storage::fake('public')` em todo cenário que toca caminho de arquivo — sem ele, `exists()` depende do disco real

### Estratégia de DB
- `RefreshDatabase` do `tests/Pest.php` da suíte `Kit`; `alinharConfiguracoesDoKit()` depois de gravar settings para a config refletir

---

## Regra R1 — `logo_dark` da organização persiste e resolve URL pública com guarda de arquivo

> `RQ-01`, `P-01`, `P-03` · perfil **padrão** · técnica: **EP** (presente+existe / presente+órfão / vazio / mass assignment)

```gherkin
# language: pt
Funcionalidade: Variante escura da logo da organização

  Regra: a organização grava logo_dark e a URL só resolve com o arquivo no disco público

    Cenário: [CT-01] logo_dark com arquivo no disco devolve a URL pública
      Dado uma organização com logo "organizacoes/logos/acme.png" e logo_dark "organizacoes/logos/acme-dark.png"
      E ambos os arquivos existem no disco público
      Quando a URL da variante escura é resolvida
      Então ela aponta para "organizacoes/logos/acme-dark.png" — e não para a logo clara

    Cenário: [CT-02] logo_dark apontando arquivo inexistente devolve vazio
      Dado uma organização com logo_dark "organizacoes/logos/sumiu.png"
      E o arquivo não existe no disco público
      Quando a URL da variante escura é resolvida
      Então ela é vazia — o caminho órfão não vira <img> quebrada

    Cenário: [CT-03] organização sem logo_dark devolve vazio
      Dado uma organização sem logo_dark
      Quando a URL da variante escura é resolvida
      Então ela é vazia

    Cenário: [CT-04] logo_dark é atributo atribuível em massa
      Dado nenhuma organização
      Quando o sistema cria uma organização com logo_dark "organizacoes/logos/acme-dark.png"
      Então o banco guarda "organizacoes/logos/acme-dark.png" em logo_dark
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `urlDaLogoEscura()` sem a guarda `Storage::exists()` — devolve `asset()` do caminho órfão | CT-02 | caminho órfão → `null`; o mutante devolve a URL quebrada |
| M2 | resolve `$this->logo` em vez de `$this->logo_dark` | CT-01 | com `logo` e `logo_dark` diferentes, o esperado é o caminho **dark**; o mutante devolve o da clara |
| M3 | `logo_dark` fora do `$fillable` — `create()` descarta em silêncio | CT-04 | `assertDatabaseHas('tenants', logo_dark: …)` falha com coluna vazia |
| M4 | `Storage::disk('public')->url()` no lugar de `asset()` | ~~⚠️ sem matador~~ **resolvido em implementação** — o browser test deu o `Host` divergente que o console não dá (`127.0.0.1:porta` vs `localhost` do `APP_URL`), a `<img>` quebrou de verdade e `doDisco()` foi corrigido para `asset()` upstream. O mutante **voltou** e é quem causava o defeito | CT-B01 (evidência visual — screenshot com `<img>` quebrada) |

---

## Regra R2 — `unifica_logo_marca` nasce `true` e persiste

> `RQ-02`, `P-02` · perfil **padrão** · técnica: **EP** + **valor literal** (o default `true` é cláusula do requisito)

```gherkin
# language: pt
Funcionalidade: Toggle de unificação da marca

  Regra: a instalação nasce unificada (true) e o valor gravado persiste nas settings

    Cenário: [CT-05] instalação nova tem a marca unificada
      Dado as settings recém-migradas, sem ajuste do teste
      Quando o valor de "unifica_logo_marca" é lido
      Então ele é true — o requisito fixa o default

    Cenário: [CT-06] desligar o toggle na página persiste e alinha a config
      Dado o administrador na página "configurações da aplicação"
      Quando ele desliga "unifica_logo_marca" e grava
      Então "unifica_logo_marca" fica false nas settings
      E "kit.identidade.unifica_logo_marca" lê false na config
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M5 | default `false` (settings migration ou config) | CT-05 | o lido é `true`; o mutante devolve `false` |
| M6 | propriedade declarada mas fora do `mapaDeConfiguracao()` — settings e config desalinham | CT-06 | `config('kit.identidade.unifica_logo_marca')` é `false`; o mutante deixa `true` |
| M7 | chave fora de `identidade.*` (ex.: `kit.unifica_logo_marca`) | CT-06 | a asserção lê a chave `kit.identidade.*` — o mutante grava/lê outra chave |

---

## Regra R3 — marca unificada torna `logo_dark` inerte em toda superfície

> `RQ-03`, `D2` · perfil **padrão** · técnica: **EP** (unificado × separado)

```gherkin
# language: pt
Funcionalidade: Campo inerte quando a marca é unificada

  Regra: com unifica_logo_marca ligado, logo_dark não é exibida nem usada, mesmo gravada

    Cenário: [CT-07] logo_dark gravada é ignorada com a marca unificada
      Dado a marca unificada e uma logo_dark válida configurada no kit
      Quando a variante escura da marca do kit é resolvida
      Então ela é vazia — logo_dark não vale no modo unificado

    Cenário: [CT-08] o campo logo_dark some da página de settings no modo unificado
      Dado a marca unificada
      Quando a página "configurações da aplicação" carrega a aba Identidade
      Então o campo "logo_dark" não é exibido

    Cenário: [CT-09] o campo logo_dark some do formulário da organização no modo unificado
      Dado a marca unificada
      Quando a página de edição de uma organização carrega
      Então o campo "logo_dark" não é exibido
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M8 | a resolução ignora o toggle — devolve a logo_dark mesmo unificada | CT-07 | com unificado, esperado `null`; o mutante devolve a URL |
| M9 | `->visible()` ausente ou com a condição invertida na página de settings | CT-08 | `logo_dark` escondido é o esperado; o mutante o exibe (ou o esconde quando separado — pego no CT-10) |
| M10 | campo `logo_dark` sempre visível no formulário da organização | CT-09 | esperado escondido; o mutante exibe |

---

## Regra R4 — em modo separado, `logo` e `logo_dark` são obrigatórios entre si

> `RQ-04`, Adendo 1 (Q2) · perfil **padrão** · técnica: **tabela de decisão** (logo × logo_dark, modo separado) + `Esquema do Cenário` para a linha "vazio válido"

```gherkin
# language: pt
Funcionalidade: Obrigatoriedade cruzada dos campos de logo

  Regra: separado exige o par — enviar um sem o outro falha; os dois vazios salvam

    Esquema do Cenário: [CT-10] grava sem exigir o par quando a regra não se aplica
      Dado o administrador na aba Identidade com o modo <modo>
      E logo "<logo>" e logo_dark "<logo_dark>" preenchidas como indicado
      Quando ele grava
      Então a gravação é aceita — sem erro de required nos dois campos

      Exemplos:
        | modo      | logo     | logo_dark |
        | unificado | presente | vazia     |
        | separado  | vazia    | vazia     |

    Cenário: [CT-11] só a logo clara preenchida é recusada
      Dado o administrador na aba Identidade com a marca separada
      E somente "logo" preenchida, "logo_dark" vazia
      Quando ele grava
      Então a gravação é recusada com erro de required em "logo_dark"
      E nenhum valor novo é persistido

    Cenário: [CT-12] só a logo escura preenchida é recusada
      Dado o administrador na aba Identidade com a marca separada
      E somente "logo_dark" preenchida, "logo" vazia
      Quando ele grava
      Então a gravação é recusada com erro de required em "logo"
      E nenhum valor novo é persistido

    Cenário: [CT-13] o par completo grava as duas logos
      Dado o administrador na aba Identidade com a marca separada
      E "logo" e "logo_dark" preenchidas — o campo logo_dark exibido
      Quando ele grava
      Então as duas entradas persistem nas settings
```

> Estouro do teto (4 cenários em regra de perfil padrão): as 4 linhas da tabela
> logo × logo_dark são células distintas — cada uma mata um mutante que as outras deixam vivo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | `logo_dark` com `required()` incondicional | CT-10 (linha "separado, vazia, vazia") | o esperado é salvar; o mutante reprova por `logo_dark` vazia |
| M12 | regra de um lado só — `logo` exigida sem a recíproca em `logo_dark` | CT-11 | só `logo` → esperado erro em `logo_dark`; o mutante salva |
| M13 | regra exigindo o par **sempre** no modo separado (inclusive ambos vazios) | CT-10 (linha "separado, vazia, vazia") | o esperado é salvar; o mutante reprova |
| M14 | validação indiferente ao toggle — exige o par também no modo unificado | CT-10 (linha "unificado, presente, vazia") | o esperado é salvar; o mutante reprova por `logo_dark` |

---

## Regra R5 — a logo na lock-screen renderiza `contain` em wrapper, sem `fi-auth-media`; a arte preserva `cover`

> `RQ-05`, `RQ-07`, Adendo 1 (Q1) · perfil **mínimo** · técnica: **EP** (mídia = logo / mídia = arte)

```gherkin
# language: pt
Funcionalidade: Markup da mídia da lock-screen

  Regra: a logo troca de markup — wrapper centrado e contain; a arte continua cover

    Cenário: [CT-14] a logo renderiza contida e sem a classe da arte
      Dado uma organização com logo e a sessão bloqueada nessa organização
      Quando a tela de bloqueio renderiza
      Então a mídia contém a logo dentro de um contêiner centralizado com "object-fit:contain"
      E a classe "fi-auth-media" não aparece na imagem da logo

    Cenário: [CT-15] sem logo, a arte mantém o markup original
      Dado a sessão bloqueada numa organização sem logo e sem logo do kit
      Quando a tela de bloqueio renderiza
      Então a mídia é a arte padrão com a classe "fi-auth-media"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | a `<img>` da logo mantém `fi-auth-media` (o override esqueceu de remover a classe) | CT-14 | `fi-auth-media` ausente da imagem da logo; o mutante a emite |
| M16 | `object-fit:cover` no lugar de `contain` (classe ou estilo) | CT-14 | o markup exige `contain`; o mutante emite `cover`/omite |
| M17 | o bloco de logo aplica-se também à arte de fallback — o override decide pela página, não pela mídia | CT-15 | com arte, `fi-auth-media` deve estar presente; o mutante a remove e coloca `contain` na arte |

---

## Regra R6 — a variante resolve por variante com override da organização

> `RQ-06`, `D3`, Adendo 1 (Q1, Q3) · perfil **padrão** · técnica: **tabela de decisão** (modo × logo_dark da organização × logo_dark do kit)

```gherkin
# language: pt
Funcionalidade: Resolução das variantes de logo

  Regra: cada variante cai para a da instalação; em modo unificado só existe a clara

    Esquema do Cenário: [CT-16] a lock-screen exibe o par resolvido
      Dado a sessão bloqueada na organização
      E o modo <modo>, com a organização tendo logo "<org_logo>" e logo_dark "<org_dark>"
      E o kit tendo logo "<kit_logo>" e logo_dark "<kit_dark>"
      E todos os caminhos não-vazios existem no disco público
      Quando a tela de bloqueio renderiza
      Então a variante clara exibida é "<clara>"
      E a variante escura exibida é "<escura>"

      Exemplos:
        | modo      | org_logo | org_dark | kit_logo | kit_dark | clara | escura  |
        | unificado | A        | B        | C        | D        | A     | nenhuma |
        | separado  | A        | B        | C        | D        | A     | B       |
        | separado  | A        | vazia    | C        | D        | A     | D       |
        | separado  | vazia    | vazia    | C        | D        | C     | D       |
        | separado  | A        | vazia    | C        | vazia    | A     | nenhuma |
```

> "Nenhuma" significa que o HTML **não** traz uma segunda imagem de logo — a clara serve aos dois
> modos (a mesma degradação que o comportamento nativo do Filament).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | a escura cai para a `logo` clara da organização quando `logo_dark` está vazia (`?? $tenant->logo`) | CT-16 (linha `A, vazia, C, D`) | esperado `D` (a dark do kit); o mutante renderiza `A` |
| M19 | a escura resolve mesmo com a marca unificada | CT-16 (linha `unificado`) | esperado "nenhuma"; o mutante renderiza `B` |
| M20 | a clara não cai para a logo do kit — organização sem logo mostra a arte | CT-16 (linha `vazia, vazia, C, D`) | esperado `C` como mídia; o mutante cai na arte |
| M21 | ordem invertida — a variante do kit ganha da da organização | CT-16 (linha `separado, A, B, C, D`) | esperado `B`; o mutante renderiza `D` |

---

## Regra R7 — a marca do topo emite a variante escura em modo separado

> `RQ-06`, Adendo 1 (Q3) · perfil **mínimo** · técnica: **EP** (separado × unificado)

```gherkin
# language: pt
Funcionalidade: Variante escura da marca nas páginas

  Regra: a marca do topo (login e topbar) carrega a logo_dark no modo separado

    Cenário: [CT-17] a página de login traz a logo_dark quando a marca é separada
      Dado a marca separada com logo e logo_dark configuradas no kit
      Quando a página de login do painel admin renderiza
      Então o HTML traz a variante escura da marca apontando para a logo_dark

    Cenário: [CT-18] a página de login não traz logo_dark no modo unificado
      Dado a marca unificada e uma logo_dark gravada no kit
      Quando a página de login do painel admin renderiza
      Então o HTML não traz nenhuma imagem da logo_dark
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M22 | `darkModeBrandLogo` não registrado nos providers | CT-17 | a URL da logo_dark ausente do HTML; o mutante omite a variante |
| M23 | `darkModeBrandLogo` ignora o toggle — devolve a escura mesmo unificada | CT-18 | com unificado, a URL da logo_dark ausente; o mutante a emite |

---

## Regra R8 — a `<img>` da logo carrega `alt` com o nome da organização

> `RQ-07`, `P-05` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt
Funcionalidade: Acessibilidade da imagem da logo

  Regra: a imagem da logo nomeia a organização no alt

    Cenário: [CT-19] o alt da logo carrega o nome da organização
      Dado a organização "Acme Brasil" com logo e a sessão bloqueada nela
      Quando a tela de bloqueio renderiza
      Então a imagem da logo declara "Acme Brasil" no alt
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M24 | `alt` vazio ou genérico ("logo") na imagem da marca | CT-19 | o alt contém o nome da organização; o mutante emite vazio/genérico |

---

## Regra R9 — a documentação (pt + en) descreve o recurso e referencia os assets

> `RQ-09`, `RQ-10`, Adendo 1 (Q4) · perfil **mínimo** · técnica: **EP** (idioma × seção × asset)

```gherkin
# language: pt
Funcionalidade: Documentação da marca light/dark

  Regra: os dois idiomas explicam o toggle e os campos, com screenshots existentes

    Esquema do Cenário: [CT-20] a página de configurações documenta o recurso
      Dado a documentação do kit em "<idioma>"
      Quando a página "configurações do kit" é lida
      Então ela contém a seção sobre logo para modo claro e escuro
      E referencia "logo-tema-claro.png" e "logo-tema-escuro.png"
      E os dois arquivos existem em "art/" *(alterado em implementação — convenção real das docs)*

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | seção/imagens escritas num idioma só, ou referência a arquivo que não existe | CT-20 | por idioma: seção + 2 referências + 2 arquivos presentes; o mutante falha no idioma ou asset faltante |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a resolução lê somente a organização já resolvida pela sessão (`tenant_corrente`) — não há lookup novo por id; a guarda do painel `app` é a existente, coberta por `IdentidadeVisualTest` | — |
| Autorização exercida na ação | não se aplica: nenhuma rota/ação nova | — |
| Idempotência | não se aplica: gravação de settings/`Tenant` é `update` idempotente | — |
| Concorrência | não se aplica: leitura por request, sem contador/limite | — |
| **Fronteira no ponto de entrada** (gravação) | CT-10…CT-13 (obrigatório-entre-si); mimes/tamanho/SVG herdados de `arquivo()` e do `acceptedFileTypes` do TenantForm — a regra nova é só a cruzada | Aba Identidade |
| **Domínio condicionado** (tipo × valor) | CT-07…CT-10 (a validade/exibição de `logo_dark` é condicionada pelo toggle) | Aba Identidade |
| **Estado × operação de escrita** | não se aplica: nenhuma entidade removível/desativável nova | — |
| Ausente ≠ null ≠ vazio | CT-02, CT-03, CT-16 (`logo_dark` vazio ≠ ausente: o órfão degrada para a do kit — linha `A, vazia, C, D`) | Resolução e render |
| Paginação / ordenação | não se aplica | — |
| Timezone / DST | não se aplica | — |
| Unicode / limite de varchar | não se aplica: caminho de arquivo com as mesmas validações do campo `logo` existente | — |
| Unicidade + soft delete | não se aplica: coluna sem unique, tenant sem soft delete | — |
| CRUD combinado | CT-04 (create), CT-13 (edit/save por componente) | Formulário da organização |
| Mass assignment | CT-04 — `logo_dark` no `$fillable`, por tabela `tenants` (única persistida nova) | Formulário da organização |
| Upload | CT-13 (gravação do par); tipos recusados herdados — `acceptedFileTypes`/`mimes` são os mesmos do campo `logo` existente | Aba Identidade |
| Precisão monetária | não se aplica | — |
| **Superfície Livewire** (método público, prop pública, estado do framework) | o método público novo (`urlsDasLogos`) não recebe argumentos e só devolve o que a própria página já renderiza — chamá-lo fora do domínio é impossível por construção (sem parâmetros) | — |
| **Estado do framework usado sem validar** | `session('tenant_corrente')` já tem guarda de tipo existente (is_int/is_string) — inalterada | — |
| **IDOR por entidade** (`tenants`) | não se aplica: a feature não cria acesso a organização por id — a resolução é a sessão existente | — |
| **Escopo com discriminante nulo** | CT-16 (linha `vazia, vazia, C, D`): organização "nula" para a logo fecha o escopo para o kit — não abre para a arte | Resolução e render |
| **Saída do estado de erro** | CT-11, CT-12: a recusa de validação mantém o administrador na página com o erro assinalado (comportamento nativo de `assertHasFormErrors`) | Aba Identidade |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | URL da logo_dark com arquivo no disco | R1 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M1, M2 |
| CT-02 | logo_dark órfã devolve vazio | R1 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M1 |
| CT-03 | sem logo_dark devolve vazio | R1 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | — |
| CT-04 | logo_dark em `create()` (fillable) | R1 | EP | Formulário da organização | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M3 |
| CT-05 | default `unifica_logo_marca=true` | R2 | EP+literal | Aba Identidade | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M5 |
| CT-06 | toggle gravado persiste e alinha config | R2 | EP | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M6, M7 |
| CT-07 | logoDark ignorada no modo unificado | R3 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M8 |
| CT-08 | campo logo_dark oculto (settings) | R3 | EP | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M9 |
| CT-09 | campo logo_dark oculto (tenant) | R3 | EP | Formulário da organização | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M10 |
| CT-10 | esquema: grava sem exigir o par | R4 | tabela decisão | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M11, M13, M14 |
| CT-11 | só logo → required em logo_dark | R4 | tabela decisão | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M12 |
| CT-12 | só logo_dark → required em logo | R4 | tabela decisão | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | M12 |
| CT-13 | par completo grava | R4 | tabela decisão | Aba Identidade | componente Livewire/Filament | `tests/Kit/LogoDarkModeTest.php` | gate gravação |
| CT-14 | logo com contain, sem fi-auth-media | R5 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M15, M16 |
| CT-15 | arte preserva fi-auth-media | R5 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M17 |
| CT-16 | esquema: resolução por variante | R6 | tabela decisão | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M18…M21 |
| CT-17 | login traz logo_dark separado | R7 | EP | Marca do topo | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M22 |
| CT-18 | login não traz logo_dark unificado | R7 | EP | Marca do topo | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M23 |
| CT-19 | alt com nome da organização | R8 | EP | Resolução e render | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M24 |
| CT-20 | docs pt/en + assets | R9 | EP | Documentação | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M25 |
| CT-B01 | swap visual por tema + screenshots | R5, R6 | EP | Swap por tema | browser | `tests/Browser/LogoDarkModeTest.php` | ver `05` |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B separado para a logo do topo/login em dark | o swap da topbar é o mecanismo **nativo** do Filament (`logo.blade.php`), já coberto pelo upstream; CT-17/18 provam a configuração |
| Cenário de SVG recusado no campo `logo_dark` | `acceptedFileTypes`/`mimes` são a mesma lista do `logo` existente — testar a lista de novo testaria o framework, não a feature |
| Toggle ligado/desligado em sequência (2-switch) | sem ciclo de vida de estado — o toggle não é status de entidade; a inércia do campo já é CT-07 |

references lidas: `template-04.md` (escrita), `template-05.md` (05), `pest-plugin-browser.md` (05), `taxonomia-de-defeito.md` (passo 4)
