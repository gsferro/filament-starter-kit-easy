# Casos de Teste — Dashboard dinâmico nos painéis

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Decisor liga/desliga (flag × lista de painéis) | 2 | 2 | 4 | padrão |
| Troca de página ativa (rotas, redirect, menu) | 2 | 2 | 4 | padrão |
| Permissão de gestão (`canEdit`) | 2 | 3 | 6 | padrão — **Impacto 3 → revisão adversarial** |
| Tenancy (escopo, criação, backfill) | 3 | 3 | 9 | **completo** |
| Tela de settings | 1 | 2 | 2 | mínimo |
| Inércia de update (RQ-08) | 2 | 3 | 6 | padrão — Impacto 3 |

- Técnicas aplicadas: tabela de decisão (flag × painel), matriz papel × ação,
  EP (partições de tenant/painel), rastreio de efeito (observer, seeder).
- **Revisão adversarial: EXECUTADA** — achados incorporados como premissas
  P5–P11 no `00` (Adendo 1) e como CT-27, CT-28 e CT-29 abaixo. Resumo:
  gate do observer é por painel (não flag global); scope/hook de tenancy vão
  na classe resolvida por `DashboardModelHelper::model()`; `use_spatie_permissions`
  é eixo de visibilidade; o vendor aborta 403 quando há dashboards e nenhum é
  exibível; faltava cobertura da descoberta de widgets pela trait.
- Divergência skill × rule: nenhuma.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | 6 páginas novas (2 por painel), trait, decisor, observer, service, seeder, 2 migrations, settings, custom permission | CT-01…CT-26 |
| F | redirect por request, escopo de tenant, criação de dashboard padrão, gate de edição | CT-01…CT-18 |
| D | `dashboards`/`dashboard_widgets` persistidos; `tenant_id` nullable; dado de OUTRO tenant; dashboards órfãos de toggle | CT-03, CT-11…CT-15 |
| I | rotas `/` e `/inicio` dos painéis, tela /admin de settings, seeder artisan, observer de model | CT-01, CT-16…CT-18, CT-22 |
| P | GridStack (JS) — só o browser prova drag/resize | CT-B01, CT-B02 |
| O | `panel_user` read-only; `admin_app` gestor; painel novo do projeto | CT-07…CT-10, CT-20 |
| T | toggle muda entre requests sem restart; tenant criado depois da ativação | CT-02, CT-16 |

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O decisor combina flag global E lista de painéis (vazia = todos) | decisor (padrão) | RQ-03, RQ-07 | tabela de decisão | CT-04…CT-06 |
| R2 — Desligado, a raiz do painel responde o dashboard clássico | troca de página (padrão) | RQ-02, RQ-04 | EP | CT-01 |
| R3 — Ligado, a raiz responde a dinâmica e a clássica redireciona | troca de página (padrão) | RQ-02, RQ-03 | EP | CT-02, CT-25 |
| R4 — Desligar preserva os dados; religar os devolve | troca de página (padrão) | RQ-04 | rastreio de efeito | CT-03 |
| R5 — Só quem tem a permission de gestão edita | permissão (padrão, I3) | RQ-06 | matriz papel × ação | CT-07…CT-10 |
| R6 — Com tenancy, dashboard é do tenant: não vaza, nasce com tenant_id, tenant novo ganha o seu | tenancy (completo) | RQ-05 | EP + rastreio de efeito | CT-11…CT-17 |
| R7 — Sem tenancy, o escopo é no-op e o dashboard é global | tenancy (completo) | RQ-05 | EP | CT-18, CT-19 |
| R8 — Painéis além dos três entram pela lista e pelo registro do par de páginas | decisor (padrão) | RQ-07 | EP | CT-20, CT-21 |
| R9 — Update do kit é inerte: default desligado, nada muda | update (padrão, I3) | RQ-08 | EP | CT-23, CT-24 |
| R10 — A tela de settings grava e o valor governa | settings (mínimo) | RQ-03 | EP | CT-22 |
| R11 — Ligado sem dashboards, a página responde grade vazia (não 403/500) | troca de página (padrão) | RQ-02 | EP | CT-26 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| slug `/inicio` da clássica | escolha de implementação | detalhe do cenário |
| nome `DashboardDinamico::habilitado()` | escolha de implementação | detalhe do cenário |
| nome `Manage:Dashboard` | o requisito pede a permission, não o nome | detalhe + P4 |
| "Padrão" como nome do dashboard semeado | vem da referência, não do pedido | detalhe do cenário |
| redirect (vs. 403/404) no modo inativo | o requisito diz "ativar ou desativar"; a forma é do plano | cenário afirma "responde o clássico", não o código HTTP |

