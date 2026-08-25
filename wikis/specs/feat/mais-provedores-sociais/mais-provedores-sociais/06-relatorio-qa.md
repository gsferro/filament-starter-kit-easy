# Relatório de QA — W8: mais provedores de login social

Confronto `00-requisito.md` × `01-plano-acao.md` × aplicação rodando. Ciclo único.

## Veredito

**APROVADO COM DÉBITO.** Nenhum achado bloqueante em aberto. Os cinco defeitos encontrados foram
corrigidos dentro desta entrega (quatro no código, um num caso de teste); dois débitos ficam
declarados e um deles é de escopo, não de implementação.

## Matriz de Rastreabilidade

| RQ | Cláusula | Plano | Código | Teste | Situação |
|---|---|---|---|---|---|
| RQ-01 | GitHub, Facebook, LinkedIn, X, Discord | passos 1–7 | `ProvedorSocial` (4 casos) | R1–R7, R13–R19 | ⚠️ **parcial e justificado**: 3 dos 5 entram. Facebook (ADR-05) e Discord (ADR-04) fora, com o motivo e o que faltaria, nos ADRs e nos dois READMEs |
| RQ-02 | lista aberta, sem reescrever os anteriores | 1, 3, 4, 6, 7 | o enum é o único ponto de extensão | R15 (o enum é a única lista, em 7 superfícies) | ✅ |
| RQ-03 | ícone da marca em cada botão | 6 | 4 partials de SVG + `data-provedor` | CT-08, CT-B01 | ✅ — **o oráculo não existia** até a derivação dos casos apontar; ver Achados |
| RQ-04 | botão só com todos os dados preenchidos | 2, 4 | `ConfiguracaoDoLogin::disponivel()` | R1 (tabela de decisão × 4 provedores) | ✅ |
| RQ-05 | ligar a opção abre os campos | 7 | `Section` + `visible()` no toggle `->live()` | R11, **CT-B02** | ✅ — o `->live()` só é matável no navegador |
| RQ-06 | campos para todos os provedores | 5, 7 | 9 propriedades + migration | R11 (uma seção por provedor), R12 | ✅ |
| RQ-07 | default `false` no registro aberto | — | estado do kit, não tocado | `tests/Kit/RegistroAbertoTest.php:140` | ✅ — o PRD apontava para um `CT-R1` inexistente; corrigido |
| RQ-08 | default `false` em cada provedor | 3, 5 | `filter_var` por provedor | R14 (coerção × 4) | ✅ |
| RQ-09 | ligado reflete em tudo que vem | 2, 4, 6 | rota 404 por provedor, isolamento | R2, R3, R12 | ⚠️ **débito declarado**: o painel de destino é sempre o `/app`. Ver Débitos |
| RQ-10 | muito bem documentado nos READMEs | 10 | `README.md`, `README.en.md`, `.env.example` | R16 (termos declarados nos 3 arquivos) | ✅ |

**Nenhuma cláusula órfã.** Toda `RQ` tem passo, código e caso. As duas marcadas ⚠️ são escopo
declarado e débito declarado, não omissão silenciosa.

## Achados

Cinco defeitos. **Nenhum deles estava no plano**, e quatro são de código — o que faz desta a
rodada em que o gate rendeu mais do que a matriz.

| # | Severidade | Achado | Destino | Situação |
|---|---|---|---|---|
| QA-01 | **Blocker** | O `client_secret` e a senha de SMTP **não podiam ser gravados pela tela**. `fn (?string $estado)` na closure de `->dehydrated()`: o Filament resolve por NOME (`schemas/src/Components/Component.php:87-98`), nome desconhecido com tipo escalar não resolve (`support/src/Concerns/EvaluatesClosures.php:143-160`), a closure recebia `null` e `filled(null)` era `false` sempre | implementação | ✅ corrigido (`$state`), + o caso que faltava |
| QA-02 | **Major** | O segredo do Google ia **em texto claro** para `audits.old_values`/`new_values` desde a v0.19.2. `AuditarConfiguracoesDoKit:127` mascara por `in_array($prop, encrypted(), true)`, e a chave estava fora da lista | implementação | ✅ corrigido + migration que mascara o já gravado + aviso de rotação |
| QA-03 | **Major** | O `client_secret` do Google era **cifrado na ida e não decifrado na volta** — `addEncrypted` na migration, ausente em `encrypted()`. Leitura devolvia ciphertext; um save pela tela regravava em claro | implementação | ✅ corrigido (ADR-06) |
| QA-04 | **Major** | A conta criada por login social nascia **sem `email_verified_at`**, prendendo a pessoa numa tela de "verifique seu e-mail" no instante seguinte a um OAuth bem-sucedido | implementação | ✅ corrigido |
| QA-05 | **Minor** | A normalização da migration deixava `''` em claro, o que **reintroduz** o modo de falha que ela existe para evitar (`decrypt('')` estoura e o `catch` do provider engole o grupo inteiro) | implementação | ✅ corrigido |
| QA-06 | **Minor** | `null` no bruto era tratado como desmentido, então o X recusava com `email_verified: null` — ausência de informação, não negação | implementação | ✅ corrigido (`blank()`) |
| QA-07 | **Minor** | Um caso de teste esperava **2** linhas em `authentication_log` para duas voltas; o pacote **deduplica** (janela de 5 min, `LoginListener.php:59-80`) | teste | ✅ corrigido |

