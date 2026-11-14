# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável (Adendos 2 e 3)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. A UI tem JS (editor Markdown EasyMDE, iframe de preview, rede animada com Alpine). O domínio é sensível: texto formatado vira HTML numa tela pública sem autenticação, e há o escopo por organização
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1 dos Adendos 2 e 3

**REPROVADO → especificação (QA-27, QA-31), com teste (QA-30) e implementação (QA-28)**

- Blocker: 0 · Major: 4 · Minor: 3 · Cosmético: 0
- Não verificadas: G, H, J, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 continua `aberta` e o código implementa só a premissa estreita P-01, então não gera achado. A confirmação de Q2 segue pendente. As duas estão nos Débitos do `03`
- Ambiente: app em `http://127.0.0.1:8123`, HEAD `7ca671df` (tag `pre-adendo2-final`). Pest 5.0.5, sem `--agent`. Playwright MCP indisponível. Boost MCP indisponível (`laravel-boost` CONNECT_TIMEOUT). Os cenários 1–4 estavam rodando em outro processo durante este gate
- O loop recomeça no ciclo 1 porque os Adendos 2 e 3 trouxeram pedido novo (RQ-25..RQ-33). Os QA-18..26 estão tratados no `03` e não foram reabertos, exceto a evidência do QA-18 (ver QA-28)

## Achados

### QA-27 — A aba "Esqueci a senha" da organização nunca é aplicada, e o preview a mostra como se fosse · Major · destino 1

- **Dimensão**: A
- **Relacionado a**: RQ-25, RQ-10, RQ-13, P-06, P-25, P-08, passo 11 do PRD, CT-64, CT-73
- **Esperado**: pela P-25, cada tela tem a sua aparência, e a organização escolhe o objeto inteiro antes da tela. Pela RQ-10, o preview mostra "como fica".
- **Observado**: pela P-06, a recuperação e a redefinição de senha nunca consideram a organização. `TelaRecuperarSenha` e `TelaRedefinirSenha` não sobrescrevem `organizacaoDaAparencia()`. Mesmo assim, o editor da organização oferece a seção "Esqueci a senha", e a doc diz "A organização com aparência própria tem as mesmas três abas". O preview com escopo `tenant:{id}` e `tela=recuperar-senha` faz `daFamilia(rascunho da organização, 'recuperacao')`, o que é declarado no docblock: "SEM as condições de P-06". O admin da organização configura e vê no preview uma tela que nenhum usuário jamais verá.
- **Repro**:
  1. `grep -n "organizacaoDaAparencia" app/Filament/Pages/Auth/*.php` → só `PreviewDasTelasDeAcesso`, `RegistroPorConvite`, `TelaBloqueio` e `TelaLogin`
  2. `sed -n 147,158p docs/pt/autenticacao/aparencia-das-telas.md` → "Recuperar e redefinir senha … nenhuma | padrão"
  3. `EditorDeAparenciaDasTelas::campos()` → `secaoDeTelaPropria('recuperacao', …)` sem condição de escopo
- **Evidência**: as três saídas acima, e `PreviewDasTelasDeAcesso::familiaDaAparencia()`/`organizacaoDaAparencia()`
- **Destino**: 1. P-06 e P-25 juntas produzem uma configuração sem efeito
- **Ação exigida**: decidir em `P-nn` uma das duas saídas: esconder ou explicar a seção "Esqueci a senha" no escopo da organização, ou aplicar a recuperação da organização quando ela é identificável. Em seguida, CT com `Origem` = `P-nn` e o preview alinhado ao que a tela real mostra.

### QA-28 — Os cenários 1–4 não têm evidência sobre o código dos adendos, e o tratamento do QA-18 aponta evidência inexistente · Major · destino 2