**Perguntas em aberto** — replicadas em `00-requisito.md → ## Ambiguidades`:
P1 (slug `/`), P2 (painel novo sem registro → clássico), P3 (`page = null`
coringa), P4 (nome da permission). Cenários dependentes marcados `@premissa`.

## Setup Global

### Personas

- `admin` — papel `admin` do kit (acesso /admin).
- `gestor_app` — usuário do /app com papel `admin_app` (recebe a permission de
  gestão pela matriz completa do painel).
- `usuario_comum` — usuário do /app com papel `panel_user` (sem a permission).

### Fixtures

- `User::factory()` + `assignRole(...)` — papéis semeados pelo `PapeisSeeder`.
- Dashboard dinâmico: `Dashboard::create([... 'page' => <FQCN da página do painel>])`.
- Tenancy: suíte `tests/Tenancy` (`TenancyTestCase`), `Tenant::factory()`.

### Estratégia de DB

- `RefreshDatabase` via `tests/Pest.php` (Kit e Tenancy já o aplicam).

---

## Regra R1 — O decisor combina flag global E lista de painéis

> `RQ-03`, `RQ-07` · perfil **padrão** · técnica: **tabela de decisão**

| habilitado | painéis | painel corrente | decisão |
|---|---|---|---|
| false | qualquer | qualquer | clássico |
| true | vazio | qualquer | dinâmico |
| true | ['app'] | app | dinâmico |
| true | ['app'] | admin | clássico |
| true | ['app'] | infra | clássico |

```gherkin
# language: pt

Funcionalidade: Dashboard dinâmico nos painéis

  Regra: O decisor combina a flag global E a lista de painéis (vazia = todos)

    Esquema do Cenário: [CT-04] a decisão é conjuntiva — flag e painel
      Dado que a feature está <flag> e a lista de painéis é <lista>
      Quando o decisor é consultado para o painel "<painel>"
      Então a resposta é "<decisao>"

      Exemplos:
        | flag      | lista     | painel | decisao  |
        | desligada | (vazia)   | app    | clássico |
        | ligada    | (vazia)   | app    | dinâmico |
        | ligada    | (vazia)   | admin  | dinâmico |
        | ligada    | ['app']   | app    | dinâmico |
        | ligada    | ['app']   | admin  | clássico |
        | ligada    | ['app']   | infra  | clássico |

    Cenário: [CT-05] fora de contexto de painel, só a flag global decide
      Dado que a feature está ligada e a lista de painéis é ['app']
      Quando o decisor é consultado sem painel corrente (console/queue)
      Então a resposta é "dinâmico"

    Cenário: [CT-06] valor de env irreconhecível falha fechado
      Dado que KIT_DASHBOARD_DINAMICO vale "off"
      Quando a config é carregada
      Então a feature está desligada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `OR` no lugar de `AND` (flag ou painel basta) | CT-04 (linha "desligada/vazia") |
| M2 | lista vazia lida como "nenhum" em vez de "todos" | CT-04 (linhas "ligada/vazia") |
| M3 | `(bool) env()` — "off" liga | CT-06 |
| M4 | decisor ignora o painel corrente | CT-04 (linhas "['app']/admin,infra") |
| M5 | sem painel corrente estoura ou devolve clássico | CT-05 |

---

## Regra R2 — Desligado, a raiz responde o clássico

> `RQ-02`, `RQ-04` · perfil **padrão** · técnica: **EP**

```gherkin
  Regra: Com a feature desligada, a raiz do painel responde o dashboard clássico

    Cenário: [CT-01] GET na raiz do /app desligado mostra o dashboard clássico
      Dado que a feature está desligada
      E o usuário comum está autenticado no /app
      Quando ele abre a raiz do painel
      Então a resposta é o dashboard clássico do Filament
      E nenhuma tela de grade dinâmica é renderizada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | dinâmica registrada sem decisor — responde sempre | CT-01 |
| M7 | desligado devolve 403/404 na raiz em vez do clássico | CT-01 |

---

## Regra R3 — Ligado, a raiz responde a dinâmica e a clássica cede

