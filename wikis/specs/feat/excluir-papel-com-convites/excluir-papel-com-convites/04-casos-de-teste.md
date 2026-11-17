# Casos de Teste — Roadmap item 9: excluir pela tela um papel que tem convite

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, `## Rotas`, `## Superfície de UI` e a decisão D4, que fecha Q5) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação: `app/` não foi lido.
> Revisão adversarial: **encerrada em 2 rodadas** (`fw-adversario-ct`, disparada por Impacto 3 na área D; teto de 2 rodadas atingido). Rodada 1: 35 achados (ADV-01…ADV-35). Rodada 2 (re-revisão única, pelos cenários novos CT-14…CT-18): 4 achados (ADV-36…ADV-39). Todos fechados, sem nova rodada (ver `## Revisão Adversarial`).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — recusa da exclusão por convite Pendente (três caminhos, massa, saída, organizações) | 2 | 2 | 4 | padrão |
| B — remoção dos convites Aceitos, Recusados e Expirados junto com o papel, com auditoria | 2 | 2 | 4 | padrão |
| C — conteúdo do aviso de recusa (e-mails, teto de 5, rótulo do papel) | 2 | 1 | 2 | mínimo |
| D — quem vê os e-mails no aviso (P-06) | 2 | 3 | 6 | padrão |

- P = 2 em todas: a correção integra com um componente existente (as três ações do `RoleResource` + a chave estrangeira sem cascade). I = 2 em A e B: erro gera retrabalho manual (convite pendente apagado por engano se reenvia; remoção dos mortos é pedida e auditada). I = 1 em C: texto. I = 3 em D: e-mail de terceiro exposto a quem não tem permissão de ver convites (autorização + dado pessoal) — disparou a revisão adversarial.
- Técnicas aplicadas: EP, BVA 3-valores (escalada em R1), BVA 2-valores (escalada em R3), tabela de decisão (R4), rastreio de efeito (R5, R9), 2-switch (R6), matriz papel × ação (R8, R9).
- Cenários: 19 · Regras: 10 · Mutantes previstos: 60 · Sem matador: 0
<!-- derivado por grep -c (comandos em references/template-04.md §Contagem do cabeçalho); recalcular a cada cenário novo -->
- Teto do perfil: as regras R2, R3, R4, R5, R7 e R8 passam de 5 mutantes só por mutantes trazidos pela revisão adversarial (marcados `revisão adversarial`), que não contam para o teto (passo 6, item 1).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | três ações de exclusão do Filament (linha, cabeçalho da edição, massa) sobre `roles`; tabela `convites` (FK `role_id` sem cascade); trilha `audits`; pivôs de permissão e de usuário do papel (o `deleting` do spatie os desliga — `vendor/spatie/laravel-permission/src/Traits/HasPermissions.php:bootHasPermissions:36-48`) | CT-02, CT-03, CT-08 |
| F | recusar exclusão com convite Pendente; listar e-mails (ou quantidade) no aviso, com o rótulo do papel; apagar os convites mortos junto; registrar na auditoria; falha fechada na massa | CT-01…CT-08, CT-13…CT-18 |
| D | estado do convite derivado de três fatos (`aceito_em`, `recusado_em`, `expira_em`); cardinalidade de Pendentes 0 / 1 / 5 / 6 por papel; convites de outro papel; convites de outra organização; quem convidou (`convidado_por_id`) | CT-01, CT-04, CT-05, CT-08, CT-11, CT-12, CT-13, CT-18 |
| I | tela `/admin/roles` (linha e massa), `/admin/roles/{record}/edit` (cabeçalho), exclusão direta pelo model (por fora da tela), tela de Convites (revogação), aceite do convite | CT-02, CT-03, CT-06, CT-09, CT-10, CT-16 |
| P | SQLite de teste com `foreign_key_constraints` ligado (citado em `tests/Kit/ConviteTest.php`, CT-12 da `phpstan-nivel-8`); `audit.console` desligado no kit — o Setup liga | CT-03, CT-08 |
| O | administrador que tenta, lê o aviso, vai à tela de Convites, revoga e tenta de novo; operador sem permissão de convite (P-06, P-08); operador que é quem convidou ou o próprio convidado; massa misturando livres e bloqueados | CT-06, CT-07, CT-10, CT-13, CT-15, CT-18 |
| T | validade do convite (`expira_em`) comparada com o agora — borda exata; ordem recusa → revogação → nova tentativa; corrida entre checagem e exclusão (lacuna declarada) | CT-01, CT-10 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Só o convite Pendente bloqueia: nem aceito, nem recusado, com validade no futuro; sem validade conta como Expirado | A (padrão) | P-01, RQ-02, RQ-07 | EP + BVA 3-valores | CT-01 |
| R2 — A exclusão de papel com convite Pendente é recusada pelos quatro caminhos, sem 500, e nada é apagado — nem convite, nem permissão, nem usuário do papel | A (padrão) | RQ-01, RQ-02, RQ-04, RQ-09, P-02 | EP (caminho) + rastreio de não-efeito | CT-02, CT-03 |
| R3 — O aviso lista os e-mails dos convites Pendentes do papel, no máximo 5 por papel, com "e mais N" contando só Pendentes | C (mínimo, técnica escalada) | RQ-03, P-04 | EP + BVA 2-valores | CT-04, CT-05 |
| R4 — Na exclusão em massa, um papel bloqueado barra a seleção inteira, e o aviso vincula cada bloqueado aos seus e-mails | A (padrão) | P-03, RQ-04 | tabela de decisão | CT-06, CT-07 |
| R5 — Convites Aceitos, Recusados e Expirados somem junto com o papel, um registro de auditoria por convite, com e-mail, papel e quem convidou | B (padrão) | RQ-07, RQ-08, P-02 | rastreio de efeito × EP (caminho) | CT-08, CT-14 |
| R6 — Depois da recusa: o convite Pendente continua aceitável com o papel, e revogado ele libera a exclusão | A (padrão) | RQ-05, RQ-06 | 2-switch | CT-09, CT-10 |
| R7 — Os convites de todas as organizações contam, nos três caminhos, e a saída pela tela de Convites os alcança | A (padrão) | P-05, RQ-02, RQ-06, RQ-07, RQ-08 | EP (organização × caminho) + 2-switch | CT-11, CT-12, CT-19 |
| R8 — Os e-mails só aparecem para quem pode ver convites; para os demais, só a quantidade de Pendentes por papel, sem e-mail e sem atalho para Convites | D (padrão) | P-06, RQ-03 | matriz papel × ação (com/sem `ViewAny:Convite` × caminho) | CT-13, CT-17, CT-18 |
| R9 — Quem pode excluir o papel apaga os convites encerrados sem precisar de permissão de convite | B (padrão) | P-08, RQ-07, RQ-08 | matriz papel × ação + rastreio de efeito | CT-15 |
| R10 — No aviso, o papel bloqueado aparece pelo rótulo da tela de papéis, com e sem os e-mails | C (mínimo) | P-07, P-06, RQ-03, P-03 | EP (caminho × forma do aviso) | CT-16 |

- Técnica escalada em R1: BVA 3-valores (perfil padrão prevê 2) — `>` × `>=` sobre `datetime` só se separa com a borda exata (validade = agora).
- Técnica escalada em R3: área mínima prevê só EP; o teto literal "5" de P-04 exige BVA (5 e 6), e o gate vence o teto (dois cenários na regra).
- RQ-03 e P-03: a identificação do papel bloqueado, antes recorte aberto, agora é afirmada pela premissa vigente **P-07** (Q3 aberta, premissa vigente até a resposta) — CT-06, CT-07, CT-16, CT-17.
- RQ-07 e RQ-08 quanto à persona: afirmadas pela premissa vigente **P-08** (Q4 aberta, premissa vigente até a resposta) — CT-15.
- Toda RQ (01–09) e toda P-nn vigente (P-01…P-08) tem ao menos um CT: ver o rastreio no `## Índice de Cenários`.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Exclusão pela tela de papéis | R1, R2 (CT-02), R3, R4, R5, R6, R8, R9, R10 | componente Livewire/Filament | existente — padrão de `tests/Kit/TelaDePapeisTest.php` (CT-05…CT-07: `ListRoles`/`EditRole`, `loadTable()`, `mountAction(TestAction::make(DeleteAction::class)->table($papel))`, `selectTableRecords` + `DeleteBulkAction` `->bulk()`, `callMountedAction()`); arquivo novo `tests/Kit/ExclusaoDePapelComConviteTest.php` | o requisito afirma sobre a tela (aviso, as três ações); aviso do Filament e ação de tabela são observáveis no componente | 2026-11-16 — sessão, sob a autorização "prossiga até o fim" (convenção dos testes irmãos citados) |
| G2 — Exclusão por fora da tela | R2 (CT-03) | Pest feature HTTP | existente — padrão do `[CT-12]` de `tests/Kit/ConviteTest.php` (`$papel->delete()` direto); mesmo arquivo novo do G1 | gate de camada da regra: a recusa e o "nada é apagado" têm de valer fora do componente, onde mora a chave estrangeira | 2026-11-16 — sessão, sob a autorização "prossiga até o fim" (convenção dos testes irmãos citados) |
| G3 — Organizações (tenancy ligada) | R7 | componente Livewire/Filament | existente — padrão de `tests/Tenancy/PapeisPorOrganizacaoTest.php` (`tenant()`, `Filament::setCurrentPanel('admin')`) + `ofertaPara($email, $tenant, $papel, $atributos)`; arquivo novo `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` | `tenant_id` dos convites só existe com significado na suíte `Tenancy` (`TenancyTestCase`) | 2026-11-16 — sessão, sob a autorização "prossiga até o fim" (convenção dos testes irmãos citados) |