### O que QA-01 ensina, e é a lição mais transferível da rodada

`.ai/rules/pages.md` pede o par: "o segredo não aparece no HTML" **e** "o segredo sobrevive a um
save que não o tocou". Os dois **afirmam o que NÃO acontece** — e um `->dehydrated()` que devolve
`false` para sempre satisfaz os dois perfeitamente, enquanto torna o campo impossível de usar.

**Dois casos de ausência não fazem um par.** O contrapeso de "não grava quando em branco" é
"grava quando preenchido", e esse caso não existia em lugar nenhum. A senha de SMTP ficou
inutilizável pela tela por três releases com dois testes verdes olhando para ela.

## As 11 dimensões

| Dimensão | Resultado |
|---|---|
| **Fronteiras** | ✅ `client_secret` vazio (não ausente) coberto **por provedor** — é o que sobra de um `.env` pela metade. `''` na normalização virou QA-05. `null` no bruto virou QA-06 |
| **Matriz de permissão** | ✅ nada novo: a tela segue sob `View:ConfiguracoesDoKit` via `ExigePermissaoDaTela`. As rotas de OAuth são públicas por desenho, e a barreira é o `abort_unless` por provedor |
| **Log real** | ✅ channel `autenticacao`, formato `[Classe@Método]`, `provedor` em toda linha, e-mail mascarado, `motivo` em cada recusa. Conferido que abrir a tela de login **não** loga nada (o `config/logging.php` mediu 1,1 MB/dia nesse erro) |
| **N+1** | ✅ nada novo. O callback faz uma query (`contaCom`) e o settings uma por boot. **A entrega tirou uma requisição HTTP externa por login do GitHub** |
| **UX de erro** | ✅ mensagens genéricas de propósito (dizer qual barreira reprovou é reconhecimento); o motivo fica no log. Placeholder do segredo diz "em branco mantém" — e agora isso é verdade, o que QA-01 mostra que não era |
| **Tema / dark mode** | ✅ os três ícones novos são `currentColor`, então seguem o tema sozinhos; o do Google mantém as quatro cores da marca. CT-B01 confere visibilidade renderizada, não presença no DOM |
| **Acessibilidade** | ✅ `aria-label="Entrar com {rotulo}"` por botão; SVG com `aria-hidden`/`focusable="false"`; divisor "ou" com `aria-hidden` |
| **Segurança da superfície nova** | ✅ é o eixo da entrega. Rota 404 por provedor; e-mail verificado com prova positiva ou presença justificada; **nenhuma criação de conta** com registro fechado; `state` de CSRF ligado; segredo fora de log, tela, HTML e — agora — da trilha |
| **Regressão adjacente** | ✅ os 61 casos do Google e os 4 de tenancy passam; o rename de rota não tocou nenhum (todos usam URL literal). `ConfiguracoesDoKitTelaTest` ganhou um caso e segue verde |
| **Adequação da suíte** | ⚠️ **o achado central**: a suíte existente tinha oráculo fraco em duas famílias — o par de segredo (QA-01) e o `icone()`/marca (RQ-03 sem oráculo em 3 dos 4 provedores). Os dois foram fechados |
| **Mutation score** | ⚠️ **não medido** — ver Débitos |

## Débitos declarados