> `RQ-02`, `RQ-03` · perfil **padrão** · técnica: **EP**

```gherkin
  Regra: Com a feature ligada, a raiz responde a dinâmica e a clássica redireciona para ela

    Cenário: [CT-02] ligar na tela muda o próximo request, sem restart
      Dado que a feature estava desligada e a raiz respondia o clássico
      Quando o admin grava o toggle ligado na tela de configurações
      Então o próximo GET na raiz do /app responde a página dinâmica
      E o GET na rota da clássica redireciona para a raiz

    Cenário: [CT-25] o item "Dashboard" do menu aponta para a página ativa
      Dado que a feature está ligada
      Quando o menu do painel é montado
      Então existe um item de dashboard para a página dinâmica
      E não existe item para a clássica
      E com a feature desligada ocorre o inverso
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | decisão fixada no boot — toggle exige restart | CT-02 |
| M9 | clássica continua respondendo em paralelo (duas homes) | CT-02 (redirect da clássica) |
| M10 | os dois itens de menu aparecem juntos | CT-25 |

---

## Regra R4 — Desligar preserva; religar devolve

> `RQ-04` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
  Regra: Desligar a feature nunca apaga dashboards; religar os devolve intactos

    Cenário: [CT-03] ciclo liga → monta → desliga → religa preserva tudo
      Dado que a feature está ligada e existe um dashboard com 2 widgets no /app
      Quando o admin desliga e depois religa a feature
      Então durante o período desligado a raiz respondeu o clássico
      E ao religar o dashboard e seus 2 widgets estão intactos no banco e na tela
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | desligar limpa as tabelas | CT-03 |
| M12 | widgets perdem posição/settings no ciclo | CT-03 |

---

## Regra R5 — Só quem tem a permission de gestão edita

> `RQ-06` · perfil **padrão (I3)** · técnica: **matriz papel × ação**

Matriz (2 papéis × 4 ações = 8 células; ✅ = permitido, ❌ = recusado sem efeito):

| papel \ ação | ver o dashboard | adicionar widget | arrastar/redimensionar | gerenciar dashboards |
|---|---|---|---|---|
| `panel_user` | ✅ | ❌ | ❌ | ❌ |
| `admin_app` | ✅ | ✅ | ✅ | ✅ |

```gherkin
  Regra: A gestão do dashboard exige a permission de gestão; ver não exige

    Cenário: [CT-07] usuário comum vê o dashboard sem nenhuma superfície de edição
      Dado que a feature está ligada e o usuário comum tem dashboard no /app
      Quando ele abre a raiz do painel
      Então o dashboard é exibido
      E não há ação de adicionar widget nem de gerenciar dashboards
      E a grade está em modo somente leitura

    Cenário: [CT-08] criar widget por fora da UI é recusado sem efeito
      Dado que a feature está ligada e o usuário comum tem dashboard no /app
      Quando ele dispara a ação de criar widget diretamente no componente
      Então a resposta é 403
      E nenhum widget é gravado

    Cenário: [CT-09] gestor do /app adiciona widget
      Dado que a feature está ligada e o gestor_app tem dashboard no /app
      Quando ele adiciona um widget pela ação da página
      Então o widget é gravado no dashboard dele

    Cenário: [CT-10] a permission existe na matriz e o panel_user não a tem
      Dado os papéis semeados do kit
      Quando a matriz de permissões é consultada
      Então a permission de gestão de dashboard existe
      E o papel panel_user não a possui
      E o papel admin_app a possui
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | `canEdit()` sem override — `true` para todos | CT-07, CT-08 |
| M14 | gate só na UI (botão escondido, ação executa) | CT-08, CT-B02 |
| M15 | permission criada mas concedida ao `panel_user` | CT-10 |
| M16 | permission fora de `custom_permissions` — invisível na tela de papéis | CT-10 |
| M33 | `canEdit()` sempre `false` — ninguém edita | CT-09 |

---

## Regra R6 — Com tenancy, o dashboard é do tenant

> `RQ-05` · perfil **completo** · técnica: **EP + rastreio de efeito**

