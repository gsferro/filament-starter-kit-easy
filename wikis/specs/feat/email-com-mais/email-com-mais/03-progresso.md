# Progresso — Permitir ou bloquear o "+" no e-mail do cadastro

**Estado**: em revisão — 2026-11-19

**Base do PR**: `main`

## 1. O leitor único e a regra
- [x] `app/Support/EmailComMais.php` (`permitido()`, `temMais()`, `aceita()`, `aceitaNoConvite()`, `mensagem()`, `regra()`, `regraDeConvite()`), `config/kit.php` (`kit.email.permitir_mais`, ausente/vazia/irreconhecível permite), `.env.example`, `phpunit.xml` — CT-01, CT-02, CT-05, CT-07 verdes, 2026-11-19

## 2. A opção na tela de configurações
- [x] Propriedade `email_permitir_mais`, linha no mapa, migration `database/settings/2026_11_19_100000_add_email_permitir_mais_to_kit_settings.php`, seção "E-mail com "+"" na aba Registro — CT-03, CT-04 verdes; `KitInfoTest` CT-06 ajustado (76 → 77 propriedades), 2026-11-19

## 3. Registro aberto
- [x] Regra no campo só no modo aberto; motivo `email_com_mais` em `RegistroAberto::exigirPortaAberta()` — CT-06, CT-08, CT-09, CT-10 verdes, 2026-11-19

## 4. Convite
- [x] Regra de convite nos dois formulários (conta existente passa), motivo `email_com_mais` no lote com busca única sem caixa, texto em `motivoLegivel()` e no docblock de `FalhaDoConviteData` — CT-11..CT-17, CT-28 verdes, 2026-11-19

## 5. CRUD de usuários
- [x] Regra nos dois `UserResource`, ignorando o e-mail atual — CT-18, CT-19, CT-20 verdes, 2026-11-19

## 6. Login social
- [x] Conferência antes do `criarConta()`, com a mensagem própria e log mascarado — CT-21, CT-22, CT-23, CT-27 verdes, 2026-11-19

## 7. Perfil
- [x] `app/Livewire/InformacoesPessoais.php` como `personal_info` nos três painéis, em commit próprio (Q2 aberta) — CT-24, CT-25, CT-26 verdes, 2026-11-19

## 8. Documentação
- [x] `docs/{pt,en}/autenticacao/registro-aberto.md` (seção "E-mail com "+""), `docs/{pt,en}/recursos/configuracoes-do-kit.md` — o tutorial guiado da tela é por aba e não precisa de passo novo (`.ai/rules/onboarding.md`), 2026-11-19

## 9. Resposta à Q4 (convite com "+" recusado no aceite)
- [x] O solicitante respondeu Q1..Q4 em 2026-11-19 (QA-01 do gate): Q1, Q2 e Q3 como a recomendação; **Q4 "não, recusar no aceite"**. P-06 revista no `00`, Q7 decorre dela; R7 reescrita no `04` (CT-16, CT-17 invertidos; CT-29, CT-30, CT-31 novos) — 2026-11-19
- [x] Código — a tela recusa ao abrir o link (`RegistroPorConvite::recusarPorMais()`: aviso e login, porque o campo do convite é desabilitado), o login social por convite recusa com a mensagem do "+", `Convite::aceitar()` recusa conta nova com "+", o Reenviar some para esse convite (`ConvitesTable`) e o lembrete o pula (`KitConvitesLembrar`); o Revogar já existia e libera o papel. Docs, CHANGELOG, helperText da tela e roteiro atualizados — `vendor/bin/pest tests/Kit/EmailComMaisTest.php --filter='CT-16|CT-17|CT-29|CT-30|CT-31'`: 6/6, 2026-11-19
- [x] Falsificabilidade — cinco mutantes, um por ponto (tela, login social, model, Reenviar, lembrete): CT-16 0/2, CT-17 0/1, CT-29 0/1, CT-30 0/1, CT-31 0/1 sob o mutante; `git status` de `app/` só com a entrega depois de restaurar, 2026-11-19
- [x] O RD-01 (convite de conta existente aceito depois de a conta trocar de e-mail) fica fechado pela mesma guarda: o aceite como conta nova recusa o "+" — 2026-11-19

