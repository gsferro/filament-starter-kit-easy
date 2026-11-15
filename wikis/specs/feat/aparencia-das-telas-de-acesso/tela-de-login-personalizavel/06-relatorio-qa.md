# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável (Ciclo 3 dos Adendos 2 e 3 e ciclo 1 do Adendo 4)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. A UI tem JS (EasyMDE, iframe de preview, rede com Alpine e agora a View Transitions API). O domínio é sensível: Markdown numa tela pública e escopo por organização
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3 dos Adendos 2 e 3 · Ciclo 1 do Adendo 4

**REPROVADO → implementação (QA-28, pendência de release)**

- Blocker: 0 · Major: 0 novos. Continua aberto 1 Major antigo, o QA-28: os cenários 1–4 ainda não rodaram sobre o commit final · Minor: 3 novos (QA-36, QA-37, QA-38) · Cosmético: 1 novo (QA-39)
- Nenhum achado novo reprova a entrega. O veredito só é REPROVADO porque o QA-28 continua aberto. Se a rodada `f1`..`f4` sair verde sobre o commit final, sem mudança de código depois dela, o veredito cai para o teto, **APROVADO COM DÉBITO**, com QA-36..39 como débito. Se `app/`, `resources/` ou `public/` mudarem por causa de QA-36..39, a rodada precisa ser refeita.
- Este é o ciclo 3 dos Adendos 2 e 3, ou seja, o teto do loop. Não há ciclo 4: o que segue aberto é escalado ao solicitante.
- Não verificadas: G, H, J, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma formalmente. Q1 (P-01) e Q12 (P-27) continuam abertas, com a recomendação implementada. São débito, como nos ciclos anteriores
- Ambiente: app em `http://127.0.0.1:8123`, HEAD `e87b1a19`. Pest 5. Boost MCP indisponível (`laravel-boost` CONNECT_TIMEOUT). Playwright MCP indisponível
- Testes que rodei, um arquivo por vez, todos verdes:
  - `tests/Kit/AparenciaDasTelasPorTelaTest.php` 103/103
  - `tests/Kit/AparenciaDasTelasAdendoTest.php` 58/58
  - `tests/Kit/AparenciaDasTelasDeAcessoTest.php` 133/133
  - `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` 32/32
  - `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php` 5/5
  - `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` 83/83
  - `tests/Kit/SiteDeDocumentacaoTest.php` 68/68
  - `--filter='CT-(98|99|10[0-3])\]'` 15/15
  - `tests/Browser/AparenciaDasTelasDeAcessoTest.php --filter=CT-B19` 2/2
- Conferência dos números da sessão: 103+58+133+32+83 = 409 = 406 + 3, sendo os 3 o CT-102 e as duas linhas do CT-103. Isso bate com o "406/406 antes da revisão do Adendo 4" e com o "15/15" (5+4+1+2+1+2)

## Situação dos achados do ciclo 2

| QA | Situação | Evidência |
|---|---|---|
| QA-34 | **fechado** | `grep -nE 'Cen.*\[CT-(8[0-9]|9[0-7])\]' 04` → 18 cenários, com `Então`/`Exemplos`. R27 está completo: CT-81 com 12 linhas hostis, CT-90 nas bordas 160/161 e 4096/4097 bytes, CT-91 e CT-92 nas bordas 80/81. Todo CT-80..CT-97 é matador de pelo menos um mutante (M109..M133). O cabeçalho do `04` (`103 · 17 · 118 · 0`) e o do `05` (`19 · 8 · 23 · 0`) batem com os `grep -cE` do template |
| QA-35 | **fechado no que pediu; resíduo novo no QA-36** | A rodada passou a apontar para `f1`..`f4` (`03:240`), a Verificação Final tem a linha de `694ec0d8`, e o Estado e o `## Testes` foram atualizados para o ciclo 2. O Adendo 4 desatualizou de novo `## Testes` e a ordem dos despachos |
| QA-28 | **aberto, como pendência de release** | `03:240` `[ ]` Cenários 1 a 4 sobre o commit final (`f1`..`f4`) |

