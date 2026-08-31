# Relatório de QA — Travas de escalada na tela de papéis e no login social

- **Data**: 2026-08-31
- **Escopo**: wiki de **correção** que toca infra compartilhada (`AdministradorDaInstalacao`, `RolePolicy`) → **regressão obrigatória** contra `tests/Kit` e `tests/Tenancy` inteiras.
- **Oráculo**: `00-requisito.md` (RQ-01..RQ-09), com sete ambiguidades resolvidas em `## Ambiguidades`.

## Matriz de Rastreabilidade

| RQ | Passo do PRD | Código | Cenário | Teste |
|----|---|---|---|---|
| RQ-01 nome reservado | 2 | `AdministradorDaInstalacao::regraDeNomeDePapel()` + `RoleResource` `name` | CT-01..CT-04 | `tests/Kit/TelaDePapeisTest.php:625,657,688,706` |
| RQ-02 papel super-admin não editável | 1 | `RolePolicy::update()` + `papelEditavelPor()` | CT-05 (linha update), CT-06, CT-07 | `TelaDePapeisTest.php:732,782,818` |
| RQ-03 papel super-admin não excluível | 1 | `RolePolicy::delete()`; `DeleteBulkAction->authorizeIndividualRecords('delete')` | CT-05 (linhas delete e bulk) | `TelaDePapeisTest.php:732` |
| RQ-04 alcance por painel | 3 | `recortarConcessao()`, `regraDeConcessao()`, `paineisAoAlcance()` | CT-08..CT-11, CT-23 | `tests/Tenancy/PapeisPorOrganizacaoTest.php:243,271,288,307,396` |
| RQ-05 vale nos caminhos de concessão | 3 | os três caminhos consomem os mesmos dois métodos | CT-12, CT-13 | `PapeisPorOrganizacaoTest.php:326,359` |
| RQ-06 conta existente não consome convite | 4 | `LoginSocialController::avisarConvitePendente()` | CT-14/CT-16, CT-15 | `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` (o par invertido) + `:197` |
| RQ-07 conta nova continua nascendo por convite | 4 | `criarContaPorConvite()` intocado | CT-17 (controle) | cobertura já existente na suíte de cadastro social |
| RQ-08 conta indisponível não queima convite | 4 | consequência de RQ-06 | CT-18 | `tests/Kit/LoginSocialContaIndisponivelTest.php:150` |
| RQ-09 link de vínculo é de uso único | 5 | `Cache::add()` em `confirmarVinculo()` | CT-20..CT-22, CT-27 | `tests/Kit/VinculoDeProvedorSocialTest.php:262,289,318,352` |

**Nenhuma cláusula órfã**: toda `RQ` tem passo, código e cenário. As duas sem teste novo (RQ-07 por
controle já existente; e os CT-24/CT-26 descartados) estão justificadas na seção
`## Cenários não implementados, com o motivo` do `04`.

## Achados

| # | Severidade | Achado | Destino | Situação |
|---|---|---|---|---|
| QA-01 | **alta** | `->rule()` do Filament avalia a closure por injeção de parâmetro por nome; a regra do Laravel passada direto estoura `[$atributo] was unresolvable` e **derruba a tela de papéis inteira** — 15 casos vermelhos, incluindo casos pré-existentes que nada tinham a ver com a mudança | implementação | **corrigido** — `->rule(fn (): Closure => …)` |
| QA-02 | **alta** | `DeleteBulkAction` não consulta a policy do registro sem `authorizeIndividualRecords()`: o `master_global` era excluído pela seleção em massa com `RolePolicy::delete()` fechado | implementação | **corrigido** — `authorizeIndividualRecords('delete')` |
| QA-03 | **alta** | O recorte de concessão travava a edição de quem **já tem** papel fora do alcance: o papel saía das opções e o `in` implícito do Select reprovava ("não contém um valor válido"). Se tivesse passado, o `syncRoles()` **revogaria** o papel alheio a cada Salvar | implementação | **corrigido** — `recortarConcessao($query, $alvo)` |
| QA-04 | **média** | RQ-04, na letra da decisão original, contradizia a própria justificativa (o papel `admin` não acessa o `/app`, logo deixaria de conceder `panel_user`) | especificação | **corrigido** antes do código — Q1 do `00`, ADR-02 |
| QA-05 | **média** | Duas falhas de baseline vindas do trabalho anterior desta família: `ImportExportTest` chamava `usuarioDoKit('admin')` sem semear papéis, e o importador passou a consultar a policy por linha | teste | **corrigido** |
| QA-06 | **baixa** | Convite **legado**, gravado antes desta wiki com papel fora do alcance de quem o gravou, ainda concede esse papel no aceite | não-defeito (lacuna declarada) | registrado em Q6 do `00` e no `04` |

Os três achados de severidade alta foram encontrados **pelos testes derivados do requisito**, não
por revisão de código — e os três são de implementação, não de especificação. QA-02 e QA-03 seriam
invisíveis em revisão: um é comportamento de vendor que só aparece exercitando o verbo irmão, o
outro só se manifesta na ficha de uma pessoa que tenha papel de outro painel.

## Dimensões verificadas além dos CTs

| Dimensão | Resultado |
|---|---|
| Matriz de permissão | `master_global` atravessa por `Gate::before` em todos os caminhos novos — CT-02, CT-07, CT-10 |
| Fronteira de organização | o alcance soma painéis dos papéis em **qualquer** contexto (CT-09); a concessão de papel do `/app` continua por organização |
| Log real | `autenticacao` recebe o descarte em `gravarPapeis()`, o nome reservado recusado e o link reutilizado; nenhum channel novo |
| Regressão adjacente | suíte `Kit`+`Tenancy` completa (ver Veredito) |
| Segurança da superfície nova | verbo irmão coberto: exclusão em massa (QA-02) e revogação por subtração (QA-03) |
| Adequação da suíte | 27 CTs derivados do requisito por agente sem acesso ao PRD; 44 mutantes previstos, 2 sem matador **declarados** no `04` |

## Veredito

**APROVADO COM DÉBITO.**

Débito registrado, todo ele declarado e nenhum bloqueante:

1. **QA-06** — convites legados com papel fora do alcance de quem os gravou. Fecha com uma migration
   que os expire, ou com revalidação no aceite (CT-24 volta junto).
2. **`authorizeIndividualRecords` é armadilha do kit inteiro**, não só da tela de papéis. Hoje só
   `RoleResource` tinha regra por registro a furar (`UserPolicy::delete()` nem recebe o registro),
   mas a próxima policy que passar a decidir por registro reabre o buraco em silêncio. **Candidato a
   rule de projeto.**
3. **M34 e M3b** — dois mutantes sem matador, com o que foi tentado escrito no `04`.