## Testes
- [x] `vendor/bin/pest tests/Kit/EmailComMaisTest.php` — 70/70 depois do step 9 (CT-01..CT-05, CT-07..CT-11, CT-13..CT-19, CT-21..CT-28); com a Q4 e os CT-29..CT-31, o lote de oito arquivos (`EmailComMaisTest`, `ConviteTest`, `CadastroSocialPorConviteTest`, `ExclusaoDePapelComConviteTest`, `SiteDeDocumentacaoTest`, `RoteiroDeAtualizacaoTest`, `CitacoesDeCodigoTest`, `DiagramasDaArquiteturaTest`) passou 977 de 979 — as 2 falhas eram o badge e uma citação deslocada pela guarda nova, corrigidas (`CitacoesDeCodigoTest` + `DiagramasDaArquiteturaTest` + `SiteDeDocumentacaoTest` 599/599)
- [x] `vendor/bin/pest tests/Tenancy/RegistroAbertoTenancyTest.php tests/Tenancy/AdminDaOrganizacaoTest.php` — 17/17 e 38/38 (CT-06, CT-12, CT-20)
- [x] Irmãos com os helpers que subiram para o `tests/Pest.php` (`registrarAberto()`, `conviteCom()`, `aceitarConvite()`, `conviteSocialPara()`, `opcaoDoMais()`) — `ConviteTest` 49/49, `RegistroAbertoTest` 35/35, `CadastroSocialPorConviteTest` 5/5, `HelpersDeTesteTest` 1/1
- [x] Falsificabilidade — seis mutantes, um por via, todos mortos pelo `fw-executor-ct` (registro por fora da tela CT-09, registro pela tela CT-08, convite um a um CT-11, CRUD CT-18/CT-19, login social CT-21 — o aviso virava a recusa genérica do convite —, lote CT-13); o lote sem `lower(email)` derruba o CT-28, 2026-11-19

## Tickets
Não fatiado — 2026-11-19: 4 RQ e uma regra só, aplicada em sete vias; um PR

## Revisão do Diff (step 9)

Três revisores sem o PRD: `fw-revisor-diff`, `/code-review` (fork) e o codex. Nenhuma via escapando da regra nos fluxos pedidos (codex e passe de eixos).

| Achado | Origem | Destino |
|---|---|---|
| Busca por endereço no lote (N+1 em `lower(email)`) | code-review 2 | corrigido: uma busca para o lote |
| O lote comparava a coluna crua e recusava conta existente gravada com caixa mista, que o formulário aceita | RD-02 | corrigido: `lower(email)` na busca; CT-28 |
| Pint pendente em `RegistroPorConvite` e `RegistroAberto` (o `--dirty` não via o que já estava commitado) | code-review 5 | corrigido |
| Motivo `email_com_mais` fora do docblock de `FalhaDoConviteData` | code-review 7 | corrigido |
| O `.env.example` não dizia que a chave só semeia o banco | code-review 8 | corrigido |
| Mensagem do lote diferente da central | codex 3 | alinhada ("e-mail com "+" antes do "@"") |
| CT-13 aceitava qualquer motivo diferente dos outros | codex 2 | corrigido: `email_com_mais` |
| CT-20 "salva sem trocar" passava sem gravar nada | codex 1 | corrigido: o nome muda e é conferido |
| CT-23 não conferia o redirecionamento | codex 4 | corrigido |
| CT-09 aceitava qualquer exceção, sem controle positivo | RD-03 | corrigido |
| Nenhum teste lia o texto do motivo do lote | RD-04 | CT-28 |
| Guarda no model em vez de em cada via | code-review 1 | por desenho: pegaria o `kit:admin` e o convite já emitido, que a P-03 e a P-06 deixam de fora |
| O "+" conferido antes da organização no login social | code-review 3 | mantido: a mensagem diz o problema real do e-mail; a outra recusa vem depois, com a dela |
| A guarda do `registrar()` lança a mensagem genérica | code-review 4 | mantido: o motivo vai no log; a tela e o login social recusam antes, com a mensagem própria |
| Perfil: caixa/espaços do e-mail atual | code-review 6 | mantido: a comparação normaliza; a gravação é a do Breezy, igual a antes |
| Variável atribuída dentro do `config/kit.php` | code-review 9 | mantido: uma chave só; um helper para um uso seria camada sem segundo cliente |
| Convite para conta existente com "+", aceito depois que a conta troca de e-mail, cria conta com "+" | RD-01 | débito (abaixo) |
| `kit:admin` troca o e-mail do admin sem a regra | RD-05 | fora de escopo (P-03) |

## Verificação Final

