# Casos de Teste — ocultar-seletor-de-organizacao-unica

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Decisão de exibição do seletor (contagem de organizações acessíveis) | 2 | 2 | 4 | padrão |
| Campo na tela de configurações (visibilidade, gravação) | 2 | 1 | 2 | mínimo — escalado a padrão pela regra de tela de escrita |
| Arte de documentação (thumbs/GIF) | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP, BVA 2-valores (borda 1↔2 organizações), tabela de decisão (opção × contagem), superfície Livewire
- Cenários: 11 · Regras: 5 · Mutantes previstos: 11 · Sem matador: 0

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | propriedade nova em `ConfiguracoesDoKit`, migration de settings, chave `kit.tenancy.*`, Closure no `tenantMenu()`, toggle na aba Kit, entradas em `KitArte` (CLIPES/IMAGENS) | CT-01, CT-11 |
| F | predicado "o seletor aparece?" decidido por opção × contagem de organizações acessíveis | CT-03, CT-04, CT-05 |
| D | cardinalidade do conjunto de organizações acessíveis: 0 (defesa), 1, 2+; tenant `ativo=false` não conta; `master_global` conta por ativas | CT-03, CT-06, CT-07 |
| I | toggle na `/admin/configuracoes-da-aplicacao`; render da sidebar/topbar do `/app`; `kit:arte` | CT-01, CT-04, CT-05, CT-08, CT-09, CT-10 |
| P | não se aplica: sem dependência de plataforma além do banco/Filament já exigidos pelo kit | — |
| O | quem instala liga/desliga o interruptor; `master_global` e `admin_app` são os perfis que chegam ao /app | CT-02, CT-07 |
| T | não se aplica: decisão avaliada por request no render, sem cache, agendamento ou validade | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — A opção existe na tela de configurações, grava como booleano e governa a exibição; default desligado preserva o comportamento de sempre | campo na tela (padrão) | RQ-01, P-02, Q2 | EP + gravação por componente | CT-01, CT-02, CT-08 |
| R2 — A exibição depende da opção e da contagem de organizações acessíveis: ligada e ≤1 esconde; ligada e ≥2 mostra; desligada sempre mostra | decisão (padrão) | RQ-02, RQ-04, P-01, P-03 | tabela de decisão + BVA (borda 1↔2) | CT-03, CT-04, CT-05 |
| R3 — "Acessível" é a lista que o próprio seletor usa: só organizações **ativas**, e `master_global` conta por todas as ativas | decisão (padrão) | RQ-03, Q1, Q3 | EP (ativo × inativo, papel comum × master) | CT-06, CT-07 |
| R4 — Sem multi-organização ligada, o interruptor não aparece na tela | campo na tela (padrão) | P-04 | EP | CT-09, CT-10 |
| R5 — O settings novo tem arte demonstrativa ligada à captura | arte (mínimo) | RQ-05 | varredura | CT-11 |

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Predicado do seletor | R2, R3 | Pest feature HTTP | nova — `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | a decisão mora num predicado que lê settings + usuário + tenants; chamar direto é a camada mais barata que falsifica | sessão, 2026-10-08 |
| Seletor renderizado | R2 | componente Livewire/Filament | existente — `Livewire::test(Sidebar::class)->html()` em `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | prova a cadeia inteira (Closure → evaluate → blade → HTML) sem Playwright | sessão, 2026-10-08 |
| Campo na tela de configurações | R1, R4 | componente Livewire/Filament | existente — `livewire(ConfiguracoesDoKit::class)` de `tests/Kit/ConfiguracoesDoKitTelaTest.php` | gravação e visibilidade de campo são costura de componente | sessão, 2026-10-08 |
| Arte | R5 | Pest feature HTTP | existente — varredura de `tests/Kit/KitArteTest.php` (CT-50) | o defeito é a declaração solta, e o oráculo é a conferência arquivo × arquivo | sessão, 2026-10-08 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `SeletorDeOrganizacao::visivel()`, `tenantMenu()`, nomes de chave/propriedade | escolha de implementação | detalhe do cenário |
| `fi-tenant-menu` (classe do markup do vendor) | o `Então` afirma "o seletor não aparece"; a classe é só o marcador que o materializa | detalhe do cenário |
| Rótulo e helper text do toggle | o requisito não determina o texto | detalhe do cenário |
| Contagem de zero organizações acessíveis | o texto não diz o que acontece com zero — mas sem organização acessível o `/app/{tenant}` não resolve tenant nenhum (`canAccessTenant`/`getUserDefaultTenant` negam antes de o menu renderizar); o ramo zero existe como defesa no predicado e entra como linha do esquema, não como pergunta | linha "0" do Esquema CT-03 |