- Divergência declarada (convenção do projeto vence a skill): o G2 é `Pest feature HTTP` mas vive em `tests/Kit/`, não em `tests/Feature/` — `tests/Pest.php` liga o mesmo `TestCase` + `RefreshDatabase` a `Kit`, e o CT-12 irmão vive lá.
- CT-09 tem o `Quando` no domínio (`Convite::aceitar()`), mas a situação de partida (a recusa) nasce pela tela: fica no G1.
- Os CT novos da revisão (CT-14…CT-18) cabem nas costuras já confirmadas (G1); nenhuma linha nova.

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| "recusa por convite pendente no `before` e no `deleting`" (02) e D4 ("o `delete()` do model recusa antes do banco e não lança `QueryException`", 01) | mecanismo (Q5, raia desenho, decidida); o requisito só diz que o papel continua e nada é apagado | CT-03 afirma o estado do banco, não a ausência nem o tipo de exceção; o executor pode chamar `delete()` sem `try` por causa de D4, mas o oráculo não depende disso |
| `[CT-12]` da `phpstan-nivel-8` | por D4 passa a provar a FK com `DELETE` pela query; RQ-05 herda só o **invariante** (Pendente aceitável com o mesmo papel) | CT-03 e CT-09 afirmam o invariante; o ajuste do `[CT-12]` é da sessão (D4), fora deste `04` |
| rotas `/admin/roles`, `/admin/roles/{record}/edit` e "Depende de JS? Não" (01, Superfície de UI) | superfície, não comportamento | nomes dos caminhos dos Esquemas; base do `## Sem CT-B` |
| texto do título do aviso, ordem dos e-mails, quais 5 aparecem quando há 6, formato da frase da quantidade | comportamento visível que o requisito não determina | não afirmado: CT-05 afirma "exatamente 5 dos 6" sem ordem; a quantidade é afirmada como número isolado (`(?<!\d)3(?!\d)`) |
| vínculo papel ↔ e-mails no texto da massa | P-03 diz "os papéis bloqueados com os e-mails de cada um" — o agrupamento é do requisito; o layout não | CT-07 e CT-17 afirmam por **trecho**: do rótulo de um papel até o rótulo do próximo papel bloqueado (ou o fim do aviso); a ordem dos papéis não é afirmada |

**Perguntas** (numeração final da sessão; as provisórias da derivação viraram Q3, Q4 e Q5):

- **Q3** (requisito; afeta RQ-03, P-03) — aberta, com **P-07 vigente**: o papel aparece pelo rótulo. Afirmado em CT-06, CT-07, CT-16, CT-17.
- **Q4** (requisito; afeta RQ-07, RQ-08) — aberta, com **P-08 vigente**. Recomendação corrigida (a da rodada anterior, "falha fechado — recusar", contradizia P-08): ➡️ **sim, apaga** — a resposta a Q2 não condiciona a permissão e a remoção fica na auditoria. Afirmado em CT-15.
- **Q5** (desenho; afeta RQ-05) — **decidida** (D4 no `01`). Nenhum cenário depende dela.

## Setup Global

### Personas
- `master_global` — `usuarioDoKit('master_global', 'master@example.com')` + `$this->actingAs(...)`. Atravessa o `Gate::before`: a autorização da exclusão não muda nesta feature (02: `RolePolicy::delete()`, `authorizeIndividualRecords('delete')`) e já é travada por `tests/Kit/TelaDePapeisTest.php` CT-05…CT-07. Persona de CT-01…CT-12, CT-14 e CT-16, onde P-06/P-08 não estão em jogo.
- operador `admin` (não-master, tem `Delete:Role`) — `usuarioDoKit('admin', 'operador.rui@example.com')`, em CT-13, CT-15, CT-17 e CT-18. Permissões por linha: "com" = `papelDoKit('admin')->givePermissionTo('ViewAny:Convite')`; "sem" = `semAPermissao('admin', 'ViewAny:Convite')`; CT-15 = `semAPermissao('admin', ...)` com **toda** permissão `*:Convite` que o `admin` tiver (o executor lista pelo `ShieldPermissionsSeeder`). Sempre seguido de `forgetCachedPermissions()`. Sanidade antes do `Quando`: `can('ViewAny:Convite')` igual à coluna, `can('Delete:Role')` verdadeiro — sem isso a linha "sem" pode medir a recusa da própria exclusão.
- convidante `lia@example.com` — `usuario('lia@example.com')`, quem convidou (`convidado_por_id`) nos convites de CT-08; distinta do autor da exclusão, para que "quem convidou" e "quem excluiu" não se confundam na trilha.
- convidada `carla@example.com` — só existe como convite até o aceite (CT-09).

### Fixtures
- `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`; `noPainelBootado('admin')` (G1/G2); `config(['audit.console' => true])` **antes** de criar convites (sem ele `audits` fica vazia e todo não-efeito de auditoria é vácuo, e o registro "created" não nasce — ver `ConviteTest` "revoga o convite"); relógio congelado num meio-dia fixo (`$this->travelTo('2026-11-16 12:00:00')`).
- Papéis de teste: `Role::create(['name' => '{chave}', 'guard_name' => 'web', 'painel' => 'admin'])` (G3: `painel` = `app`). Chaves e rótulos (P-07; padrão do dataset "usa o rotulo do papel como titulo do registro" de `TelaDePapeisTest`, conferido por `RoleResource::getRecordTitle($papel)` como sanidade): `suporte` → "Suporte"; `gerente_de_contas` → "Gerente De Contas"; `financeiro_pj` → "Financeiro Pj"; `vendas` → "Vendas". Onde o rótulo é oráculo (CT-16, CT-07, CT-17, CT-06), o papel tem **rótulo diferente da chave** (`gerente_de_contas`, `financeiro_pj`).
- "O papel tem permissão e usuário" (R2): `$papel->givePermissionTo('View:Role')` e `usuarioCom(null)` com e-mail `rui@example.com` + `assignRole('suporte')`.
- Convites: `ofertaPara($email, $tenant, '{chave}', $atributos)` — `role_id` explícito (o default da factory é o `master_global`). Situações:
  - **Pendente**: sem atributos (factory: `aceito_em` nulo, `expira_em` = agora + 7 dias) ou com `expira_em` do cenário.
  - **Aceito**: `['aceito_em' => now()]` (validade futura mantida — discrimina "pendente = validade futura").
  - **Recusado**: `['recusado_em' => now()]` (validade futura mantida).
  - **Expirado**: `['expira_em' => now()->subDay()]`; **sem validade**: `['expira_em' => null]`.
  - **Quem convidou**: `['convidado_por_id' => $convidante->id]` (coluna de `create_convites_table`).
- E-mails de Pendentes nos cenários de P-06 têm parte local distintiva (`pend.carla`, `pend.dani`, `pend.fabi`, `pend.gil`, `pend.hel`, `operador.rui`) para a asserção de ausência da parte local não colidir com palavra do texto do aviso.
- Desvio declarado da skill (helper por transições reais): o convite **não tem máquina de estados** — o estado é derivado de `aceito_em`/`recusado_em`/`expira_em` (comentário da migration `add_recusado_em_to_convites_table`), e os testes irmãos gravam esses fatos (`ConviteTest` "nao aceita token de lembrete…").
- Leitura do aviso: `avisosDoFilament()` de `tests/Pest.php`. "O aviso" = título + corpo de cada notificação; "o aviso contém X" = algum aviso contém X; "nenhum aviso de sucesso" = nenhuma notificação com `status` = `success`; "sem atalho para Convites" = nenhuma ação da notificação com URL da tela de Convites do `/admin` (`ConviteResource::getUrl()` do `/admin`) e nenhum `href` para ela no título/corpo. Limpar com `limparAvisosDoFilament()` entre duas ações do mesmo teste (CT-10).
- "Trecho do papel" (massa): a parte do texto do aviso que começa no rótulo do papel e termina no rótulo do próximo papel bloqueado ou no fim do aviso.
- Auditoria: `audits` com `auditable_type = Convite::class`, `auditable_id`, `event` (`created`/`deleted`), `old_values`, `user_id` (padrão de `ConviteTest` "revoga o convite").
- Ações: linha `mountAction(TestAction::make(DeleteAction::class)->table($papel))->callMountedAction()` em `ListRoles` após `loadTable()`; edição `Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()])->mountAction(TestAction::make(DeleteAction::class))->callMountedAction()`; massa `selectTableRecords([...])->mountAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())->callMountedAction()`. Nada de `callTableAction` (`@deprecated`).
- "Nenhum 500" no componente = a chamada da ação retorna sem exceção (uma `QueryException` não tratada estoura o teste) **e** o oráculo de estado do cenário.

