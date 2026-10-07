# Relatório de QA — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: o swap depende da classe `dark` que o `theme.js` fixa; além disso, Impacto 3 de vazamento entre organizações no `04`)
> Natureza da wiki: correção · Toca infra compartilhada: sim → `CabecalhoDoPainel`, `IdentidadeDoKit`, `TenantFactory::comIdentidadeVisual()` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 8 · Cosmético: 1
- Não verificadas: G (nível 3), H (axe e teclado), J (TIA), K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma
- Ambiente: app em `http://127.0.0.1:8012` (a tela de login respondeu, mas não houve login, ver *Não Verificado*) · Pest 5.1.1 · MCP: Playwright indisponível; Boost não usado
- O código entregue não teve defeito confirmado. O único Major é documental (L6) e se corrige no texto do `03`.

## Achados

### QA-01 — Alegações do `03` que nenhum comando reproduz · Major · destino 1
- **Dimensão**: L (L6)
- **Relacionado a**: passo 6; `## Despachos`, linha 9
- **Esperado**: todo `[x]` com número, e toda auditoria marcada como "silencioso", sai do comando que a gera.
- **Observado**: `03:35` e `03:37` dizem "`SiteDeDocumentacaoTest` 91/91", mas a HEAD dá **68/68** (254 asserções). Com `RedeDeDocumentacaoTest` somado (20) ainda não chega a 91. Em `03:170`, `## Despachos` diz "`citacoes.sh` silenciosos", mas o script sai com **exit 1** e 3 linhas (QA-02).
- **Repro**: `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/SiteDeDocumentacaoTest.php --compact` dá `tests:68`; `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}` dá exit 1.
- **Evidência**: `grep -rn "91/91" {wiki}` devolve `03-progresso.md:35` e `03-progresso.md:37`. Os demais números conferem: 41/41, 45/45, 86/86 (447), 9/9 (54), 158/712, filacheck 17 regras, badge 1.976, 77 especificações.
- **Ação exigida**: trocar as duas ocorrências de "91/91" pela saída real e reabrir ou corrigir a auditoria da linha 9 de `## Despachos`.

### QA-02 — Citações `arquivo:símbolo:linha` erradas no `01` · Minor · destino 1
- **Dimensão**: L (L2)
- **Observado**: `01:60` e `01:99` citam o método `resolverSegmentos()` de `app/Support/CabecalhoDoPainel.php`, citado na linha 202, mas o método está na linha **206** na HEAD (145 na `main`). `01:63` cita `emTelaDeAutenticacao():275`, que está na **279**.
- **Repro**: `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}` sai com exit 1 e 3 linhas.
- **Ação exigida**: reancorar as três (só `01:60`, `01:63` e `01:99` repetem esses valores).

### QA-03 — `## Conformidade com Rules` ainda em rascunho e com evidência falsa · Minor · destino 1
- **Dimensão**: L (L4)
- **Observado**:
  - `.ai/rules/css-filament.md` e `.ai/rules/providers.md` casam `app/Providers/Filament/AppPanelProvider.php` e não têm linha na tabela.
  - A linha de `providers-filament.md` diz "n.a. — nenhum `PanelProvider` editado", mas o `AppPanelProvider` foi editado no RC-02 (só comentário; o veredito n.a. procede, a evidência não).
  - Sete linhas continuam "a aplicar", e a de `support.md` diz "a medir".
- **Repro**: `bash .ai/skills/feature-wiki/scripts/conformidade-rules.sh {wiki} main` dá exit 1 com 2 linhas. Conferi o código contra as rules e não achei violação.
- **Ação exigida**: fechar cada linha com aplicada / n.a. e a evidência, acrescentar as duas rules que faltam e corrigir a evidência de `providers-filament.md`.

### QA-04 — Gherkin do `04` contradiz os testes de CT-50 e CT-51 · Minor · destino 1
- **Dimensão**: L (L1/L3)
- **Observado**:
  - Os `Exemplos` de CT-51 (`04:421-422`) ainda têm `/app/new` e `/app`. O dataset tem uma linha `/app/password-reset/request`. A troca está em `## Desvios do Plano` e na linha M51, mas não no Gherkin.
  - CT-50 (`04:376`) ainda afirma "contém kit/logo-ct.png" na linha `sem vínculo`. O teste afirma 404, `sn-brand` e a ausência de `globex.png`.
  - O checklist (`04:574`) diz "nenhum `Então` desta wiki é 4xx", e hoje o CT-50 afirma 404.