```gherkin
  Regra: Com tenancy ligada, cada organização tem seus dashboards — sem vazamento

    Cenário: [CT-11] usuário do tenant B não vê o dashboard do tenant A
      Dado tenancy ligada, a feature ligada, e um dashboard do tenant A
      Quando o usuário do tenant B abre a raiz do /app no tenant B
      Então o dashboard do tenant A não aparece na lista nem na grade

    Cenário: [CT-12] dashboard criado dentro de um tenant grava o tenant_id
      Dado tenancy ligada e a feature ligada
      Quando o gestor_app cria um dashboard dentro do tenant A
      Então o registro gravado tem tenant_id do tenant A

    Cenário: [CT-13] tenant_id não é atribuível em massa
      Dado tenancy ligada
      Quando um dashboard é criado com tenant_id no payload de fill
      Então o tenant_id gravado é o do tenant corrente, não o do payload

    Cenário: [CT-14] tenant criado depois da ativação nasce com dashboard padrão
      Dado tenancy ligada e a feature ligada
      Quando um tenant novo é criado
      Então existe um dashboard padrão com o tenant_id dele

    Cenário: [CT-15] tenant criado com a feature desligada não ganha linha órfã
      Dado tenancy ligada e a feature desligada
      Quando um tenant novo é criado
      Então nenhum dashboard é criado para ele

    Cenário: [CT-27] flag ligada mas painel fora da lista também não semeia
      Dado tenancy ligada, a flag ligada e a lista de painéis sem o app
      Quando um tenant novo é criado
      Então nenhum dashboard é criado para ele

    Cenário: [CT-16] o seeder faz backfill idempotente dos tenants existentes
      Dado tenancy ligada, a feature ligada, e dois tenants sem dashboard
      Quando o seeder de dashboard padrão roda duas vezes
      Então cada tenant tem exatamente um dashboard padrão

    Cenário: [CT-17] excluir o tenant não derruba os dashboards de outros
      Dado tenancy ligada e dashboards nos tenants A e B
      Quando o tenant A é excluído
      Então os dashboards de A ficam com tenant_id nulo (nullOnDelete)
      E os dashboards de B continuam intactos e visíveis no tenant B
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | scope ausente — dashboard de A aparece em B | CT-11 |
| M18 | hook `creating` ausente — tenant_id fica null | CT-12 |
| M19 | `tenant_id` no `$fillable` — payload forja tenant | CT-13 |
| M20 | observer cria dashboard mesmo com feature desligada | CT-15 |
| M21 | seeder não idempotente — duplica o "Padrão" | CT-16 |
| M35 | observer consulta só a flag global — painel excluído ainda semeia | CT-27 |
| M22 | FK com `cascadeOnDelete` — apaga dashboards de A e pode varrer B | CT-17 |
| M23 | scope condicionado a `getId() === 'app'` — painel tenant-aware novo fica de fora | CT-20 |
| M34 | observer ausente/não registrado — tenant novo nasce sem dashboard | CT-14 |

---

## Regra R7 — Sem tenancy, o escopo é no-op

> `RQ-05` · perfil **completo** · técnica: **EP**

```gherkin
  Regra: Sem tenancy, dashboards são globais e a coluna tenant_id fica nula

    Cenário: [CT-18] sem tenancy, o dashboard criado tem tenant_id nulo
      Dado tenancy desligada e a feature ligada
      Quando o gestor_app cria um dashboard no /app
      Então o registro tem tenant_id nulo
      E qualquer usuário do /app o vê

    Cenário: [CT-19] painel sem tenancy nunca é filtrado pelo scope
      Dado tenancy ligada e a feature ligada também no /admin
      Quando um dashboard é criado no /admin
      Então ele não recebe tenant_id
      E é visível no /admin independente de tenant corrente
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | scope aplica `where tenant_id = null` errado ou estoura sem tenant | CT-18 |
| M25 | hook `creating` roda em painel sem tenancy e grava lixo | CT-19 |

---

## Regra R8 — Painéis além dos três

> `RQ-07` · perfil **padrão** · técnica: **EP** · `@premissa` P2