### Fakes
- Nenhum. `Notification::fake()` não entra: nenhuma notificação de e-mail é efeito desta feature.

### Estratégia de DB
- `RefreshDatabase` herdado de `tests/Pest.php` (`Kit` e `Tenancy`). SQLite com chave estrangeira ligada: é ela que transforma a falta de tratamento em `QueryException` observável.

---

## Regra R1 — Só o convite Pendente bloqueia

> `P-01`, `RQ-02`, `RQ-07` · perfil **padrão** · técnica: **EP** (situação do convite) + **BVA 3-valores** (fronteira: `expira_em` × agora, granularidade 1 segundo)

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Só o convite Pendente — nem aceito, nem recusado, com validade no futuro — impede a exclusão; sem validade gravada conta como Expirado

    Esquema do Cenário: [CT-01] a situação do único convite do papel decide se a exclusão é recusada
      Dado o relógio parado em 2026-11-16 12:00:00
      E o papel "suporte" com um único convite para "carla@example.com" na situação <situacao>
      Quando o master_global exclui o papel "suporte" pela ação da linha da tabela de papéis
      Então o papel "suporte" <papel>
      E o convite de "carla@example.com" <convite>

      Exemplos:
        | situacao                                             | papel           | convite         | # partição                   |
        | sem convite algum (cenário sem a linha de convite)   | não existe mais | —               | cardinalidade 0              |
        | não aceito, não recusado, validade 12:00:01          | continua        | continua        | Pendente — borda+1           |
        | não aceito, não recusado, validade 12:00:00          | não existe mais | não existe mais | borda (agora não é futuro)   |
        | não aceito, não recusado, validade 11:59:59          | não existe mais | não existe mais | Expirado — borda−1           |
        | não aceito, não recusado, sem validade gravada       | não existe mais | não existe mais | Expirado por P-01            |
        | aceito, validade 2026-11-17 12:00:00                 | não existe mais | não existe mais | Aceito com validade futura   |
        | recusado, validade 2026-11-17 12:00:00               | não existe mais | não existe mais | Recusado com validade futura |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | Pendente com `expira_em >= now()` em vez de `>` | CT-01 linha "borda" | validade 12:00:00: papel "não existe mais"; o mutante recusa e o papel continua |
| M2 | Pendente = só `aceito_em` nulo (esquece `recusado_em`, coluna que veio depois) | CT-01 linha "Recusado" | recusado com validade futura: papel "não existe mais"; o mutante recusa |
| M3 | Pendente = não aceito e não recusado, sem olhar a validade | CT-01 linha "borda−1" | validade 11:59:59: papel "não existe mais"; o mutante recusa |
| M4 | convite sem validade tratado como Pendente (`whereNull('expira_em')` no `OR`) | CT-01 linha "sem validade" | papel "não existe mais"; o mutante recusa |
| M5 | validade comparada por data (`whereDate`/`toDateString`) | CT-01 linha "borda−1" | 11:59:59 do mesmo dia: papel "não existe mais"; o mutante vê "hoje ≥ hoje" e recusa |

---

## Regra R2 — Recusa pelos quatro caminhos, sem 500, e nada é apagado

> `RQ-01`, `RQ-02`, `RQ-04`, `RQ-09`, `P-02` · perfil **padrão** · técnica: **EP** (caminho de exclusão) + **rastreio de não-efeito** (destinatários reais: convites mortos, permissão e usuário do papel, auditoria ligada). O `deleting` do spatie desliga permissões e usuários do papel antes do `DELETE` (`HasPermissions.php:bootHasPermissions:36-48`): uma recusa que chega só pela FK chega **depois** desse efeito — é a falha depois do ponto do efeito que o não-efeito exige.

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Papel com convite Pendente não é excluído por caminho nenhum; nem o papel, nem convite algum, nem as permissões e os usuários dele são apagados, e o administrador não vê erro nem sucesso

    Esquema do Cenário: [CT-02] a exclusão pela tela é recusada com aviso e não apaga nada, em cada caminho
      Dado o papel "suporte" com a permissão "View:Role", o usuário "rui@example.com" e o convite Pendente de "carla@example.com"
      E com os convites Aceito de "ana@example.com", Recusado de "bia@example.com" e Expirado de "caio@example.com", e a auditoria ligada
      Quando o master_global exclui o papel "suporte" pela <caminho>
      Então a ação termina sem exceção, um aviso contém "carla@example.com" e nenhum aviso é de sucesso
      E nenhum aviso contém "ana@example.com", "bia@example.com" nem "caio@example.com"
      E o papel "suporte" continua existindo, com a permissão "View:Role" e o usuário "rui@example.com"
      E os quatro convites continuam existindo com o papel "suporte", sem registro de auditoria "deleted"

      Exemplos:
        | caminho                                                |
        | ação Excluir da linha da tabela de papéis              |
        | ação Excluir do cabeçalho da edição do papel           |
        | exclusão em massa com só o papel "suporte" selecionado |

    Cenário: [CT-03] a exclusão direta do papel, por fora da tela, também não apaga nada
      Dado o papel "suporte" com a permissão "View:Role", o usuário "rui@example.com" e o convite Pendente de "carla@example.com"
      E com os convites Aceito de "ana@example.com", Recusado de "bia@example.com" e Expirado de "caio@example.com"
      Quando o papel "suporte" é excluído diretamente pelo model, sem passar pela tela
      Então o papel "suporte" continua existindo, com a permissão "View:Role" e o usuário "rui@example.com"
      E os quatro convites continuam existindo, todos com o papel "suporte"