**Perguntas geradas pela derivação**: nenhuma — o único ramo não escrito (zero acessíveis) é inalcançável na superfície observável e fica coberto como defesa no predicado.

## Setup Global

### Personas
- `admin_app` com uma organização — `usuarioComPapel('admin_app', $acme)` + `noPainelDa($acme)`
- `admin_app` com duas organizações — idem + `papelNaOrganizacao($usuario, 'panel_user', $outra)`
- `master_global` — `usuarioComPapel('master_global')` (enxerga todas as ativas, sem vínculo)

### Fixtures
- `tenant('Acme', 'acme')`, `tenant('Globex', 'globex')`, `tenant('Inativa', 'inativa', ativo: false)`
- `gravarConfiguracao('ocultar_seletor_unico', true)` + `alinharConfiguracoesDoKit()` — a cadeia real settings → config (`tests/Pest.php:gravarConfiguracao():349`, `Pest.php:alinharConfiguracoesDoKit():370`)

### Estratégia de DB
- `Tests\TenancyTestCase` (liga `permission.teams`) para tudo que toca tenant; `Tests\Kit\TestCase` (a suíte padrão) para o cenário sem multi-organização

---

## Regra R1 — A opção existe na tela de configurações e governa a exibição

> `RQ-01`, `P-02` · perfil **padrão** · técnica: **EP** (ligado/desligado) + gravação por componente

```gherkin
# language: pt
Funcionalidade: Ocultar o seletor de organização quando houver uma só

  Regra: O interruptor nas configurações da aplicação decide a exibição

    Cenário: [CT-01] ligar o interruptor na tela de configurações esconde o seletor
      Dado o administrador da instalação com uma única organização acessível
      E ele abre a tela de configurações da aplicação
      Quando ele liga o interruptor de ocultar o seletor e grava
      Então o seletor de organização não aparece mais no painel de negócio

    Cenário: [CT-02] a opção nasce desligada e o seletor continua aparecendo
      Dado uma instalação com a multi-organização ligada e a opção recém-criada
      E o usuário tem uma única organização acessível
      Quando o painel de negócio é renderizado
      Então o seletor de organização aparece

    Cenário: [CT-08] um valor que não é booleano enviado ao campo não corrompe a configuração
      Dado o administrador da instalação na tela de configurações
      Quando o campo do interruptor recebe um valor de tipo errado e a tela é gravada
      Então a configuração persistida é booleana
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | toggle grava a propriedade mas o mapa aponta a chave errada (a config não muda) | CT-01 | depois de gravar ligado, `visivel()` devolve falso; com o mapa errado segue verdadeiro |
| M2 | migration semeia `true` em vez do default desligado | CT-02 | com a opção recém-criada e sem escrita manual, o seletor aparece; o mutante o esconde |
| M6 | predicado lê `config()` direto do env sem passar pelo settings | CT-01 | gravar na tela não muda nada sob o mutante; `visivel()` segue verdadeiro |

---

## Regra R2 — A exibição depende da opção e da contagem de organizações acessíveis

> `RQ-02`, `RQ-04`, `P-01`, `P-03` · perfil **padrão** · técnica: **tabela de decisão** (opção × contagem) + **BVA** (borda 1↔2, incremento 1 organização)

```gherkin
    Esquema do Cenário: [CT-03] o seletor só aparece quando há troca possível
      Dado a opção está "<opcao>" e o usuário tem acesso a <quantidade> organizações ativas
      Quando a exibição do seletor é avaliada
      Então o seletor está "<estado>"

      Exemplos:
        | opcao    | quantidade | estado  | # borda/célula      |
        | desligada | 1         | visível | desligada preserva   |
        | ligada   | 0          | oculto  | defesa (0 acessível) |
        | ligada   | 1          | oculto  | borda−1              |
        | ligada   | 2          | visível | borda                |
        | ligada   | 3          | visível | borda+1              |

    Cenário: [CT-04] ligada e uma organização: o bloco inteiro some da barra lateral
      Dado a opção ligada e o usuário com uma única organização acessível
      Quando a barra lateral do painel de negócio é renderizada
      Então o bloco do seletor de organização não existe no HTML

    Cenário: [CT-05] ligada e duas organizações: o bloco aparece
      Dado a opção ligada e o usuário com duas organizações acessíveis
      Quando a barra lateral do painel de negócio é renderizada
      Então o bloco do seletor de organização existe no HTML
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M3 | `> 1` trocado por `>= 1` (seletor visível com uma só) | CT-03 | linha `ligada × 1`: esperado "oculto"; o mutante devolve "visível" |
| M4 | `> 1` trocado por `> 2` (esconde com duas) | CT-03 | linha `ligada × 2`: esperado "visível"; o mutante devolve "oculto" |
| M5 | a Closure devolve `true` fixo / a condição é ignorada | CT-04 | o HTML da sidebar não contém `fi-tenant-menu`; o mutante o renderiza |
| M9 | esconde só o dropdown (`tenantSwitcher`), mantendo o bloco com avatar e nome | CT-04 | `fi-tenant-menu` ausente — o mutante deixa o bloco redundante visível |

