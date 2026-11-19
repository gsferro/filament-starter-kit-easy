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

## Testes
- [x] `vendor/bin/pest tests/Kit/EmailComMaisTest.php` — 70/70 depois do step 9 (CT-01..CT-05, CT-07..CT-11, CT-13..CT-19, CT-21..CT-28)
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

## Débitos
- **RD-01**: o convite que passou pela exceção "conta existente" (P-04) é aceito como convite comum se a conta trocar de e-mail antes do aceite, e cria uma conta com "+" com a opção desligada. Exige uma ação deliberada de quem já tem conta; fechar pediria guardar no convite por que ele passou, ou recusar no aceite (o que a P-06 evita)
- **Q1, Q2, Q3, Q4, Q5, Q6, Q7** abertas, com as recomendações implementadas

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