- **Repro**: comparar `04:366-376` e `04:409-422` com `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (linhas 434 a 527).
- **Ação exigida**: alinhar os Exemplos e a linha do checklist ao teste, com a marca *(alterado em …)*.

### QA-05 — Contagem "Sem matador" defasada · Cosmético · destino 1
- **Dimensão**: L (L1)
- **Observado**: `04:21` diz "Sem matador: 0" e `03:44` diz "0 sem matador". O `grep -cE '^\| M-?[0-9]+.*sem matador' 04-casos-de-teste.md` dá **1** (M51, declarado na própria tabela).
- **Ação exigida**: recalcular pelo `grep -c` e corrigir os dois arquivos.

### QA-06 — Integridade do `03` · Minor · destino 1
- **Dimensão**: L (L6)
- **Observado**:
  - `03:93-104` é um bloco duplicado e corrompido: o título "`## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso", itens repetidos da Verificação Final e uma segunda `## Revisão do Diff (step 9)` vazia.
  - `## Despachos` é cortada pela linha em branco `03:164`, e as linhas 4 a 9 ficam fora da tabela.
  - O RC-11 não existe em lugar nenhum, embora `03:168` diga "RC-01 a RC-12".
  - Não há despacho registrado para a derivação do `04` nem para a revisão adversarial que o cabeçalho do `04` declara FEITA.
- **Ação exigida**: remover o bloco duplicado, reunir a tabela, explicar o RC-11 e registrar os despachos do step 7.

### QA-07 — Vocabulário fora do glossário · Minor · destino 1
- **Dimensão**: L (L7)
- **Observado**:
  - A wiki e as docs dizem "composição do cabeçalho" e "marca simples", e o glossário (`wikis/glossario.md:19`) tem "Marca composta", com a "logo solta" como contraste.
  - A definição de "Marca composta" diz que, "com nenhum segmento ligado, a marca é a de sempre", o que deixou de valer no `/app` com organização aberta.
  - "Organização aberta" (a da rota, contra a "da sessão", na ADR-03) foi decidida nesta feature e não entrou no glossário.
- **Ação exigida**: alinhar os termos ao glossário, atualizar a entrada "Marca composta" e criar a entrada "Organização aberta".

### QA-08 — `alt` da logo da organização nomeia a aplicação · Minor · destino 1
- **Dimensão**: H
- **Relacionado a**: P-06, ADV-25 (recusado)
- **Observado**: no topo do `/app/acme`, a `<img>` da logo da Acme tem `alt` "Logo {app.name}" (`vendor/filament/filament/resources/views/components/logo.blade.php:alt:33`) ou `config('app.name')` (`resources/views/filament/cabecalho-do-painel.blade.php:alt:14`). Antes desta entrega o `alt` descrevia a imagem certa; agora descreve outra marca.
- **Ação exigida**: P-06 já declara isso fora de escopo. Registrar como débito de acessibilidade no `03` (ou perguntar ao solicitante), porque a divergência nasce desta entrega.

### QA-09 — Marca misturada no tema escuro (P-03) · Minor · destino 1
- **Dimensão**: A
- **Relacionado a**: RQ-05, P-03, CT-40 (linha "só clara"), CT-43 ("queda só da escura")
- **Observado**: com a marca separada e a organização só com a clara, o tema escuro mostra a escura **da instalação**, ou seja, outra identidade no topo da Acme. O Adendo 1 diz "caindo para a da instalação quando ela **não tem logo**", e essa organização tem logo. A P-03 replica a regra da tela de bloqueio, tem precedente no glossário ("Variante escura … nunca caindo na logo clara") e o formulário avisa "Em branco, usa a da instalação". Mas foi decidida pela sessão, sem pergunta ao solicitante, e no topo o efeito é muito mais visível do que no bloqueio.
- **Repro**: CT-43, linha `queda só da escura`, afirma `fi-logo-dark` = `kit/logo-dark-ct.png` com a clara `acme.png`.
- **Ação exigida**: pergunta de confirmação ao solicitante (`Qn` no `00`). Se ele confirmar o comportamento atual, fecha como destino 5 no ciclo 2.

### QA-10 — Reconciliação (step 10) inacabada · Minor · destino 1
- **Dimensão**: L
- **Observado**:
  - `## Verificação Final` do `03` está inteira em `[ ]`.
  - O segundo item do passo 5 do `03` ("vermelho com o passo 2 revertido") segue aberto.
  - Os pré-requisitos do `05` e o Roteiro "Desenhado × Implementado" estão "a preencher".
- **Ação exigida**: fechar a Verificação Final com evidência e preencher o Roteiro do `05` antes do ciclo 2.