---

## Regra R3 — "Acessível" é a mesma lista que o seletor usa

> `RQ-03`, `Q1`, `Q3` · perfil **padrão** · técnica: **EP** (ativo × inativo; papel comum × master)

```gherkin
    Cenário: [CT-06] a organização inativa não entra na contagem
      Dado a opção ligada e o usuário vinculado a uma organização ativa e a uma inativa
      Quando a exibição do seletor é avaliada
      Então o seletor está "oculto"

    Esquema do Cenário: [CT-07] o master_global conta por todas as organizações ativas
      Dado a opção ligada, o master_global sem vínculo explícito e <quantidade> organizações ativas existentes
      Quando a exibição do seletor é avaliada
      Então o seletor está "<estado>"

      Exemplos:
        | quantidade | estado  |
        | 1          | oculto  |
        | 2          | visível |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 | contagem por `tenants()->count()` na pivot, sem o filtro `ativo` | CT-06 | com 1 ativa + 1 inativa o esperado é "oculto"; o mutante conta 2 e mostra |
| M8 | contagem por vínculo que ignora o acesso implícito do `master_global` | CT-07 | linha `2`: master sem vínculo esperado "visível"; o mutante conta 0 e esconde |

---

## Regra R4 — Sem multi-organização ligada, o interruptor não aparece

> `P-04` · perfil **padrão** · técnica: **EP**

```gherkin
    Cenário: [CT-09] instalação sem multi-organização não mostra o interruptor
      Dado uma instalação com a multi-organização desligada
      Quando o administrador abre a tela de configurações
      Então o interruptor de ocultar o seletor não aparece na aba Kit

    Cenário: [CT-10] com a multi-organização ligada, o interruptor aparece
      Dado uma instalação com a multi-organização ligada
      Quando o administrador abre a tela de configurações
      Então o interruptor de ocultar o seletor aparece na aba Kit
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | toggle sem `->visible()`, sempre presente | CT-09 | sem tenancy o campo está oculto no formulário; o mutante o exibe inerte |

---

## Regra R5 — O settings novo tem arte demonstrativa ligada à captura

> `RQ-05` · perfil **mínimo** · técnica: **varredura**