## Achados

### QA-36 — A wiki do Adendo 4 ficou para trás em relação ao código e à revisão do diff · Minor · destino 1

- **Dimensão**: L (L1, L3, L6, L7)
- **Relacionado a**: passo 12 do `01`, P-32, CT-102, CT-103, CT-B19, RD-D1..D3
- **Esperado**: a revisão do diff do Adendo 4 mudou a regra (a raiz clássica guarda a opção) e acrescentou CTs. O texto da wiki deveria acompanhar.
- **Observado**:
  1. `01:389` diz "**Testes**: CT-98..CT-101 e CT-B19 (prova pelo `pageswap` da página de origem)". Faltam CT-102 e CT-103, e o CT-B19 hoje também confere o `pagereveal` do destino. O passo 12 não traz a regra do RD-D1: com o login no clássico, a raiz guarda `transicao_entre_telas`, e o editor a reabre (`AparenciaDasTelasDeAcesso::normalizar` e `EditorDeAparenciaDasTelas::telaParaOFormulario`).
  2. A `# Origem` do CT-102 (`04:2206`) cita entre aspas, como se fosse da P-32, a frase "com o login no clássico, vale o que estiver gravado na raiz". A frase não está na P-32 (`00:109`). Ela só existe no comentário do código (`app/Support/AparenciaDasTelasDeAcesso.php:normalizar:157`).
  3. O `03`:
     - `03:118` diz "badge 2.592", mas o README e o README.en têm 2.594, e o `SiteDeDocumentacaoTest` passa com 2.594.
     - `## Testes` (`03:158-160`) diz que o PorTela tem "CT-57..CT-92" (ele vai até o CT-103) e que o Browser tem "CT-B01..CT-B18" (falta o CT-B19).
     - Os despachos estão na ordem 29, 31, 32, 33, 30, embora o tratamento do QA-35 diga "despachos 25..30 em ordem".
     - A Verificação Final tem o `[ ]` da rodada `f1`..`f4` (`03:240`) entre a linha de `694ec0d8` e a de `ca469486`, que é mais antiga.
     - O parágrafo "Degradações" não diz que CT-98..CT-103 e CT-B19 foram derivados pela sessão. O `04:2141` diz, e não há revisão adversarial do Adendo 4 registrada.
  4. O cabeçalho do `04` (`04:9`) diz "Oráculo atual: … RQ-01..RQ-33 … P-01..P-31". Faltam RQ-34..RQ-36 e P-32..P-34.
  5. L7: o `wikis/glossario.md` não tem o termo "Transição entre as telas", que foi decidido nesta feature (`grep -n "Transição" wikis/glossario.md` → nada).
- **Repro**: `sed -n 389p 01-plano-acao.md` · `grep -rn "vale o que estiver gravado na raiz" {wiki} app/` · `grep -rn "2\.59[0-9]\|2,59[0-9]" {wiki} README*.md` → `03:118` (2.592) × `README.md:8` (2.594) · `sed -n 155,162p 03-progresso.md` · `sed -n 380,390p 03-progresso.md`
- **Evidência**: `ids-ct.sh` exit 0, `citacoes.sh` exit 0 e `checkbox-sem-evidencia.sh` exit 0. Nenhum dos três checa o que está acima
- **Destino**: 1
- **Ação exigida**:
  - Reescrever o passo 12 do `01` com o RD-D1, o CT-102, o CT-103 e o `pagereveal`, com a marca *(alterado)*.
  - Ou registrar na P-32 a regra da raiz clássica, com a marca *(alterado)*, ou tirar as aspas da `# Origem` do CT-102.
  - No `03:118`, trocar 2.592 pelo valor real (o único lugar que repete o número).
  - Atualizar `## Testes` com o PorTela até CT-103 e o Browser até CT-B19.
  - Ordenar os despachos e a Verificação Final.
  - Declarar a degradação do Adendo 4 no `03`.
  - Atualizar o "Oráculo atual" do `04`.
  - Pôr o termo no glossário.