```

> CT-03 não afirma exceção nem a ausência dela (mecanismo — Q5/D4); o oráculo é o banco. Permissão e usuário conferidos pela tabela-pivô depois de `forgetCachedPermissions()` (`$papel->fresh()->permissions`, `User::...->hasRole('suporte')`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | tratamento só na ação da linha; edição e massa continuam em `QueryException` | CT-02 linhas "edição" e "massa" | a ação termina sem exceção; o mutante estoura a `QueryException` no `callMountedAction()` |
| M7 | a ação apaga os convites mortos primeiro e só então checa o Pendente / deixa o banco recusar | CT-02 (qualquer linha) | ana, bia e caio continuam existindo; o mutante os apagou |
| M8 | limpeza dos mortos no evento `deleting` do model, sem transação; a recusa vem da FK depois | CT-03 | ana, bia e caio continuam existindo; no mutante a FK recusa o papel com os três já apagados |
| M9 | a "recusa" revoga o convite Pendente para destravar (a opção que Q1 não escolheu) | CT-02 | carla continua existindo com `role_id` do suporte, sem `deleted`; o mutante a apagou |
| M10 | captura a `QueryException` e mostra aviso genérico, sem os e-mails | CT-02 | algum aviso contém "carla@example.com"; o mutante mostra só "não foi possível excluir" |
| M34 | recusa só pela FK (captura a `QueryException`), sem transação: o `deleting` do spatie já desligou permissões e usuários — revisão adversarial (ADV-10/ADV-22) | CT-02 (todas as linhas), CT-03 | "suporte" continua com "View:Role" e com rui; o mutante deixa o papel sem permissão e sem usuário |
| M35 | a recusa interrompe a exclusão, mas a notificação de sucesso padrão da ação dispara junto — revisão adversarial (ADV-11/ADV-21) | CT-02, CT-06 | nenhum aviso com `status` `success`; o mutante mostra "Excluído" ao lado da recusa |
| M36 | o aviso das ações de edição e de massa (código próprio) lista todos os convites do papel, mortos inclusive — revisão adversarial (ADV-12) | CT-02 linhas "edição" e "massa" | nenhum aviso contém ana, bia nem caio; o mutante os lista |

---

## Regra R3 — O aviso lista os e-mails dos Pendentes do papel, no máximo 5 por papel

> `RQ-03`, `P-04` · perfil **mínimo, técnica escalada** · técnica: **EP** (de quem é o e-mail) + **BVA 2-valores** (fronteira: 5 e-mails por papel, granularidade 1 convite)

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: O aviso de recusa lista os e-mails dos convites Pendentes do papel excluído — e só deles —, no máximo 5 por papel, dizendo quantos Pendentes faltam

    Cenário: [CT-04] o aviso lista só os e-mails Pendentes do papel excluído
      Dado o papel "suporte" com os convites Pendentes de "carla@example.com" e "dani@example.com" e o Expirado de "caio@example.com"
      E o papel "vendas" com o convite Pendente de "eva@example.com"
      Quando o master_global exclui o papel "suporte" pela ação da linha da tabela de papéis
      Então um aviso contém "carla@example.com" e "dani@example.com"
      E nenhum aviso contém "caio@example.com" nem "eva@example.com"

    Esquema do Cenário: [CT-05] o aviso mostra no máximo 5 e-mails por papel e diz quantos Pendentes faltam
      Dado <papeis>, cada um com <pendentes> convites Pendentes de e-mails distintos e mais um Expirado e um Aceito com validade futura
      Quando o master_global exclui <alvo> pela <caminho>
      Então o aviso contém exatamente <listados> desses e-mails Pendentes e nenhum e-mail dos convites Expirado e Aceito
      E <resto>

      Exemplos:
        | papeis                                         | pendentes | alvo                  | caminho                       | listados                       | resto                                                                 | # borda          |
        | o papel "suporte"                              | 5         | o papel "suporte"     | ação da linha                 | 5                              | o aviso não contém "e mais"                                           | borda            |
        | o papel "suporte"                              | 6         | o papel "suporte"     | ação da linha                 | 5                              | o aviso contém "e mais 1" e não contém "e mais 3"                     | borda+1          |
        | os papéis "suporte" e "gerente_de_contas"      | 6         | a seleção dos dois    | exclusão em massa             | 5 de cada papel, 10 no total   | o trecho de cada papel contém "e mais 1"; o aviso não contém "e mais 7" | borda+1 por papel |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | lista todos os convites do papel (`$papel->convites`), mortos inclusive | CT-04, CT-05 | nenhum aviso contém "caio@example.com" / os e-mails Expirado e Aceito; o mutante os lista |
| M12 | lista os Pendentes sem filtrar por papel (consulta solta em `convites`) | CT-04 | nenhum aviso contém "eva@example.com"; o mutante a lista |
| M13 | teto off-by-one (`take(4)` ou `> 5` ↔ `>= 5` no corte) | CT-05 linhas 1 e 2 | linha 5: exatamente 5 e-mails e sem "e mais"; o mutante mostra 4 + "e mais 1" — ou a linha 6 mostra 6 |
| M14 | "e mais N" com N = total de Pendentes em vez dos que faltam | CT-05 linha 2 | contém "e mais 1"; o mutante escreve "e mais 6" |
| M15 | corta em 5 sem dizer quantos faltam | CT-05 linha 2 | contém "e mais 1"; o mutante não tem o trecho |
| M37 | "e mais N" conta todos os convites do papel além dos 5 listados (mortos inclusive) — revisão adversarial (ADV-08) | CT-05 linhas 1 e 2 | linha 5: sem "e mais"; o mutante escreve "e mais 2" — linha 6: "e mais 1", o mutante "e mais 3" |
| M38 | teto e "e mais" aplicados à lista inteira da massa, não por papel — revisão adversarial (ADV-09/ADV-29) | CT-05 linha 3 | exatamente 10 e-mails, "e mais 1" em cada trecho; o mutante lista 5 no total e escreve "e mais 7" |

---

## Regra R4 — Na massa, um papel bloqueado barra a seleção inteira

> `P-03`, `RQ-04`, `P-07` · perfil **padrão** · técnica: **tabela de decisão** (condições: há papel bloqueado na seleção? há papel livre? o livre/o bloqueado tem convite morto?)

| Seleção | Ação esperada | Cenário |
|---|---|---|
| só livres | todos excluídos, mortos junto | CT-08 (linha massa, um livre), CT-14 (dois livres) |
| só bloqueado(s) | nada excluído — nem os mortos dos bloqueados —, aviso com os e-mails de cada bloqueado no trecho dele | CT-02 (linha massa), CT-07 |
| livre + bloqueado | nada excluído — nem o livre, nem os mortos do livre —, aviso com o bloqueado e sem o livre | CT-06 |

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Se algum papel selecionado tem convite Pendente, nada da seleção é excluído e o aviso traz cada papel bloqueado, pelo rótulo, com os e-mails dele

    Cenário: [CT-06] um papel bloqueado na seleção impede a exclusão do papel livre
      Dado o papel "financeiro_pj", criado antes, com o convite Expirado de "eva@example.com" e nenhum Pendente
      E o papel "suporte" com o convite Pendente de "carla@example.com"
      Quando o master_global exclui em massa a seleção "financeiro_pj" e "suporte", nessa ordem
      Então os papéis "financeiro_pj" e "suporte" e os convites de "eva@example.com" e "carla@example.com" continuam existindo
      E um aviso contém "carla@example.com" e nenhum aviso é de sucesso
      E nenhum aviso contém "Financeiro Pj" nem "financeiro_pj"

    Cenário: [CT-07] o aviso da massa traz cada papel bloqueado com os próprios e-mails
      Dado o papel "suporte" com o convite Pendente de "carla@example.com"
      E o papel "gerente_de_contas" com o convite Pendente de "dani@example.com" e o Expirado de "hel@example.com"
      Quando o master_global exclui em massa a seleção "suporte" e "gerente_de_contas"
      Então os papéis "suporte" e "gerente_de_contas" e os três convites continuam existindo
      E o trecho de "Suporte" no aviso contém "carla@example.com" e não contém "dani@example.com"
      E o trecho de "Gerente De Contas" no aviso contém "dani@example.com" e não contém "carla@example.com"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M16 | comportamento por registro do bulk: exclui os livres e recusa só os bloqueados | CT-06 | "financeiro_pj" continua existindo; o mutante o excluiu |
| M17 | processa a seleção em ordem e para no primeiro bloqueado, sem transação | CT-06 | "financeiro_pj" (processado antes) continua, com "eva"; o mutante já o excluiu com o convite |
| M18 | limpa os mortos de todos os selecionados antes de checar os Pendentes | CT-06 | o convite de "eva@example.com" continua; o mutante o apagou |
| M19 | para no primeiro bloqueado e só lista os e-mails dele | CT-07 | o trecho de "Gerente De Contas" contém "dani"; o mutante não traz dani nem o papel |
| M39 | o aviso nomeia todos os papéis da seleção, o livre inclusive — revisão adversarial (ADV-20) | CT-06 | nenhum aviso contém "Financeiro Pj"; o mutante o lista como bloqueado |
| M40 | junta os e-mails de todos os bloqueados numa lista só, sem vínculo com o papel — revisão adversarial (ADV-03/ADV-27) | CT-07 | o trecho de "Suporte" não contém "dani"; no mutante a lista única fica no trecho do primeiro papel |
| M41 | na massa recusada, apaga os mortos dos papéis bloqueados (limpeza por registro antes da checagem da seleção) — revisão adversarial (ADV-19) | CT-07 | o convite de "hel@example.com" continua existindo; o mutante o apagou |

---

## Regra R5 — Os convites mortos somem com o papel, e a remoção fica auditada

> `RQ-07`, `RQ-08`, `P-02` · perfil **padrão** · técnica: **rastreio de efeito** (aconteceu / não aconteceu fora do alvo / uma vez / conteúdo do registro) × **EP** (caminho)

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Convites Aceitos, Recusados e Expirados não impedem a exclusão: são apagados junto com o papel, cada um com um registro de exclusão na auditoria que guarda o e-mail, o papel e quem convidou, sem apagar o registro de criação

    Esquema do Cenário: [CT-08] excluir papel sem Pendente apaga os convites mortos dele, só os dele, auditando cada um uma vez
      Dado a auditoria ligada e "lia@example.com" como quem convidou
      E o papel "suporte" com os convites Aceito de "ana@example.com", Recusado de "bia@example.com" e Expirado de "caio@example.com", e nenhum Pendente
      E o papel "vendas" com o convite Expirado de "eva@example.com"
      Quando o master_global exclui o papel "suporte" pela <caminho>
      Então o papel "suporte" e os convites de ana, bia e caio não existem mais
      E cada um dos três tem exatamente 1 registro de auditoria "deleted", com o e-mail dele, o papel "suporte" e "lia@example.com" como quem convidou nos valores antigos
      E o registro de auditoria "created" de cada um dos três continua existindo
      E o convite de "eva@example.com" continua existindo, sem registro de auditoria "deleted"

      Exemplos:
        | caminho                                                |
        | ação Excluir da linha da tabela de papéis              |
        | ação Excluir do cabeçalho da edição do papel           |
        | exclusão em massa com só o papel "suporte" selecionado |

    Cenário: [CT-14] a exclusão em massa de dois papéis livres apaga e audita os mortos dos dois
      Dado a auditoria ligada
      E o papel "suporte" com o convite Expirado de "caio@example.com" e o papel "vendas" com o convite Recusado de "bia@example.com", nenhum Pendente
      Quando o master_global exclui em massa a seleção "suporte" e "vendas"
      Então os papéis "suporte" e "vendas" e os convites de "caio@example.com" e "bia@example.com" não existem mais
      E cada um dos dois convites tem exatamente 1 registro de auditoria "deleted"
```