1. **`pest --mutate` não foi executado.** Nesta máquina uma passada da suíte de Kit leva de 6 a
   12 minutos, e mutação multiplica isso por dezenas. Em compensação, cada regra do `04` declara
   os mutantes plausíveis e aponta o cenário que mata cada um — e a rodada mostrou que essa
   declaração funciona: **quatro defeitos reais** foram pegos por casos derivados dessa forma, não
   por sorte.

2. **O painel de destino é sempre o `/app`** (RQ-09). Os botões estão nas três telas de login,
   porque o render hook é único; quem clica em `/infra/login` termina no `/app`, e uma recusa
   volta para o login do `/app`. Não é furo — a pessoa é autenticada e o papel dela continua
   governando o que ela alcança —, é atrito de navegação. Comportamento **herdado sem alteração**
   da entrega do Google; guardar o painel de origem entre a ida e a volta do OAuth é feature nova.
   Registrado no ADR-09 item 6, nas Ambiguidades do `00` e nos dois READMEs.

3. **`vendor/` está atrás do `composer.lock`.** A `main` atualizou oito dependências
   (`laravel/framework` 13.25.0 → 13.26.1 entre elas) enquanto esta branch estava aberta. O
   rebase trouxe o lock novo sem conflito, mas o `vendor/` instalado aqui é o antigo, e **todos os
   números de teste deste relatório foram produzidos contra 13.25.0**. Não foi corrigido por
   instrução explícita de escopo (o lock é de outra pessoa nesta rodada). Precisa de um
   `composer install` e uma passada da suíte antes do merge.

## Hipóteses levantadas e REJEITADAS

Registrar rejeição é o que separa "procurei" de "achei onde olhei" (`.ai/rules/specs.md`).

| Hipótese | Por que foi rejeitada |
|---|---|
| Deletar `icone()` do enum, renomeando a partial do LinkedIn — corte real de ~10 linhas | O risco que o corte eliminaria (o método divergir do nome do arquivo) **já tem guarda automática**: um caso percorre `cases()` e assere que a partial de cada `icone()` existe. Corte que troca 10 linhas por churn coordenado em quatro arquivos não é o mais barato |
| Guardar a invariante de escopo do GitHub em **runtime**, com `in_array('user:email', getApprovedScopes())` | Pareceria mais seguro e seria pior: depende de o GitHub sempre devolver `scope` na resposta do token (`AbstractProvider:261`), e se essa suposição estivesse errada o login do GitHub morreria inteiro. Invariante de configuração se guarda com teste, não com indisponibilidade — e trocar uma suposição de vendor não verificada por outra seria repetir o erro que a rodada acabou de corrigir |
| Editar o `redirect` pela tela, para RQ-05 abrir "todos os dados de config" | Permitiria apontar o callback para fora do domínio. Ele é derivado do `APP_URL` de propósito, e o predicado continua conferindo as três chaves |
| Aceitar o Facebook documentando o risco | Faria o nível de garantia do login depender de **qual botão a pessoa clicou**, e o botão mais fraco seria o vetor. Com o registro fechado — o default — o caminho principal é o casamento com conta existente, que é justamente o lado perigoso do e-mail não verificado |
| Apagar as linhas de `audits` que contêm o segredo em claro, em vez de mascarar | Destruiria a auditoria para consertar um vazamento. Quem alterou, quando e de onde tem valor; o segredo não |

## Varredura de padrão

`.ai/rules/specs.md` manda varrer o padrão antes de consertar o ponto. Feito, e rendeu:

- **`encrypted()` tem UM dono e TRÊS consumidores** — o decifrador da leitura
  (`SettingsMapper:92`), o cifrador da gravação (`:67`) e a máscara da trilha
  (`AuditarConfiguracoesDoKit:127`). Nenhum dos três foi escrito pensando nos outros. Varrer os
  consumidores da lista é o que achou QA-02, que nenhuma matriz de rastreabilidade acharia.
- **`fn (?string $estado)`** — `grep` por `dehydrated(fn` achou as **duas** ocorrências, e as duas
  estavam erradas. Consertar só a do provedor deixaria a senha de SMTP quebrada por mais uma
  release.
- **Leitura de vendor sem abrir as linhas ao redor** — o erro do GitHub (ADR-03) tinha
  `file:line` **correto** (`:68-70` é mesmo o `catch`) e leitura errada, porque a linha que
  decidia era a `:48`, da atribuição. `file:line` correto não é o mesmo que leitura correta.

## Próximo ciclo

Não é necessário. Nenhum achado em aberto; os três débitos são declarados e dois deles têm dono
fora desta branch.