Rodados em 2026-11-19, na raiz, sobre o branch (`bash .claude/skills/feature-wiki/scripts/{script}.sh wikis/specs/feat/email-com-mais/email-com-mais`):

| Script | Saída |
|---|---|
| `rastreabilidade.sh` | silêncio, exit 0 |
| `checkbox-sem-evidencia.sh` | silêncio, exit 0 |
| `citacoes.sh` | silêncio, exit 0 (depois de tirar o número de três citações sem símbolo do `04`) |
| `conformidade-rules.sh {wiki} main` | silêncio, exit 0 (depois da `## Conformidade com Rules`) |
| `ids-ct.sh {wiki} 'tests/Kit/EmailComMaisTest.php' 'tests/Tenancy/RegistroAbertoTenancyTest.php' 'tests/Tenancy/AdminDaOrganizacaoTest.php'` | exit 0 com os três arquivos; com só o `EmailComMaisTest.php`, acusa CT-06, CT-12 e CT-20, que moram nos arquivos de Tenancy |

Suítes:
- `vendor/bin/pest --parallel --processes=3 --exclude-testsuite=Browser --compact` sobre `2fc89953`: 6289 testes, 6279 passaram, 7 falhas — citações de linha deslocadas pela feature (`CitacoesDeCodigoTest` CT-26, `DiagramasDaArquiteturaTest` CT-66), a página em inglês com a mensagem em português (`SiteDeDocumentacaoTest` CT-19), as contagens do README (3) e o CT-28, que rodou com a árvore no meio da edição. Corrigidas; os arquivos reexecutados: `CitacoesDeCodigoTest` + `DiagramasDaArquiteturaTest` 531/531, `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88, `EmailComMaisTest` 70/70
- `vendor/bin/pest --testsuite=Browser --compact`: 275 testes, 221 passaram, 52 pulados, 2 falhas por timeout de 45 s sob carga (o gate rodava em paralelo): `ConfiguracoesSecoesColapsaveisTest` CT-B04 e `DockDeAtalhosTest` CT-B01, verdes isolados (4/4 e 1/1)

Roteiro manual: não — a feature é validação de formulário e uma recusa do login social, todas medidas por componente e HTTP nos 28 CT; nenhum fluxo novo de tela.

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | n.a. | nenhum papel atribuído, nenhum DTO novo (só o docblock de `FalhaDoConviteData`), nenhuma URL de painel ou de arquivo |
| `auth.md` | `app/Filament/Pages/Auth/**` | n.a. | `RegistroPorConvite` só ganha uma regra no campo; `$layout`, `mount()` e resolução de painel intocados |
| `commands.md` | `app/Console/Commands/**` | n.a. | `KitConvitesLembrar` só ganha um `continue` no laço; nenhuma chave gravada no `.env` |
| `config.md` | `config/**` | aplicada, com divergência declarada | uma dona só (`EmailComMais`, CT-05); `env()` vazio tratado (a chave não usa `(bool) env(..., true)`). Não "falha fechado": a chave FECHA superfície, e a ausência de valor permite (P-01, `01` → Decisões de Desenho) |
| `controllers-auth.md` | `app/Http/Controllers/Auth/**` | n.a. | a recusa do "+" acontece antes de qualquer sessão; nenhuma troca de conta |
| `css-filament.md` | `app/Providers/**` | n.a. | nenhum CSS |
| `filament-resources.md` | `app/Filament/**/Resources/**` | n.a. | nenhum resource novo; só uma regra nos campos de e-mail |
| `filament.md` | `app/Filament/**` | n.a. | nenhum papel, permissão, resource ou mídia novos |
| `kit.md` | `tests/Kit/**` | aplicada | `Livewire::test` monta com a sessão e os cabeçalhos do teste, sem query na chamada seguinte |
| `livewire.md` | `app/Livewire/**` | aplicada | `InformacoesPessoais` não registra log; o log do login social usa o e-mail mascarado |
| `models.md` | `app/Models/**` | n.a. | `Convite` ganha um motivo no lote; nada de cache, papel, mídia ou soft delete |
| `onboarding.md` | `app/Filament/**` | aplicada | o tutorial da tela de configurações é por aba (`ConfiguracoesDaAplicacaoSeeder`), sem passo por seção — nada a acrescentar |
| `pages.md` | `app/Filament/Admin/Pages/**` | aplicada | a seção nova é colapsável pelo helper `secao()`, com ícone `heroicon-o-at-symbol` e id estável `email-com-mais` |
| `providers-filament.md` | `app/Providers/Filament/**` | aplicada | `InformacoesPessoais` registrado nos TRÊS painéis (CT-25, CT-26) |
| `providers.md` | `app/Providers/**` | n.a. | nenhuma rota nova |
| `resources.md` | `app/Filament/App/Resources/**` | n.a. | o recorte por organização do `/app` não muda; CT-12 e CT-20 rodam com organização |
| `settings.md` | `app/Settings/**` | aplicada | lida por request (validação), não no boot; os três lugares: propriedade, linha no mapa e migration |
| `specs.md` | `wikis/specs/**` | aplicada | o Breezy foi lido antes de afirmar o merge do `myProfileComponents()` (`HasMyProfile::myProfileComponents()`); teste citado pelo ID |
| `support.md` | `app/Support/**` | n.a. | nenhuma chave gravada no `.env` |
| `testes.md` | `tests/**` | aplicada | helpers usados por mais de um arquivo subiram para o `tests/Pest.php`; CT-05 filtra comentário na varredura |

## Débitos
- ~~**RD-01**~~ fechado pela resposta à Q4 (seção 9)
- **Q5 e Q6** abertas, com as recomendações implementadas (texto da mensagem; `off`/`no` como desligado). Q1..Q4 respondidas em 2026-11-19; Q7 decorre da Q4

## Despachos

| # | Agente | Modelo | Tarefa | Resultado | Custo |
|---|---|---|---|---|---|
| 1 | `Explore` | herdado | mapa das vias que dão e-mail a uma conta, das opções de registro, das regras e dos testes | 6 seções, usadas no step 3 e nas premissas | 161.201 tokens · 359 s |
| 2 | `advisor` | — | revisão do desenho antes de implementar | 3 bloqueios (P-01 falhando no sentido errado, P-03 × P-04 no convite, P-06 com estado preso) — `00` e `01` corrigidos | — |
| 3 | `general-purpose` | opus | derivação do `04` (feature-test-design), sem o PRD | 27 CT, 52 mutantes, Q?1..Q?3 (viraram Q5..Q7) | 235.907 tokens · 830 s |
| 4 | `fw-executor-ct` | herdado | escrever e rodar os CT | 69 casos verdes, 6 mutantes mortos; retomado uma vez (limite de turnos) | 212.291 tokens · 681 s |
| 5 | `fw-revisor-diff` | herdado | passe de eixos | RD-01..RD-05 (Minor) | 123.026 tokens · 571 s |
| 6 | `/code-review` (fork) | sessão | revisão do diff | 9 achados | 100.711 tokens · 146 s |
| 7 | `codex exec` | não exposto | revisão do diff | 4 achados | 90.271 tokens |

## Revisão pelo blueprint (`reviewing-filament-plans`, 2026-11-19)

Contrato: o `01` (Estrutura de Implementação e Superfície de UI) com as emendas do `00` (Q1..Q4 respondidas). Alvo: `0e8a7223`, árvore limpa, painéis `admin`, `app` e `infra`. Filament 5.10.

**Resultado**: 13 obrigações; 12 conformes, 1 desvio de contrato (BP-13, corrigido) e nenhum defeito de comportamento. Nenhuma obrigação ficou sem verificar.

| ID | Requisito do plano | Veredito | Evidência |
|---|---|---|---|
| BP-01 | Seção "E-mail com "+"" na aba Registro, colapsável, com ícone e id `email-com-mais` | conforma | `ConfiguracoesDoKit::abaRegistro()`; CT-04 (executado); captura do navegador (a seção aberta, o toggle e o texto de ajuda) |
| BP-02 | Leitor único; ausência de valor permite | conforma | `EmailComMais::permitido()`, `config/kit.php`; CT-01, CT-02, CT-03, CT-05 (executados) |
| BP-03 | Registro aberto: erro no campo, só no modo aberto | conforma | `RegistroPorConvite::getEmailFormComponent()`; CT-08, CT-10 (executados); captura (erro sob o campo, com a mensagem) |
| BP-04 | Guarda no `registrar()` | conforma | `RegistroAberto::exigirPortaAberta()`; CT-09 com controle positivo (executado) |
| BP-05 | Formulários de convite: regra do convite, conta existente passa | conforma | `ConviteForm`, `ConviteResource` do `/app`; CT-11, CT-12 (executados) |
| BP-06 | Convite em massa: motivo próprio, os outros seguem, conta existente em qualquer caixa | conforma | `Convite::convidarEmMassa()`, `ConvidaEmMassa::motivoLegivel()`; CT-13..CT-15, CT-28 (executados) |
| BP-07 | Aceite do convite com "+" para conta nova recusado ao abrir o link, com aviso e login (Q4) | conforma | `RegistroPorConvite::recusarPorMais()`; CT-16 (executado); captura (login com o aviso "Este convite não pode ser aceito") |
| BP-08 | Model e login social por convite recusam a conta nova com "+" (Q4) | conforma | `Convite::aceitar()`, `LoginSocialController::retorno()`; CT-17, CT-29 (executados) |
| BP-09 | Reenviar oculto para esse convite; Revogar continua | conforma | `ConvitesTable`; CT-30 (executado) |
| BP-10 | Lembrete pula esse convite | conforma | `KitConvitesLembrar`; CT-31 (executado) |
| BP-11 | CRUD de usuários: e-mail novo com "+" recusado, o atual passa | conforma | os dois `UserResource`; CT-18..CT-20 (executados; o CT-20 confere a gravação do nome) |
| BP-12 | Perfil nos três painéis | conforma | `InformacoesPessoais` como `personal_info` nos três `PanelProvider`; CT-24..CT-26 (executados) |
| BP-13 | O plano descreve a P-06 da Q4 | desvio de contrato: o `01` ainda dizia que o convite emitido valia e que o `aceitar()` não mudava | corrigido no `01` (Cobertura, passos 3, 4 e 6, Decisões e Superfície de UI) com a marca da Q4 |

Execução: `vendor/bin/pest tests/Kit/EmailComMaisTest.php` (todos os CT acima) e um teste de navegador temporário (`tests/Browser/ZzBlueprintMaisTempTest.php`, 3/3, apagado depois) que abriu a aba Registro, o link de um convite com "+" e o registro aberto com "+", e capturou as três telas.

## Quality Gate

| Ciclo | Veredito | Data |
|---|---|---|
| 1 | REPROVADO → especificação (QA-01, QA-02 Major; QA-03..QA-06, QA-08 Minor; QA-07, QA-09 Cosmético); B, E, F, G, H, J, K não verificadas pelo gate | 2026-11-19 |
| 2 | REPROVADO → especificação (QA-10 Major; QA-11, QA-13, QA-14, QA-15, QA-17 Minor; QA-12, QA-16 Cosmético); B, E, F, G, H, K não verificadas; teto de 2 ciclos atingido | 2026-11-19 |

Tratamento do ciclo 1:

| QA | Destino | O que foi feito |
|---|---|---|
| QA-01 | 1 | Q1..Q4 levadas ao solicitante e respondidas; a Q4 divergiu ("recusar no aceite"): P-06 → R7 → CT-16, CT-17, CT-29..CT-31 → correção (seção 9) |
| QA-02 | 1 | o `01` diz a fórmula real da chave, os dois métodos do convite e que o `aceitar()` não tinha guarda — depois da Q4, ele passou a ter (seção 9) |
| QA-03 | 1 | as seis citações do `Convite.php` nas docs pt e en, e de novo depois da guarda nova |
| QA-04 | 1 | a seção "E-mail com "+"" na tabela de abas das docs |
| QA-05 | 1 | o CHANGELOG diz o que acontece sem o `migrate` (o boot lê TODAS as opções do `.env`) |
| QA-06 | 3 | as linhas `off`/`no` do CT-01 entraram nos Exemplos do `04`, com a Q6; o rótulo "respondida" saiu do dataset |
| QA-07 | 1 | cabeçalho, índice e mapa do `04` com o CT-28 (e depois com CT-29..CT-31) |
| QA-08 | 1 | `## Verificação Final`, a decisão de roteiro manual e `## Impacto em Features Existentes` no `01` |
| QA-09 | 2 | o comentário do lote diz `lower(email)` |

No `06` do ciclo 1, as citações `arquivo:linha` sem símbolo foram reescritas como prosa ("`arquivo` (linha N no ciclo)") para o `citacoes.sh` passar; o conteúdo dos achados não mudou.

**Blocker (ciclo 2)**: o perfil padrão permite 2 ciclos e o QA-10 (Major, destino 1: a tabela de impacto e os passos 3, 4 e 7 do `01` ainda descrevem a P-06 antiga) ficou aberto. Escalado ao usuário em 2026-11-19; o PR não abre até a decisão.