```gherkin
  Regra: Painel registrado pelo projeto entra na lista e recebe a feature pelo par de páginas

    @premissa
    Cenário: [CT-20] painel novo sem o par de páginas responde o clássico
      Dado um painel "vendas" registrado pelo projeto, sem as páginas da feature
      Quando a feature está ligada e "vendas" está na lista de painéis
      Então a raiz de "vendas" responde o dashboard clássico
      E nenhum erro é lançado

    Cenário: [CT-21] o seletor de painéis da tela lista os painéis registrados
      Dado um painel "vendas" registrado pelo projeto
      Quando o admin abre a tela de configurações
      Então "vendas" aparece como opção na lista de painéis da feature
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | lista de painéis fixa em `['admin','app','infra']` | CT-20, CT-21 |
| M27 | painel desconhecido na lista estoura o decisor | CT-20 |

---

## Regra R9 — Update do kit é inerte

> `RQ-08` · perfil **padrão (I3)** · técnica: **EP**

```gherkin
  Regra: Quem atualiza o kit sem ligar a chave não vê mudança nenhuma

    Cenário: [CT-23] sem a chave no ambiente, o default é desligado
      Dado uma instalação sem KIT_DASHBOARD_DINAMICO definida
      Quando a config é carregada
      Então a feature está desligada
      E a raiz de cada painel responde o dashboard clássico

    Cenário: [CT-24] a migration de settings semeia o valor do config, não true
      Dado uma instalação atualizada que roda a migration de settings nova
      Quando a seed da propriedade é gravada
      Então o valor semeado é o do config (desligado por default)
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | default `true` na config ou na seed | CT-23, CT-24 |
| M29 | migration de settings edita uma já rodada — instalação de terceiro sem a linha | CT-24 |

---

## Regra R10 — A tela grava e o valor governa

> `RQ-03` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: O toggle e a lista de painéis da tela de configurações governam a feature

    Cenário: [CT-22] gravar o toggle na tela persiste e governa
      Dado o admin na tela de configurações, aba Kit
      Quando ele liga o dashboard dinâmico e restringe aos painéis app e admin
      Então os valores são persistidos
      E o decisor responde dinâmico para app e admin, clássico para infra
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | campo na tela sem linha no `mapaDeConfiguracao()` — grava e não governa | CT-22 |
| M31 | seletor de painéis não persiste como array | CT-22 |

---

## Regra R12 — Só widget com a trait é oferecido na grade

> `RQ-02`, `RQ-07` · perfil **padrão** · técnica: **EP**
> Nascido na revisão adversarial — o conjunto original não cobria a descoberta.

```gherkin
  Regra: A descoberta de widgets filtra pela interface DynamicWidget do pacote

    Cenário: [CT-29] widget com a trait aparece; sem a trait, não
      Dado que a feature está ligada no /app
      E o painel tem um widget com a trait de compatibilidade e um sem
      Quando o gestor_app abre a ação de adicionar widget
      Então o widget compatível é oferecido como opção
      E o widget sem a trait não aparece
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | trait não implementa `DynamicWidget` — widget some da lista | CT-29 |
| M37 | descoberta sem filtro — widget clássico vira opção e quebra a grade | CT-29 |

---

## Regra R13 — Dashboard sem papel visível para o usuário é 403 (contrato do vendor)

> `RQ-06` · perfil **padrão** · técnica: **EP** · `@premissa` P8
> Nascido na revisão adversarial — fixa o contrato que a feature expõe.

```gherkin
  Regra: Com dashboards existentes e nenhum exibível, a página responde 403

    @premissa
    Cenário: [CT-28] único dashboard do tenant tem roles que excluem o usuário
      Dado tenancy ligada, a feature ligada e use_spatie_permissions ativo
      E o único dashboard do tenant A tem uma role que o usuário comum não tem
      Quando o usuário comum abre a raiz do /app no tenant A
      Então a resposta é 403
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | `canDisplay()` sobrescrito para sempre `true` — role vira decoração | CT-28 |

---

## Regra R11 — Ligado sem dashboards responde grade vazia

> `RQ-02` · perfil **padrão** · técnica: **EP**