> "o papel" e "quem convidou" nos valores antigos = `old_values` com `role_id` = id do "suporte" e `convidado_por_id` = id da lia. Se o `$fillable` do convite não expuser essas colunas à auditoria, o CT fica vermelho por **implementação divergente** (RQ-08: "o registro de quem convidou quem continua na auditoria") — não por CT errado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | remoção por query em massa (`$papel->convites()->delete()` / `whereIn()->delete()`), que não dispara evento de model | CT-08, CT-14 | 1 registro `deleted` por convite; o mutante grava 0 |
| M21 | apaga os convites mortos de todos os papéis (filtro pelo estado, sem `role_id`) | CT-08 | "eva@example.com" continua existindo; o mutante a apagou |
| M22 | limpeza só na ação da linha; edição e massa batem na FK | CT-08 linhas "edição" e "massa" | papel "suporte" não existe mais e a ação termina sem exceção; o mutante estoura `QueryException` |
| M23 | apaga só Aceitos e Expirados, esquece o Recusado (`recusado_em`, coluna posterior) | CT-08, CT-14 | "bia@example.com" não existe mais; no mutante ela fica e a FK recusa o papel |
| M42 | auditoria do convite restrita (`auditInclude`/`$fillable` sem `role_id`/`convidado_por_id`): o "deleted" só traz o e-mail — revisão adversarial (ADV-16) | CT-08 | `old_values` com `role_id` do suporte e `convidado_por_id` da lia; o mutante não os traz |
| M43 | a limpeza apaga também a trilha do convite (poda de `audits` órfãos do papel) — revisão adversarial (ADV-24/ADV-34) | CT-08 | o registro "created" de ana, bia e caio continua; o mutante o apagou |
| M44 | na massa, a limpeza dos mortos roda só para o primeiro papel da seleção (fora do laço por registro) — revisão adversarial (ADV-15) | CT-14 | "vendas" e "bia" não existem mais; no mutante bia fica e a FK derruba a exclusão de vendas |

---

## Regra R6 — A saída da recusa: aceitar continua possível, revogar libera

> `RQ-05`, `RQ-06` · perfil **padrão** · técnica: **2-switch** (recusa → aceite; recusa → revogação → nova exclusão)

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Depois da recusa o convite Pendente continua aceitável com o mesmo papel; revogado na tela de Convites, a exclusão do papel passa

    Cenário: [CT-09] o convite Pendente continua aceitável com o papel depois da recusa
      Dado o papel "suporte" com o convite Pendente de "carla@example.com"
      E a exclusão do papel "suporte" pela ação da linha já foi recusada
      Quando "carla@example.com" aceita o convite com conta nova
      Então existe o usuário "carla@example.com" com o papel "suporte"
      E o convite de "carla@example.com" está aceito

    Cenário: [CT-10] revogado o convite Pendente na tela de Convites, a nova exclusão passa
      Dado o papel "suporte" com o convite Pendente de "carla@example.com" e o Expirado de "caio@example.com"
      E a exclusão do papel "suporte" pela ação da linha já foi recusada
      E o master_global revogou o convite de "carla@example.com" pela ação Excluir da tabela de Convites
      Quando o master_global exclui de novo o papel "suporte" pela ação da linha
      Então o papel "suporte" não existe mais
      E o convite de "caio@example.com" não existe mais
      E nenhum aviso contém "carla@example.com"
```

> CT-10 é o par de "estado de erro declara a saída": a recusa de CT-02 tem destino alcançável (tela de Convites → nova tentativa). Revogação: `Livewire::test(ListConvites::class)->callAction(TestAction::make('delete')->table($convite))`, como em `ConviteTest` "revoga o convite"; avisos limpos com `limparAvisosDoFilament()` antes do `Quando`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M24 | a recusa "solta" o convite Pendente do papel (zera `role_id`) para não bloquear da próxima vez | CT-09 | o usuário carla tem o papel "suporte"; no mutante o aceite cai no invariante do papel nulo (`[CT-13]` da `phpstan-nivel-8`) e não cria o usuário |
| M25 | a recusa marca o papel como bloqueado (flag/cache persistente) e a nova tentativa continua recusada sem reconsultar | CT-10 | o papel "suporte" não existe mais; o mutante recusa de novo |
| M26 | a revogação conta como Pendente por algum resto (consulta incluindo `trashed` ou cache do conjunto de Pendentes) | CT-10 | nenhum aviso contém "carla@example.com" e o papel some; o mutante recusa listando carla |

---

## Regra R7 — Os convites de todas as organizações contam

> `P-05`, `RQ-02`, `RQ-07`, `RQ-08` · perfil **padrão** · técnica: **EP** (organização do convite: corrente × outra) × caminho · `@premissa` de mecanismo: o escopo de organização do convite é forçado a valer (a Acme corrente) — sem isso o escopo `BelongsToTenant` falha aberto no `/admin` (`.ai/rules/resources.md`) e o cenário não discriminaria; é a afirmação negativa "no /admin o escopo não morde" escrita como se fosse falsa

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Convites de qualquer organização contam para recusar e para apagar junto, não só os da organização corrente, em todo caminho

    Esquema do Cenário: [CT-11] o convite Pendente de outra organização também recusa a exclusão
      Dado as organizações "Acme" e "Globex", com a Acme como organização corrente do contexto
      E o papel "suporte" com o convite Pendente de "carla@example.com" da Globex, e nenhum convite da Acme
      Quando o master_global exclui o papel "suporte" pela <caminho> do /admin
      Então a ação termina sem exceção e um aviso contém "carla@example.com"
      E o papel "suporte" e o convite de "carla@example.com" continuam existindo

      Exemplos:
        | caminho                                                |
        | ação Excluir da linha da tabela de papéis              |
        | ação Excluir do cabeçalho da edição do papel           |
        | exclusão em massa com só o papel "suporte" selecionado |

    Esquema do Cenário: [CT-12] os convites mortos das duas organizações somem com o papel, auditados
      Dado as organizações "Acme" e "Globex", com a Acme como organização corrente do contexto, e a auditoria ligada
      E o papel "suporte" com o convite Aceito de "ana@example.com" da Acme e o Expirado de "gil@example.com" da Globex, e nenhum Pendente
      Quando o master_global exclui o papel "suporte" pela <caminho> do /admin
      Então o papel "suporte" e os convites de "ana@example.com" e "gil@example.com" não existem mais
      E cada um dos dois convites tem exatamente 1 registro de auditoria "deleted"

      Exemplos:
        | caminho                                                |
        | ação Excluir da linha da tabela de papéis              |
        | ação Excluir do cabeçalho da edição do papel           |
        | exclusão em massa com só o papel "suporte" selecionado |

    Cenário: [CT-19] revogado no /admin o Pendente de outra organização, a nova exclusão passa
      Dado as organizações "Acme" e "Globex", com a Acme como organização corrente do contexto
      E o papel "suporte" com o convite Pendente de "carla@example.com" da Globex, e a exclusão de "suporte" pela ação da linha já foi recusada
      E o master_global revogou o convite de "carla@example.com" pela ação Excluir da tabela de Convites do /admin
      Quando o master_global exclui de novo o papel "suporte" pela ação da linha, no mesmo componente da tabela de papéis que recusou
      Então o convite de "carla@example.com" e o papel "suporte" não existem mais
      E nenhum aviso contém "carla@example.com"
```

> CT-19 é a saída da recusa de CT-11 (RQ-06 × P-05): o destino que o aviso aponta — a tela de Convites — tem de alcançar o convite da outra organização. Revogação como em CT-10 (`ListConvites`, `TestAction::make('delete')->table($convite)`), avisos limpos antes do `Quando`; o `Quando` reusa a mesma instância `Livewire::test(ListRoles::class)` da recusa. Se a tela de Convites do `/admin` não alcançar o convite da Globex com a Acme fixada, o CT fica vermelho no `Dado` — achado de desenho (saída inalcançável), a voltar ao `00` como pergunta, não CT a cortar.
> Sanidade do arranjo (obrigatória antes do `Quando`): `Convite::query()->count()` enxerga só os convites da Acme — prova que o escopo está mordendo; se o painel `/admin` não montar com a organização fixada, é lacuna de arnês a registrar com o que foi tentado, não cenário cortado. Auditoria do convite da Globex conferida com `DB::table('audits')` (sem escopo). Fixtures pela `ofertaPara()`, que garante a organização pedida.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M27 | conta os Pendentes pela relação com o escopo de organização (só a corrente) | CT-11 | a ação termina sem exceção com o aviso; o mutante não vê carla, tenta excluir e estoura a `QueryException` da FK |
| M28 | apaga os mortos pela relação escopada (só os da Acme) | CT-12 | "gil@example.com" não existe mais e o papel some; no mutante gil fica e a FK derruba a exclusão |
| M45 | remove os mortos com `withoutGlobalScopes()->delete()` pela query, sem evento de model — revisão adversarial (ADV-14/ADV-23) | CT-12 | 1 registro `deleted` para ana e para gil; o mutante grava 0 |
| M46 | escopo removido só na ação da linha; edição e massa consultam pela relação escopada — revisão adversarial (ADV-13) | CT-11 e CT-12 linhas "edição" e "massa" | sem exceção, com o aviso (CT-11) / gil não existe mais (CT-12); o mutante estoura a FK nesses caminhos |
| M60 | os Pendentes de todas as organizações são lidos uma vez e guardados no estado do componente (propriedade/`#[Computed(persist: true)]`), e a nova tentativa reusa a lista — revisão adversarial, rodada 2 (ADV-38) | CT-19 | o papel "suporte" não existe mais e nenhum aviso contém carla; o mutante recusa de novo listando carla |