- **Dimensão**: A / J / L6
- **Relacionado a**: RQ-07, RQ-08, RQ-17, P-23
- **Esperado**: RQ-08 pede os cenários "ao finalizar". O código distribuído é o de `7ca671df`, com 10 arquivos em `app`, `resources` e `public` mudados desde `fe9ea589`.
- **Observado**: a Verificação Final só tem rodadas sobre `b28debff` e `d343fc56`. A linha QA-18 do `03` diz "cenários 1–4 sobre o commit final do adendo … — Verificação Final", mas essa linha não existe (há a tag `pre-adendo-final` e nenhum registro). Para os Adendos 2 e 3, a seção 11 nem tem o item. Segundo o orquestrador, a rodada sobre `7ca671df` está em andamento.
- **Repro**: `grep -n "Cenários 1 a 4" 03-progresso.md` → linhas 202 e 203 apenas. `git diff --stat fe9ea589..7ca671df -- app resources public` → 10 arquivos.
- **Destino**: 2
- **Ação exigida**: registrar na Verificação Final a saída dos 4 cenários sobre `7ca671df`, incluindo uma instalação com aparência `painel` e texto gravados antes do Adendo 2 (CT-61/CT-67 na instalação atualizada). Corrigir a linha QA-18: ou colar a rodada do Adendo 1, ou dizer que ela não foi registrada.

### QA-29 — "possibilidade de usar html" ficou fora da RQ-27, e a P-27 a estreita sem pergunta ao solicitante · Minor · destino 1

- **Dimensão**: A (auditoria do requisito) / L5
- **Relacionado a**: RQ-27, P-27, P-05
- **Observado**: o texto do Adendo 2 diz "temos que ter a possibilidade de usar html, mude para uso do componente que é usado no 'Rodapé da tela de login'". O trecho literal da RQ-27 corta a primeira metade. A P-27 decide Markdown seguro com o HTML cru descartado, e justifica só com "— tela pública". O motivo completo, XSS armazenado numa tela sem autenticação e o mesmo `Str::markdown` com `html_input: strip` do componente pedido, está no docblock de `ConfiguracoesDoKit::login_rodape` e na doc pt/en, mas não na wiki. A decisão é a estreita e segura, e coincide com o componente pedido, por isso não é Major (mesmo critério da Q1). Ainda assim, ela contradiz o literal "usar html", e nenhuma `Qn` foi registrada.
- **Repro**: `sed -n 168p 00-requisito.md` × a linha RQ-27 da decomposição do Adendo 2. `grep -n "Q1[2-9]" 00-requisito.md` → nada.
- **Ação exigida**: incluir "possibilidade de usar html" no trecho da RQ-27. Registrar `Q12` com a recomendação "Markdown seguro, o próprio componente pedido descarta HTML" e o porquê (XSS armazenado em tela pública). Marcar a P-05 como alterada pela P-27.

### QA-30 — 17 dos 22 cenários dos Adendos 2 e 3 não têm "Então", não há mutantes previstos e não houve revisão adversarial · Major · destino 3

- **Dimensão**: K / L1
- **Relacionado a**: R26–R30, RQ-25..RQ-33, P-25..P-30
- **Observado**:
  - Na seção "Adendos 2 e 3" do `04`, CT-57, 61, 62, 64–72 e 74–78 não têm passo `Então`. Os esquemas têm `Exemplos:` em linha, sem tabela, ou nenhum exemplo (CT-71, CT-72). O oráculo de R27 (segurança, Impacto 3, Markdown numa tela pública) existe só no teste: o dataset do CT-57 tem 8 linhas, e o Gherkin cita 6. Faltam "título com href" e "sem esquema".
  - Nenhuma linha `| M` foi acrescentada (Mutantes previstos continua 57).
  - O texto diz "Derivados pela sessão". Nenhum `fw-adversario-ct` rodou sobre R26–R30, nem há a linha `Revisão adversarial: NÃO FEITA` no cabeçalho.
  - O cabeçalho diz "Cenários: 56" e "Oráculo atual: RQ-01..RQ-24, P-01..P-24". O cabeçalho do `05` também para no Adendo 1.
- **Repro**:
  1. `awk` da seção "## Adendos 2 e 3" contando `Então` por `[CT-nn]` → 17 sem `Então`
  2. `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` → 78. Regras 17, mutantes 57, sem matador 0
  3. `sed -n 195,205p tests/Kit/AparenciaDasTelasPorTelaTest.php`
- **Evidência**: `grep -rn "Cenários: 56" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` → `04:5` e a linha QA-23 do `03` (histórica)
- **Destino**: 3
- **Ação exigida**: invocar a `feature-test-design` para dar `Então` e `Exemplos` em tabela a R26–R30, prever mutantes (R27: esquema do `href`, lista de tags, teto visível × bruto) e rodar o `fw-adversario-ct` com o `00` e o `04`/`05`. Sem a revisão, declarar `Revisão adversarial: NÃO FEITA — {motivo}`. Recalcular o cabeçalho pelos `grep -cE` e atualizar o oráculo dos cabeçalhos do `04` e do `05`.