### QA-37 — Na organização, a "Transição entre as telas" promete o que não controla · Minor · destino 1

- **Dimensão**: A / L5 (premissas que se cruzam: P-32 × P-31 × P-06)
- **Relacionado a**: RQ-34, RQ-13, P-06, P-31, P-32
- **Esperado**: a P-32 diz que a opção do login "vale para todas as telas". A ajuda do interruptor diz "Ao ir do login para o cadastro ou o 'esqueci a senha' (e de volta), o painel desliza…".
- **Observado**: o editor da organização tem o interruptor, porque `EditorDeAparenciaDasTelas::campos(daOrganizacao: true)` chama `camposDaTela(raiz: true)`, linha 77. Mas a recuperação de senha usa sempre a aparência padrão (P-31, P-06): `VesteAparenciaDasTelas::aparenciaDaTela()` com a família `recuperacao`, sem organização. Como a transição exige as duas pontas, o resultado depende da padrão:
  - Organização com a opção ligada e a padrão desligada, ou no clássico: o login da organização para o "esqueci a senha" não anima, apesar do texto de ajuda.
  - Organização com a opção desligada e a padrão ligada: o login da organização não participa, e ninguém percebe.
  
  Nenhum `RQ`/`P-nn` decide esse cruzamento. Nenhum CT de Tenancy toca a transição (`grep -c transicao tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` → 0).
- **Repro** (por leitura, sem gravar no app):
  1. `sed -n 62,80p app/Filament/Admin/Schemas/EditorDeAparenciaDasTelas.php`: o interruptor está na raiz, também no formulário da organização.
  2. `sed -n 50,60p app/Filament/Concerns/VesteAparenciaDasTelas.php` e P-31: a recuperação resolve a padrão.
  3. `painel-de-marca.blade.php`: a regra `@view-transition` só sai na página cuja aparência tem a opção ligada.
- **Destino**: 1
- **Ação exigida**: decidir em `P-nn` qual opção vale no par "login da organização → recuperação padrão" e se o interruptor da organização deve existir ou explicar esse limite. Depois, corrigir a ajuda e as docs pt/en. Se a decisão mudar o comportamento, escrever o CT de Tenancy antes da correção.

### QA-38 — A transição no cadastro não tem CT, e a revisão do diff já tinha achado essa classe de falha · Minor · destino 3

- **Dimensão**: A / K
- **Relacionado a**: RQ-34 ("login → 'esqueci a senha' **ou cadastro**, e de volta"), CT-98, CT-103, M141
- **Esperado**: a RQ-34 cita o cadastro. Nesta mesma feature, o RD-D3 mostrou que uma página pode deixar de participar da transição sem que nada a pegue: as páginas únicas do login unificado precisaram do CT-103.
- **Observado**: o CT-98 confere só `/admin/login` e `/admin/password-reset/request`. O CT-103 confere só `/login` e `/esqueci-minha-senha`. Nenhum CT abre uma página de cadastro (cadastro aberto, `/cadastro` unificado ou `RegistroPorConvite`) nem afirma `participaDaTransicao`. O comportamento provavelmente está certo: o partial `media` do auth-designer é comum a todas as telas, e é ele que inclui `painel-de-marca`. Mas o mutante "o cadastro não usa o partial, ou não empurra a regra" sobrevive.
- **Repro**: `grep -n "participaDaTransicao(htmlDe" tests/Kit/AparenciaDasTelasPorTelaTest.php` → só os caminhos de login e recuperação. No app servido, `/app/register` responde 302 (cadastro desligado localmente), então não há como observar ao vivo.
- **Destino**: 3
- **Ação exigida**: pela `feature-test-design`, incluir no CT-98 ou no CT-103 as linhas do cadastro (painel, unificado e convite) com um mutante próprio, e só depois mexer no código, se for preciso.