---

## Regra R8 — Os e-mails só aparecem para quem pode ver os convites

> `P-06`, `RQ-03` · perfil **padrão** (Impacto 3) · técnica: **matriz papel × ação** — a ação é sempre a mesma (excluir papel bloqueado, permitida aos dois lados); as dimensões são a permissão `ViewAny:Convite` (com/sem, mesma persona base `admin`), o caminho (linha/edição/massa) e o envolvimento do operador com os convites (nenhum / convidou / é o convidado)

Fixture comum de CT-13: papel "suporte" com os Pendentes `pend.carla@`, `pend.dani@`, `pend.fabi@`, o Expirado `caio@`, o Aceito `ana@` e o Recusado `bia@` (os dois com validade futura); papel "vendas" com o Pendente `eva@`. Números discriminantes: 3 = Pendentes do suporte (certo); 4 = Pendentes de todos os papéis; 5 = suporte sem olhar aceito/recusado; 6 = todos os convites do suporte; 7 = todos os convites.

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: O aviso de recusa mostra os e-mails só a quem pode ver os convites; a quem só pode excluir papéis, mostra a quantidade de convites Pendentes de cada papel, sem e-mail, sem parte dele e sem atalho para a tela de Convites

    Esquema do Cenário: [CT-13] a permissão de ver convites decide entre e-mails e quantidade, na linha e na edição
      Dado o papel "suporte" com 3 convites Pendentes, um Expirado, um Aceito e um Recusado com validade futura, e o papel "vendas" com 1 Pendente
      E o operador "operador.rui@example.com", com o papel admin, que pode excluir papéis, e <permissao> a permissão de ver convites
      Quando o operador exclui o papel "suporte" pela <caminho>
      Então o papel "suporte" e os seus seis convites continuam existindo
      E o aviso <conteudo>

      Exemplos:
        | permissao | caminho                         | conteudo                                                                                                                                                       |
        | com       | ação da linha                   | contém "pend.carla@example.com", "pend.dani@example.com" e "pend.fabi@example.com", e nenhum dos e-mails de caio, ana, bia e eva                                |
        | com       | ação do cabeçalho da edição     | contém "pend.carla@example.com", "pend.dani@example.com" e "pend.fabi@example.com", e nenhum dos e-mails de caio, ana, bia e eva                                |
        | sem       | ação da linha                   | contém o número 3 isolado e nenhum dos números 4, 5, 6 e 7 isolados; não contém "@example.com" nem "pend.carla", "pend.dani", "pend.fabi"; sem atalho para Convites |
        | sem       | ação do cabeçalho da edição     | contém o número 3 isolado e nenhum dos números 4, 5, 6 e 7 isolados; não contém "@example.com" nem "pend.carla", "pend.dani", "pend.fabi"; sem atalho para Convites |

    Esquema do Cenário: [CT-17] na massa, a quantidade sem a permissão é por papel, e os e-mails com ela ficam no trecho de cada papel
      Dado o papel "suporte" com os Pendentes "pend.carla@example.com", "pend.dani@example.com" e "pend.fabi@example.com", o Expirado de "caio@example.com" e o Aceito de "ana@example.com" e o Recusado de "bia@example.com", os dois com validade futura
      E o papel "gerente_de_contas" com os Pendentes "pend.gil@example.com" e "pend.hel@example.com"
      E o operador "operador.rui@example.com", com o papel admin, e <permissao> a permissão de ver convites
      Quando o operador exclui em massa a seleção "suporte" e "gerente_de_contas"
      Então os dois papéis e os oito convites continuam existindo
      E <conteudo>

      Exemplos:
        | permissao | conteudo                                                                                                                                                                             |
        | com       | o trecho de "Suporte" contém os três e-mails Pendentes do suporte e nenhum do gerente, de caio, de ana ou de bia; o trecho de "Gerente De Contas" contém os dois e-mails do gerente e nenhum do suporte |
        | sem       | o trecho de "Suporte" contém o número 3 isolado e nenhum 5 nem 6 isolado; o trecho de "Gerente De Contas" contém o número 2 isolado; o aviso não contém "@example.com", nem "pend.", nem "caio", "ana@", "bia@"; sem atalho para Convites |

    Esquema do Cenário: [CT-18] sem a permissão, o aviso esconde os e-mails mesmo de quem convidou ou foi convidado
      Dado o operador "operador.rui@example.com", com o papel admin, sem a permissão de ver convites
      E o papel "suporte" com 3 convites Pendentes, <envolvimento>
      Quando o operador exclui o papel "suporte" pela ação da linha da tabela de papéis
      Então o papel "suporte" e os três convites continuam existindo
      E o aviso contém o número 3 isolado, não contém "@example.com" nem a parte local de nenhum dos três e-mails, e não tem atalho para Convites

      Exemplos:
        | envolvimento                                                                                 |
        | todos convidados pelo próprio operador ("pend.carla", "pend.dani", "pend.fabi")               |
        | um deles para o e-mail do próprio operador ("pend.carla", "pend.dani", "operador.rui")        |
```

> Arranjo: ver `### Personas` (operador `admin`, sanidade de `can()`). "Número isolado" = `(?<!\d)N(?!\d)` sobre o texto do aviso (título + corpo). "Atalho para Convites": ver `### Fixtures`; o texto "Revogue… na tela de Convites" é permitido. Na linha "com" o atalho não é afirmado.
> Gate de camada da regra (autorização por fora da UI): **lacuna declarada** — o aviso só existe no componente; nenhuma rota ou serviço do requisito expõe a lista de e-mails fora dele. A permissão sobre a **exclusão** em si não muda (02) e tem seu cenário por fora em `TelaDePapeisTest`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M29 | sempre lista os e-mails, sem checar permissão (P-06 ignorada) | CT-13 linhas "sem" | não contém "@example.com"; o mutante mostra os três e-mails |
| M30 | checa "é `master_global`?" em vez da permissão | CT-13 linhas "com" | contém os três e-mails para o `admin` com `ViewAny:Convite`; o mutante esconde (admin não é master) |
| M31 | checa a permissão errada (`ViewAny:Role`/`Delete:Role`, que o operador tem nas duas linhas) | CT-13 linhas "sem" | não contém "@example.com"; o mutante vê `Delete:Role` verdadeiro e lista |
| M32 | quantidade conta todos os convites do papel, mortos inclusive | CT-13 linhas "sem" | número 3 isolado e nenhum 6; o mutante mostra 6 |
| M33 | sem a permissão, omite a lista e também a quantidade (aviso genérico) | CT-13 linhas "sem" | contém 3 isolado; o mutante não traz número algum |
| M47 | checagem de permissão só na ação da linha; edição e massa sempre listam — revisão adversarial (ADV-04/ADV-05) | CT-13 linha "sem, edição"; CT-17 linha "sem" | não contém "@example.com"; o mutante lista os e-mails |
| M48 | na massa sem permissão, mostra o total de Pendentes da seleção, não por papel — revisão adversarial (ADV-28) | CT-17 linha "sem" | 3 no trecho de "Suporte" e 2 no de "Gerente De Contas"; o mutante escreve 5 nos dois trechos (o 5 isolado no trecho de "Suporte" é proibido) |
| M58 | a contagem da massa (código próprio, por papel) conta todos os convites do papel ou todo convite com validade futura — revisão adversarial, rodada 2 (ADV-36) | CT-17 linha "sem" | trecho de "Suporte" com 3 isolado e sem 6 nem 5; o mutante escreve 6 (todos) ou 5 (3 + Aceito + Recusado com validade futura) |
| M49 | conta como Pendente todo convite com validade futura (ignora aceito/recusado) — revisão adversarial (ADV-06/ADV-07) | CT-13 linhas "sem" | 3 isolado e nenhum 5; o mutante conta 5 (3 + Aceito + Recusado) |
| M50 | conta os Pendentes sem filtrar por papel — revisão adversarial (ADV-06/ADV-18) | CT-13 linhas "sem" | 3 isolado e nenhum 4; o mutante conta 4 (eva do "vendas") |
| M51 | "mascara" o e-mail mostrando só a parte local (`pend.carla@…`) — revisão adversarial (ADV-17) | CT-13 linhas "sem", CT-18 | não contém "pend.carla" etc.; o mutante mostra a parte local |
| M52 | exceção de "dono": mostra o e-mail a quem convidou ou ao próprio convidado — revisão adversarial (ADV-30/ADV-31) | CT-18 (as duas linhas) | não contém "@example.com" nem a parte local; o mutante mostra os convites do operador |
| M53 | o aviso oferece ação "Ver convites"/link para a tela de Convites sem checar `ViewAny:Convite` — revisão adversarial (ADV-33) | CT-13 linhas "sem", CT-17 linha "sem", CT-18 | sem atalho para Convites; o mutante tem a ação com a URL de Convites |