### QA-31 — PRD, ADR-05, docs pt/en e CHANGELOG descrevem "abas", e o código tem seções; superfície e premissas sem marca · Major · destino 1

- **Dimensão**: L3 / L5
- **Relacionado a**: passo 11 do PRD, ADR-05, Superfície Livewire do `02`, P-05
- **Observado**:
  - Passo 11 do PRD: "**Editor**: abas Login / Cadastro / Esqueci a senha". ADR-05: "um único conjunto de campos por aba". Doc pt:39 e pt:56 dizem "três abas"; en:39 e en:57 dizem "three tabs"; o `CHANGELOG.md`:23 diz "abas próprias". O código (`EditorDeAparenciaDasTelas::campos`) usa três `Section` colapsáveis. O desvio está registrado só no `03` (seção 11), sem marca *(alterado)*.
  - A Superfície Livewire do `02` lista só `aparenciaDaTela()`, `corDoPainelDeMarca()` e `logosDoPainelDeMarca()`. Faltam os métodos públicos `gradienteDoPainelDeMarca()` (Adendo 2) e `destaqueDoPainelDeMarca()` (Adendo 1).
  - P-05 ("Textos da aparência saem escapados") segue `vigente` sem marca, e o docblock de `painel-de-marca.blade.php` diz "Tudo aqui sai escapado". A frase e o apoio agora saem como `HtmlString` filtrada (P-27).
- **Repro**: `grep -rn "abas\|three tabs" docs/*/autenticacao/aparencia-das-telas.md CHANGELOG.md wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/01-plano-acao.md`. `grep -n "public function" app/Filament/Concerns/VesteAparenciaDasTelas.php`
- **Destino**: 1
- **Ação exigida**: reescrever com marca o passo 11, a ADR-05, a doc pt/en e o CHANGELOG ("seções"). Acrescentar os dois métodos à Superfície Livewire. Marcar a P-05 e o docblock da view.

### QA-32 — `03`: Despachos e Degradações sem os Adendos 2 e 3, e estado e tabela de testes defasados · Minor · destino 1

- **Dimensão**: L6 (o mesmo padrão do QA-22)
- **Observado**:
  - A seção 11 cita o Codex como juiz cego do desenho e o workflow de revisão (Codex, `fw-revisor-diff`, `filament-security-audit`, céticos), mas `## Despachos` termina no #18.
  - As Degradações não dizem que R26–R30 foram derivadas pela sessão, sem revisão adversarial.
  - O `**Estado**` ainda diz "Adendo 1".
  - `## Testes` não tem `AparenciaDasTelasPorTelaTest`/`AdendoTest` e para no CT-B08.
  - A seção 11 não traz contagem por arquivo nem item aberto de gate e cenários.
- **Repro**: `grep -n "^| 1[89] \|^| 2[0-9] " 03-progresso.md` → só o #18. `sed -n 3p 03-progresso.md`
- **Ação exigida**: uma linha por disparo dos adendos, com modelo, `Custo` e auditoria. Declarar a degradação da derivação, atualizar Estado e Testes, e acrescentar à seção 11 a contagem medida (PorTela 55, Adendo 58, DeAcesso 133, Entrega 5, Tenancy 75, Browser 55) e o item aberto.

### QA-33 — Glossário defasado e termo do rodapé diferente do glossário · Minor · destino 1

- **Dimensão**: L7
- **Observado**:
  - "Painel de marca" ainda diz "com cor ou arte de fundo" e "duas frases opcionais", sem gradiente nem formatação.
  - O glossário chama de **Recado do login** o texto Markdown do cartão, e tem "Assinatura do rodapé" como "não confundir com". A P-27, o passo 11 e as docs falam em "rodapé da tela de login", o rótulo da UI.
  - Os termos decididos na feature ("tela própria", "Igual ao login", "família de tela") não estão no glossário.