### QA-39 — As capturas do editor não mostram a opção nova · Cosmético · destino 1

- **Dimensão**: L5 (pedido "gif com todas as possibilidades de customização")
- **Observado**: `art/telas-de-acesso-editor-*.png` e `telas-de-acesso-editor.gif` são de 14/11 20:29, de antes do commit `55c5c09f`. O `editor-4-ilustracao.png` termina no "Alternador de tema claro/escuro" e não tem o interruptor "Transição entre as telas", que fica logo abaixo dele. A transição tem o GIF próprio dela, mas o GIF do editor, que mostra "todas as possibilidades", não está completo.
- **Repro**: `ls -la art | grep telas-de-acesso-editor`, e abrir `art/telas-de-acesso-editor-4-ilustracao.png`
- **Destino**: 1
- **Ação exigida**: gerar de novo os quadros do editor pelo `composer art`.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. Não há `07-tickets/`. Lacunas:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-34 | troca animada, inclusive com o cadastro | 12 | CT-98, CT-99, CT-103 (sem cadastro) | CT-B19 | `painel-de-marca.blade.php`, `kit.css` | ao vivo, login e recuperação de `/admin`, `/app` e `/infra` com a regra no `<head>` e `data-kit-transicao` | ⚠️ QA-38 |
| RQ-34 × P-31/P-06 | opção da organização × recuperação padrão | 12 | — | — | `EditorDeAparenciaDasTelas::campos` | cruzamento sem decisão | ⚠️ QA-37 |
| RQ-07 / RQ-08 / RQ-17 | não quebrar quem usa o kit; cenários | — (processo) | CT-21, CT-27 `@obsoleto` | — | HEAD `e87b1a19` | sem rodada sobre o commit final | ⏳ QA-28 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. Diff por passo `fb051a17..HEAD`: o passo 12 está todo no código (decisor, editor, view, CSS duplicado em `kit.css` e `kit-correcoes.css`). O CSS servido tem 2 `view-transition-name`. As RQ-35 e RQ-36 batem com o CSS: só o grupo `kit-painel` anima posição; o formulário antigo fica com `opacity: 0` e o novo entra por `kit-formulario-aparece`. Achados QA-37, QA-38; pendência QA-28 |
| B | Fronteiras e dados | ✅ | A opção é booleana com padrão. O valor inválido (`"sim"`), a ausência (gravada antes do Adendo 4) e o clássico estão no CT-98, verde aqui. A regra da transição é estática, sem entrada do usuário. As fronteiras dos Adendos 2 e 3 (CT-81, 90, 92) estão verdes |
| C | Matriz de permissão | ✅ | Nenhuma rota, policy ou action nova. O interruptor fica no editor que já existia (master global; formulário da organização) |
| D | Observabilidade | ✅ | Nenhum ponto de log novo no Adendo 4 (o diff de `app/` não tem `Log::`). A linha real do ciclo 2 continua válida, sem PII |
| E | Performance | ✅ | Por leitura: `@push` de um `<style>` fixo e um booleano no normalizador. Nenhuma query nova |
| F | UX de erro | ✅ | Não há caminho de erro novo. O texto de ajuda está em português (a imprecisão dele na organização está no QA-37) |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). O nível 1 sobre `--diff-filter=AM fb051a17...HEAD` (app/resources/public) deu exit 0. O nível 3 não rodou, por falta de MCP |
| H | Acessibilidade | ⏭️ não verificada | O "reduzir movimento" é respeitado pelo `@media` do CSS e da regra, mas só está afirmado como texto (CT-98, CT-101), sem navegador com `reducedMotion`. Nenhum CT-B chama `assertNoAccessibilityIssues`. MCP indisponível |
| I | Segurança da superfície nova | ✅ | O step 9 do Adendo 4 cobre o diff (`## Revisão do Diff — Adendo 4`: 4 achados, 0 rejeitados), e as quatro linhas *eixo 9* não rodaram de novo. Por cima dele, conferi que o `<style>` empurrado não tem nenhum dado do usuário. `aparenciaDaTela()`, que pode ser chamado por `$wire`, passa a expor `transicao_entre_telas`, que é um booleano sem sigilo. A navegação é de mesma origem, e a View Transitions API não vaza estado entre documentos |
| J | Regressão adjacente | ⏭️ não verificada | Os 7 arquivos da feature e os vizinhos rodaram verdes, 1 por vez. A suíte inteira e o `--tia` não rodaram, por restrição de memória. Os cenários 1–4 estão pendentes (QA-28) |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` sobre os testes `694ec0d8...HEAD` deu exit 1. As candidatas foram rejeitadas: os 12 testes `CapturaDeArteTest` são geração de arte (só com `KIT_ART=1`), não CT; CT-75, CT-33, CT-07 e CT-11 já foram julgadas no ciclo 2 (helpers com `expect`). K2 não rodou. A falsificabilidade alegada no `03` é plausível pela leitura do dataset do CT-99 (as 4 linhas invertem sem a cópia), mas não foi reproduzida. Não há revisão adversarial do Adendo 4 (QA-36, item 3) |
| L | Consistência documental | ⚠️ | Scripts: L1 `ids-ct.sh {wiki} 'tests/**/AparenciaDasTelas*.php'` exit 0; L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh {wiki} main` exit 0; L6 `checkbox-sem-evidencia.sh` exit 0; L8 `roteiro-manual.sh` exit 0 (n/a: sem `08`, decisão `não`). L5: pt × en coerentes (as mesmas 26 imagens, todas existem em `art/`); o CHANGELOG está alinhado. Achados: QA-36 e QA-39 |