```gherkin
  Regra: Com a feature ligada e nenhum dashboard, a página responde a grade vazia

    Cenário: [CT-26] primeiro acesso sem dashboards não é erro
      Dado que a feature está ligada e não existe nenhum dashboard no /app
      Quando o gestor_app abre a raiz do painel
      Então a página responde a grade vazia
      E a ação de gerenciar dashboards está disponível para ele
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | página aborta 403/500 quando não há dashboard | CT-26 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-11 (dashboard de outro tenant) |
| Autorização exercida na ação, não só `can()` | CT-08 (ação disparada fora do caminho feliz) |
| Idempotência (ancorada no agregado) | CT-16 (seeder 2× → 1 dashboard por tenant) |
| Concorrência | não se aplica: sem contador/limite |
| Fronteira no ponto de entrada (gravação) | CT-13 (tenant_id forjado no payload) |
| Domínio condicionado | CT-04 (flag × lista) |
| Estado × operação de escrita | CT-03 (desligado → dados intactos; religado → funcionam) |
| Ausente ≠ null ≠ vazio | CT-04 (lista vazia = todos) + CT-23 (env ausente = desligado) |
| Paginação / ordenação | não se aplica: sem listagem paginada própria |
| Timezone / DST | não se aplica: sem data/hora |
| Unicode / limite de varchar | não se aplica: sem texto livre do kit |
| Unicidade + soft delete | não se aplica: dashboards não têm unique nem soft delete |
| CRUD combinado | CT-17 (excluir tenant) + CT-16 (re-seed) |
| Mass assignment | CT-13 |
| Upload | não se aplica: sem upload |
| Precisão monetária | não se aplica: sem dinheiro |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | raiz desligada → clássico | R2 | EP | Feature | `tests/Kit/DashboardDinamico*` | M6, M7 |
| CT-02 | toggle muda o próximo request | R3 | EP | Feature | idem | M8, M9 |
| CT-03 | ciclo liga/desliga preserva | R4 | efeito | Feature | idem | M11, M12 |
| CT-04 | decisão conjuntiva flag × painel | R1 | decisão | Feature | idem | M1, M2, M4 |
| CT-05 | sem painel corrente | R1 | decisão | Feature | idem | M5 |
| CT-06 | env "off" falha fechado | R1 | EP | Feature | idem | M3 |
| CT-07 | panel_user read-only | R5 | papel×ação | Livewire | idem | M13 |
| CT-08 | criar widget fora da UI → 403 | R5 | papel×ação | Livewire | idem | M13, M14 |
| CT-09 | admin_app adiciona widget | R5 | papel×ação | Livewire | idem | M33 |
| CT-10 | permission existe, panel_user sem ela | R5 | papel×ação | Feature | idem | M15, M16 |
| CT-11 | tenant B não vê dashboard de A | R6 | EP | Feature (Tenancy) | `tests/Tenancy/*` | M17 |
| CT-12 | criação grava tenant_id | R6 | efeito | Feature (Tenancy) | idem | M18 |
| CT-13 | tenant_id não é fillable | R6 | EP | Feature (Tenancy) | idem | M19 |
| CT-14 | tenant novo ganha dashboard | R6 | efeito | Feature (Tenancy) | idem | M34 |
| CT-15 | feature off → observer não cria | R6 | efeito | Feature (Tenancy) | idem | M20 |
| CT-16 | seeder idempotente | R6 | efeito | Feature (Tenancy) | idem | M21 |
| CT-17 | excluir tenant → nullOnDelete | R6 | EP | Feature (Tenancy) | idem | M22 |
| CT-18 | sem tenancy → global, tenant_id null | R7 | EP | Feature | `tests/Kit/*` | M24 |
| CT-19 | painel sem tenancy não filtra | R7 | EP | Feature (Tenancy) | `tests/Tenancy/*` | M25 |
| CT-20 | painel novo sem páginas → clássico | R8 | EP | Feature | `tests/Kit/*` | M23, M26, M27 |
| CT-21 | seletor lista painéis registrados | R8 | EP | Livewire | idem | M26 |
| CT-22 | tela grava e governa | R10 | EP | Livewire | idem | M30, M31 |
| CT-23 | default desligado | R9 | EP | Feature | idem | M28 |
| CT-24 | seed do settings ≠ true | R9 | EP | Feature | idem | M28, M29 |
| CT-25 | item de menu alterna | R3 | EP | Feature | idem | M10 |
| CT-26 | ligado sem dashboards → grade vazia | R11 | EP | Feature | idem | M32 |
| CT-27 | flag on + painel fora da lista → observer não semeia | R6 | efeito | Feature (Tenancy) | `tests/Tenancy/*` | M35 |
| CT-28 | dashboard com role excludente → 403 | R13 | EP | Feature (Tenancy) | idem | M38 |
| CT-29 | descoberta filtra pela trait | R12 | EP | Livewire | `tests/Kit/*` | M36, M37 |

## Sem CT-B?

Não — há CT-B: ver `05-casos-de-teste-browser.md`. A grade GridStack
(drag/resize/persistência de posição) só o navegador prova.