- **Repro**: `grep -n "Painel de marca\|Recado do login" wikis/glossario.md`
- **Ação exigida**: atualizar "Painel de marca". Na P-27, citar "Recado do login (seção 'Rodapé da tela de login')". Registrar os termos novos.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (não há `07-tickets/`). Lacunas:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-25 / P-25 × P-06 | por tela, também na organização | 11 | CT-64, CT-73 | — | editor, preview, `TelaRecuperarSenha` | "Esqueci a senha" da organização sem efeito; o preview mente | ❌ QA-27 |
| RQ-27 / P-27 | textos formatados | 11 | CT-57, 65–67 (sem `Então`) | — | `textoComoHtml` | correto; oráculo só no teste; "usar html" sem `Qn` | ⚠️ QA-29, QA-30 |
| RQ-07 / RQ-08 / P-23 | não quebrar quem usa o kit; cenários | — (processo) | CT-21, CT-27 `@obsoleto` | — | 10 arquivos distribuídos | sem rodada registrada sobre `7ca671df` | ❌ QA-28 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | `rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (sem `07-tickets/`). Conferi o diff por passo: passo 11 × `fe9ea589..7ca671df`. RQ-25/26 (CT-58..64, 73), RQ-28/29 (CT-68..70, 76, 77), RQ-30 (CT-B16) e RQ-31..33 (CT-71, CT-72, CT-B17) estão implementadas. No app servido, `/admin/login` é `media-left`, gradiente `135deg #1e3a8a→#7c3aed`, frase `<p>…<strong>` e apoio com `<a … rel="noopener noreferrer">`; `/admin/password-reset/request` é `media-right`, cor `#0f766e` (formulário à esquerda); `/app/register` e `/cadastro` respondem 302 (cadastro desligado localmente). Achados: QA-27, QA-28, QA-29 |
| B | Fronteiras e dados | ✅ | Pest 5.0.5 sem `--agent`. Sondei `textoComoHtml` por `tinker` (sem gravação) com 15 entradas: `JaVaScRiPt:`, `data:`, autolink `<javascript:…>`, `https:evil`, ` //evil`, `<//evil>`, HTML cru, atributo injetado no título, lista, citação, código. Todas inertes: o `href` só sai com `https?://`, `mailto:` ou caminho `/` sem `//`. O dataset do CT-66 cobre 80/81 visíveis e 4 KiB; o do CT-68 cobre a injeção na direção e na cor |
| C | Matriz de permissão | ✅ | Nenhuma rota, policy ou action nova. Os editores continuam nas configurações e no `TenantPolicy`. No preview, `$tela` é `#[Locked]` numa lista fechada, e `familiaDaAparencia()` é `protected`. CT-38/39 verdes no `DeAcessoTest` |
| D | Observabilidade | ✅ | Nenhum ponto de log novo no diff. A origem do log de valor inválido ganha `:{familia}`. Linha real em `storage/logs/autenticacao-2026-11-14.log`: `[AparenciaDasTelasDeAcesso@normalizar] … {"origem":"x:cadastro","motivo":"layout"}`, sem PII |
| E | Performance | ✅ | Por leitura. `daFamilia()` trabalha sobre o objeto já carregado, sem query nova. `Str::markdown` e o filtro por DOM rodam duas vezes por render, sobre no máximo 4 KiB. Não houve contagem dinâmica de queries (sem `--agent`) |
| F | UX de erro | ✅ | Mensagens "Use no máximo 80 caracteres visíveis." e "O texto, com a formatação, passa de 4 KB." no campo. As ajudas explicam o HTML descartado e o comportamento no celular |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 sobre `--diff-filter=AM fe9ea589..HEAD` (app/resources/public): exit 0. A superfície do gradiente é fixa e escura, `rgb(17 24 39/.9)` com texto `#fff`, independente do tema. Nível 3 sem MCP |
| H | Acessibilidade | ⏭️ não verificada | `grep -c assertNoAccessibilityIssues` no arquivo de navegador = 0, e o MCP está indisponível. Por leitura: os links do painel são sublinhados e, sobre o gradiente, o texto fica em superfície opaca |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9: `## Revisão do Diff — Adendos 2 e 3` do `03` (10 linhas, 14 achados, 0 rejeitados). As quatro linhas *eixo 9* não rodaram de novo. Além do step 9: sondagem do filtro (B); `aparenciaDaTela()` da família login sem a chave `telas` (`unset` em `daFamilia`), então o `$wire` do login não revela cadastro nem recuperação; o gradiente é montado só de peças fechadas (CT-68); a chave secreta do anti-robô fora do HTML (CT-72). A lacuna da Superfície Livewire do `02` está no QA-31 |
| J | Regressão adjacente | ⏭️ não verificada | Rodada em parte. Arquivos da feature: PorTela 55/55, Adendo 58/58, DeAcesso 133/133, Entrega 5/5, Tenancy 75/75, Browser 55/55. Vizinhos: `TelasDeAutenticacao`, `BloqueioDeSessao`, `LoginSocialPorPainel`, `CadastroSocialPorConvite`, `ConfiguracoesSecoesColapsaveis` e `HelpersDeTeste` 158/158; `ProtecaoAntiRobo` 98/98. Sem `--tia` e sem as suítes, por restrição de memória do orquestrador. Kit 5166, Tenancy 744 e Browser 235 com 1 falha (Dock CT-B02) foram lidos como alegação, não reproduzidos. Cenários pendentes: QA-28. RCRCRC: social e anti-robô (Core/Risk), recuperação e convite (Recent) |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1 com 5 candidatas, todas rejeitadas: CT-75, CT-07 e CT-11 afirmam no helper `afirmarAparenciaNoHtml`; CT-31 e CT-33 já foram julgadas em ciclos anteriores. K2 não rodou. Revisão adversarial de R26–R30 não feita (QA-30) |
| L | Consistência documental | ❌ | L1 `ids-ct.sh` exit 0, sem ID duplicado, mas há o QA-30. L2 `citacoes.sh` exit 0. L3: QA-31. L4 `conformidade-rules.sh main` exit 0; conferidas `pages.md` (seções colapsáveis com ícone e id), `testes.md` (helpers de um arquivo só, `HelpersDeTesteTest` verde), `css-filament.md` (classes `kit-*`, espelho `public/css/kit/kit-correcoes.css` idêntico no trecho novo) e `views.md`. L5: QA-29, QA-31; pt × en coerentes entre si. L6 `checkbox-sem-evidencia.sh` exit 0, mas há o QA-28 e o QA-32. L7: QA-33. L8 `roteiro-manual.sh` exit 0; n/a, sem `08` e com decisão `não` |

