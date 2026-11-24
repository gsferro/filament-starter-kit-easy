# Relatório de QA — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (superfície de UI com JS: diagramas do archify)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → build do site, conferidores do site, página do catálogo, `.ai/rules/index.md`, contadores do README · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

*(O ciclo 1 foi feito no HEAD `a624bff1`; o texto inteiro dele está no `06` do commit `c642c23c`, e aqui fica resumido em *Ciclo 1 — situação*. O ciclo 2 foi feito no HEAD `b3681538`. Gravado pela sessão em 2026-11-24, a partir do texto que o `fw-qa-gate` devolveu no despacho #67; o que a sessão fez com cada achado está no `03`, seção `## Quality Gate`.)*

## Veredito — Ciclo 2

**REPROVADO → teste**

- Blocker: 0 · Major: 1 · Minor: 4 · Cosmético: 1
- Não verificadas: G (nível 3), J (TIA e as suítes inteiras), K (K2, e a revisão adversarial dos cenários dos Adendos 2 a 4). As causas estão em *Não Verificado*
- `RQ` abertas: RQ-09 (Q6), sem implementação, conta no teto — a página continua sem lista fechada de fluxos; Q12 aberta, com a P-11 valendo até a resposta
- Ambiente: prévia `http://localhost:4321` do `b3681538`, com os links do passaporte no `b3681538`; Pest 5; o `laravel-boost` não conectou, e o Playwright MCP não está exposto (o ToolSearch não acha ferramenta de browser). Usei o Playwright do `site/` via `node`

## Achados

### QA-10 — O dashboard dinâmico dos três painéis só abre com `KIT_DASHBOARD_DINAMICO`, desligado de fábrica, e o mapa o desenha sem a marca · Major · destino 3

- **Dimensão**: A (e L3)
- **Relacionado a**: RQ-27, RQ-28, P-16 · R30/CT-32 (linha de controle), R42/CT-57 · Setup Global do `04` (o discriminante)
- **Esperado**: pela RQ-27, a peça que o painel registra, mas que "só fica disponível com uma chave", leva a marca da chave e do valor de fábrica. Pela RQ-28, nenhum diagrama a mostra como sempre disponível. A P-16 exclui só "a que só esconde o item de navegação".
- **Observado**:
  - `App\Filament\{Admin,App,Infra}\Pages\Dashboard` vão além de esconder o menu: o `mount()` redireciona para o `DashboardClassico` quando `DashboardDinamico::atende()` é falso (`app/Filament/Admin/Pages/Dashboard.php:mount:55`). O `atende()` exige `habilitado()`, que lê `kit.dashboard_dinamico.habilitado`, com padrão `false` (`config/kit.php:624`).
  - A própria suíte do kit afirma o redirect: `tests/Kit/DashboardDinamicoTest.php`, no caso "devolve a pagina dinamica para a raiz com a feature desligada".
  - O `04` afirma o contrário: `04:517` diz que o `Dashboard` "some do menu sem `KIT_DASHBOARD_DINAMICO` … e abre"; `04:2377` e `04:2405` o põem entre as disponíveis; a linha de controle `04:2415` do CT-32 reprova o modelo que der ao `Dashboard` a chave `KIT_DASHBOARD_DINAMICO`. O CT trava o fato errado: o discriminante só avalia `canAccess()`/`canView()`, que dão `true`.
  - As três peças (F-86) não têm `disponibilidade` em `pecas.json` e são desenhadas sem marca em 9 mapas e nas árvores de registro.
  - A doc diz que as guardas cobram o campo de "toda peça assim" (`docs/pt/operacao/desenvolvendo-o-kit.md`, e o equivalente en).
- **Repro**: `sed -n 50,58p app/Filament/Admin/Pages/Dashboard.php` (o redirect no `mount()`); `grep -n "function flagLigada" -A3 app/Support/DashboardDinamico.php` (padrão `false`); `node -e` sobre `site/mapa/pecas.json`: os três `Dashboard` com `disponibilidade` `null`; `curl -s http://localhost:4321/mapa/pt/admin-registro-sem.html | grep -c KIT_DASHBOARD_DINAMICO` dá 0, com o `Dashboard` desenhado.
- **Evidência**: as saídas acima; o teste citado está verde na suíte do kit.
- **Destino**: 3, depois 2.
- **Ação exigida**: levar à `feature-test-design` "peça cuja indisponibilidade vem de um redirect no `mount()`, e não de `canAccess()`": estender o discriminante, inverter a linha de controle `04:2415`, corrigir `04:517`, `04:2377` e `04:2405`, ver o vermelho, e só então pôr a condição no modelo e regenerar. Se a sessão achar que a P-16 cobre o caso, é pergunta ao mantenedor.

### QA-11 — ADR-07 "86 dos 87 no piso": hoje três de 95 ficam abaixo de 6,5 px, dois deles por causa da marca nova · Minor · destino 1

- **Dimensão**: L (L3)
- **Esperado**: a ADR-07 (`02:371`) diz que só a linha do tempo abre abaixo do piso, e a página do mapa (`docs/pt/referencia/mapa-interativo.md:123`) declara só essa exceção.
- **Observado**: menor texto visível no carregamento, em 1440×900, pelo método do ciclo 1 (a linha do tempo reproduz o 6,31 px da ADR):

  | Diagrama | pt | en |
  |---|---|---|
  | `marcos-do-changelog` | 6,31 px | 6,31 px |
  | `app-produtividade-e-ui-com` ("recurso · só com tenant ativo · KIT_DEMO") | 6,40 px | 6,80 px |
  | `kit-ia` ("Livewire · KIT_AGENTES_IA=false · …") | 6,42 px | 6,42 px |

  Nos dois novos, o texto pequeno é a marca: o rótulo secundário alarga a caixa (D41), e o desenho encolhe. Ressalva: medido no Chromium do Windows; a ADR foi medida no Linux.
- **Repro**: abrir os três HTML na prévia com o Playwright do `site/` e calcular o mínimo de `fontSize × escala(getScreenCTM())` entre os `svg text` visíveis no viewport.
- **Evidência**: `grep -rn "86 dos 87"` acha `02-decisoes-arquiteturais.md:371`, `03-progresso.md:13` e a frase do ciclo 1 no `06`.
- **Destino**: 1
- **Ação exigida**: medir de novo os 95 diagramas e corrigir com a marca o `02:371` e o `03:13`; depois, reposicionar os dois mapas ou declará-los na página, nos dois idiomas.

### QA-12 — P-13 a P-17 fora da `## Cobertura do Requisito` do `01`, e o cabeçalho do `04` ainda diz "P-01 a P-16" · Minor · destino 1

- **Dimensão**: A (o mapa declarado) e L3
- **Observado**: a tabela do `01` (`01:20`–`68`) vai até a P-12; P-16 e P-17 não aparecem no `01` (`grep -c` dá 0); o `04:3` declara "P-01 a P-16", e a P-17 existe no `00:98`. O `rastreabilidade.sh` sai com exit 0 porque não cobra P-nn na Cobertura. Os CT existem (P-15 aparece 24 vezes no `04`, P-16 21, P-17 7).
- **Repro**: `for p in P-13 P-14 P-15 P-16 P-17; do grep -c $p 01-plano-acao.md; done` dá 1, 2, 1, 0, 0.
- **Ação exigida**: uma linha por P-13 a P-17 na Cobertura do `01`, com o passo; `04:3` passa a "P-01 a P-17".

### QA-13 — A P-17 estreita a RQ-33 sem pergunta registrada · Minor · destino 1

- **Dimensão**: A (auditoria do requisito)
- **Observado**: a RQ-33 pede a marca no componente flutuante "como as telas condicionais", que a trazem no nó. A P-17 põe a marca só no card, nos diagramas escritos à mão (`app-chat-de-ia`, `ia-do-prompt-ao-ledger`), e está "vigente — sinalizada no PR", sem linha em `## Perguntas ao Solicitante`. A marca existe e está visível (o `<li>` do card), em `app-chat-de-ia` em y≈3074 px, abaixo da dobra. Mesmo padrão da QA-06 do ciclo 1.
- **Repro**: a sonda Playwright sobre `/mapa/{pt,en}/app-chat-de-ia.html` acha "AssistenteChat: KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia de fábrica" num `LI` visível, e nenhuma marca no nó.
- **Ação exigida**: uma Q nova no `00` sobre o lugar da marca no diagrama escrito à mão, com a P-17 valendo até a resposta; vale também para a P-15 e a P-16, se a sessão as considerar interpretação do pedido.

### QA-14 — "Peça condicional" e "condição de disponibilidade" não entraram no glossário · Minor · destino 1

- **Dimensão**: L (L7)
- **Observado**: os dois termos foram decididos nesta feature (P-15, P-16, D41, a página "Peça condicional / Conditional piece" e o card "Condições de disponibilidade / Availability conditions"); `grep -n -i "condicional\|disponibilidade" wikis/glossario.md` não acha nada.
- **Ação exigida**: a entrada no glossário, com o "Não confundir com" da peça que só pede permissão ou só esconde o menu (P-16).

### QA-15 — Texto defasado no `03` · Cosmético · destino 1

- **Dimensão**: L (L6)
- **Observado**: `03:3`, o Estado, ainda diz "faltam a revisão do diff do Adendo e o QA ciclo 2", e o `03` registra as duas rodadas como feitas; `03:483` diz "174 HTML", e hoje são 190 (95 × 2).
- **Ação exigida**: corrigir as duas linhas.

## Ciclo 1 — situação

| Achado | Situação no ciclo 2 | Como foi conferido |
|---|---|---|
| QA-01 | fechado | Adendos 2 a 4; nenhuma peça condicional por modo em diagrama de modo errado (0 violação em 52 mapas); `TenantResource` 0 vez em `admin-*-sem` e 6, 14 e 17 vezes nos `-com`; marcas pt/en presentes |
| QA-02 | fechado | o comando do `03` dá 42 no `a624bff1` (43 no `c642c23c`; no HEAD, 49, porque o `04` ganhou citações) |
| QA-03 | fechado | o grep do ciclo 1 só acha as marcas `*(alterado em …)*`; a ADR-07 reabre em outro ponto: QA-11 |
| QA-04 | fechado | `--tentativa 0` e "sem slug" saem com `[mapa] ERRO …` e exit 2 |
| QA-05 | débito aceito | `03:13` |
| QA-06 | fechado | Q12 `aberta`; Q23 e Q24 retiradas, como D39 e D40 |
| QA-07 | fechado | docs pt/en citam `MapaInterativo*Test.php` e o Tenancy |
| QA-08 | fechado | `wikis/glossario.md:53` |
| QA-09 | fechado | as quatro linhas; defasagem nova no `03:3`: QA-15 |

## Matriz de Rastreabilidade

| RQ/P | Cláusula | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------|-----------|----|------|--------|-----------|----------|
| RQ-27, RQ-28 | a peça condicional tem marca e entra só no seu modo | 2, 4, 6, 8 | CT-32, 33, 56, 57 | CT-B01 p.4 | `pecas.json` (18 peças), `gerar.mjs` | as 18 certas; os 3 `Dashboard` dinâmicos sem condição | ❌ QA-10 |
| RQ-29 | o `/admin` dividido por modo | 4, 6 | CT-33, 37 | CT-B01 | `admin-*-sem/-com`, `admin-resumo-sem-com` | `TenantResource` só nos `-com` | OK |
| RQ-30 a RQ-33 | configuração de pacote, herança, plugin/middleware, flutuante | 2, 4, 8 | CT-32, 33, 56, 57 | CT-B01 p.4 | marcas `command-center.sources`, `AcessosPorPainel`, chat | presente | OK (RQ-33: QA-13) |
| P-13 a P-17 | — | não estão na Cobertura | CT presentes | — | — | — | ⚠️ QA-12 |
| RQ-09 | fluxos | 3, 4 | CT-24, 39 | CT-B01 | — | sem lista fechada | ⏳ aberta (Q6), teto |
| demais | — | — | — | — | — | `rastreabilidade.sh` exit 0 | OK |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-10, QA-12, QA-13. `rastreabilidade.sh` exit 0; sem `07-tickets/`. RQ-15: 0 arquivo em `app/`, `routes/`, `config/` e `database/` no diff |
| B | Fronteiras e dados | ✅ | repro da QA-04 fechada; recusas novas do gerador (condição e `modos`) cobertas pelo CT-50, `MapaInterativoPublicacaoTest` reproduzido em 65/65 e 611 asserções |
| C | Matriz de permissão | n/a | 0 arquivo de `app/`, `routes/` e `config/` no diff; site estático |
| D | Observabilidade | ✅ | o ciclo só acrescenta mensagens `[mapa]` de recusa que nomeiam o culpado (CT-50); sem log de aplicação |
| E | Performance | ✅ | 95 diagramas × 2 idiomas = 190 HTML; conferidor completo sem erro |
| F | UX de erro | ✅ | mensagens em pt com o argumento ou a peça culpada; a QA-04 fechou |
| G | Tema e cor | ⏭️ | `dark-mode.sh --mecanismo` exit 1; nível 1 `dark-mode.sh` exit 0; nível 2 CT-B05 em 42 HTML, 0 reprovação; nível 3 não verificado |
| H | Acessibilidade | ✅ | `verifica-mapa.mjs`: 42 HTML, 11 tipos, 0 serious/critical fora das toleradas (nested-interactive 40×, color-contrast do índice 12×) |
| I | Segurança | ✅ | coberto pelo step 9: 48 linhas na tabela, 5 rejeitadas por inteiro (AD-11, AD2-04 a AD2-07); além do step 9, nenhuma rota e nenhum código de aplicação |
| J | Regressão adjacente | ⏭️ | arquivos do mapa reproduzidos (269/269 com 1.432 asserções, 65/65, 23/23 com 281); TIA, suítes `Kit`/`Tenancy` inteiras e ancestrais por ID não rodadas pelo QA |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; K2 não verificado (JS); revisão adversarial feita (2 rodadas), mas não sobre R41–R44, CT-56 e CT-57 (`04:14`, "Sem revisão adversarial nesta rodada") — o QA-10 é o tipo de oráculo errado que ela pegaria |
| L | Consistência documental | ⚠️ | QA-11, QA-14, QA-15 (e a parte L3 do QA-12). L1: `ids-ct.sh` exit 1 com 8 linhas (CT-B01 a CT-B08), rejeitadas pela D17; cabeçalho 57 · 44 · 340 · 10 e 8 CT-B conferem pelos `grep -c` do template. L2: `citacoes.sh` exit 0. L4: `conformidade-rules.sh` exit 0. L6: `checkbox-sem-evidencia.sh` exit 0; números reproduzidos: 269/1.432, 65/611, 23/281, 32 compartilhados com 0 ligação de um modo só, CT-B01 2.280 (182 de condição, 186 de card), CT-B02 3.344, CT-B08 5, 42 HTML, 95 diagramas, 18 peças condicionais. L8: n/a, sem `08` (`roteiro-manual.sh` exit 0) |

## Débitos Aceitos

- QA-05 (ciclo 1): piso de leitura fora do CI, aceito pela sessão.
- Nenhum outro aceito nesta execução; QA-11 a QA-15 são débito candidato.

## Suspeitas Não Confirmadas

- `convite.json`, diagrama compartilhado: o estado "Recusado" leva o sublabel "só com tenant" (a redação decidida é "só com tenant ativo") e cita o `ConvitesRecebidos`. Pela D43, um estado não designa a peça, e a guarda não o acusa: observação de redação, não achado.
- Foco visível nos nós `<g role="button">` (do ciclo 1): continua sem olhar a tela.

## Não Verificado

- **G, nível 3**: o Playwright MCP não está exposto, e o hook nega gravar captura.
- **J**: TIA, suítes `Kit`/`Tenancy` inteiras e CT das ancestrais por ID — a sessão roda as suítes em paralelo, e o limite é de 3 processos pest.
- **K2**: o código de produção é JS (`site/mapa/*.mjs`); o `pest-plugin-mutate` só muta PHP, e não há ferramenta de mutação JS no projeto.
- **K, revisão adversarial dos Adendos 2 a 4**: não foi feita (`04:14`).
- **L6, falsificabilidade** ("7 de 7", "3 de 4" no `03`): exige checkout dos commits anteriores; conferida só a ordem dos commits, teste antes da correção (`b9a365c1` → `68a1f789`).
- **MCP `laravel-boost`**: não conectou.