## Matriz de Rastreabilidade

Sem lacuna. O `rastreabilidade.sh` saiu com exit 0. O `git diff` por passo cobre os passos 1 a 6. O passo 7 (`INDEX.md`) fica para depois do Estado final, por desenho. Não existe `07-tickets/`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-09 — `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/`; o diff de cada passo faz o que o passo diz (`logosPara`, `organizacaoAberta`, memo único, factory, textos) |
| B | Fronteiras e dados | ✅ | leitura mais CT-40 (null, branco e órfão nas duas variantes, 12 linhas, verde); o upload não mudou |
| C | Matriz de permissão | ✅ | membro (CT-42…), não membro com 404 (CT-50), `master_global` sem vínculo (CT-56), `admin` em `/admin`, `infra` em `/infra`, visitante (CT-51); nenhuma ação destrutiva |
| D | Observabilidade | ✅ | nenhum log novo; `storage/logs/configuracoes-2026-10-07.log` lido, sem PII; o `warning` existente leva só o id do painel |
| E | Performance | ✅ | grep de `DB::`, `query()`, `->get()` e `->where(` no diff: exit 1 (nenhum acesso a banco); `exists()` uma vez por request (memo) |
| F | UX de erro | n/a | o diff não acrescenta mensagem, validação nem estado de erro (grep de `Notification`, `ValidationException`, `abort`, `throw`: exit 1); o 404 do CT-50 já existia |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0; nível 2: CT-B01 9/9 (54), alternância nos dois sentidos e nas duas formas; nível 3 não rodou |
| H | Acessibilidade | ⏭️ parcial | QA-08 (estático); CT-B01 sem `assertNoAccessibilityIssues()`; teclado não verificado |
| I | Segurança da superfície nova | ✅ | eixos 9 cobertos pelo step 9 (11 RC, nenhum rejeitado; RC-11 ausente, ver QA-06); além dele: IDOR por CT-50 e CT-56 rodados e verdes; nenhuma rota nova, nenhum mass assignment, upload igual, nenhum dado sensível |
| J | Regressão adjacente | ⏭️ parcial | TIA não rodou; ancestrais por ID verdes: CT-16, CT-04, CT-27, CT-11, CT-15, CT-29 a 31; regressão do `01` 126/126 (659); vizinhos da factory e da identidade 227/227 (1.250); browser `LogoDarkMode` e `CabecalhoDoPainel` 5/5; o RCRCRC (`CabecalhoDoPainel`: Core, Recent, Repaired) bate com `## Impacto` |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; K2 não rodou; os 6 mutantes manuais do `03` são anteriores ao refactor dos testes e não têm score nem Duration; revisão adversarial do `04`: feita |
| L | Consistência documental | ❌ | QA-01 a 07 e QA-10; L1 `ids-ct.sh` (3 arquivos juntos) exit 1, só IDs das ancestrais, como o `### Colisão de IDs` prevê; L2 `citacoes.sh` exit 1; L4 `conformidade-rules.sh` exit 1; L6 `checkbox-sem-evidencia.sh` exit 0, e os números foram reproduzidos um a um (QA-01) |

## Débitos Aceitos

- Nenhum ainda. Os Minor e o Cosmético deste ciclo só viram débito se a sessão os aceitar.

## Suspeitas Não Confirmadas

- O `IdentidadeVisualTest` sai com `warnings: 2` e `warning_details` vazio. Não reproduzi a causa.
- O CT-55 lê `docs/` com `naArvoreDoKit()`: soma 2 pulados ao teto do projeto instalado. Isso é assunto da validação antes da tag, que está fora do escopo daqui.

## Não Verificado

- G, nível 3 — motivo: Playwright MCP indisponível, e o Cloudflare Turnstile do `/app/login` barra o login automatizado no app servido. Só vi a tela de login, que mostra a clara da instalação. Nota de infra (destino 4): os dados de QA passados não previam o captcha.
- O update real do Livewire (`refresh-topbar`) no app servido — motivo: o mesmo bloqueio de login; o CT-53 cobre por `Livewire::test` (D5).
- H, `assertNoAccessibilityIssues` e teclado — motivo: fora do arnês do CT-B01, e o app servido não abriu (captcha).
- J, passo 1 (`pest --parallel --tia`) — motivo: proibido pelo orquestrador (memória do host).
- K2, `pest --mutate` — motivo: proibido pelo orquestrador; os 6 mutantes manuais do `03` antecedem o refactor dos testes e não têm score nem Duration.