---

## Regra R9 — Quem pode excluir o papel apaga os convites encerrados

> `P-08`, `RQ-07`, `RQ-08` · perfil **padrão** · técnica: **matriz papel × ação** (célula: pode excluir papel, não tem permissão alguma de convite) + **rastreio de efeito** (apagado e auditado em nome do operador)

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: Os convites encerrados saem com o papel para quem pode excluir o papel, sem exigir permissão de excluir convite

    Cenário: [CT-15] o operador sem permissão de convite exclui o papel e os convites encerrados saem auditados
      Dado a auditoria ligada e o operador "operador.rui@example.com", com o papel admin, que pode excluir papéis e não tem permissão alguma sobre convites
      E o papel "suporte" com os convites Aceito de "ana@example.com", Recusado de "bia@example.com" e Expirado de "caio@example.com", e nenhum Pendente
      Quando o operador exclui o papel "suporte" pela ação da linha da tabela de papéis
      Então o papel "suporte" e os convites de ana, bia e caio não existem mais
      E cada um dos três tem exatamente 1 registro de auditoria "deleted", em nome do operador
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M54 | a limpeza autoriza cada convite (`can('delete', $convite)`/`authorize`) e recusa ou pula os não autorizados — revisão adversarial (ADV-01/ADV-32) | CT-15 | o papel e os três convites não existem mais; o mutante recusa (papel fica) ou pula os convites e a FK derruba a exclusão |
| M55 | para contornar a falta de permissão, apaga por query sem evento ou fora do contexto do operador (job/sistema) — revisão adversarial (ADV-25) | CT-15 | 1 `deleted` por convite com `user_id` do operador; o mutante grava 0 ou sem autor |

---

## Regra R10 — O papel bloqueado aparece pelo rótulo

> `P-07`, `RQ-03`, `P-03` · perfil **mínimo** · técnica: **EP** (caminho), com papel de rótulo diferente da chave

```gherkin
# language: pt
Funcionalidade: Excluir pela tela um papel que tem convite

  Regra: No aviso de recusa, o papel bloqueado aparece pelo rótulo que a tela de papéis usa como título do registro

    Esquema do Cenário: [CT-16] o aviso nomeia o papel bloqueado pelo rótulo, em cada caminho e nas duas formas do aviso
      Dado o papel "gerente_de_contas", cujo rótulo é "Gerente De Contas", com o convite Pendente de "carla@example.com"
      Quando <quem> exclui o papel "gerente_de_contas" pela <caminho>
      Então o papel "gerente_de_contas" continua existindo
      E um aviso contém "Gerente De Contas"
      E nenhum aviso contém "gerente_de_contas"

      Exemplos:
        | quem                                                                  | caminho                                                          | # forma do aviso         |
        | o master_global                                                       | ação Excluir da linha da tabela de papéis                        | com e-mails              |
        | o master_global                                                       | ação Excluir do cabeçalho da edição do papel                     | com e-mails              |
        | o master_global                                                       | exclusão em massa com só o papel "gerente_de_contas" selecionado | com e-mails              |
        | o operador admin, que pode excluir papéis, sem a permissão de ver convites | ação Excluir da linha da tabela de papéis                        | só a quantidade (P-06)   |
        | o operador admin, que pode excluir papéis, sem a permissão de ver convites | ação Excluir do cabeçalho da edição do papel                     | só a quantidade (P-06)   |
```