## Débitos Aceitos

- Nenhum neste ciclo, porque há Major aberto. Os débitos do `03` (Q1, Q2, alternador fora do teclado, organização fora do navegador, positivo em redefinir/verificar/2FA, preview sem a pilha do painel, ADV-A15) seguem lá e não foram contestados.

## Suspeitas Não Confirmadas

- A família de dois fatores, bloqueio, confirmação de e-mail e redefinição de senha só é provada por reflexão (CT-62). Não houve visita HTTP com aparência própria nessas telas. É o mesmo débito BP-51.
- A imagem trocada continua deixando o arquivo antigo em `telas-de-acesso/` (já registrada no ciclo anterior). Com três telas, a cópia multiplica as referências. Não reproduzi, porque exigiria gravar no app.

## Não Verificado

- G, nível 3, e H, axe e teclado ao vivo: Playwright MCP indisponível, e nenhum CT-B da feature chama `assertNoAccessibilityIssues`.
- J: as suítes inteiras e o `--tia` não rodaram, por restrição de memória (os cenários rodavam em paralelo). Os números de suíte do `03` ficaram como alegação.
- J / RQ-08: cenários 1–4 sobre `7ca671df` em andamento, fora deste gate.
- K2, `--mutate`: as ferramentas existem (`php -m` → pcov e xdebug; `vendor/pestphp/pest-plugin-mutate`), mas o custo, centenas de mutantes × ~30 s por subprocesso, não cabia na restrição de memória. Também há prova pendente: R26–R30 não tiveram revisão adversarial (QA-30).
- B / I ao vivo: Markdown hostil gravado no app servido exigiria escrever configuração. O filtro foi sondado em `tinker` e está coberto pelo CT-57.
- Cadastro próprio, login social e anti-robô no app servido: o cadastro está desligado e não há provedor nem anti-robô configurados localmente (302 em `/app/register` e `/cadastro`, `data-provedor`/`data-anti-robo` = 0). A cobertura vem de CT-58, CT-71, CT-72 e CT-B17.
- RQ-12: os nomes de referência estão mascarados no `00`. Conferi só o oráculo genérico: os hosts novos do diff são `raw.githubusercontent.com` do kit, `img.shields.io` e `exemplo.test` (em teste).