## Débitos Aceitos

- Nenhum neste ciclo, porque o QA-28 está aberto. Quando ele fechar, entram como débito QA-36..QA-39 e os débitos que já estão no `03`: Q1, Q2, Q12, alternador fora do teclado, organização fora do navegador, BP-51, preview sem a pilha do painel, ADV-A15, ADV-C23 e ADV-C30.

## Suspeitas Não Confirmadas

- O cache do navegador: `kit-correcoes.css` é servido com `?v=5.10.2.0`, que é a versão do Filament e não o hash do arquivo. Quem já usava o kit e atualiza pode ficar com o CSS antigo e ver a regra sem os nomes, até o cache vencer. Isso vem de antes da feature, e não reproduzi.
- O GIF da transição tem 3 quadros do meio reconstruídos (`03:117`), e as docs não avisam que são ilustração. Não é defeito do produto.

## Não Verificado

- G nível 3, e H com axe, teclado e "reduzir movimento" no navegador: o Playwright MCP está indisponível, e nenhum CT-B usa `assertNoAccessibilityIssues` nem `reducedMotion` para a transição.
- J: a suíte inteira (Kit, Tenancy e Browser) e o `--tia` não rodaram, por restrição de memória do orquestrador. A medição está prevista para o commit final.
- J / RQ-08 / RQ-17: os cenários 1–4 sobre o commit final (`f1`..`f4`) ainda não rodaram. É o QA-28, e bloqueia o merge.
- K2, `--mutate`: as ferramentas existem (já provado no ciclo 2). Não rodou por restrição de memória e porque o plugin não é confiável no Windows. A amostragem manual exigiria editar a árvore.
- Revisão adversarial dos CT do Adendo 4: não foi feita. O perfil da área é "padrão" (P×I 4), então ela não é obrigatória, mas o `03` não declara isso.
- B e I ao vivo com configuração gravada (opção desligada; organização): não gravei nada no app. A cobertura vem dos CT rodados aqui. Ao vivo, conferi só o estado atual (as três telas de login e a recuperação de `/admin` com a regra e o painel marcado).
- Cadastro ao vivo: `/app/register` responde 302 e `/admin/register` responde 404 (cadastro desligado localmente).
- RQ-12: os nomes de referência estão mascarados. Pelo oráculo genérico, as docs novas só apontam para o próprio repositório do kit.