> A asserção é só sobre o texto dos avisos (`avisosDoFilament()`), nunca sobre o HTML da página: a chave aparece legitimamente no campo "Nome" da edição. Linhas do operador: arranjo de `### Personas` (`semAPermissao('admin', 'ViewAny:Convite')` + sanidade de `can()`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M56 | o aviso usa `$papel->name` | CT-16 (todas as linhas) | contém "Gerente De Contas" e não "gerente_de_contas"; o mutante traz a chave |
| M57 | rótulo só no aviso da massa; linha e edição (código próprio) usam a chave — revisão adversarial (ADV-26) | CT-16 linhas "linha" e "edição" | contém "Gerente De Contas"; o mutante traz "gerente_de_contas" |
| M59 | o ramo "só a quantidade" do aviso (sem `ViewAny:Convite`) monta a frase com a chave do papel — revisão adversarial, rodada 2 (ADV-37) | CT-16 linhas do operador sem a permissão | contém "Gerente De Contas" e não "gerente_de_contas"; o mutante escreve "gerente_de_contas: 1 convite pendente" |

---

## Revisão Adversarial

Rodada 1 (`fw-adversario-ct`, contexto novo; entrada: `00` + `04` + glossário): **35 achados** (ADV-01…ADV-35), todos fechados; ADV-35 com destino "não se aplica". Rodada 2 (re-revisão única, disparada pelos cenários novos CT-14…CT-18; é a última pelo teto de 2 rodadas): **4 achados** (ADV-36…ADV-39), todos fechados sem nova rodada. O fechamento da rodada 2 criou CT-19, que **não** passa por nova revisão (teto atingido) — registrado como limite, não como aprovação.

| Achado | Fechamento | CT | Mutante |
|---|---|---|---|
| ADV-01, ADV-32, ADV-25 | cenário novo (P-08, operador sem permissão de convite, auditoria em nome dele) | CT-15 | M54, M55 |
| ADV-02, ADV-26 | cenário novo (P-07, rótulo ≠ chave, três caminhos); CT-06/CT-07/CT-17 também com rótulo | CT-16 | M56, M57 |
| ADV-03, ADV-27, ADV-19 | `Então` reforçado: vínculo por trecho; convites (inclusive um Expirado do bloqueado) continuam | CT-07 | M40, M41 |
| ADV-04, ADV-05, ADV-28 | CT-13 ganha a edição; cenário novo da massa com quantidade por papel | CT-13, CT-17 | M47, M48 |
| ADV-06, ADV-07, ADV-18 | fixture do CT-13 com Pendente de outro papel, Aceito e Recusado com validade futura; número isolado; 4–7 proibidos | CT-13 | M49, M50, M32 |
| ADV-08, ADV-09, ADV-29 | CT-05 com mortos no papel e linha de massa (teto por papel) | CT-05 | M37, M38 |
| ADV-10, ADV-22 | `Então` reforçado: permissão e usuário do papel mantidos nos quatro caminhos | CT-02, CT-03 | M34 |
| ADV-11, ADV-21 | `Então` reforçado: nenhum aviso de sucesso na recusa | CT-02, CT-06 | M35 |
| ADV-12 | `Então` reforçado: e-mails mortos fora do aviso nos três caminhos | CT-02 | M36 |
| ADV-13 | CT-11 e CT-12 viram Esquema com linha, edição e massa | CT-11, CT-12 | M46 |
| ADV-14, ADV-23 | `Então` reforçado: 1 "deleted" por convite nas duas organizações | CT-12 | M45 |
| ADV-15 | cenário novo: massa com dois livres | CT-14 | M44 |
| ADV-16, ADV-24, ADV-34 | `Então` reforçado: e-mail, papel e quem convidou em `old_values`; "created" continua | CT-08 | M42, M43 |
| ADV-17, ADV-30, ADV-31 | parte local proibida; cenário novo com o operador que convidou e com o operador convidado | CT-13, CT-18 | M51, M52 |
| ADV-20 | `Então` reforçado: o papel livre não aparece no aviso | CT-06 | M39 |
| ADV-33 | `Então` reforçado: sem ação/link para Convites sem `ViewAny:Convite` | CT-13, CT-17, CT-18 | M53 |
| ADV-35 (sonda 8, teto de texto) | **não se aplica**: o `00` não tem campo de texto livre novo; o único teto de texto é o de P-04 (5 e-mails por papel), já em CT-05 — destino registrado pela rodada 2 (ADV-39) | — | — |
| **Rodada 2** | | | |
| ADV-36 | `Dado` e `Então` reforçados: na massa sem `ViewAny:Convite`, "suporte" ganha Expirado, Aceito e Recusado (os dois com validade futura); trecho de "Suporte" com 3 isolado e sem 5 nem 6 | CT-17 | M58 |
| ADV-37 | CT-16 ganha as linhas do operador sem `ViewAny:Convite` (aviso só com a quantidade), na linha e na edição: rótulo, nunca a chave | CT-16 | M59 |
| ADV-38 | cenário novo (Tenancy): com a Acme corrente, recusado por Pendente da Globex, revogado na tela de Convites do `/admin`, a nova exclusão passa | CT-19 | M60 |
| ADV-39 | registro do destino de ADV-35 nesta tabela | — (linha ADV-35) | — |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a feature não recebe `{id}` novo; o registro é resolvido pela query da tabela (02, linhas `DeleteAction`/`DeleteBulkAction`) | — |
| Autorização exercida na ação | CT-13, CT-17, CT-18 (visibilidade do e-mail por `ViewAny:Convite`, os dois lados, persona não-master, três caminhos); CT-15 (P-08: excluir papel basta para apagar os encerrados). `RolePolicy::delete()`/`authorizeIndividualRecords('delete')` não mudam (02) e seguem travadas por `TelaDePapeisTest` CT-05…CT-07 | G1 |
| Idempotência (ancorada no agregado) | CT-10 (segunda tentativa depois da recusa decide pelo estado atual); CT-08/CT-14 (exatamente 1 "deleted" por convite); repetição da exclusão permitida não tem alvo | G1 |
| Concorrência | lacuna declarada: convite Pendente criado entre a checagem e o `DELETE` exige duas conexões; SQLite de teste em memória não sustenta; a FK sem cascade continua sendo a trava (CT-03 prova que ela não é contornada) | G2 |
| Fronteira no ponto de entrada (gravação) | CT-01 (validade × agora, 3 valores), CT-05 (teto 5 por papel) | G1 |
| Domínio condicionado (tipo × valor) | CT-01 e CT-13 (validade só importa se não aceito e não recusado: Aceito/Recusado com validade futura) | G1 |
| Estado × operação de escrita (excluído ainda funciona?) | CT-09 (convite Pendente depois da recusa ainda aceita), CT-10 (convite revogado deixa de bloquear) | G1 |
| Cardinalidade do destinatário (0 / 1 / N) | 0 Pendente: CT-01 linha "sem convite"; 1: CT-02; N: CT-04, CT-05, CT-13; mortos N: CT-08; papéis livres N: CT-14 | G1 |
| Não-efeito com destinatário real | CT-02 (mortos, permissão, usuário e auditoria existentes), CT-06 (livre com morto), CT-07 (bloqueado com morto), CT-03 (falha depois do ponto do efeito do `deleting` do spatie) | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-01 linha "sem validade gravada" (`expira_em` nulo = Expirado, P-01) | G1 |
| Paginação / ordenação | não se aplica: o aviso não pagina; ordem dos e-mails e dos papéis não determinada e não afirmada (Fronteira) | — |
| Timezone / DST | não se aplica: a comparação é instante × instante no relógio da aplicação; relógio congelado ao meio-dia evita virada de dia | — |
| Texto livre (e-mail no corpo do aviso) | lacuna declarada: escape do e-mail no corpo do aviso não é derivável do `00`; o e-mail passa pela validação do formulário de convite; fica para a dimensão de segurança do quality gate | G1 |
| Dado pessoal de terceiro exposto | CT-13, CT-17, CT-18 (nem e-mail, nem parte local, nem atalho sem a permissão) | G1 |
| Unicidade + soft delete | não se aplica: nenhuma unicidade nova; revogação é exclusão física (comentário da migration de convites) — CT-10 prova que o revogado não volta a contar | — |
| CRUD combinado (excluir duas vezes) | CT-10 | G1 |
| Mass assignment | não se aplica: nenhum formulário ou payload novo (02: "Nenhum `public function` nem `public $` novo") | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| Superfície Livewire | não se aplica a superfície nova (02); os três pontos de framework são exercitados com papel bloqueado e livre em CT-02, CT-08, CT-11, CT-12, CT-13, CT-16 | G1, G3 |
| Estado do framework usado sem validar | não se aplica: a feature não consome `$tableFilters`/`$tableSearch`; a seleção da massa é resolvida pela query da tabela (02) | — |
| IDOR por entidade (por tabela) | `roles`: não se aplica (autorização inalterada, `TelaDePapeisTest` CT-05) · `convites`: CT-08 ("eva" de outro papel continua), CT-04 e CT-13 (convite de outro papel fora do aviso e da contagem) · `audits`: CT-08 ("created" preservado) · pivôs de permissão/usuário: CT-02, CT-03 | G1, G2 |
| Escopo com discriminante nulo | CT-11, CT-12 (escopo de organização forçado; os de outra organização contam, nos três caminhos); `tenant_id` nulo na suíte Kit: CT-02, CT-08 | G3, G1 |
| Saída do estado de erro | CT-10 | G1 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a situação do único convite decide se a exclusão é recusada | R1 | EP + BVA 3-valores | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M1, M2, M3, M4, M5 |
| CT-02 | recusa com aviso e nada apagado, em cada caminho | R2 | EP + não-efeito | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M6, M7, M9, M10, M34, M35, M36 |
| CT-03 | exclusão direta pelo model também não apaga nada | R2 | não-efeito (atomicidade) | G2 | Pest feature HTTP | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M8, M34 |
| CT-04 | o aviso lista só os e-mails Pendentes do papel | R3 | EP | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M11, M12 |
| CT-05 | no máximo 5 e-mails por papel, com "e mais N" só de Pendentes | R3 | BVA 2-valores | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M11, M13, M14, M15, M37, M38 |
| CT-06 | um bloqueado na seleção impede a exclusão do livre | R4 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M16, M17, M18, M35, M39 |
| CT-07 | aviso da massa traz cada bloqueado com os próprios e-mails | R4 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M19, M40, M41 |
| CT-08 | mortos somem com o papel, só os dele, auditados uma vez com e-mail, papel e quem convidou | R5 | rastreio de efeito × EP | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M20, M21, M22, M23, M42, M43 |
| CT-09 | Pendente continua aceitável depois da recusa | R6 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M24 |
| CT-10 | revogado o Pendente, a nova exclusão passa | R6 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M25, M26 |
| CT-11 | Pendente de outra organização também recusa, em cada caminho | R7 | EP × caminho | G3 | componente Livewire/Filament | `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` | M27, M46 |
| CT-12 | mortos das duas organizações somem com o papel, auditados, em cada caminho | R7 | EP × caminho | G3 | componente Livewire/Filament | `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` | M28, M45, M46 |
| CT-13 | permissão de ver convites decide entre e-mails e quantidade, na linha e na edição | R8 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M29, M30, M31, M32, M33, M47, M49, M50, M51, M53 |
| CT-14 | massa de dois livres apaga e audita os mortos dos dois | R5 | rastreio de efeito | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M20, M23, M44 |
| CT-15 | operador sem permissão de convite exclui o papel e os encerrados saem auditados | R9 | matriz papel × ação + rastreio de efeito | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M54, M55 |
| CT-16 | o aviso nomeia o papel bloqueado pelo rótulo, em cada caminho e nas duas formas do aviso | R10 | EP (caminho × forma do aviso) | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M56, M57, M59 |
| CT-17 | na massa, quantidade por papel sem a permissão e e-mails por trecho com ela | R8 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M47, M48, M53, M58 |
| CT-18 | sem a permissão, nem quem convidou nem o convidado vê o e-mail | R8 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/ExclusaoDePapelComConviteTest.php` | M51, M52, M53 |
| CT-19 | revogado no /admin o Pendente de outra organização, a nova exclusão passa | R7 | 2-switch × EP (organização) | G3 | componente Livewire/Filament | `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` | M60 |

Rastreio de origem: RQ-01 → CT-02, CT-11 · RQ-02 → CT-01, CT-02, CT-03, CT-11 · RQ-03 → CT-04, CT-05, CT-07, CT-13, CT-16, CT-17, CT-18 · RQ-04 → CT-02, CT-03, CT-06, CT-07 · RQ-05 → CT-09 · RQ-06 → CT-10, CT-19 · RQ-07 → CT-01, CT-08, CT-12, CT-14, CT-15 · RQ-08 → CT-08, CT-12, CT-14, CT-15 · RQ-09 → CT-02, CT-03 · P-01 → CT-01, CT-13 · P-02 → CT-02, CT-08, CT-16 · P-03 → CT-06, CT-07, CT-17 · P-04 → CT-05 · P-05 → CT-11, CT-12, CT-19 · P-06 → CT-13, CT-16, CT-17, CT-18 · P-07 → CT-06, CT-07, CT-16, CT-17 · P-08 → CT-15.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-01 repetido nos três caminhos | o predicado de Pendente é um só; o caminho já é partição em CT-02/CT-08 (M6, M22) |
| CT-05 com 7 Pendentes ("e mais 2") | mata o mesmo M14 que a linha 6 |
| recusa repetida duas vezes seguidas sem revogar | mata o mesmo conjunto de CT-02 (nada apagado); a segunda tentativa relevante é a de CT-10 |
| CT-15 nos três caminhos | a autorização da limpeza não depende do caminho; o caminho já é partição de CT-08 (M22) |
| CT-18 na edição e na massa | o envolvimento do operador não depende do caminho; M47 já cobre a visibilidade por caminho (CT-13, CT-17) |

## Sem CT-B

- Motivo: as três linhas da `## Superfície de UI` do `01` declaram "Depende de JS? Não". Tudo o que os cenários afirmam — o aviso (notificação do Filament: título, corpo, `status`, ações), o resultado das ações de linha, de cabeçalho e de massa, e o estado do banco — é observável por teste de componente Livewire/Filament (`avisosDoFilament()`, `mountAction`/`callMountedAction`, `selectTableRecords`). Nenhum cenário afirma JavaScript executado, console, acessibilidade, cor ou layout; nenhuma linha de `## Costuras de Teste` é `browser`, e por isso o `05` não foi criado.