```gherkin
    Cenário: [CT-11] cada quadro declarado do clipe nasce de uma captura
      Dado o clipe do seletor de organização declarado em KitArte
      Quando a definição é confrontada com os testes de captura
      Então cada quadro do clipe tem o nome de arquivo correspondente em CapturaDeArteTest
      E o quadro da tela de configurações está entre as imagens publicadas
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | quadro declarado em `CLIPES` sem captura que o produza (GIF nasce sem o quadro) | CT-11 | a varredura cruza `CLIPES` × nomes em `CapturaDeArteTest`; o mutante deixa um quadro órfão |

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota/ação recebe `{id}` de recurso | — |
| Autorização exercida na ação | não se aplica: a feature não cria ação autorizada — a tela já existente segue sob `View:ConfiguracoesDoKit` e o seletor é decisão de UI (P-03), não de permissão | — |
| Idempotência (ancorada no agregado) | não se aplica: gravar settings é atribuição de propriedade idempotente (mesma chave, mesmo payload) — não cria linha nova nem efeito duplicável | — |
| Concorrência | não se aplica: leitura de config por request, sem contador ou saldo | — |
| **Fronteira no ponto de entrada** (gravação) | CT-08 | Campo na tela de configurações |
| **Domínio condicionado** | CT-06 (ativo × inativo condiciona a contagem) | Predicado do seletor |
| **Estado × operação de escrita** | não se aplica: sem entidade com ciclo de vida | — |
| Ausente ≠ null ≠ vazio | não se aplica: campo booleano de domínio fechado, sem opcional | — |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: sem data/hora | — |
| Unicode / limite de varchar | não se aplica: sem texto livre | — |
| Unicidade + soft delete | não se aplica: sem entidade nova | — |
| CRUD combinado | não se aplica: sem CRUD | — |
| Mass assignment | não se aplica: o formulário da tela tem schema fechado e a gravação é via `Settings`, sem `fill` em model | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem dinheiro | — |
| **Superfície Livewire** (valor fora do domínio, tipo errado) | CT-08 | Campo na tela de configurações |
| **Estado do framework usado sem validar** | não se aplica: nenhum `$filters`/`$pageFilters`/parse consumido | — |
| **IDOR por entidade** | não se aplica: nenhuma tabela persistida nova (a linha de `settings` é do pacote, já coberta) | — |
| **Escopo com discriminante nulo** | CT-03, linha `0` (sem organização acessível fecha: seletor oculto — e inalcançável na prática porque `/app` não resolve tenant sem nenhuma acessível) | Predicado do seletor |
| **Saída do estado de erro** | não se aplica: nenhum `Então` é 4xx/redirect | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | ligar o interruptor na tela esconde o seletor | R1 | gravação por componente | Campo na tela | componente Livewire/Filament | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M1, M6 |
| CT-02 | opção nasce desligada | R1 | EP | Predicado do seletor | Pest feature HTTP | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M2 |
| CT-03 | esquema opção × contagem (5 linhas) | R2 | tabela de decisão + BVA | Predicado do seletor | Pest feature HTTP | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M3, M4 |
| CT-04 | bloco inteiro some da sidebar | R2 | — | Seletor renderizado | componente Livewire/Filament | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M5, M9 |
| CT-05 | bloco aparece com duas | R2 | — | Seletor renderizado | componente Livewire/Filament | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M4, M5 |
| CT-06 | inativa não conta | R3 | EP | Predicado do seletor | Pest feature HTTP | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M7 |
| CT-07 | master_global por ativas (2 linhas) | R3 | EP | Predicado do seletor | Pest feature HTTP | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | M8 |
| CT-08 | tipo errado no campo | R1 | superfície Livewire | Campo na tela | componente Livewire/Filament | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | — (cobertura de fronteira) |
| CT-09 | sem multi-organização, interruptor ausente | R4 | EP | Campo na tela | componente Livewire/Filament | `tests/Kit/SeletorDeOrganizacaoCampoTest.php` | M10 |
| CT-10 | com multi-organização, interruptor presente | R4 | EP | Campo na tela | componente Livewire/Filament | `tests/Tenancy/SeletorDeOrganizacaoTenancyTest.php` | — (par de CT-09) |
| CT-11 | quadros do clipe ligados às capturas | R5 | varredura | Arte | Pest feature HTTP | `tests/Kit/SeletorDeOrganizacaoArteTest.php` | M11 |

## Sem CT-B

- Motivo: presença/ausência do bloco é decisão do HTML renderizado, provada pela costura de componente (`Livewire::test(Sidebar::class)->html()`) — nenhum cenário afirma sobre JS, pixel, cor ou acessibilidade. Os testes de captura de arte (`CapturaDeArteTest`, grupo `browser`,`art`) são o **entregável** do RQ-05, não oráculo de comportamento — sua ligação com `CLIPES`/`IMAGENS` é coberta por CT-11 na costura mais barata.
