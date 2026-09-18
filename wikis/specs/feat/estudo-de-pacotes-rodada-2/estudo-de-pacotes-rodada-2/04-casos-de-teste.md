# Casos de Teste — Estudo e adoção de pacotes Filament, rodada 2

> Requisito: `00-requisito.md` (incluindo **Adendo 1** e **Adendo 2**) · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação da
> feature.

## Degradação declarada — a implementação precedeu o desenho de teste

Esta derivação foi feita **depois** de o código dos cinco itens nativos já existir na árvore de
trabalho, o que inverte a ordem que a `feature-wiki` estabelece (step 4 antes do step 5) e que a
Proibição 1 desta skill protege. Duas consequências práticas, registradas para que ninguém as leia
como coincidência:

1. **Nada foi derivado do código.** As regras abaixo saem do `00-requisito.md`, das suas
   `## Ambiguidades` resolvidas e das duas tabelas de decisão do **Adendo 2**. O `01` e o `02`
   entraram só para path, rota, nome de classe e superfície — o que foi recusado como oráculo está
   na seção `## Fronteira com o Plano`.
2. **Já existem testes escritos antes destes cenários.** `tests/Kit/FundacaoTest.php` traz dois
   casos sobre `getAuditInclude()` e `tests/Kit/KitInfoTest.php` foi tocado. Eles nasceram pelo
   caminho inverso (teste primeiro, wiki depois), que é a Proibição 11. O tratamento aqui é o que a
   skill manda: os cenários foram derivados **do requisito, sem consultá-los**, e a reconciliação
   de IDs é trabalho do step 7 da `feature-wiki` — se um caso existente não corresponder a nenhum
   `CT-nn` daqui, ele é dívida a reconciliar, não cobertura a creditar. Em particular,
   **CT-26 a CT-29 não são os casos que já existem**: os que existem afirmam sobre a *lista*
   devolvida por `getAuditInclude()`, e os daqui afirmam sobre a **linha gravada em `audits`**, que
   é o comportamento que RQ-13 compra.

## Perfil de Derivação

| Área | P | I | P×I | Perfil | Por quê |
|---|---|---|---|---|---|
| A1 — rodapé com a versão do sistema | 2 | 3 | 6 | padrão | integra com os 3 painéis **e** com o layout das telas de autenticação; versão exposta a visitante é divulgação de informação a não autenticado |
| A2 — alerta de alterações não salvas | 2 | 2 | 4 | padrão | liga comportamento de navegador em toda tela `create`/`edit` dos 3 painéis; falha = perda de digitação (retrabalho manual) |
| A3 — contrato das 3 propriedades de Settings | 2 | 2 | 4 | padrão | **reclassificada após a revisão adversarial** (era P1×I2, `mínimo`). O invariante existente guarda **nomes**; os três defeitos reais desta área são sobre **valor** e sobre **quem chama o alinhamento**, e o `mínimo` não os alcançava |
| A4 — avatar padrão de iniciais | 2 | 3 | 6 | padrão | o nome de cada pessoa ia para um domínio de terceiro a cada page load — dado pessoal de terceiro |
| A5 — trilha de auditoria da fronteira de acesso | 2 | 3 | 6 | padrão | compliance: é o registro de quem cortou o acesso de quem |
| A6 — constraint do Filament sem trava | 1 | 1 | 1 | mínimo | uma linha de `composer.json` |
| A7 — registro documental das dez decisões | 1 | 1 | 1 | mínimo | texto; falha = a próxima varredura reavalia do zero |

- **Técnicas aplicadas**: EP, BVA (só onde há faixa — praticamente ausente nesta feature),
  **tabela de decisão** (2×2 das duas chaves de versão), **tabela estado × operação** (conta de
  usuário × operações que escrevem fronteira), **rastreio de efeito** (linha em `audits` e log),
  **matriz painel × chave**, normalização/identidade (iniciais do nome).
- **Revisão adversarial**: obrigatória (Impacto 3 em A1, A4 e A5). Resultado em
  `## Revisão Adversarial`.
- Cenários: **45** CT + **2** CT-B · Regras: **13** · Mutantes previstos: **62** neste arquivo
  (`M1`…`M62`) + **2** no `05` (`MB1`, `MB2`) · Sem matador: **3** (`M6`, `M31`, `M45`) e
  **5 parcialmente vivos** (`M5`, `M19`, `M53`, e o par de premissa das perguntas nº 9 e nº 10),
  todos em `## Lacunas declaradas` com o que foi tentado.
- **`M11` foi vivo e está morto.** A reconciliação (step 7) o encontrou implementado em
  `config/kit.php`, medido, e deixou CT-09 **deliberadamente sem teste** em vez de escrevê-lo já
  verde contra o comportamento errado. O ciclo 2 do quality gate cobrou; CT-09 foi escrito, ficou
  **vermelho**, e só então o `config/kit.php` passou a usar `BooleanoDoEnv::comPadrao()`. Ver
  `## Reconciliação` › D2.
- **Duas rodadas de revisão adversarial**, por sub-agentes independentes: a primeira achou 5
  implementações erradas que passavam no conjunto inteiro; a segunda achou mais 3 e mostrou que
  **metade dos cenários que a primeira criou eram oráculos posicionais**. O teto da skill é 2
  rodadas, e o que sobrou está escalado em `## Fim do ciclo`.
- **10 cenários e 9 mutantes nasceram das revisões adversariais** (CT-36…CT-45, `M53`…`M61`), e os
  dois conjuntos **não se pareiam**: M55, M58, M60 e M61 caem em cenários antigos, e CT-38, CT-41,
  CT-42 e CT-44 matam mutantes antigos ou nenhum. Mutante trazido por revisão **não conta para o
  teto** por regra — é a exceção que a própria skill prevê: achado medido, não enchimento.
- **Teto de cenários por regra estourado em seis regras**, com o motivo, como a skill exige:

  | Regra | Cenários | Teto | Justificativa |
  |---|---|---|---|
  | R4 | 7 | 3 | a regra é, na verdade, três: origem do valor (CT-10, CT-12, CT-38), domínio do campo (CT-44), autorização (CT-11, CT-41) e a proibição de ler `.git` (CT-13). **Candidata a divisão** |
  | R9 | 6 | 3 | rastreio de efeito **consome o teto inteiro** por desenho da técnica (3 obrigatórios, 4 com atomicidade); aqui são dois efeitos (trilha e log) sobre uma matriz de 20 células |
  | R1, R5 | 4 cada | 3 | já nasceram com quatro, antes de qualquer revisão: em R1 o quarto é o cenário de escape (taxonomia obrigatória), em R5 é a partição de env booleana. O **gate vence o teto** |
  | R3 | 4 | 3 | o quarto (CT-43) veio da rodada 1, ao desfazer um corte com justificativa contrafactual |
  | R6 | 4 | 3 | saiu de 2 para 4 na rodada 1 (CT-36 e CT-39), junto com a reclassificação da área |
  | R7 | 4 | 3 | o quarto (CT-45) veio da rodada 2, e é o único cenário que liga o avatar renderizado à pessoa da linha |

- **Teto de mutantes por regra estourado**, registrado e não renumerado: R4 e R8 já declaravam 6
  contra o teto de 5 do perfil `padrão`, e a revisão acrescentou mais. Nos dois casos o diagnóstico
  da skill se confirma — **provavelmente são duas regras cada**: R4 mistura "de onde vem o valor"
  com "quem pode gravá-lo", e R8 mistura "quais são as iniciais" com "o documento gerado não
  quebra". A divisão fica como dívida de estrutura: renumerar 44 cenários e toda a rastreabilidade
  por um motivo cosmético custa mais do que a linha corrige.

### Divergência entre esta skill e as Project Rules do projeto

A skill sugere `pest --parallel --tia` como padrão de execução. **As rules do projeto vencem**:
`.ai/rules/testes-browser.md` mede que `--parallel` derruba 4 de 11 cenários de navegador e que,
sem PCOV, o `--tia` não termina (abortado após 35 min). Os comandos desta feature são, portanto:

```bash
vendor/bin/pest tests/Kit --compact      # os CT
composer test:kit                        # regressão obrigatória (a entrega toca infra compartilhada)
composer test:browser                    # os CT-B, em série, com build e view:cache embutidos
```

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | um `AvatarProvider` novo; uma Blade nova no render hook `FOOTER`; 3 propriedades novas de Settings com 2 migrations; 2 chaves novas de `config/kit.php` e 1 de `config/app.php`; 1 ponto de extensão na trait de auditoria; 3 `PanelProvider` alterados; 2 campos novos na tela de configurações | CT-01…CT-44 |
| **F**unction | exibir a versão do sistema; esconder a versão de quem não autenticou; exibir opcionalmente a versão do kit; governar o alerta de alterações não salvas pela tela; gerar avatar sem sair da aplicação; registrar na trilha a mudança de fronteira de acesso; registrar as dez decisões de pacote | CT-01…CT-44 |
| **D**ata | `versao_do_sistema` (texto livre: ausente, vazio, com espaços nas bordas, muito longo, com `<`, `>`, `&`, aspas); `exibir_versao_do_kit` e `alerta_alteracoes_nao_salvas` (booleanas, com as formas de env `"false"`, `"0"`, `""`); `config('kit.version')`; **nome do usuário** (vazio, um termo, dois, três ou mais, acentuado, unicode fora do BMP, com marcação HTML); `old_values`/`new_values` de `audits` | CT-01, CT-04, CT-09, CT-10, CT-17, CT-23…CT-25, CT-26…CT-29, CT-38, CT-39, CT-44, CT-45 |
| **I**nterfaces | HTTP nos 3 painéis; telas de autenticação (layout `simple`); a tela Livewire `/admin/configuracoes-da-aplicacao`; `.env` (`APP_VERSION`, `KIT_EXIBIR_VERSAO`, `KIT_ALERTA_ALTERACOES_NAO_SALVAS`); as Actions `desativar`/`reativar`/`aprovar` da listagem do `/admin`; **o model chamado direto**, sem tela; `php artisan kit:info`; `composer.json` | CT-03, CT-05, CT-10…CT-12, CT-14, CT-15, CT-26, CT-27, CT-32, CT-37, CT-41, CT-43 |
| **P**latform | Filament 5.7.6 — o hook `FOOTER` é emitido **também** pelo layout `simple`, o das telas de autenticação; Livewire 4; navegador (o alerta é `beforeunload` em JavaScript; o avatar é um `data:` URI que o navegador precisa decodificar); SQLite `:memory:`; **`phpunit.xml` força `APP_VERSION=""`, `KIT_EXIBIR_VERSAO=false` e `KIT_ALERTA_ALTERACOES_NAO_SALVAS=true`** — por isso nenhum cenário afirma "a configuração de fábrica" sem dizer de onde leu o valor | CT-05, CT-07, CT-09, CT-16, CT-17, CT-37, CT-39, CT-B01, CT-B02 |
| **O**perations | administrador editando as configurações; **visitante** nas telas de login/registro/recuperação; usuário autenticado comum em qualquer tela dos 3 painéis; auditor lendo `/infra/audits`; quem implanta preenchendo `APP_VERSION`; quem instala o kit rodando `composer update` | CT-05, CT-06, CT-11, CT-12, CT-27, CT-32, CT-41, CT-43 |
| **T**ime | **declarado e quase vazio**: nenhuma regra desta entrega depende de data, hora, fuso, expiração, agendamento ou concorrência. A única dimensão temporal real é **ordem de boot × request**: a config é alinhada uma vez no boot e o alerta é avaliado no render, o que é o que torna o toggle governável sem deploy | CT-15, CT-36 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o rodapé mostra a versão do **sistema** a quem está autenticado | A1 (padrão) | RQ-17 | tabela de decisão 2×2 + EP | CT-01…CT-04 |
| R2 — a versão nunca alcança quem não autenticou | A1 (padrão) | RQ-17 (consequência do layout `simple`) | EP por tela de autenticação + par positivo + destinatário do efeito | CT-05, CT-06, CT-37 |
| R3 — a versão do kit é opcional e nasce desligada | A1 (padrão) | RQ-18 | EP + partição das formas de env booleana | CT-07…CT-09, CT-43 |
| R4 — a versão do sistema vem da tela, semeada por `APP_VERSION`, sem ler `.git` | A1 (padrão) | RQ-17 (tabela de decisões do Adendo 2) + `## Fora de escopo do adendo` | EP do campo + matriz persona × gravação + varredura de ausência | CT-10…CT-13, CT-38, CT-41, CT-44 |
| R5 — o alerta de alterações não salvas é decidido por request, pelo Settings, nos três painéis | A2 (padrão) | RQ-03, RQ-04 (Ambiguidades resolvidas), RQ-13 | matriz painel × chave + rastreio do efeito por request | CT-14…CT-17 |
| R6 — cada propriedade nova cumpre o contrato de três lugares, é semeada com o valor certo e é alinhada pelo bootstrap | A3 (padrão) | RQ-13 | invariante por reflexão + partição do valor semeado | CT-18, CT-19, CT-36, CT-39 |
| R7 — o avatar padrão é gerado dentro da aplicação, sem requisição a terceiro, e é o da pessoa certa | A4 (padrão) | **RQ-19 (Adendo 3)** | EP + par de falsificabilidade + par discriminante de personas | CT-20…CT-22, CT-45 |
| R8 — as iniciais derivam do nome em toda partição de nome, sem quebrar o SVG | A4 (padrão) | **RQ-19 (Adendo 3)** | EP + normalização/identidade | CT-23…CT-25 |
| R9 — mudar a fronteira de acesso deixa linha na trilha, com o antes e o depois | A5 (padrão) | RQ-13 + `00 › Fora de Escopo` (item 4) | tabela estado × operação + rastreio de efeito | CT-26…CT-29, CT-40, CT-42 |
| R10 — a extensão da trilha soma, não substitui, e não vaza para quem não a declara | A5 (padrão) | RQ-13 | par de falsificabilidade | CT-30, CT-31 |
| R11 — o kit não trava a versão do Filament e não a abre para major novo | A6 (mínimo) | RQ-14 | EP da constraint | CT-32 |
| R12 — as dez decisões ficam registradas com o veredito certo, e nenhum dos dez pacotes entra | A7 (mínimo) | RQ-01, RQ-07, RQ-08 | EP por pacote + asserção de ausência com destinatário | CT-33, CT-34 |
| R13 — o que o kit afirma sobre o render hook do menu do usuário bate com o vendor instalado | A7 (mínimo) | RQ-01, RQ-13 | medição do vendor | CT-35 |

**Técnica escalada acima do perfil da área**: R1 usa **tabela de decisão** (prevista no perfil
completo) numa área `padrão` porque as duas chaves de versão se combinam e há uma combinação —
"sistema vazia **e** kit ligada" — que nenhuma partição isolada alcança. R9 usa **tabela
estado × operação** pelo mesmo motivo: a célula que interessa (operação repetida numa conta que já
está no estado de destino) não existe em nenhum eixo sozinho.

**`RQ` sem regra, e por quê:**

| `RQ` | Tratamento |
|---|---|
| RQ-01, RQ-07, RQ-08 | R12 (o produto verificável é o registro documental; a "análise" em si não é falsificável por teste) |
| RQ-02 | **substituída por RQ-17** (Adendo 2). Nenhum cenário a atende, de propósito |
| RQ-03, RQ-04 | R5 (a necessidade por trás, resolvida pelo recurso nativo); a granularidade por formulário está em `## Fora de Escopo` do `00` |
| RQ-05 | sem regra: `## Fora de Escopo (declarado)` — o kit não tem `routes/api.php`. O gatilho de reabertura é documental, não testável hoje |
| RQ-06 | sem regra: é restrição de **processo** de pesquisa (sub-agentes em paralelo), consumada antes da entrega e registrada em `03-progresso.md`. Não há artefato de produção que a falsifique |
| RQ-09 | sem regra: decidido em ADR-01; o artefato é a própria branch única |
| RQ-10, RQ-11, RQ-12 | sem regra própria: são restrições de processo do step 7/7.5. RQ-11 já tem guarda permanente no repositório (`tests/Kit/BlueprintForaDoPacoteTest.php`), e repeti-la aqui seria cobertura duplicada |
| RQ-13 | R5…R10, R13 (é a cláusula que compra os cinco itens nativos) |
| RQ-14 | R11 |
| RQ-15, RQ-16 | **lacuna declarada** — ver `## Perguntas` nº 6. "Se sair atualização, atualize" e "os projetos que o usarem também" não têm oráculo verificável dentro do repositório: o primeiro depende de um evento externo e futuro, e o segundo depende de um `composer update` rodado em outra árvore. O que é verificável é a **constraint** que os habilita, e isso é R11. Tentado: um caso que compare `composer.lock` com a última release do Packagist — descartado por exigir rede dentro da suíte e ficar vermelho por motivo alheio |
| RQ-17 | R1, R2, R4 |
| RQ-18 | R3 |

---

## Fronteira com o Plano

O que veio do `01`/`02` e foi **recusado como oráculo**, para que nenhum cenário vire teste do PRD:

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da classe `App\Support\AvatarDeIniciais` | escolha de implementação | detalhe do cenário (o `Então` fala do provider **padrão do painel**, não da classe) |
| `configuraVersaoNoRodape()` e o hook `FOOTER` sem `scopes:` | escolha de mecanismo | detalhe; o `Então` fala do **rodapé de toda tela dos três painéis** |
| `data:image/svg+xml;base64,...` como formato do avatar | escolha de implementação | detalhe — mas o **não sair para a rede** é requisito de privacidade e vira oráculo em CT-21/CT-B02 |
| fundo `gray[950]`, texto branco, `font-family` do sistema | escolha de aparência, que o requisito não determina | **pergunta** (nº 7) — nenhum cenário afirma cor |
| `->unsavedChangesAlerts(fn () => ...)` como Closure e não escalar | escolha de mecanismo | detalhe; o `Então` de CT-15 fala do **efeito por request**, que é o que a Closure compra |
| `auditaAlemDoFillable()` como nome do ponto de extensão | escolha de implementação | detalhe; CT-30/CT-31 afirmam sobre o **conteúdo da trilha** |
| rótulos "Avisar sobre alterações não salvas", "Exibir versão do kit" | comportamento visível que o requisito não determina | **pergunta** (nº 8); nenhum `Então` casa rótulo |
| `KIT_ALERTA_ALTERACOES_NAO_SALVAS=true` como default | **o valor é do plano, não do requisito** — mas "o alerta nasce ligado" é decisão que alguém precisa confirmar | **pergunta** (nº 4); CT-16 fica `@premissa` |
| "toggle da versão do kit desligado por padrão" | **é do requisito** — está na tabela de decisões do Adendo 2, assinada pelo solicitante | vira oráculo, em CT-07 |
| "versão do sistema vem de tela + `.env`, sem `.git`" | **é do requisito** — mesma tabela do Adendo 2 | vira oráculo, em CT-10…CT-13 |

**Valor do requisito escrito literalmente**: CT-07 e CT-16 leem o default **do arquivo de config**,
com a variável de ambiente removida (`kitConfigCom($chave, null)`), justamente porque o
`phpunit.xml` força as três chaves. Um cenário que apenas lesse `config('kit.exibir_versao')`
estaria medindo o `phpunit.xml`, e o default errado no arquivo sobreviveria sem nada ficar
vermelho.

---

## Perguntas para o `00-requisito.md` › `## Ambiguidades`

Bloco pronto para colagem. Cada uma bloqueia o que está indicado.

1. **O item "avatar padrão de iniciais" não tem cláusula própria.** Ele entra só por RQ-13 ("os 5
   nativos"), e a lista dos cinco vive no `01`/`02`, não no `00`. Confirmar como **Adendo 3** com
   uma cláusula própria, ou aceitar que R7/R8 fiquem permanentemente `@premissa`.
   **RESPONDIDA em 2026-09-18: vira cláusula.** Adendo 3, RQ-19. R7 e R8 deixam de ser `@premissa`.
   — *bloqueia R7, R8 (CT-20…CT-25).*
   **Premissa adotada** (falha fechado): nenhum dado de usuário sai da aplicação para terceiro na
   renderização de um avatar. **Se negado**: CT-21 inverte e passa a admitir o provider remoto.
2. **Versão do sistema vazia com a versão do kit ligada** — o rodapé mostra só a do kit, mostra
   nada, ou mostra um marcador de "sem versão"? O Adendo 2 decide as pontas (as duas preenchidas;
   as duas vazias) e não decide o meio.
   — *bloqueia a linha 3 de CT-01.*
   **Premissa adotada** (falha fechado, corrigida pela revisão adversarial — a direção anterior era
   a aberta): **o rodapé não renderiza nada**. A área recebeu Impacto 3 por divulgação de
   informação; anunciar a versão do kit quando o produto não declarou a dele é abrir por
   conveniência. **Invariante afirmado no mesmo cenário**, e ele vale nas duas leituras: nada no
   rodapé apresenta a versão do kit como se fosse a do produto. **Se negado**, a linha 3 inverte
   para "0.34.2, identificada como do kit" e o invariante fica como está.
3. **`versao_do_sistema` tem limite de tamanho ou formato?** O Adendo 2 diz "texto livre"
   (SemVer, data ou número de build), e texto livre sem limite entra no HTML de **toda** tela dos
   três painéis.
   — *bloqueia CT-44.*
   **Premissa adotada**: o requisito não decide, e **o cenário não escolhe por ele** — CT-44 tem
   `Então` disjuntivo (*ou grava inteiro, ou recusa*) e afirma o invariante que vale nas duas
   leituras: **nunca trunca em silêncio**. A revisão adversarial mostrou que a versão anterior
   escolhia a direção aberta ("grava 1024 caracteres") e, se negada, **inverteria** em vez de
   continuar valendo — que é a definição de premissa mal fixada. **Se negado** (houver limite),
   CT-44 fixa o ramo da recusa e o limite vira borda de BVA.
4. **O alerta de alterações não salvas nasce ligado ou desligado?** RQ-03/RQ-04 pedem
   "ativar/desativar conforme necessidade" e não dizem o estado inicial. O `01` escolheu ligado.
   — *bloqueia CT-16.*
   **Premissa adotada**: **ligado**. Aqui falha fechado aponta para ligado, não para desligado: o
   modo de falha do alerta é incômodo (um clique para desligar), e o modo de falha da ausência é
   perda de dado digitado, que é irreversível. **Invariante afirmado no mesmo cenário** (última
   linha de CT-16, acrescentada pela revisão): qualquer que seja o default, a chave governa. **Se
   negado**, só o valor de fábrica inverte.
5. **A lista de colunas de fronteira de acesso auditadas está fechada em `ativo` e
   `aprovacao_pendente`?** `deleted_at` (exclusão lógica) é fronteira de acesso pelo mesmo critério
   e não está na lista.
   — *não bloqueia mais nenhuma célula.* A revisão adversarial mostrou que usar esta premissa para
   marcar quatro células como "não se aplica" era converter escolha de implementação em cobertura:
   ela é premissa de **mecanismo** (como a exclusão aparece na trilha), não de escopo.
   **Premissa adotada**: a lista das colunas **declaradas** fica fechada nas duas, e a coluna
   `excluir` da matriz é exercitada assim mesmo, pelo invariante que vale nas duas leituras e que
   CT-42 afirma — **a exclusão lógica de uma conta deixa registro na trilha**. Para uma regra de
   compliance, falha fechado é auditar mais. **Se negado** (a exclusão também dever entrar pela
   lista declarada), CT-42 ganha a asserção da coluna e as células viram `✅F`.
6. **RQ-15 e RQ-16 têm oráculo verificável?** "Se sair atualização, atualize no starter-kit e os
   projetos que o usarem" descreve um compromisso operacional contínuo, não um estado do
   repositório. A constraint que o habilita é testável (R11); o cumprimento futuro não é.
   — *bloqueia nada hoje; declara a lacuna.*
   **Premissa adotada**: a constraint em caret é o oráculo aceito, e a atualização em si é
   processo. **Se negado**, é preciso decidir qual artefato (data do `composer.lock`? um comando de
   verificação?) passa a ser medido.
   **Correção da revisão adversarial**: o `00` assume **dois** mecanismos de propagação — a
   constraint em caret **e** `php artisan kit:update` —, e a lacuna L3 só justificava o descarte do
   primeiro (rede/Packagist). O segundo existe no repositório e é testável. A pergunta que falta é
   **qual contrato do `kit:update`** RQ-16 compra: ele deve propagar a constraint do Filament para
   o `composer.json` do projeto? apenas avisar? nada? Sem isso, escrever o cenário seria inventar o
   requisito. *bloqueia R11 pela metade.*
7. **A aparência do avatar é requisito?** Fundo, cor de texto e contraste vêm do `02` (cinza fixo,
   texto branco) e o `00` não diz nada.
   — *bloqueia qualquer cenário de cor/contraste, que por isso não foi escrito.*
   **Premissa adotada**: aparência não é requisito; só privacidade e legibilidade das iniciais são.
8. **Os rótulos dos dois campos novos na tela são requisito?** O `00` não os determina.
   — *bloqueia nada;* nenhum `Então` casa rótulo, de propósito.
9. **A versão do kit precisa aparecer no rodapé com um rótulo que a distinga da versão do
   sistema?** A pergunta nasce de uma contradição que a rodada 2 encontrou: o invariante da
   premissa nº 2 — *nada no rodapé apresenta a versão do kit como se fosse a do produto* — só é
   verificável casando **rótulo**, e a pergunta nº 8 declara que nenhum `Então` casa rótulo. Ou o
   rótulo vira requisito (Adendo 3) e o invariante ganha forma operacional, ou o invariante
   continua sendo prosa e a premissa nº 2 volta a ser lacuna cega. **Não há terceira saída**, e é
   por isso que isto é pergunta e não decisão do derivador.
   — *bloqueia o invariante de CT-01.*
   **RESPONDIDA em 2026-09-18: o rótulo vira requisito.** Adendo 3, RQ-20; regra R14, CT-46 e CT-47.
10. **A tela de configurações tem uma barreira só, para abrir e para gravar?** Se tiver, a variante
    de defeito *"a autorização vive só no `mount`"* é **inexpressável** — CT-11 e CT-41 entram pela
    mesma porta e recusam juntos. Se houver (ou dever haver) permissão separada de leitura e de
    escrita, CT-41 ganha a persona que monta e não grava, e a linha do checklist deixa de ser
    cobertura parcial.
    — *bloqueia o fechamento de L7.*

---

## Setup Global

### Personas

- `usuarioDoKit('admin')` — quem tem a permissão de configuração do kit; é quem grava as três
  propriedades novas.
- `usuarioDoKit('master_global')` — administrador geral, que passa sem permissão atribuída.
- `usuarioDoKit('panel_user')` — usuário comum autenticado: **vê** o rodapé e **não** grava
  configuração.
- **visitante** — nenhuma chamada a `actingAs()`; é a persona de R2, e a única que o guard do
  rodapé separa.
- `usuario('alvo@example.com')` + um segundo usuário — o **par** de R9: quem executa a desativação
  nunca é quem a sofre (`User::desativar()` recusa a própria conta).

### Fixtures

- `usuario()` / `usuarioDoKit()` / `usuarioComPapel()` de `tests/Pest.php`.
- Conta em estado de fronteira: `forceFill(['aprovacao_pendente' => true])->save()` — é como o
  repositório já arranja esse estado (`tests/Kit/AbasDeListagemTest.php:38`), porque a coluna não é
  `$fillable` de propósito.
- Usuário **com** foto de perfil (para o par de CT-22) e usuário **sem** foto (o caso que cai no
  provider padrão).

### Fakes

- `espiarAutenticacao()` — espia o channel `autenticacao` sem silenciar os outros; é o que sustenta
  as asserções de **não-log** das células `❌` de R9.
- Nenhum `Mail::fake()`, `Queue::fake()` ou `Http::fake()`: a entrega não envia e-mail, não
  enfileira e não chama HTTP. **É justamente o ponto de A4** — a ausência de saída para a rede é
  afirmada por CT-21 (HTML) e CT-B02 (rede observada no navegador), não por um fake.

### Configuração

- `gravarConfiguracao($propriedade, $valor)` + `alinharConfiguracoesDoKit()` — grava no banco e
  alinha a config como o boot alinharia. Com `RefreshDatabase` o alinhamento do boot é no-op, então
  **todo cenário que depende de valor gravado chama o alinhamento explicitamente**.
- `kitConfigCom($chave, null)` — relê `config/kit.php` com a variável de ambiente **ausente**. É o
  único jeito de afirmar o default do arquivo, porque o `phpunit.xml` força as três chaves.

### Estratégia de DB

`RefreshDatabase` global, ligado em `tests/Pest.php` para `Kit`, `Tenancy`, `Browser` e
`BrowserTenancy`.

### Camada disponível neste projeto

`tests/Pest.php` **não liga** `TestCase` a `tests/Unit` — a suíte `Unit` do `phpunit.xml` existe e
roda sem container. Portanto a camada mais barata deste projeto é `tests/Kit` (grupo `kit`,
`TestCase` da aplicação, `RefreshDatabase`), e é onde ficam inclusive os cenários que seriam
"unitários" em outro projeto (CT-23…CT-25 dependem do container para resolver a paleta e o nome
do avatar).

---

## Regra R1 — o rodapé mostra a versão do sistema a quem está autenticado

> `RQ-17` · área A1, perfil **padrão** · técnica: **tabela de decisão 2×2** + EP

```gherkin
# language: pt
Funcionalidade: Versão no rodapé dos painéis

  Regra: o rodapé mostra a versão do sistema a quem está autenticado

    Esquema do Cenário: [CT-01] as duas chaves de versão decidem juntas o que o rodapé mostra
      Dado que a versão do sistema gravada é "<sistema>"
      E que a exibição da versão do kit está "<kit>"
      E que a versão do kit foi fixada pelo caso em "0.34.2"
      Quando a administradora autenticada abre o painel /admin
      Então o rodapé da página mostra "<mostra>"
      E, qualquer que seja a combinação, nada no rodapé apresenta a versão do kit
        como se fosse a do produto

      Exemplos:
        | sistema | kit       | mostra                        | # combinação                  |
        | 2.4.0   | ligada    | 2.4.0 e 0.34.2                | as duas                       |
        | 2.4.0   | desligada | 2.4.0, e nada de 0.34.2       | só a do sistema               |
        |         | ligada    | 0.34.2, identificada como do kit | **virou requisito**: Adendo 3, RQ-20, e o invariante passou a ser asserível — CT-47 |
        |         | desligada | nada — o rodapé não renderiza | nenhuma                       |

    Cenário: [CT-02] a versão do sistema é a do produto, não a do kit
      Dado que a versão do sistema gravada é "2.4.0"
      E que a versão do kit foi fixada pelo caso em "0.34.2"
      E que a exibição da versão do kit está desligada
      Quando a administradora autenticada abre o painel /admin
      Então o rodapé da página mostra "2.4.0"
      E a página inteira não contém "0.34.2"

    Esquema do Cenário: [CT-03] a versão acompanha os três painéis
      Dado que a versão do sistema gravada é "2.4.0"
      E um usuário autenticado com o papel "<papel>"
      Quando ele abre o painel "<painel>"
      Então o rodapé da página mostra "2.4.0"

      Exemplos:
        | papel        | painel |
        | admin        | /admin |
        | infra        | /infra |
        | panel_user   | /app   |

    Cenário: [CT-04] a versão gravada com marcação HTML chega escapada ao rodapé
      Dado que a versão do sistema gravada é "<script>alert(1)</script>"
      Quando a administradora autenticada abre o painel /admin
      Então o rodapé da página não contém a tag "<script>" vinda da versão
      E o texto visível do rodapé é exatamente "<script>alert(1)</script>"
```

**"O rodapé da página", operacionalmente** — e isto é oráculo, não detalhe. `assertSee` sobre a
página inteira é a assertion proibida desta regra: ela fica verde com a versão emitida na barra do
topo, no menu lateral ou no corpo da tela, e a regra é sobre o **rodapé**. O recorte
implementation-neutral vem do próprio vendor: nos dois layouts o hook `FOOTER` é emitido **depois**
do fechamento do `</main>` (`layout/index.blade.php:126`, `layout/simple.blade.php:61`), então o
`Então` assere sobre o trecho do documento **posterior ao último `</main>`**. O kit não tem
`data-testid`, e esta é a mesma dívida de seletor que `.ai/rules/testes-browser.md` já registra —
declarada aqui para que ninguém a resolva casando texto na página inteira.

**Por que a linha 3 mostra "nada"**: é premissa de comportamento (pergunta nº 2), e a direção é
**falha fechado**. A área A1 recebeu Impacto 3 porque versão exposta é divulgação de informação;
assumir que o rodapé passa a anunciar a versão do kit quando o produto não declarou a dele é a
direção **aberta**, e contradiz a própria razão do perfil. O invariante afirmado junto — nada no
rodapé apresenta a versão do kit como se fosse a do produto — vale nas duas leituras, e é ele que
impede a premissa de virar lacuna cega. **Se negado**, a linha 3 inverte para "0.34.2, identificada
como sendo do kit", e o invariante continua exatamente como está.

**Por que estes valores discriminam**: `2.4.0` e `0.34.2` são **diferentes** e ambos não vazios —
um rodapé que lesse a chave errada ficaria verde com qualquer valor único. CT-04 usa a carga que
distingue "escapa" de "não escapa"; um valor como `v1.0` não distingue nada.

**A vacuidade das linhas 3 e 4 é limitada, não ignorada**: "o rodapé não renderiza" é asserção de
ausência, e ela só discrimina porque as linhas 1 e 2 do **mesmo** `Esquema`, com a mesma fixture,
mostram o rodapé renderizando. CT-37 fecha o resto do buraco, provando que o ponto de extensão
existe e é alcançado.

**Camada**: `Feature`/HTTP em `tests/Kit` — `$this->actingAs(...)->get('/admin')`. O rodapé é HTML
renderizado no servidor; nada aqui exige navegador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o rodapé lê `config('kit.version')` (a do kit) achando que é a do produto — **é o erro que o Adendo 2 corrigiu** | CT-02 |
| M2 | o hook é registrado com `scopes:` de um painel só, e os outros dois ficam sem rodapé | CT-03 (linhas `/infra` e `/app`) |
| M3 | o valor é impresso com `{!! !!}` ou concatenado no HTML sem escapar | CT-04 |
| M4 | o rodapé renderiza uma casca vazia (traço, `v`, espaço) quando as duas chaves estão vazias | CT-01 (linha 4) |
| M5 | a versão é emitida em outro ponto de extensão do layout (topbar, fim do conteúdo, fim da página), e não no rodapé — **o mutante que um `assertSee` da página inteira deixaria passar, junto com todo o guard de R2** | CT-01…CT-04 (o recorte posterior ao `</main>`) + CT-37 |

---

## Regra R2 — a versão nunca alcança quem não autenticou

> `RQ-17`, pela consequência registrada no `02` (ADR-04): o hook `FOOTER` é emitido **também** pelo
> layout `simple`, o das telas de autenticação · área A1, perfil **padrão** · técnica: **EP por
> tela de autenticação**, com par positivo obrigatório

```gherkin
# language: pt
Funcionalidade: Versão no rodapé dos painéis

  Regra: a versão do sistema não alcança quem não está autenticado

    Esquema do Cenário: [CT-05] nenhuma tela de autenticação expõe a versão
      Dado que a versão do sistema gravada é "2.4.0"
      E que a exibição da versão do kit está ligada
      E um visitante sem sessão
      Quando ele abre "<tela>"
      Então a página responde com sucesso
      E a página não contém "2.4.0"
      E a página não contém "0.34.2"

      Exemplos:
        | tela                          | # partição                          |
        | /admin/login                  | login do painel administrativo      |
        | /infra/login                  | login do painel de infra            |
        | /app/login                    | login do painel de negócio          |
        | /login                        | página única de login (rota própria) |
        | /admin/password-reset/request | recuperação de senha                |

    Cenário: [CT-06] a mesma versão que o visitante não vê é a que o autenticado vê
      Dado que a versão do sistema gravada é "2.4.0"
      E que o visitante sem sessão não encontrou "2.4.0" em /admin/login
      Quando a administradora autenticada abre /admin
      Então o rodapé da página mostra "2.4.0"

    Cenário: [CT-37] o ponto de extensão do rodapé é renderizado também na tela de login
      Dado um marcador neutro registrado no mesmo ponto de extensão que o rodapé usa
      E um visitante sem sessão
      Quando ele abre /admin/login
      Então o marcador aparece na página
```

**CT-37 é o que dá destinatário a CT-05, e nasceu da revisão adversarial.** Sem ele, a ausência da
versão na tela de login é verdadeira por dois motivos indistinguíveis: *o guard escondeu* ou *o
rodapé nunca chega àquele layout*. No segundo caso, M7 — "não existe guard nenhum" — atravessa o
conjunto inteiro, e o cenário que mais importa em toda a área A1 vira decoração. O marcador é
registrado pelo próprio teste no ponto de extensão do rodapé e provado na tela de autenticação; é
o `Http::fake()` desta regra — um mundo em que o efeito **poderia** acontecer.

O fato que ele trava está medido no vendor instalado:
`vendor/filament/filament/resources/views/components/layout/simple.blade.php:61` emite o mesmo
`FOOTER` que `layout/index.blade.php:126`. Se um upgrade do Filament deixar de emitir, CT-37 fica
vermelho e alguém descobre **antes** de o guard virar código morto.

**As cinco partições foram conferidas contra as rotas reais** (`php artisan route:list`), e não
escritas de memória. A lista fixa é uma escolha consciente: ela é legível e falha com o nome da
tela. Se o kit ganhar tela de autenticação nova, a partição não a alcança — a alternativa, derivar
as telas do inventário que `tests/Kit/InventarioDeTelasTest.php` mantém, fica registrada como a
evolução natural deste cenário quando isso acontecer.

**A asserção de ausência tem destinatário**: o `Dado` de CT-05 **grava** a versão. Sem isso o
cenário seria vácuo — o `phpunit.xml` força `APP_VERSION=""`, então "a tela de login não contém a
versão" passaria com o guard removido, com o rodapé removido e com a feature inteira revertida.
CT-06 fecha o par provando, no mesmo arranjo, que o valor **apareceria** se o guard não existisse.

**Camada**: `Feature`/HTTP em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | o guard usa `@auth` (guard **default**) em vez do guard do painel — funciona no kit e falha no projeto que declarar `->authGuard()` | ⚠️ **sem matador direto** — lacuna declarada. Tentado: registrar um painel de teste com `painelRegistradoEmTeste()` e um guard próprio, para que o default e o do painel divirjam; o arranjo exige um provider de usuário adicional no `config/auth.php` do processo de teste e o cenário passou a medir o arranjo, não o guard. **O que sobra**: CT-05 mata a ausência total de guard, não a escolha do guard errado. Registrado como dívida em `## Lacunas declaradas` |
| M7 | não existe guard nenhum: o rodapé sai em toda tela, inclusive no login | CT-05 **com** CT-37 — sozinho, CT-05 não o mata |
| M8 | o guard esconde de todo mundo (condição invertida) | CT-06 |
| M9 | o guard cobre só o `/admin` e as telas de login dos outros painéis continuam expondo | CT-05 (linhas `/infra/login`, `/app/login` e `/login`) |
| M53 | o rodapé é emitido num ponto de extensão que o layout das telas de autenticação não renderiza — o guard nunca é exercido, e ninguém percebe que ele podia estar ausente | CT-37 |

---

## Regra R3 — a versão do kit é opcional e nasce desligada

> `RQ-18` e a tabela de decisões do Adendo 2 ("Toggle, desligado por padrão") · área A1, perfil
> **padrão** · técnica: EP + partição das formas de env booleana

```gherkin
# language: pt
Funcionalidade: Versão no rodapé dos painéis

  Regra: a versão do kit só aparece se quem usa o kit pedir

    Cenário: [CT-07] o arquivo de configuração nasce com a exibição da versão do kit desligada
      Dado o arquivo config/kit.php lido com a variável de ambiente da chave ausente
      Quando o valor de fábrica da chave de exibição da versão do kit é consultado
      Então ele é o booleano falso

    Cenário: [CT-08] ligada pela tela, a versão do kit passa a acompanhar a do sistema
      Dado que a versão do sistema gravada é "2.4.0"
      E que a versão do kit foi fixada pelo caso em "0.34.2"
      E que a exibição da versão do kit está desligada, e o rodapé não contém "0.34.2"
      Quando a administradora liga a exibição da versão do kit pela tela de configurações
      Então o rodapé do painel /admin passa a mostrar "2.4.0" e "0.34.2"

    Cenário: [CT-43] a versão do kit continua alcançável pela linha de comando com o toggle desligado
      Dado que a exibição da versão do kit está desligada
      E que a versão do kit foi fixada pelo caso em "0.34.2"
      Quando a pessoa que mantém a instalação executa o comando de informações do kit
      Então a saída do comando mostra "0.34.2"

    Esquema do Cenário: [CT-09] o vocabulário do .env é respeitado na exibição da versão do kit
      Dado o arquivo config/kit.php lido com a variável de ambiente valendo "<env>"
      Quando o valor da chave de exibição da versão do kit é consultado
      Então ele é exatamente <esperado>, e é um booleano

      Exemplos:
        | env   | esperado | # partição                                        |
        | off   | false    | **discrimina**: `(bool) 'off'` é true             |
        | no    | false    | **discrimina**: `(bool) 'no'` é true              |
        | ligar | false    | **discrimina**: ilegível cai no default, que é false; `(bool) 'ligar'` é true |
        | false | false    | vocabulário óbvio                                 |
        | true  | true     | positivo — sem ele o cenário passaria com a chave fixa em false |
```

**Por que estas partições e não `"false"` sozinha**: o `env()` do Laravel **já** converte a string
`"false"`, então `(bool) env(...)` acerta esse caso e uma tabela feita só de `false`/`0`/`true` não
distingue implementação nenhuma. O que distingue é o vocabulário que o kit promete no
`.env.example` e o PHP não conhece — `off`, `no` e o valor ilegível, os três em que `(bool)` devolve
**true** e o contrato do kit devolve o valor certo (`tests/Kit/BooleanoDoEnvTest.php` trava esse
vocabulário para o helper; este cenário prova que a chave **nova** passa por ele).

**Camada**: `Feature` em `tests/Kit`, com `kitConfigCom()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | o default no arquivo é `true` — o kit passa a anunciar a si mesmo em toda instalação | CT-07 |
| M54 | o comando de informações do kit deixa de mostrar a versão do kit — e, com o toggle nascendo desligado, **não sobra nenhum caminho** para descobri-la. O Adendo 2 nomeia esse comando como a saída ("quem quiser vê-la usa `php artisan kit:info` ou liga o toggle"), o que torna a cláusula requisito, não detalhe do plano | CT-43 |
| M11 | a chave é lida com `(bool) env(...)` em vez do contrato de booleano do kit: `off`, `no` e qualquer texto ilegível **ligam** a exibição da versão do kit em toda instalação que os usar | CT-09 (linhas `off`, `no`, `ligar`) |
| M12 | o toggle grava e não governa (falta a linha do mapa de configuração) | CT-08 |
| M13 | ligar a versão do kit **substitui** a do sistema em vez de acompanhá-la | CT-08 (o `Então` exige as duas) |

---

## Regra R4 — a versão do sistema vem da tela, semeada por `APP_VERSION`, sem ler `.git`

> `RQ-17`, tabela de decisões do Adendo 2 e `## Fora de escopo do adendo` · área A1, perfil
> **padrão** · técnica: EP do campo + matriz persona × gravação + varredura de ausência

```gherkin
# language: pt
Funcionalidade: Versão no rodapé dos painéis

  Regra: a versão do sistema é editável na tela e semeada pelo ambiente

    Esquema do Cenário: [CT-10] o valor gravado na tela é o valor exibido, sem alteração silenciosa
      Dado a administradora na tela de configurações da aplicação
      Quando ela grava a versão do sistema como "<digitado>"
      Então a configuração de versão da aplicação vale "<gravado>"
      E o rodapé do painel /admin mostra "<exibido>"

      Exemplos:
        | digitado              | gravado               | exibido               | # partição       |
        | 2.4.0                 | 2.4.0                 | 2.4.0                 | SemVer           |
        | 2026-09-18            | 2026-09-18            | 2026-09-18            | data             |
        | 1                     | 1                     | 1                     | um caractere     |
        | ` 2.4.0 `             | ` 2.4.0 `             | ` 2.4.0 `             | espaços nas bordas — corrigido; ver `## Reconciliação` › D3 |
        | (vazio)               | (vazio)               | (nada — o rodapé some) | limpeza do campo |

    Cenário: [CT-38] limpar o campo apaga o rodapé mesmo com a versão do ambiente preenchida
      Dado que a variável de ambiente APP_VERSION vale "9.9.9"
      E que a versão do sistema gravada pela tela é "2.4.0"
      Quando a administradora limpa o campo de versão do sistema e salva
      Então o rodapé do painel /admin não mostra "2.4.0"
      E o rodapé do painel /admin não mostra "9.9.9"

    Cenário: [CT-44] valor longo é gravado inteiro ou recusado, nunca truncado em silêncio
      Dado que a versão do sistema gravada é "1.0.0"
      E a administradora na tela de configurações da aplicação
      Quando ela grava a versão do sistema com 1024 caracteres
      Então ou a gravação é recusada com erro de validação e a versão gravada continua "1.0.0",
        ou o valor gravado tem os mesmos 1024 caracteres
      E o valor gravado nunca é um prefixo do que foi digitado

    Cenário: [CT-11] quem não tem a permissão de configuração não abre a tela de configurações
      Dado um administrador que alcança o painel /admin e perdeu a permissão de configuração
      E que a versão do sistema gravada é "2.4.0"
      Quando ele tenta abrir a tela de configurações da aplicação
      Então o acesso é recusado com 403
      E ele continua com acesso às demais telas do painel dele

    Cenário: [CT-41] quem não tem a permissão não grava a versão do sistema nem chamando o salvamento
      Dado um administrador que alcança o painel /admin e perdeu a permissão de configuração
      E que a versão do sistema gravada é "2.4.0"
      Quando ele chama o salvamento da tela de configurações com a versão "9.9.9"
      Então a operação é recusada
      E a versão do sistema gravada continua "2.4.0"

    Cenário: [CT-12] sem linha no banco, a versão do ambiente continua valendo
      Dado que a variável de ambiente APP_VERSION vale "9.9.9"
      E que a propriedade de versão do sistema não tem linha na tabela de configurações
      Quando o alinhamento da configuração roda como o boot o roda
      Então a configuração de versão da aplicação continua valendo "9.9.9"
      E o rodapé do painel /admin mostra "9.9.9"

    Cenário: [CT-13] nada na aplicação lê o repositório git para descobrir a versão
      Dado o código de produção de app/, config/ e resources/views/, sem os comentários
      Quando ele é varrido por leitura de ".git", "packed-refs" e "git describe",
        e por execução de processo externo
      Então nenhuma ocorrência existe fora da lista de exceções declarada no próprio caso
```

**CT-41 separa "não abre" de "não grava", e a separação é obrigatória**: a autorização que vive só
no `mount` da página (ou na visibilidade do campo) deixa o `403` de CT-11 verde e aceita a gravação
de qualquer persona. É o gate de camada da regra — CT-11 mede a tela, CT-41 mede a **ação**.

**CT-38 é o cenário que o `phpunit.xml` esconderia**: a suíte força `APP_VERSION=""`, então a linha
"(vazio)" de CT-10 fica verde com um `?:` que devolva a variável de ambiente — não há o que
devolver. Fixando `APP_VERSION="9.9.9"` no `Dado`, o fallback silencioso aparece.

**CT-44 é `@premissa` (pergunta nº 3), e o `Então` é deliberadamente disjuntivo.** O requisito não
decide se existe limite; o invariante que vale nas duas leituras é *ou grava inteiro, ou recusa —
nunca trunca em silêncio*, porque truncar é o único dos três resultados que mente para quem
digitou. **Se negado** (houver limite declarado), CT-44 deixa de ser disjuntivo e fixa o ramo da
recusa, com o valor limite virando borda de BVA.

**CT-13 filtra comentário antes de afirmar ausência** — `.ai/rules/testes.md` documenta três casos
em que a própria documentação do kit reprovou a asserção de ausência, porque os arquivos bem
comentados **citam** o que proíbem. Aqui isso é quase certo: a decisão de não ler `.git` está
escrita no código.

**Camada**: CT-10 e CT-11 são componente Livewire (`fillForm` → `->call('save')`), seguidos de um
`get()` para o rodapé — é o **gate de tela de escrita**, e a tela é a mesma que
`tests/Kit/ConfiguracoesDoKitTelaTest.php` já cobre. CT-12 e CT-13 são `Feature`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | a propriedade não tem linha no mapa de configuração: o campo grava e o rodapé não muda | CT-10 (linha "2.4.0") |
| M15 | o alinhamento sobrepõe a config com `null` quando a propriedade não existe, apagando o `APP_VERSION` | CT-12 |
| M16 | o campo trunca em 50/100/255 caracteres sem avisar | CT-44 |
| M17 | limpar o campo não limpa o rodapé, porque um `?: env('APP_VERSION')` devolve a versão do ambiente por trás | CT-38 |
| M18 | um resolvedor lê `.git` e vence a tela — duas fontes de verdade divergentes | CT-13 |
| M19 | a autorização vive só no `mount` da página ou na visibilidade do campo, e o salvamento aceita de qualquer persona | CT-41 |
| M55 | a versão gravada chega à config e o rodapé continua lendo outra fonte — o alinhamento acontece e ninguém o consome | CT-12 (que fecha em rodapé, não em `config()`) |

---

## Regra R5 — o alerta de alterações não salvas é decidido por request, pelo Settings, nos três painéis

> `RQ-03`, `RQ-04` (via `## Ambiguidades` do `00`: *"a necessidade por trás é atendida pelo
> `unsavedChangesAlerts()` nativo, que é `bool|Closure` e por isso já é governável pelo Settings
> globalmente"*) e `RQ-13` · área A2, perfil **padrão** · técnica: **matriz painel × chave** +
> rastreio de efeito por request

```gherkin
# language: pt
Funcionalidade: Alerta de alterações não salvas

  Regra: o alerta é decidido a cada request pela configuração, nos três painéis

    Esquema do Cenário: [CT-14] cada painel obedece ao que foi gravado nas configurações
      Dado que a administradora gravou o alerta de alterações não salvas como <valor>
        na tela de configurações
      Quando o painel "<painel>" é consultado sobre o alerta
      Então ele responde <valor>

      Exemplos:
        | painel | valor |
        | admin  | true  |
        | admin  | false |
        | infra  | true  |
        | infra  | false |
        | app    | true  |
        | app    | false |

    Cenário: [CT-15] desligar pela tela faz efeito no request seguinte, sem reinício do processo
      Dado que o alerta está ligado e o painel /admin responde que o alerta está ligado
      Quando a administradora desliga o alerta pela tela de configurações
      Então o painel /admin, no request seguinte, responde que o alerta está desligado

    Cenário: [CT-16] o arquivo de configuração nasce com o alerta ligado
      Dado o arquivo config/kit.php lido com a variável de ambiente da chave ausente
      Quando o valor de fábrica da chave de alerta é consultado
      Então ele é o booleano verdadeiro

    Esquema do Cenário: [CT-17] chave vazia no .env cai no default, e "desligado" continua desligando
      Dado o arquivo config/kit.php lido com a variável de ambiente valendo "<env>"
      Quando o valor da chave de alerta é consultado
      Então ele é exatamente <esperado>, e é um booleano

      Exemplos:
        | env   | esperado | # partição                                                      |
        |       | true     | **discrimina**: chave presente e VAZIA. O segundo argumento do `env()` só cobre chave ausente, então `(bool) env('X', true)` devolve false e o default nunca entra |
        | off   | false    | **discrimina**: `(bool) 'off'` é true                            |
        | false | false    | "configurou como desligado" precisa vencer o default true        |
        | 0     | false    | idem                                                             |
        | true  | true     | positivo                                                         |
```

**A linha vazia é a que importa nesta chave**, e ela não existe em CT-09: numa chave cujo default é
`false`, vazio e defeito produzem o mesmo resultado, e a partição não discrimina nada. Numa chave
cujo default é `true`, vazio é exatamente onde o defeito medido do projeto aparece —
`.ai/rules/config.md` o documenta para inteiros e `tests/Kit/BooleanoDoEnvTest.php` registra que a
"correção óbvia" para booleanos **reproduziu** o defeito e nasceu commitada, com três chaves de
tabela desligadas em silêncio.

CT-16 é `@premissa` (pergunta nº 4): o estado inicial do alerta é decisão do plano, não do
requisito. A direção adotada segue falha fechado no sentido do dado do usuário — a ausência do
alerta perde digitação e é irreversível, o excesso de alerta custa um clique.

**O invariante que vale nas duas leituras — *qualquer que seja o default, a chave governa* — vive
em CT-14, não dentro de CT-16**, e isso é desvio declarado da skill. A rodada 1 o escreveu como uma
linha extra no `Então` de CT-16, e a rodada 2 mostrou que aquilo era uma **ação** disfarçada de
asserção — exatamente o defeito de multi-`Quando` que a mesma rodada 1 corrigira em quatro
cenários. Duplicar CT-14 dentro de CT-16 seria pagar com redundância; a alternativa honesta é
apontar onde o invariante mora. **Se negado**, só o valor de fábrica de CT-16 inverte; CT-14 e
CT-15 ficam como estão.

**CT-14 grava pela tela, e não por `config()->set()`.** Esta é a correção mais importante de R5: um
`Dado` que setasse a config e um `Então` que lesse a mesma config formam tautologia — provariam que
`config()` devolve o que `config()` guardou, com o Settings inteiro fora do caminho. Gravando pela
tela, o cenário atravessa propriedade → mapa → alinhamento → painel, que é a cadeia que o requisito
compra.

**CT-15 não chama o alinhador como passo do `Quando`.** A versão anterior deste cenário fazia isso,
e era oráculo autorreferente: o mecanismo sob teste (o alinhamento acontecer sozinho) virava passo
de arranjo, e a implementação que **nunca registra o alinhador no bootstrap** passava no conjunto
inteiro. O `Então` agora fala do **request seguinte**; quem prova que existe um chamador em
produção é CT-36.

**Camada**: `Feature` em `tests/Kit` (CT-14, CT-16, CT-17) e componente Livewire + `Feature` em
CT-15. O comportamento do navegador é CT-B01, no `05`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | `->unsavedChangesAlerts(config('kit.alerta_alteracoes_nao_salvas'))` — escalar avaliado no boot; o toggle grava e só faz efeito no próximo deploy | CT-15 |
| M21 | o alerta é ligado só no `/admin`, e as telas de escrita do `/app` e do `/infra` ficam sem | CT-14 (linhas `infra` e `app`) |
| M22 | `->unsavedChangesAlerts()` sem argumento, fixo em `true`: a chave não governa nada | CT-14 (linhas `false`) |
| M23 | a chave é lida com `(bool) env('KIT_ALERTA_ALTERACOES_NAO_SALVAS', true)`: quem deixar a linha do `.env` presente e vazia perde o alerta em silêncio, com o `.env.example` prometendo o contrário | CT-17 (linha vazia) |
| M24 | o alerta chega ao painel mas não ao navegador (o script não é emitido) | CT-B01 |

---

## Regra R6 — cada propriedade nova cumpre o contrato de três lugares e nenhuma é segredo

> `RQ-13` · área A3, perfil **padrão** (reclassificada pela rodada 1) · técnica: invariante por
> reflexão + partição do valor semeado + varredura de registro

```gherkin
# language: pt
Funcionalidade: Configurações do kit

  Regra: propriedade declarada é propriedade mapeada e semeada

    Esquema do Cenário: [CT-18] cada propriedade nova existe nos três lugares e alcança a chave dela
      Dado a classe de configurações do kit e as migrations de settings aplicadas
      E que a propriedade "<propriedade>" foi gravada com <valor>
      Quando a configuração é alinhada
      Então a propriedade tem linha semeada no banco
      E a chave "<chave>" vale <valor>

      Exemplos:
        | propriedade                   | valor    | chave                              |
        | versao_do_sistema             | "2.4.0"  | app.version                        |
        | alerta_alteracoes_nao_salvas  | false    | kit.alerta_alteracoes_nao_salvas   |
        | exibir_versao_do_kit          | true     | kit.exibir_versao                  |

    Cenário: [CT-39] a migration semeia cada propriedade com o valor de fábrica que o kit promete
      Dado que a variável de ambiente APP_VERSION vale "9.9.9"
      E as migrations de settings desfeitas e refeitas com esse ambiente
      Quando as linhas semeadas das três propriedades novas são lidas
      Então a exibição da versão do kit está desligada
      E o alerta de alterações não salvas está ligado
      E a versão do sistema é "9.9.9"

    Cenário: [CT-36] a aplicação alinha as configurações sozinha, sem ninguém pedir
      Dado a exibição da versão do kit gravada como ligada direto na tabela de configurações
      E a aplicação reinicializada depois das migrations, como um processo novo faria
      Quando um request é servido sem que o caso de teste alinhe nada
      Então a chave de configuração da exibição da versão do kit já vale ligado
      E o alinhamento não é disparado dentro de nenhum dos três providers de painel

    Cenário: [CT-19] nenhuma das três propriedades novas é tratada como segredo
      Dado a lista de propriedades cifradas da classe de configurações
      Quando as três propriedades novas são procuradas nela
      Então nenhuma das três está na lista
      E o valor gravado da versão do sistema é legível direto na tabela de configurações
```

**CT-18 deixou de afirmar só presença.** A versão anterior comparava duas listas de nomes e ficava
verde com o `mapaDeConfiguracao()` vazio — que é exatamente o defeito silencioso que
`.ai/rules/settings.md` chama de "o campo aparece, grava, e não governa nada". Cada linha agora
fecha na **chave de configuração** correspondente, e os valores diferem do que o `phpunit.xml`
força, de propósito: `false` contra o alerta que nasce `true`, `true` contra a exibição que nasce
`false`, `"2.4.0"` contra o `APP_VERSION=""`. Um alinhamento que não fizesse nada reprovaria nas
três.

**CT-39 mede o valor semeado, não o do arquivo.** CT-07 e CT-16 leem `config/kit.php` com a
variável de ambiente ausente, e isso prova o default do **arquivo** — mas quem governa a partir do
primeiro boot real é a **linha semeada no banco**, que o alinhamento sobrepõe por cima. Uma
migration que semeasse a exibição da versão do kit como ligada faria toda instalação nascer
anunciando o kit no rodapé, contra a decisão explícita do Adendo 2, e passaria em CT-07, CT-16 e na
versão antiga de CT-18. Achado da revisão adversarial.

**CT-36 é o cenário sem o qual toda a governança por Settings é indemonstrável.** Com
`RefreshDatabase`, o alinhamento do boot é no-op e todo cenário o chama à mão — então o conjunto
inteiro pode ficar verde com a aplicação **nunca** alinhando em produção: a tela grava e nada muda
até o próximo deploy. O repositório já tem o precedente exato desse oráculo
(`tests/Kit/ConfiguracoesDoKitTest.php`, *"liga o alinhamento no provider da aplicação e em nenhum
painel"*), e a segunda metade do `Então` importa tanto quanto a primeira: registrado dentro de um
painel, o alinhamento não alcançaria os outros dois. Achado da revisão adversarial.

**Estouro do teto do perfil (3 → 4), justificado**: A3 nasceu classificada como `mínimo`
porque "o contrato de três lugares já é guardado por invariante existente" — a revisão adversarial
mostrou que não: o invariante existente guarda **nomes**, e os três defeitos que importam aqui
(mapa vazio, semente com o valor errado, alinhador não registrado) são todos sobre **valor** e
sobre **quem chama**. A reclassificação honesta de A3 é P2 × I2 = 4, perfil `padrão`, e é assim que
ela consta no `## Perfil de Derivação`.

**Camada**: `Feature` em `tests/Kit` — o arquivo natural é `tests/Kit/ConfiguracoesDoKitTest.php`,
onde o invariante equivalente já mora.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | a propriedade é declarada na classe e esquecida no `mapaDeConfiguracao()` — o campo grava e não governa nada | CT-18 (a metade da chave de config) |
| M26 | a migration nova é esquecida, e o boot avisa em todo request sobre propriedade não semeada | CT-18 (a metade da linha semeada) |
| M27 | uma das três entra em `addEncrypted()` por simetria com as credenciais — o valor vira criptograma ilegível | CT-19 |
| M56 | a migration semeia a exibição da versão do kit **ligada** (ou o alerta desligado): o valor de fábrica do arquivo está certo e a instalação nasce com o oposto | CT-39 |
| M57 | o alinhamento nunca é registrado no bootstrap — ou é registrado dentro de um `PanelProvider`: a tela grava, o banco guarda, e a config só muda no próximo deploy | CT-36 |

---

## Regra R7 — o avatar padrão é gerado dentro da aplicação, sem requisição a terceiro

> `RQ-19` (**Adendo 3** — deixou de ser `@premissa`) · área A4, perfil **padrão** · técnica: EP + par de
> falsificabilidade

```gherkin
# language: pt
Funcionalidade: Avatar padrão

  Regra: o avatar de quem não enviou foto é gerado pela própria aplicação

    Esquema do Cenário: [CT-20] em cada painel, o avatar de quem não tem foto é gerado pela aplicação
      Dado uma usuária autenticada sem foto de perfil, chamada "Ana Souza"
      Quando ela abre o painel "<painel>"
      Então o avatar dela é uma imagem embutida no próprio documento
      E o endereço dessa imagem não aponta para host nenhum fora da aplicação

      Exemplos:
        | painel |
        | admin  |
        | infra  |
        | app    |

    Cenário: [CT-21] nenhum endereço de imagem da tela aponta para fora da aplicação
      Dado uma usuária autenticada sem foto de perfil, chamada "Ana Souza"
      Quando ela abre o painel /admin
      Então todo endereço de imagem da página é embutido ou tem o host da própria aplicação
      E a página não contém "ui-avatars.com"

    Cenário: [CT-45] numa mesma tela, cada pessoa recebe o avatar dela
      Dado uma listagem com "Ana Souza" e "Bruno Teixeira", nenhuma das duas com foto
      E uma terceira pessoa autenticada olhando a listagem
      Quando a listagem é aberta
      Então o avatar da linha da Ana traz as iniciais "AS"
      E o avatar da linha do Bruno traz as iniciais "BT"

    Cenário: [CT-22] quem tem foto continua com a foto
      Dado uma usuária autenticada com a foto de perfil "perfis/ana.png" gravada
      Quando ela abre o painel /admin
      Então o endereço do avatar dela é o da foto gravada
      E o avatar dela não é uma imagem embutida de iniciais
```

**CT-20 deixou de nomear a classe**, e por isso passou a ter oráculo. A `## Fronteira com o Plano`
recusa `App\Support\AvatarDeIniciais` como oráculo (é escolha de implementação) — e a versão
anterior dizia "é o provedor local de iniciais do kit", que **não tem forma operacional nenhuma**:
não é o nome da classe e não é um observável. O `Então` agora fala do que o usuário recebe: uma
imagem embutida no documento, sem host externo.

**CT-21 afirma sobre host, não sobre um domínio nomeado.** Achado da revisão adversarial: "não
contém `ui-avatars.com`" é uma lista negra de um item — trocar o provedor remoto por Gravatar,
DiceBear ou qualquer outro passa. A asserção genérica ("todo endereço de imagem é embutido ou é da
própria aplicação") fecha a classe inteira, e é a mesma forma que CT-B02 usa no navegador.

**CT-21 tem destinatário**: a usuária existe, não tem foto, e é exatamente a condição em que o
provider padrão é chamado. Sem esse `Dado`, a asserção passaria numa página sem nenhum avatar.

**CT-22 é o par de falsificabilidade**, e agora com critério: um provider que ignorasse a foto e
desenhasse iniciais para todo mundo passaria em CT-20 e CT-21. "Não é uma imagem de iniciais" ficou
operacional — o endereço é o da foto gravada, e não um embutido.

**CT-45 liga o avatar renderizado à pessoa da linha, e é o buraco que a rodada 2 encontrou.**
CT-23…CT-25 medem o gerador **isolado**; CT-20 e CT-21 medem que a imagem é embutida e sem host
externo. Nenhum deles liga uma coisa à outra — e a implementação que ignora o registro recebido e
usa sempre quem está autenticado (ou um avatar fixo) passava nos cinco, com **toda listagem
mostrando as iniciais de quem está olhando**. Duas pessoas sem foto, com iniciais diferentes, na
mesma página, e uma terceira autenticada: é o arranjo mínimo que discrimina.

**Camada**: `Feature`/HTTP em `tests/Kit`. A observação de **rede** (nenhuma requisição sai) é
CT-B02, no `05`, porque HTML ausente não prova rede ausente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | o provider é registrado só no `/admin`, e os outros dois painéis continuam com o remoto | CT-20 (linhas `infra` e `app`) |
| M29 | o provider local é registrado mas o remoto continua sendo usado em algum ponto da página (widget, tabela) | CT-21 + CT-B02 |
| M30 | o provider sequestra também quem **tem** foto | CT-22 |
| M58 | o provedor remoto é trocado por **outro** provedor remoto (Gravatar, DiceBear): o domínio nomeado some da página e o dado do usuário continua saindo | CT-20, CT-21 (asserção por host, não por domínio nomeado) + CT-B02 |
| M62 | o provider ignora o registro que recebe e desenha sempre as iniciais de quem está autenticado (ou um avatar fixo): toda listagem mostra o avatar errado, e nenhuma asserção de privacidade percebe | CT-45 |
| M31 | o avatar é gerado localmente e **salvo em disco**, servido por URL — volta a ser requisição, agora interna, com órfão e invalidação | ⚠️ **sem matador** — lacuna declarada. CT-21 afirma "imagem embutida na própria página", o que mata a variante de URL externa mas não distingue um `data:` URI de uma URL local servida por rota. Tentado: afirmar a ausência de qualquer `<img src>` que não comece por `data:` dentro do menu do usuário — o HTML do Filament traz outras imagens (logo, favicon) e o cenário passou a depender do markup do vendor. **O que sobra**: o `02` (ADR-03) descarta a variante em prosa; o cenário não a falsifica |

---

## Regra R8 — as iniciais derivam do nome em toda partição de nome, sem quebrar o SVG

> `RQ-19` (**Adendo 3** — deixou de ser `@premissa`) · área A4, perfil **padrão** · técnica: EP +
> normalização/identidade

```gherkin
# language: pt
Funcionalidade: Avatar padrão

  Regra: as iniciais saem do nome, e nenhum nome quebra a imagem

    Esquema do Cenário: [CT-23] cada forma de nome produz as iniciais esperadas
      Dado um usuário chamado "<nome>"
      Quando o avatar padrão dele é gerado
      Então o texto dentro da imagem é "<iniciais>"

      Exemplos:
        | nome                  | iniciais | # partição                        |
        | Ana Souza             | AS       | dois termos                       |
        | Ana                   | A        | um termo                          |
        | Ana Maria Souza Lima  | AM       | quatro termos, só os dois primeiros |
        | ana souza             | AS       | minúsculas                        |
        |   Ana   Souza         | AS       | espaços nas bordas e no meio      |
        | Ána Élis              | ÁÉ       | acento preservado na maiúscula    |
        | 李 明                  | 李明     | unicode fora do alfabeto latino   |
        | (vazio)               | (nenhum) | nome vazio — imagem sem texto     |
        | (só espaços)          | (nenhum) | equivalente a vazio               |

    Cenário: [CT-24] nome com marcação não quebra a imagem nem injeta conteúdo
      Dado um usuário cujo nome contém "<", "&" ou aspas
      Quando o avatar padrão dele é gerado
      Então a imagem gerada é um documento XML bem formado
      E o texto dentro dela é a inicial CRUA, escapada e não descartada
      E ela não contém nenhum elemento além dos que o desenho do avatar usa

    # [CT-25] FUNDIDO EM CT-23 — inexpressável por tipo. O provider não lê `name` do registro:
    # recebe o nome de `Filament::getNameForDefaultAvatar()`, declarado `: string`. Nome nulo
    # estoura antes de chegar ao kit, e a partição alcançável é a string vazia, que CT-23 cobre.
    # Ver `## Reconciliação` › D5.
```

**Por que CT-24 afirma sobre o XML e não sobre a string**: uma implementação que escapasse só `&`
passaria em qualquer `assertStringNotContainsString('<script')`. Exigir que o documento **parseie**
e que o nó de texto contenha exatamente as iniciais distingue "escapou tudo" de "escapou quase
tudo" — e aspas dentro de atributo são o caso que escapa da checagem ingênua.

**Estado do framework como entrada não validada** (item obrigatório da taxonomia): o nome chega ao
provider vindo do model, sem validação de formato, e vira conteúdo de um documento XML. CT-24 e
CT-25 são os dois cenários de entrada inválida dessa superfície — um com valor fora do domínio,
outro com ausência de valor.

**Camada**: `Feature` em `tests/Kit` (seria `Unit` em outro projeto; aqui `tests/Unit` não tem o
`TestCase` da aplicação e o provider depende do container para resolver o nome e a paleta).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | `explode(' ', $nome)` sem filtrar vazios: "  Ana   Souza" vira iniciais " " e "A" | CT-23 (linha de espaços) |
| M33 | `strtoupper()` em vez de `mb_strtoupper()`: "Ána" vira "Á" corrompido ou fica minúsculo | CT-23 (linha acentuada) |
| M34 | `substr($nome, 0, 1)` em vez de `mb_substr`: nome unicode produz meio caractere e o XML quebra | CT-23 (linha unicode) + CT-24 |
| M35 | o escape usa `htmlspecialchars` sem `ENT_QUOTES`, e a aspa dentro do atributo fecha o atributo | CT-24 |
| M36 | nome vazio produz `substr()` em string vazia e o método lança, derrubando toda tela com avatar | CT-23 (linhas vazia e só espaços — CT-25 foi fundido nele) |
| M37 | pega as iniciais do **primeiro e do último** termo em vez dos dois primeiros | CT-23 (linha de quatro termos) |

---

## Regra R9 — mudar a fronteira de acesso deixa linha na trilha, com o antes e o depois

> `RQ-13` + `00 › ## Fora de Escopo`, que nomeia o item 4 como *"corrige o defeito de auditoria
> (`ativo` fora da trilha)"* · área A5, perfil **padrão** · técnica: **tabela estado × operação** +
> **rastreio de efeito**

### Matriz estado × operação — produto cartesiano fechado

**4 estados × 5 operações = 20 células.** Estados da conta: `ativa`, `desativada`,
`aprovação pendente`, `excluída` (exclusão lógica). Operações que escrevem ou poderiam escrever
uma coluna de fronteira: `desativar()`, `reativar()`, `aprovar()`, `editar o nome pela tela`,
`excluir (exclusão lógica)`.

**Legenda, que é uma asserção — três símbolos, porque dois mentiam:**
- `✅F` = a operação muda uma coluna de **fronteira**, e uma linha nova aparece em `audits` com
  `old_values`/`new_values` **daquela coluna**, **e** o channel `autenticacao` recebe o registro.
- `✅C` = a operação muda um campo **comum**, e a linha nova em `audits` traz **só** esse campo —
  nenhuma coluna de fronteira aparece nela, e as duas colunas de fronteira continuam com o valor
  anterior.
- `✅E` = a operação **exclui** o registro, e a trilha ganha uma linha registrando a exclusão, com a
  autora, sem que as colunas de fronteira mudem. Símbolo próprio porque a exclusão não é campo
  comum nem coluna de fronteira declarada: chamá-la de `✅C` tornava a legenda falsa em três
  células, que foi o que a rodada 2 mediu.
- `❌` = a operação **não** muda coluna nenhuma, **não** grava linha nova em `audits` **e não**
  registra no channel `autenticacao`. As três metades são afirmadas; a versão anterior desta
  legenda prometia as três e o cenário afirmava duas.

A distinção `✅F`/`✅C` existe porque a legenda antiga era **falsa nas três células de
`editar o nome`**: editar o nome não muda coluna de fronteira nenhuma, e a célula admitia isso
entre parênteses, usando um segundo significado de `✅` que a legenda não definia. Achado da
revisão adversarial.

| Estado \ Operação | `desativar()` | `reativar()` | `aprovar()` | editar o nome | excluir |
|---|---|---|---|---|---|
| **ativa** | ✅F CT-26 | ❌ CT-28 | ❌ CT-28 | ✅C CT-29 | ✅E CT-42 |
| **desativada** | ❌ CT-28 | ✅F CT-26 | ❌ CT-28 | ✅C CT-29 | ✅E CT-42 |
| **aprovação pendente** | ✅F CT-26 | ❌ CT-28 | ✅F CT-26 | ✅C CT-29 | ✅E CT-42 |
| **excluída** | não se aplica: as ações de fronteira da listagem não alcançam registro excluído — a lixeira restaura antes | não se aplica: idem | não se aplica: idem | não se aplica: idem | ❌ CT-28 |

**Contagem auditada, recontada célula a célula na rodada 2**: 20 células — **10 válidas**
(**4** `✅F`, que são exatamente as 4 linhas do `Esquema` de CT-26; **3** `✅C`; **3** `✅E`),
**6 inválidas** (`❌`) e **4 não aplicáveis** com motivo. `10 + 6 + 4 = 20`.

> A contagem anterior dizia "9 válidas (3 `✅F` + 6 `✅C`), 7 inválidas", e fechava em 20 **por
> compensação de dois erros**. Pior: a seção `## Revisão Adversarial` chegou a registrar, em
> "dimensões sem achado", que a aritmética da matriz estava correta — era a única linha do
> documento que se elogiava, e era falsa. Fica como lembrete de que soma que fecha não é conta
> conferida.

**A coerência da linha `excluída`**: as quatro primeiras células não se aplicam porque as **ações
de fronteira da listagem** não alcançam registro excluído; a quinta é exercitável porque excluir de
novo é a operação que o próprio caminho de exclusão oferece (e é a célula de idempotência). CT-42
exclui **pela ação da listagem**, com a conta ainda visível — não pela lixeira.

**As quatro células que estavam resolvidas por ponteiro, sem execução** — achado da revisão
adversarial, fechado aqui:

| Célula | Estava | Ficou |
|---|---|---|
| `desativada × aprovar` | apontava CT-28, que tinha 4 exemplos para 6 células | linha própria no `Esquema` de CT-28 |
| `pendente × reativar` | idem — e é onde mora *"`reativar()` limpa `aprovacao_pendente` de lambuja"* | linha própria no `Esquema` de CT-28 |
| `desativada × editar o nome` | apontava CT-29, cujo `Dado` era "uma conta **ativa**" | CT-29 virou `Esquema` com os três estados |
| `pendente × editar o nome` | idem — e é o estado em que o arrasto da coluna de fronteira é mais plausível | idem |

**A coluna `excluir` deixou de ser "não se aplica".** A premissa nº 5 (a lista de colunas de
fronteira está fechada em `ativo` e `aprovacao_pendente`) é premissa de **mecanismo**, não de
escopo: ela decide *como* a exclusão aparece na trilha, não *se* a exclusão deixa rastro. A direção
por falha fechado, para uma regra de compliance, é **auditar mais**, e o invariante que vale nas
duas leituras é o que CT-42 afirma — *a exclusão lógica de uma conta deixa registro na trilha*.
Usar a premissa para apagar quatro células era exatamente o que a skill chama de converter escolha
de implementação em cobertura.

**Duas dimensões que a matriz não fixa**, e que os cenários variam de propósito:
- **persona**: quem executa nunca é quem sofre (o model recusa a própria conta), e CT-27 entra pela
  Action da tela com um administrador distinto do alvo.
- **caminho**: CT-26 chama o model **direto**, sem passar por tela — é o
  [gate de camada da regra](#regra-r9), e é o cenário que distingue "a trilha registra" de "a tela
  registra".

```gherkin
# language: pt
Funcionalidade: Trilha de auditoria da fronteira de acesso

  Regra: mudar a fronteira de acesso de uma conta deixa registro na trilha

    Esquema do Cenário: [CT-26] cada operação de fronteira grava o antes e o depois
      Dado uma conta alvo no estado "<estado>"
      E uma administradora distinta do alvo
      Quando a operação "<operacao>" é executada direto no model, sem passar por tela
      Então existe uma linha nova em audits para essa conta
      E o valor anterior registrado da coluna "<coluna>" é <antes>
      E o valor novo registrado da coluna "<coluna>" é <depois>

      Exemplos:
        | estado              | operacao   | coluna              | antes | depois | # célula      |
        | ativa               | desativar  | ativo               | true  | false  | ativa × desativar |
        | desativada          | reativar   | ativo               | false | true   | desativada × reativar |
        | aprovação pendente  | aprovar    | aprovacao_pendente  | true  | false  | pendente × aprovar |
        | aprovação pendente  | desativar  | ativo               | true  | false  | pendente × desativar |

    Cenário: [CT-27] a desativação feita pela tela também deixa a linha na trilha
      Dado uma conta alvo ativa e uma administradora distinta dela
      Quando a administradora desativa a conta pela ação da listagem do /admin
      Então existe uma linha em audits para a conta alvo com ativo passando de true para false
      E a linha registra a administradora como autora

    Cenário: [CT-40] a desativação bem-sucedida continua registrando no canal de autenticação
      Dado uma conta alvo ativa e uma administradora distinta dela
      Quando a administradora desativa a conta
      Então o channel de autenticação recebe o registro da desativação
      E o executor registrado é o identificador da administradora, não o da conta alvo
      E o alvo registrado é o identificador da conta alvo
      E existe a linha correspondente em audits

    Esquema do Cenário: [CT-28] operação que não muda nada não muda coluna, não grava linha nem loga
      Dado uma conta alvo no estado "<estado>"
      E uma administradora distinta dela
      E o número de linhas que a trilha dessa conta já tem, seja ele qual for
      Quando a operação "<operacao>" é executada
      Então as duas colunas de fronteira da conta continuam com o valor que tinham
      E a trilha dessa conta continua com o mesmo número de linhas de antes
      E nenhum registro é escrito no channel de autenticação

      Exemplos:
        | estado     | operacao  | # célula                |
        | ativa      | reativar  | ativa × reativar        |
        | ativa      | aprovar   | ativa × aprovar         |
        | desativada | desativar | desativada × desativar  |
        | desativada | aprovar   | desativada × aprovar    |
        | pendente   | reativar  | pendente × reativar     |
        | excluída   | excluir   | excluída × excluir — SEM LINHA de teste; ver `## Reconciliação` › D6 |

    Esquema do Cenário: [CT-29] editar só o nome não arrasta a coluna de fronteira, em nenhum estado
      Dado uma conta alvo no estado "<estado>" e uma administradora distinta dela
      Quando a administradora troca apenas o nome da conta
      Então a linha nova da trilha registra só o nome, com o antes e o depois
      E ela não menciona ativo nem aprovacao_pendente
      E as duas colunas de fronteira continuam com o valor que tinham

      Exemplos:
        | estado     |
        | ativa      |
        | desativada |
        | pendente   |

    Esquema do Cenário: [CT-42] a exclusão lógica deixa registro na trilha, a partir de qualquer estado
      Dado uma conta alvo no estado "<estado>", com a trilha já registrando uma alteração anterior
      E uma administradora distinta dela
      Quando a administradora exclui a conta
      Então existe uma linha nova na trilha para essa conta, registrando a exclusão
      E ela registra a administradora como autora
      E as duas colunas de fronteira continuam com o valor que tinham

      Exemplos:
        | estado     |
        | ativa      |
        | desativada |
        | pendente   |
```

**CT-40 é o par positivo que faltava, e é o achado mais silencioso da revisão adversarial.** Nenhum
cenário do conjunto afirmava que o log **acontece** — CT-27 afirmava só a linha de `audits`, e
CT-28 afirmava que o log **não** acontece. Com o log removido de toda a aplicação (uma
"simplificação" plausível para quem acabou de mover a auditoria para `audits`), as seis linhas de
não-log de CT-28 continuariam verdes, agora por vácuo, e o checklist seguiria marcando "canal
correto do efeito" como coberto. CT-40 é o mundo com destinatário.

**Os `<linhas>` de CT-28 passaram a ser coerentes com o estado.** A conta "ativa" chega ao cenário
sem operação prévia (0 linhas); "desativada", "pendente" e "excluída" exigiram uma operação para
chegar lá, e por isso partem de 1. A versão anterior declarava 1 para todos, inclusive para o
estado que não tinha como ter linha nenhuma — incoerência apontada pela revisão.

**As asserções de ausência de CT-28 têm destinatário e alvo**: a conta existe, o channel existe e é
o mesmo que CT-40 prova receber no caminho feliz, e a mesma operação no estado oposto **grava** — o
que CT-26 mostra no mesmo arquivo.

**Camada**: `Feature` em `tests/Kit`. CT-26, CT-28 e CT-29 entram pelo model e pela tela conforme o
cenário; CT-27 é componente Livewire (`callAction(TestAction::make('desativar')->table($alvo))`),
que é o padrão já usado em `tests/Kit/SituacaoDaContaTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | a lista extra alcança `ativo` e esquece `aprovacao_pendente` | CT-26 (linha "pendente × aprovar") |
| M39 | a trilha registra a mudança mas com `old_values` vazio — não dá para saber o estado anterior | CT-26 (as colunas `antes`) |
| M40 | a operação repetida grava linha nova com `old_values` igual a `new_values`, poluindo a trilha | CT-28 |
| M41 | a coluna de fronteira é forçada em **toda** linha de auditoria, inclusive na edição de nome | CT-29 — e o mutante é fraco de propósito: sob o auditor do projeto a linha só carrega campo alterado, então só a variante que **força** a coluna é alcançável. Mantido porque é a única forma de M41 que um dev escreveria; a variante "vaza sozinho" é inexpressável |
| M42 | a trilha só é gravada quando a mudança vem da tela; a chamada direta ao model passa despercebida | CT-26 (que entra pelo model) |
| M59 | o log no channel `autenticacao` é removido junto com a mudança, por parecer redundante com a trilha | CT-40 |
| M60 | `reativar()` limpa `aprovacao_pendente` de lambuja (ou `aprovar()` reativa a conta), misturando as duas fronteiras | CT-28 (linhas `desativada × aprovar` e `pendente × reativar`) |

---

## Regra R10 — a extensão da trilha soma, não substitui, e não vaza para quem não a declara

> `RQ-13` · área A5, perfil **padrão** · técnica: par de falsificabilidade

```gherkin
# language: pt
Funcionalidade: Trilha de auditoria da fronteira de acesso

  Regra: a extensão da trilha é aditiva e local ao model que a declara

    Cenário: [CT-30] alterar um campo comum continua entrando na trilha do usuário
      Dado uma conta alvo ativa com o e-mail "antigo@example.com"
      Quando o e-mail é alterado para "novo@example.com"
      Então a trilha registra o e-mail passando de "antigo@example.com" para "novo@example.com"

    Esquema do Cenário: [CT-31] nenhum model sem override passa a auditar coluna de fora
      Dado um registro do model "<model>", que usa a mesma trait e não declara colunas extras
      E que ele tem uma coluna fora dos campos editáveis em massa
      Quando um campo editável e essa coluna são alterados na mesma gravação
      Então a linha nova da trilha registra o campo editável
      E ela não registra a coluna de fora

      Exemplos:
        | model   |
        | Tenant  |
        | Projeto |
        | Convite |
        | AgenteIa |
```

**CT-31 afirma sobre a linha gravada, não sobre a lista devolvida** — e a correção veio da revisão
adversarial apontando que a versão anterior **contradizia o critério da própria
`## Degradação declarada`**: lá, os casos que já existem em `tests/Kit/FundacaoTest.php` são
desqualificados justamente por afirmarem sobre a *lista*, e o CT que os substituiria fazia o mesmo.
Consultar `getAuditInclude()` prova que a função devolve o que devolve; só a linha de `audits`
prova que o auditor a respeita.

**CT-30 é o par que mata a substituição**: uma implementação que **trocasse** a lista de campos
editáveis pela lista extra passaria em toda a R9 — `ativo` entraria na trilha — e apagaria da
trilha o nome, o e-mail e os demais campos. Só um cenário sobre um campo comum percebe.

**Camada**: `Feature` em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M43 | a lista extra **substitui** a lista de campos editáveis em vez de somar: a trilha passa a registrar só `ativo` | CT-30 |
| M44 | o ponto de extensão é declarado no model base e vaza para os outros quatro models que usam a trait | CT-31 |
| M45 | `ativo` é tornado editável em massa para "simplificar" — resolve a trilha e abre atribuição em massa da fronteira de acesso | ⚠️ **sem matador nesta wiki** — a guarda já existe e é permanente: `tests/Kit/RegistroAbertoTest.php:137` afirma que `aprovacao_pendente` não é editável em massa, e `tests/Kit/AbasDeListagemTest.php:31-38` documenta o mesmo para o par. Lacuna declarada por **redundância deliberada**, não por impossibilidade |

---

## Regra R11 — o kit não trava a versão do Filament e não a abre para major novo

> `RQ-14` (Adendo 1) · área A6, perfil **mínimo** · técnica: EP da constraint

```gherkin
# language: pt
Funcionalidade: Constraint do Filament

  Regra: a constraint permite toda a série corrente e nenhum major novo

    Cenário: [CT-32] o kit declara o Filament em caret na série 5, e só nela
      Dado o composer.json commitado do kit
      Quando a constraint de filament/filament é lida
      Então ela aceita a versão instalada e toda versão seguinte da série 5
      E ela não aceita 6.0.0
      E ela não aceita apenas uma versão exata
```

**Por que as quatro asserções**: `*` e versão exata são as duas partições inválidas que o `00`
(Ambiguidades do Adendo 1) trata explicitamente — `*` como "travar ao contrário", versão exata como
a trava que RQ-14 proíbe. As duas últimas linhas fecham a **constraint composta**, achado da
revisão adversarial: `"^5.6 || ^6.0"` começa por `^5.`, não é `*`, não é exata — e passava no
cenário inteiro enquanto deixa um major novo entrar sozinho, que é precisamente o que a premissa
(a) do Adendo 1 recusa.

**Camada**: `Feature` em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M46 | a constraint vira `*` e um major novo entra sozinho num `composer update` de rotina | CT-32 |
| M47 | a constraint é fixada em `5.7.6` "para estabilizar" — que é exatamente o que RQ-14 proíbe | CT-32 |
| M61 | a constraint vira composta (`^5.6 \|\| ^6.0`) para "já aceitar o próximo major": o kit passa a aceitar major sem revisão, com a aparência de estar em caret na série 5 | CT-32 (as duas últimas asserções) |

---

## Regra R14 — a versão do kit, quando exibida, é distinguível da versão do sistema

> `RQ-20` (**Adendo 3**) · área A1, perfil **padrão** · técnica: partição + par discriminante

Esta regra **não existia** até o Adendo 3. O invariante que ela opera vinha da premissa nº 2 —
*nada no rodapé apresenta a versão do kit como se fosse a do produto* — e era **prosa**: a pergunta
nº 8 declarava que nenhum `Então` casa rótulo, e a rodada 2 da revisão adversarial registrou a
contradição com a frase *"não há terceira saída"*. A saída foi o solicitante transformar o rótulo em
requisito.

```gherkin
# language: pt
Funcionalidade: Distinção entre as duas versões no rodapé

  Regra: a versão do kit nunca é apresentada como se fosse a do produto

    Cenário: [CT-46] as duas versões aparecem distinguíveis uma da outra
      Dado que a versão do sistema é "2.4.1"
      E que a exibição da versão do kit está ligada
      Quando um usuário autenticado abre qualquer tela de painel
      Então o rodapé apresenta a versão do sistema
      E apresenta a versão do kit acompanhada de um rótulo que a identifica como do kit
      E o rótulo não acompanha a versão do sistema

    Cenário: [CT-47] com a versão do sistema vazia, a do kit continua rotulada
      Dado que a versão do sistema não está informada
      E que a exibição da versão do kit está ligada
      Quando um usuário autenticado abre qualquer tela de painel
      Então a única versão no rodapé é a do kit, e ela está rotulada como tal
```

**Por que CT-47 existe separado**: é exatamente a linha 3 de CT-01, aquela que a premissa nº 2
decidia às cegas. Com a versão do sistema vazia, um rodapé que mostrasse `0.34.2` sozinho seria
lido como a versão do produto — e é esse o dano que o invariante previne. Antes do Adendo 3 não
havia como afirmá-lo; agora há.

**O texto do rótulo não é fixado pelo requisito** (o Adendo 3 diz isso por extenso). O cenário
afirma que existe distinção legível, não qual palavra é usada — fixar a string seria o teste
escolhendo a redação da interface, que é o que a pergunta nº 8 evita.

**Camada**: `Feature` em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M63 | o rodapé passa a emitir as duas versões separadas só por espaço ou ponto, sem rótulo — e o leitor não sabe qual é qual | CT-46 |
| M64 | o rótulo é aplicado às duas, e aí ele deixa de discriminar | CT-46 (terceira linha) |
| M65 | com a versão do sistema vazia, a do kit é emitida **sem** rótulo, porque o código só rotula quando há duas | CT-47 |

---

## Regra R12 — as dez decisões ficam registradas, e nenhum dos dez pacotes entra

> `RQ-01`, `RQ-07`, `RQ-08` · área A7, perfil **mínimo** · técnica: EP por pacote + asserção de
> ausência com destinatário

```gherkin
# language: pt
Funcionalidade: Registro documental da rodada 2

  Regra: cada pacote avaliado tem veredito registrado, e nenhum foi adotado

    Esquema do Cenário: [CT-33] os dez pacotes têm veredito registrado pelo nome Composer real
      Dado a página de pacotes candidatos do repositório
      Quando o pacote "<pacote>" é procurado nela
      Então ele aparece pelo nome Composer
      E o veredito registrado na mesma linha é "<veredito>"

      Exemplos:
        | pacote                                      | veredito |
        | mortalkiller/filament-page-header           | ADIAR    |
        | jeffersongoncalves/filament-page-visits     | ADIAR    |
        | jeffersongoncalves/filament-ban             | RECUSAR  |
        | packstub/filament-flow                      | ADIAR    |
        | syofyanzuhad/filament-connection-indicator  | RECUSAR  |
        | matondojk/filament-avatar-picker            | RECUSAR  |
        | vaslv/filament-app-version                  | ADIAR    |
        | ronssij/filament-simple-draft               | RECUSAR  |
        | yousefaman/filament-autosave                | ADIAR    |
        | alexkramse/filament-openapi-docs            | RECUSAR  |

    Cenário: [CT-34] nenhum dos dez pacotes entrou nas dependências
      Dado a lista de dependências declaradas no composer.json, com os dez nomes conhecidos
      Quando as dependências são comparadas com a lista dos dez
      Então nenhum dos dez aparece entre elas
```

**De onde vem cada metade do oráculo de CT-33**, porque as duas têm fontes diferentes: *"nenhum é
ADOTAR"* vem do **requisito** (RQ-08, resolvida em `## Ambiguidades` com a escolha "Os 5 nativos");
o par ADIAR/RECUSAR de cada pacote vem do **`02`**, e por isso não é oráculo de comportamento —
é o valor que o registro documental precisa manter **consistente consigo mesmo**, que é o que R12
compra. A rodada 2 flagrou que, sem a coluna, marcar `mortalkiller/filament-page-header` como
RECUSAR passaria, apesar de o `00` (Adendo 1, *Consequência sobre uma decisão já tomada*) registrar
ADIAR — e essa linha específica **é** requisito.

**A asserção de ausência de CT-34 tem alvo**: a lista dos dez é declarada no próprio cenário, e o
`composer.json` do kit tem 60 dependências diretas — o cenário compara dois conjuntos não vazios.
"Nenhuma dependência nova" genérico seria vácuo.

**Estouro do teto do perfil mínimo (1 → 2), justificado**: CT-33 prova o registro e CT-34 prova a
decisão. São proposições diferentes — um registro completo é compatível com um pacote adotado por
engano, e vice-versa.

**Camada**: `Feature` em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M48 | o registro usa o **slug do diretório** em vez do nome Composer — os dez divergem nesta rodada, e a próxima varredura não encontra nenhum | CT-33 |
| M49 | um ou dois pacotes ficam sem veredito e a próxima varredura os reavalia do zero | CT-33 |
| M50 | um dos avaliados entra no `composer.json` junto com a entrega | CT-34 |

---

## Regra R13 — o que o kit afirma sobre o render hook do menu do usuário bate com o vendor instalado

> `RQ-01`, `RQ-13` (item 5 dos cinco nativos) · área A7, perfil **mínimo** · técnica: medição do
> vendor

```gherkin
# language: pt
Funcionalidade: Afirmações do kit sobre o vendor

  Regra: o que o kit escreve sobre o hook do menu do usuário é verdade no Filament instalado

    Cenário: [CT-35] o hook do menu do usuário é emitido fora do dropdown, e o kit cita a linha certa
      Dado a view de menu do usuário do Filament instalado
      Quando a posição do hook anterior ao menu do usuário é comparada com a abertura do dropdown
      Então o hook é emitido antes de o dropdown abrir
      E o hook do perfil, esse sim, é emitido dentro do dropdown
      E cada arquivo do kit que justifica a escolha cita essa view pelo caminho, sem número de linha
```

**O oráculo é presença de citação, não ausência de frase.** A versão anterior afirmava que "nenhum
arquivo do kit diz que o hook renderiza dentro do dropdown" — ausência de **texto livre**, que a
mesma afirmação errada reescrita com outras palavras satisfaz. Achado da revisão adversarial. Exigir
a **citação** (`user-menu.blade.php:43`) é asserção de presença, e é a forma que
`.ai/rules/testes.md` recomenda quando o objeto é o texto de um comentário: citar não é executar, e
a asserção de presença roda sobre o texto cru.

**É um teste de comportamento do vendor**, e o repositório tem precedente explícito para isso —
`tests/Tenancy/CarimboDeOrganizacaoTest.php` existe para "medir o comportamento do vendor e ficar
vermelho se ele mudar". A afirmação errada nos quatro arquivos foi o defeito que o item 5 corrige;
sem este cenário, ela pode voltar no próximo `kit:update` sem ninguém notar.

**A asserção de ausência filtra comentário?** Não: aqui a ausência é justamente **sobre** o texto
dos comentários, que é onde a afirmação errada morava. O filtro de `.ai/rules/testes.md` se aplica
quando o comentário **cita** o que o código proíbe; aqui o comentário **é** o objeto.

**Camada**: `Feature` em `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M51 | a correção troca o texto em três dos quatro arquivos e esquece o quarto | CT-35 (varre os arquivos do kit, não um nome fixo) |
| M52 | o Filament muda a posição do hook num upgrade e a justificativa do kit volta a ser falsa em silêncio | CT-35 (mede o vendor) |

---

## Checklist de Taxonomia

Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou
`lacuna declarada: {o que foi tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: a entrega não cria rota que receba `{id}` de recurso. As três propriedades novas são singleton por instalação, e a trilha de auditoria é lida por telas que já existem |
| Autorização exercida na ação (não só consultada) | CT-41 — o **salvamento** é chamado e o valor persistido é conferido depois. ⚠️ **Cobertura parcial declarada**: a rodada 2 mostrou que CT-41 entra pela mesma porta de CT-11, então a variante *autorização só no `mount`* só morre se a página tiver gate separado de leitura e de gravação. Ver L7 |
| ≥1 cenário de autorização/domínio **por fora do componente de UI** | CT-26 (a trilha é exercida chamando o model direto) · CT-36 (o registro do alinhamento é medido fora de qualquer tela) |
| Idempotência, ancorada no agregado | CT-28 — o agregado é a **conta persistida**: as duas colunas de fronteira dela, afirmadas explicitamente, mais a contagem de linhas da trilha. A asserção sobre a coluna foi acrescentada pela revisão adversarial, que apontou que a versão anterior ancorava só no efeito colateral |
| Concorrência | não se aplica: nenhum contador, saldo, estoque ou limite de uso nesta entrega |
| Fronteira no ponto de entrada (gravação, não só uso) | CT-10 (versão do sistema: vazio, 1 caractere, espaços nas bordas) · CT-44 (1024 caracteres) · CT-09 e CT-17 (as duas booleanas, em todas as formas de env) |
| Domínio condicionado (um campo depende de outro) | CT-01 — as duas chaves de versão decidem juntas; a tabela 2×2 é a forma canônica |
| Estado × operação de escrita (o "desligado" ainda funciona?) | CT-28 — a operação repetida sobre a conta já no estado de destino; e a matriz de R9, com as 20 células resolvidas |
| Ausente ≠ nulo ≠ vazio | CT-12 (propriedade **ausente** do banco, com o ambiente preenchido) · CT-38 (campo **limpo** com o ambiente preenchido — a partição que separa "vazio" de "ausente" de verdade) · CT-25 (nome **nulo**). As três semânticas estão em cenários distintos, de propósito |
| Paginação | não se aplica: a entrega não cria listagem nem altera nenhuma |
| Ordenação | não se aplica: idem |
| Timezone / DST / virada de dia | não se aplica: nenhuma regra desta entrega lê data ou hora. A dimensão T da varredura está declarada e quase vazia |
| Unicode / limite de varchar | CT-23 (nome acentuado e fora do alfabeto latino) · CT-44 (versão de 1024 caracteres) |
| Unicidade + exclusão lógica | não se aplica: nenhuma entidade nova, nenhuma coluna única nova |
| CRUD combinado (ler/editar/excluir inexistente; editar sem mudar nada) | CT-28 (operação que não muda nada) · CT-12 (propriedade inexistente) |
| Mass assignment | CT-18 — "não existe linha no banco sem propriedade correspondente na classe" é a asserção que pega campo extra chegando ao grupo de configurações. Para a conta de usuário, a guarda de `ativo`/`aprovacao_pendente` fora do `$fillable` é permanente e já medida (`tests/Kit/RegistroAbertoTest.php:137`) — ver M45 |
| Upload | não se aplica: a entrega não recebe arquivo |
| Precisão monetária | não se aplica: nenhum valor monetário |
| Superfície Livewire — propriedade pública e estado do framework | CT-24, CT-25 — o nome do usuário é entrada não validada que vira conteúdo de um documento XML; CT-10 — a versão é entrada não validada que vira conteúdo de toda página; CT-45 — o registro que o provider recebe é o da linha, não o de quem está autenticado |
| Superfície Livewire — método público chamável por `$wire.` | não se aplica: a entrega não acrescenta método público a nenhum componente Livewire. A tela de configurações ganha **dois campos de formulário**, cujo estado é a lista declarada no schema, e o `save` já existia |
| Estado do framework usado sem validar (índice de array, `parse`, nome de coluna) | não se aplica: nenhum valor de `$filters`/`$tableFilters`/`$tableSearch` é consumido por código novo desta entrega |
| IDOR por entidade (uma linha **por tabela** persistida) | `settings`: não se aplica (singleton por instalação, sem identificador de dono) · `audits`: não se aplica (a entrega muda **o que** é gravado, não quem lê; a leitura é de telas existentes) · nenhuma tabela nova é criada |
| Escopo com discriminante nulo (fecha ou abre?) | não se aplica: a entrega não acrescenta nenhum filtro por organização, dono ou discriminante |
| Saída do estado de erro (todo 4xx/redirect tem destino) | CT-11 — o 403 da tela de configurações, **com** a asserção de que a persona continua alcançando as demais telas do painel dela (a versão anterior afirmava o destino só na prosa). Nenhum outro cenário desta entrega termina em 4xx ou redirect |
| Efeito colateral — canal correto do efeito | CT-40 (o log **acontece** no channel `autenticacao`) + CT-27 (a linha em `audits`) + CT-28 (as duas ausências). O par positivo do log veio da revisão adversarial: sem CT-40, o não-log de CT-28 era vácuo e a implementação que para de logar ficava verde |

---

## Lacunas declaradas

| # | Mutante / item | O que foi tentado | Consequência |
|---|---|---|---|
| L1 | **M6** — o guard usa o guard **default** em vez do guard do painel | registrar um painel de teste com `painelRegistradoEmTeste()` e um guard próprio, para fazer os dois divergirem; exige um provider extra em `config/auth.php` do processo, e o cenário passou a medir o arranjo | a escolha errada de guard só falha em projeto que declare `->authGuard()`. Fica como dívida de teste, com o motivo escrito em ADR-04 |
| L2 | **M31** — o avatar é gerado localmente mas servido por URL em vez de embutido | afirmar que todo `<img>` do menu do usuário começa por `data:`; o HTML do Filament traz logo e favicon e o cenário virou refém do markup do vendor | a variante "gera em disco e serve por rota" passa em CT-20…CT-22. Descartada em prosa no ADR-03, não por cenário |
| L3 | **RQ-15** — "se sair atualização, atualize no starter-kit" | comparar `composer.lock` com a última release publicada; exige rede dentro da suíte e fica vermelho por motivo alheio | o que é testável é a constraint (CT-32). O cumprimento é processo, e a pergunta nº 6 devolve a decisão ao solicitante |
| L4 | **RQ-16** — "e os projetos que o usarem" pelo caminho do `php artisan kit:update` | nada foi tentado: o `00` nomeia o comando como mecanismo de propagação, ele existe no repositório e é testável — **o que falta é o contrato**, não o arnês. Achado da revisão adversarial, que mostrou que L3 descartava os dois mecanismos citando o motivo de um só | bloqueada pela pergunta nº 6, ampliada. Escrever hoje seria inventar o requisito |
| L5 | **M5, parcialmente** — a versão emitida no **fim da página** (e não na topbar) | o recorte "posterior ao último `</main>`" mata a variante da topbar e **não** a variante do fim do documento, que é posterior ao `</main>` por definição. Tentado: fechar o recorte por baixo (até o primeiro `<script>` de fim de documento) — depende do markup do vendor, que é a mesma razão pela qual L2 descartou a asserção de `<img>`; aplicar o critério num lugar e não no outro seria incoerente | M5 fica **parcialmente vivo**. Achado da rodada 2 |
| L6 | **M53, parcialmente** — o rodapé emitido num ponto de extensão diferente do que CT-37 registra o marcador | o `Dado` de CT-37 precisa nomear o ponto de extensão para registrar o marcador, e ponto de extensão é mecanismo, que a `## Fronteira com o Plano` recusa como oráculo. Tentado: derivar o ponto do observável, afirmando o **conteúdo do rodapé** na tela de login com o guard satisfeito — não há persona que veja a tela de login autenticada, porque o painel a redireciona | CT-37 prova que **aquele** ponto alcança o layout de autenticação; se a implementação usar outro, M53 sobrevive. Achado da rodada 2 |
| L7 | **M19, parcialmente** — autorização que vive só no `mount` | CT-41 chama o salvamento pelo mesmo componente que CT-11 monta, então uma barreira única no `mount` recusa os dois e o cenário fica verde sem distinguir. Tentado: entrar por um caminho de gravação que não passe pelo `mount` — a tela de configurações não expõe outro, e inventar um seria escrever requisito | a variante *"só no `mount`"* só é distinguível se a página tiver gate separado para abrir e para gravar. Virou a **pergunta nº 10**. Achado da rodada 2 |
| L8 | **CT-36 sob `RefreshDatabase`** | o alinhamento do boot é no-op na suíte porque o provider boota antes das migrations. O cenário reinicializa a aplicação **depois** das migrations para pagar essa conta; se o arnês do projeto não sustentar isso, a alternativa é medir o registro (posição do código), que a rodada 2 mostrou ser oráculo fraco — e aí a lacuna é declarada, não disfarçada | a escolha entre as duas formas é do step 5; a forma fraca **não** pode ser adotada em silêncio |

---

## Poda — cenários cogitados e cortados

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o rodapé aparece também nas telas de erro (404, 500)" | não mata mutante previsto; o guard de visitante já é a fronteira que importa, e as telas de erro não usam o layout dos painéis |
| "o alerta de alterações não salvas aparece nas telas de plugin de terceiro" | mata o mesmo mutante que CT-14 (o painel inteiro responde, não a tela) e amarraria o cenário ao plugin da vez |
| "o avatar tem contraste suficiente entre texto e fundo" | a aparência não é requisito (pergunta nº 7); e contraste só se prova com screenshot e olho, o que a rule do projeto já diz não ter saída barata |
| "a tela de configurações mostra os dois campos novos na aba certa" | é rótulo e posição, recusados como oráculo na `## Fronteira com o Plano` |
| ~~"`kit:info` mostra a versão do kit"~~ — **descortado** | a justificativa anterior ("o `00` não determina nada sobre o `kit:info`") era **contrafactual**: o Adendo 2 diz literalmente *"Quem quiser vê-la usa `php artisan kit:info` ou liga o toggle"*, e com o toggle nascendo desligado esse comando é o **único** caminho restante. Virou CT-43. Achado da revisão adversarial |
| ~~"a trilha registra também a exclusão"~~ — **descortado** | estava "bloqueado pela pergunta nº 5", mas a premissa é de **mecanismo**, e mecanismo escolhe qual cenário escrever, não se ele existe. Virou CT-42, com o invariante que vale nas duas leituras. Achado da revisão adversarial |
| CT-B cogitado: "com o alerta desligado, sair da página suja não pede confirmação" | mata o mesmo mutante que CT-14 (linhas `false`), que é três ordens de grandeza mais barato |
| CT-B cogitado: "o rodapé da versão aparece no navegador" | é HTML renderizado no servidor; CT-01 a CT-03 provam, e nenhum navegador acrescenta informação |
| CT-B cogitado: "a tela de login não mostra a versão, no navegador" | idem CT-05; o guard é decidido no servidor |

---

## Revisão Adversarial

Disparada por **Impacto 3** em A1, A4 e A5.

### Rodada 0 — autocrítica durante a derivação (**não** é a revisão adversarial)

Registrada por honestidade de procedimento, e explicitamente **não** contabilizada como a revisão
que a skill exige: quem escreveu os cenários foi quem os criticou, e a skill é clara em que o mesmo
agente conferindo o próprio conjunto reproduz o viés que o gerou. Serve para mostrar o que já
estava corrigido **antes** de o revisor independente olhar.

| # | Achado | Virou o quê |
|---|---|---|
| 1 | *"CT-05 afirma ausência num arranjo em que o `phpunit.xml` já força `APP_VERSION=\"\"`; o cenário passa com a feature inteira revertida."* | o `Dado` de CT-05 passou a gravar a versão, e CT-06 fecha o par provando que o valor apareceria sem o guard |
| 2 | *"a matriz de R9 percorria só `ativo`; `aprovacao_pendente` aparecia na prosa e em nenhuma linha de exemplo."* | CT-26 ganhou as linhas "pendente × aprovar" e "pendente × desativar" |
| 3 | *"nenhum cenário prova que a extensão da trilha é **aditiva**; todos olham a coluna nova."* | R10 nasceu desta revisão: CT-30 (campo comum continua na trilha) e CT-31 (o model sem override) |
| 4 | *"CT-24 afirmava `não contém <script>`, que uma implementação que escapa só `&` satisfaz."* | o oráculo virou "documento XML bem formado **e** o nó de texto é exatamente as iniciais" |
| 5 | *"a idempotência de CT-28 estava ancorada no retorno da operação."* | reancorada na **conta persistida**: a contagem de linhas da trilha dela, com o `Dado` declarando quantas havia antes |

Cinco achados, cinco fechados durante a própria derivação.

### Rodada 1 — sub-agente independente (**esta** é a revisão adversarial)

Executada por um sub-agente que **não** derivou os cenários, recebendo apenas o `00-requisito.md` e
este arquivo — sem o `01`, sem o `02`, sem o código e sem o raciocínio de quem derivou. Contrato:
provar que o conjunto deixa passar um defeito; proibido elogiar, reescrever ou dizer "está bom".

Ela achou **cinco implementações erradas que passavam no conjunto inteiro**, mais três da mesma
classe, quinze oráculos fracos, quatro células de matriz resolvidas sem execução, uma legenda falsa,
três premissas com a direção invertida e sete erros de contabilidade — inclusive a contagem do
cabeçalho, que dizia 41 mutantes sobre uma lista de 52.

| # | Achado | Virou o quê |
|---|---|---|
| 1 | **I-1 — o alinhador Settings→config nunca é registrado no bootstrap.** Todo cenário o chamava à mão (o `Setup Global` até declarava isso como método), então a tela podia gravar sem nada mudar em produção. M12, M14 e M20 estavam vivos, marcados como mortos | **CT-36** (novo) + CT-15 reescrito, sem o alinhamento como passo do `Quando` |
| 2 | **I-2 — a versão emitida em outro ponto de extensão.** Nenhum `Então` ancorava região, e pior: se o ponto não fosse renderizado no layout das telas de autenticação, CT-05 e CT-06 passariam **com o guard inteiramente ausente** (M7 vivo) | **CT-37** (novo, dá destinatário a CT-05) + oráculo de R1 ancorado no trecho posterior ao `</main>` + **M5** e **M53** reescritos |
| 3 | **I-3 — fallback silencioso para `APP_VERSION`.** A linha "(vazio)" de CT-10 não fixava a variável de ambiente, e o `phpunit.xml` a força vazia: o fallback não tinha o que devolver. M17 vivo | **CT-38** (novo) + M17 reescrito |
| 4 | **I-4 — a migration semeia o valor errado.** CT-07 e CT-16 leem o **arquivo**; quem governa no primeiro boot é a **linha semeada**. Uma semente `exibir_versao_do_kit = true` faria toda instalação nascer contra a decisão do Adendo 2. M10 vivo | **CT-39** (novo) + **M56** |
| 5 | **I-5 — o log some e ninguém percebe.** Nenhum cenário afirmava que o registro no channel `autenticacao` **acontece**; com ele removido, as ausências de CT-28 ficavam verdes por vácuo, e o checklist reivindicava o contrário | **CT-40** (novo) + **M59** + a linha do checklist corrigida |
| 6 | constraint composta (`^5.6 \|\| ^6.0`) passava em CT-32 inteiro | CT-32 ganhou duas asserções + **M61** |
| 7 | avatar servido por **outro** terceiro (Gravatar, DiceBear) passava: CT-21 nomeava um domínio, e CT-20 não tinha oráculo operacional | CT-20 e CT-21 reescritos para afirmar sobre **host**, não sobre domínio nomeado + **M58** |
| 8 | autorização só no `mount`: CT-11 nunca chegava ao salvamento, e o checklist creditava "autorização exercida na ação" | **CT-41** (novo) + M19 reescrito + checklist corrigido |
| 9 | quatro células da matriz resolvidas por ponteiro para cenários que executam **outra** operação ou **outro** estado | CT-28 ganhou 2 linhas, CT-29 virou `Esquema` de 3 estados + **M60** |
| 10 | legenda da matriz **falsa** em 3 células (`editar o nome` marcado `✅` sem mudar coluna de fronteira) e incompleta nas `❌` (prometia 3 asserções, o cenário fazia 2) | legenda passou a ter `✅F`/`✅C`/`❌`, e CT-28 afirma as três metades |
| 11 | a coluna `excluir` inteira marcada "não se aplica" por uma premissa de **mecanismo** | **CT-42** (novo); as 4 células saíram de "não se aplica" |
| 12 | CT-31 afirmava sobre a **lista** devolvida — exatamente o critério pelo qual a `## Degradação declarada` desqualifica os testes pré-existentes | CT-31 reescrito para afirmar sobre a **linha gravada** |
| 13 | CT-33 degradava para "o nome aparece no arquivo"; CT-35 era ausência de texto livre | CT-33 exige o veredito do vocabulário; CT-35 virou asserção de **presença** da citação |
| 14 | premissas nº 2, nº 3 e nº 5 com a direção **aberta**, e o invariante das duas leituras ausente nas nº 2, nº 3 e nº 4 | as três direções invertidas para falha fechado; invariante afirmado dentro de CT-01, CT-44 e CT-16 |
| 15 | a poda cortava o `kit:info` com justificativa **contrafactual** (o Adendo 2 o nomeia) | **CT-43** (novo); a linha da poda ficou como registro do erro |
| 16 | L3 descartava os **dois** mecanismos de RQ-16 citando o motivo de um só | **L4** (nova), e a pergunta nº 6 ampliada para o contrato do `kit:update` |
| 17 | contagem do cabeçalho errada em 11; quatro atribuições de matador falsas; A3 subclassificada | cabeçalho recalculado, matadores reatribuídos, A3 reclassificada para `padrão` |

**Dimensão sem achado**, registrada como a skill pede: nenhum cenário sem `Então`. *(A rodada 1
também registrou aqui que "a aritmética da matriz estava correta"; a rodada 2 recontou e mostrou
que não estava — ver a nota sob a matriz de R9.)*

**Cenários com mais de um `Quando`** (CT-06, CT-08, CT-10, CT-15): os quatro foram reescritos. Em
três deles a segunda ação era a navegação de observação, que virou parte do `Então`; no quarto
(CT-15) a segunda ação era o mecanismo sob teste, e virou CT-36.

### Rodada 2 — executada, pelo mesmo contrato e por outro sub-agente

Disparada porque o fechamento da rodada 1 criou cenários novos, e superfície nova é onde mora a
lacuna de segunda ordem. **Ela achou o que a rodada 1 não podia ver**, e a tese dela vale registro
literal: os cenários que a rodada 1 criou eram, em boa parte, *oráculos posicionais ou oráculos de
mundo arranjado à mão* — CT-36 afirmava **onde o código está declarado**, CT-37 afirmava que **um
marcador registrado pelo próprio teste** aparece, CT-39 afirmava um valor que o `phpunit.xml` força
a ser vazio, CT-41 entrava pela mesma porta que CT-11 já trancava.

39 achados. Os que mudaram o conjunto:

| # | Achado | Virou o quê |
|---|---|---|
| 1 | **CT-36 não era falsificável como comportamento.** Um alinhamento registrado no provider **e** embrulhado num `try/catch` ou num `Schema::hasTable()` que no-opa em produção passava — I-1 inteiro voltava, com M12, M14, M20 e M57 vivos | CT-36 reescrito: aplicação reinicializada **depois** das migrations, request servido **sem** o caso alinhar nada. L8 declara o que fazer se o arnês não sustentar |
| 2 | **CT-39 tinha a terceira asserção vácua**: mede `APP_VERSION` num ambiente que a força vazia | o `Dado` passou a fixar `APP_VERSION="9.9.9"` e a refazer as migrations de settings com ele |
| 3 | **CT-11 e CT-41 usavam `panel_user`**, que não alcança o `/admin`: o 403 vinha da fronteira de painel, e uma página **sem nenhuma autorização** passava nos dois | persona trocada por *administrador que perdeu a permissão de configuração* — a única que discrimina |
| 4 | **CT-41 entra pela mesma porta de CT-11**, então "autorização só no `mount`" continua indistinguível | **L7** + **pergunta nº 10**; a linha do checklist virou cobertura **parcial declarada** |
| 5 | **CT-42 era verde contra o código anterior à feature** e cobria um estado, embora a matriz lhe creditasse três células — o achado nº 9 da rodada 1 reintroduzido pelo cenário que o fechou | virou `Esquema` de três estados, com as asserções que a legenda promete |
| 6 | **CT-44 ficaria vermelho contra implementação correta**: sem versão anterior declarada, o ramo da recusa deixa `""`, que **é** prefixo do digitado | `Dado` passou a fixar a versão anterior `"1.0.0"` |
| 7 | **CT-40 não pareava executor e alvo** — um log que trocasse os dois passava | o `Então` nomeia qual identificador ocupa qual posição |
| 8 | **CT-28 usava contagem absoluta de linhas** (`0` para conta nova): se o auditor registra `created`, reprova sem defeito | passou a afirmar **delta zero** |
| 9 | **nenhum cenário ligava o avatar à pessoa da linha** — o provider que ignora o registro e usa sempre quem está autenticado passava em CT-20…CT-25 e CT-B02 | **CT-45** (novo) + **M62** |
| 10 | **CT-32 virou oráculo de sintaxe**: reprovava `>=5.7 <6.0`, que satisfaz RQ-14 | reescrito em termos de quais versões a constraint aceita |
| 11 | **CT-35 fixava número de linha do vendor** — num kit obrigado por RQ-14/RQ-15 a aceitar toda atualização da série, isso é ruído garantido | passou a citar caminho e **posição relativa**, sem número de linha |
| 12 | **CT-33 aceitava o veredito errado**: `page-header` marcado RECUSAR passava, e o `00` registra **ADIAR** | coluna `veredito` nos Exemplos + a nota sobre a origem de cada metade do oráculo |
| 13 | **CT-31 não nomeava o model**, e M44 fala de quatro | virou `Esquema` sobre os quatro models que usam a trait |
| 14 | **CT-16 trocou multi-`Quando` por ação dentro do `Então`** — o defeito que a rodada 1 corrigira em quatro cenários | a linha saiu; o invariante fica em CT-14, com o desvio declarado |
| 15 | **contagem da matriz errada** (9+7+4, fechando 20 por compensação de dois erros), legenda `✅C` falsa nas três células de `excluir`, e a rodada 1 registrando "aritmética correta" | recontagem 10+6+4, símbolo `✅E` próprio, e a linha falsa da rodada 1 corrigida no lugar |
| 16 | **o recorte `</main>` não mata a variante "fim da página"** de M5, e é refém do markup do vendor pelo mesmo critério que L2 usou para descartar outra asserção | **L5** |
| 17 | **CT-37 precisa nomear o ponto de extensão** que a Fronteira recusa como oráculo | **L6** |
| 18 | **o invariante de CT-01 não tem forma operacional** — só se assere casando rótulo, e a pergunta nº 8 proíbe | **pergunta nº 9**, com as duas únicas saídas escritas |
| 19 | contradições de contagem e crédito: "35 CT" no pós-implementação, R6 com dois perfis, justificativa de estouro contrafactual, "9 e 9" sugerindo pareamento, checklist citando CT-10 no lugar de CT-44 | todas corrigidas |

### Fim do ciclo: o que **não** foi fechado, e por quê

A skill fixa o teto em duas rodadas e manda **registrar e escalar** o que sobrar, em vez de rodar
uma terceira. Sobrou, e o padrão dos resíduos é coerente: quase todos são casos em que **o oráculo
honesto exige uma decisão que só o solicitante pode tomar**.

| Resíduo | Onde está | O que destrava |
|---|---|---|
| M5 parcial (versão no fim da página) | L5 | decidir se o rodapé ganha marcação própria — hoje não há como recortá-lo sem depender do vendor |
| M53 parcial (rodapé em outro ponto de extensão) | L6 | idem |
| M19 parcial (autorização só no `mount`) | L7 | pergunta nº 10 |
| invariante da premissa nº 2 sem forma asserível | pergunta nº 9 | Adendo 3 decidindo se o rótulo é requisito |
| RQ-16 pelo `kit:update` | L4 | pergunta nº 6 ampliada |
| forma de CT-36 sob `RefreshDatabase` | L8 | medição no step 5 |

**Achado estrutural, como a skill pede que se registre**: R4 (7 cenários, 3 assuntos) e R8 (6
mutantes, 2 assuntos) deveriam ser cinco regras, não duas. A divisão não foi feita porque
renumeraria 45 cenários e toda a rastreabilidade; fica como a primeira coisa a fazer se esta wiki
for revisitada.


---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo (conferido na reconciliação) | Mata |
|----|---------|-------|---------|--------|------------------|------|
| CT-01 | as duas chaves decidem juntas o rodapé | R1 | tabela de decisão 2×2 | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M4, M5 (com CT-02…CT-04) |
| CT-36 | o alinhamento é registrado no bootstrap, não num painel | R6 | varredura de registro | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` (caso de outra wiki; ver D8) | M57 |
| CT-37 | o ponto de extensão do rodapé alcança a tela de login | R2 | destinatário do efeito | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M53, M7 (com CT-05) |
| CT-38 | campo limpo apaga o rodapé com o ambiente preenchido | R4 | ausente ≠ vazio | Livewire+Feature | `tests/Kit/VersaoNoRodapeTest.php` | M17 |
| CT-39 | a migration semeia o valor de fábrica certo | R6 | partição do valor semeado | Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M56 |
| CT-40 | a desativação registra no canal de autenticação | R9 | par positivo do efeito | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M59 |
| CT-41 | persona sem permissão não grava, nem chamando o salvamento | R4 | matriz persona × ação | Livewire | `tests/Kit/VersaoNoRodapeTest.php` | M19 |
| CT-42 | a exclusão lógica deixa registro na trilha | R9 | invariante da premissa de mecanismo | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | — (invariante) |
| CT-43 | a versão do kit continua alcançável pela linha de comando | R3 | EP da saída restante | Feature | `tests/Kit/VersaoNoRodapeTest.php` (ver D9) | M54 |
| CT-44 | valor longo é gravado inteiro ou recusado, nunca truncado | R4 | EP do campo, `@premissa` | Livewire | `tests/Kit/VersaoNoRodapeTest.php` | M16 |
| CT-45 | numa mesma tela, cada pessoa recebe o avatar dela | R7 | par discriminante de personas | Livewire | `tests/Kit/AvatarDeIniciaisTest.php` | M62 |
| CT-02 | a versão é a do sistema, não a do kit | R1 | EP | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M1 |
| CT-03 | o rodapé acompanha os três painéis | R1 | matriz painel | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M2 |
| CT-04 | versão com marcação chega escapada | R1 | EP de dado hostil | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M3 |
| CT-05 | nenhuma tela de autenticação expõe a versão | R2 | EP por tela | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M9; M7 só junto com CT-37 |
| CT-06 | o par positivo do guard | R2 | par | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M8 |
| CT-07 | a exibição da versão do kit nasce desligada | R3 | valor do requisito, lido do arquivo | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M10 |
| CT-08 | ligada pela tela, a versão do kit acompanha | R3 | EP | Feature+Livewire | `tests/Kit/VersaoNoRodapeTest.php` | M12, M13 |
| CT-09 | formas de env booleana da exibição | R3 | EP | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M11 |
| CT-10 | o valor gravado é o exibido, sem alteração silenciosa | R4 | EP do campo | Livewire+Feature | `tests/Kit/VersaoNoRodapeTest.php` | M14 |
| CT-11 | persona sem permissão não abre a tela | R4 | matriz persona | Livewire | `tests/Kit/VersaoNoRodapeTest.php` | — (par de CT-41; saída do 403) |
| CT-12 | sem linha no banco, o ambiente vale | R4 | EP ausente/nulo | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M15, M55 |
| CT-13 | nada lê `.git` | R4 | varredura de ausência | Feature | `tests/Kit/VersaoNoRodapeTest.php` | M18 |
| CT-14 | cada painel obedece ao que foi gravado | R5 | matriz painel × chave | Livewire+Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M21, M22 |
| CT-15 | desligar pela tela faz efeito no request seguinte | R5 | rastreio por request | Livewire+Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M20 |
| CT-16 | o alerta nasce ligado (`@premissa`) | R5 | valor do requisito, lido do arquivo | Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | — (premissa) |
| CT-17 | formas de env booleana do alerta | R5 | EP | Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M23 |
| CT-18 | cada propriedade nos três lugares, com a chave dela | R6 | invariante por reflexão | Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M25, M26 |
| CT-19 | nenhuma das três é segredo | R6 | invariante | Feature | `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` | M27 |
| CT-20 | em cada painel o avatar é gerado pela aplicação | R7 | matriz painel | Feature | `tests/Kit/AvatarDeIniciaisTest.php` | M28, M58 |
| CT-21 | nenhum endereço de imagem aponta para fora | R7 | rastreio de ausência com destinatário | Feature | `tests/Kit/AvatarDeIniciaisTest.php` | M29, M58 |
| CT-22 | quem tem foto continua com a foto | R7 | par | Feature | `tests/Kit/AvatarDeIniciaisTest.php` | M30 |
| CT-23 | cada forma de nome produz as iniciais | R8 | EP / normalização | Feature | `tests/Kit/AvatarDeIniciaisTest.php` | M32, M33, M34, M36, M37 |
| CT-24 | nome com marcação não quebra a imagem | R8 | EP de dado hostil | Feature | `tests/Kit/AvatarDeIniciaisTest.php` | M35 |
| CT-25 | registro sem nome não lança | R8 | EP nulo | Feature | **fundido em CT-23** — ver D5 | M36 |
| CT-26 | cada operação de fronteira grava antes e depois | R9 | estado × operação + efeito | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M38, M39, M42 |
| CT-27 | a desativação pela tela também grava | R9 | efeito pela UI | Livewire | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M42 |
| CT-28 | operação que não muda nada não muda coluna, não grava nem loga | R9 | células `❌` da matriz | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M40, M60 |
| CT-29 | editar o nome não arrasta a fronteira, em nenhum estado | R9 | isolamento de efeito | Livewire | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M41 |
| CT-30 | campo comum continua na trilha | R10 | par | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M43 |
| CT-31 | a linha do model sem override traz só o editável | R10 | par | Feature | `tests/Kit/TrilhaDeEstadoDaContaTest.php` | M44 |
| CT-32 | a constraint do Filament é caret na série 5, e só nela | R11 | EP da constraint | Feature | `tests/Kit/PacotesRodada2Test.php` | M46, M47, M61 |
| CT-33 | os dez têm veredito do vocabulário, pelo nome Composer | R12 | EP por pacote | Feature | `tests/Kit/PacotesRodada2Test.php` | M48, M49 |
| CT-34 | nenhum dos dez entrou nas dependências | R12 | ausência com alvo | Feature | `tests/Kit/PacotesRodada2Test.php` | M50 |
| CT-35 | os quatro arquivos citam a linha que o vendor confirma | R13 | medição do vendor | Feature | `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` | M51, M52 |
| CT-B01 | o navegador bloqueia a saída com formulário sujo | R5 | JS executado | Browser | `tests/Browser/AlertaDeAlteracoesNaoSalvasTest.php` | M24 |
| CT-B02 | nenhuma requisição sai para o domínio de terceiro | R7 | rede observada | Browser | `tests/Browser/AvatarDeIniciaisTest.php` | M29 |

---

## Reconciliação — este `04` × os arquivos de teste

Feita depois da implementação e depois dos primeiros testes, como a `## Degradação declarada`
previa. Ela roda **nos dois sentidos**: todo `[CT-nn]` que existe num arquivo de teste está nesta
tabela, e todo CT deste arquivo aponta um caso existente ou declara por que não tem.

O comando que a fecha é `vendor/bin/pest tests/Kit --compact`.

### Sentido 1 — CT → caso de teste

| CT | Onde está | Estado |
|---|---|---|
| CT-01 | `VersaoNoRodapeTest` › `[CT-01] compoe o rodape conforme as duas chaves` (4 datasets) e `[CT-01] nao renderiza o elemento quando nao ha o que mostrar` | já existia, etiquetado; **linha 3 invertida** — ver Divergência D1 |
| CT-02 | `VersaoNoRodapeTest` › `[CT-02] mostra a versao do sistema e nao a do kit` | **novo** |
| CT-03 | `VersaoNoRodapeTest` › `[CT-03] mostra a versao do sistema nos tres paineis` | já existia; oráculo trocado do documento inteiro para o recorte do rodapé |
| CT-04 | `VersaoNoRodapeTest` › `[CT-04] escapa a marcacao html da versao gravada` | **novo** |
| CT-05 | `VersaoNoRodapeTest` › `[CT-05] nao mostra versao nenhuma para quem nao entrou` (5 datasets) | já existia com 3 linhas; **+2** (`/login` e a recuperação de senha) |
| CT-06 | `VersaoNoRodapeTest` › `[CT-06] mostra ao autenticado a mesma versao que o visitante nao ve` | **novo** |
| CT-07 | `VersaoNoRodapeTest` › `[CT-07] nasce com a exibicao da versao do kit desligada…` | **novo** |
| CT-08 | `VersaoNoRodapeTest` › `[CT-08] faz a versao do kit acompanhar a do sistema…` | **novo** |
| CT-09 | `[CT-09]` (par negativa/afirmativa) | escrito no ciclo 2, **vermelho antes** da correção de `config/kit.php`. Ver D2 |
| CT-10 | `VersaoNoRodapeTest` › `[CT-10] exibe no rodape exatamente o que foi gravado na tela` (5 datasets) | **novo**; a linha dos espaços nas bordas foi corrigida — ver D3 |
| CT-11 | `VersaoNoRodapeTest` › `[CT-11] recusa a tela de configuracoes a quem perdeu a permissao, sem recusar o painel` | **novo**. O 403 sozinho já era coberto, por outras personas, em `ConfiguracoesDoKitTelaTest` (wiki `settings-do-kit`); o que é novo é o par com a saída do estado de erro |
| CT-12 | `VersaoNoRodapeTest` › `[CT-12] mantem a versao do ambiente quando a propriedade nao tem linha no banco` | **novo** |
| CT-13 | `VersaoNoRodapeTest` › `[CT-13] nao le o git para resolver a versao` | já existia varrendo só a blade; ampliado para `app/`, `config/` e `resources/views/`, com filtro de comentário |
| CT-14 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-14] leva ate cada painel o valor gravado na tela` (6 datasets) | reescrito a partir de `leva o valor gravado na tela ate o painel`, que cobria 1 das 6 células |
| CT-15 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-15] muda de resposta no mesmo processo, sem remontar o painel` | já existia, etiquetado |
| CT-16 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-16] nasce com o alerta ligado no arquivo de configuracao` | **novo** |
| CT-17 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-17] respeita o vocabulario do env na chave do alerta` (5 datasets) | **novo** |
| CT-18 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-18] declara as propriedades novas com a chave de config correspondente` | já existia afirmando só presença; ganhou a linha semeada e a chave de config depois do alinhamento |
| CT-19 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-19] nao trata as chaves novas como segredo` | já existia; ganhou a leitura do `payload` cru |
| CT-20 | `AvatarDeIniciaisTest` › `[CT-20] nao pede o avatar padrao a nenhum dominio externo` (3 painéis) | já existia, etiquetado |
| CT-21 | `AvatarDeIniciaisTest` › `[CT-21] nao aponta nenhuma imagem da pagina para fora da aplicacao` | **novo** |
| CT-22 | `AvatarDeIniciaisTest` › `[CT-22] mantem a foto de quem enviou uma, sem trocar por iniciais` | **novo** |
| CT-23 | `AvatarDeIniciaisTest` › `[CT-23] desenha as iniciais do nome` (9 datasets) e `[CT-23] nao quebra com nome vazio` | já existia com 7 linhas; **+2** (quatro termos e nome sem caixa). As linhas "vazio" e "só espaços" vivem no segundo caso, com asserção própria |
| CT-24 | `AvatarDeIniciaisTest` › `[CT-24] escapa caractere que quebraria o svg` | já existia; ganhou a asserção "nenhum elemento além dos que o desenho usa". Ver D4 |
| CT-25 | — | **fundido em CT-23**: inexpressável por tipo. Ver D5 |
| CT-26 | `TrilhaDeEstadoDaContaTest` › `[CT-26] grava o antes e o depois de cada operacao de fronteira` (4 datasets) | reescrito a partir de três casos soltos, que cobriam 3 das 4 células e chamavam `forceFill` no lugar de `aprovar()` |
| CT-27 | `TrilhaDeEstadoDaContaTest` › `[CT-27] registra na trilha a desativacao feita pela acao da listagem` | **novo** |
| CT-28 | `TrilhaDeEstadoDaContaTest` › `[CT-28] nao muda coluna, nao grava linha e nao loga…` (5 datasets) | **novo**; a 6ª célula não tem linha — ver D6 |
| CT-29 | `TrilhaDeEstadoDaContaTest` › `[CT-29] registra so o nome ao editar o nome, em qualquer estado` (3 estados) | **novo** |
| CT-30 | `TrilhaDeEstadoDaContaTest` › `[CT-30] mantem na trilha a alteracao de um campo comum` | **novo** |
| CT-31 | `TrilhaDeEstadoDaContaTest` › `[CT-31] mantem fora da trilha a coluna nao declarada dos models sem override` (4 models) | **novo** |
| CT-32 | `PacotesRodada2Test` › `[CT-32] declara o filament em caret na serie 5 e so nela` | **novo** (arquivo novo) |
| CT-33 | `PacotesRodada2Test` › `[CT-33] registra o veredito de cada um dos dez pacotes, pelo nome composer` (10 datasets) | **novo** |
| CT-34 | `PacotesRodada2Test` › `[CT-34] nao tem nenhum dos dez pacotes avaliados nas dependencias` | **novo** |
| CT-35 | `CabecalhoDoMenuDoUsuarioTest` › `[CT-35] mantem USER_MENU_BEFORE fora do dropdown…` | já existia medindo o vendor; ganhou a citação nos quatro arquivos do kit. Ver D7 |
| CT-36 | `ConfiguracoesDoKitTest` › `liga o alinhamento no provider da aplicacao e em nenhum painel` | **coberto por caso de outra wiki** (`settings-do-kit`), na forma ESTRUTURAL que L8 prevê. Não duplicado. Ver D8 |
| CT-37 | `VersaoNoRodapeTest` › `[CT-37] renderiza o ponto de extensao do rodape tambem na tela de login` | **novo** |
| CT-38 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-38] deixa o banco vencer o env…` + `[CT-38] leva a versao gravada na tela ate a config`; `VersaoNoRodapeTest` › `[CT-38] apaga o rodape ao limpar o campo…` | os dois primeiros já existiam (só em `config()`); o terceiro é **novo** e fecha no rodapé |
| CT-39 | `AlertaDeAlteracoesNaoSalvasTest` › `[CT-39] semeia as tres propriedades novas com o valor de fabrica prometido` | **novo** |
| CT-40 | `TrilhaDeEstadoDaContaTest` › `[CT-40] registra a desativacao no canal de autenticacao e na trilha…` | **novo**. O log sozinho já era afirmado em `SituacaoDaContaTest` (wiki `situacao-da-conta`); o que é novo é a conjunção dos dois efeitos |
| CT-41 | `VersaoNoRodapeTest` › `[CT-41] nao grava a versao do sistema para quem perdeu a permissao` | **novo**, com a cobertura parcial de L7 declarada no docblock |
| CT-42 | `TrilhaDeEstadoDaContaTest` › `[CT-42] registra na trilha a exclusao logica, a partir de qualquer estado` (3 estados) | **novo** |
| CT-43 | `VersaoNoRodapeTest` › `[CT-43] mostra a versao do kit no comando de informacoes…` | **novo**; mora fora do arquivo sugerido — ver D9 |
| CT-44 | `VersaoNoRodapeTest` › `[CT-44] grava a versao longa inteira ou recusa, nunca truncada` | **novo**; a premissa nº 3 está resolvida pela implementação — ver D10 |
| CT-45 | `AvatarDeIniciaisTest` › `[CT-45] desenha as iniciais do registro recebido…` | **novo**, sem a listagem que o cenário arranja — ver D11 |

**Contagem, conferida linha a linha**: 45 CT =
**28 que não tinham teste nenhum e ganharam um**
(CT-02, CT-04, CT-06, CT-07, CT-08, CT-10, CT-11, CT-12, CT-16, CT-17, CT-21, CT-22, CT-27, CT-28,
CT-29, CT-30, CT-31, CT-32, CT-33, CT-34, CT-37, CT-39, CT-40, CT-41, CT-42, CT-43, CT-44, CT-45)
\+ **14 que já tinham caso**, todos etiquetados, e **nove deles reforçados** — dois reescritos
(CT-14 e CT-26, que cobriam 1 de 6 e 3 de 4 células), seis com asserção nova (CT-03, CT-13, CT-18,
CT-19, CT-23, CT-24) e um que ganhou um terceiro caso (CT-38)
\+ **1 coberto por caso de outra wiki** (CT-36, em `ConfiguracoesDoKitTest`)
\+ **1 sem teste** (CT-25, fundido em CT-23 — ver D5). CT-09 ganhou teste no ciclo 2 do quality gate, depois que o defeito que ele reprovava foi corrigido.
`28 + 14 + 1 + 2 = 45`.

Em arquivos de teste isso são **42 IDs `[CT-nn]` distintos** — os 45 menos CT-09, CT-25 e CT-36 —,
e o conjunto é conferível com
`grep -o "\[CT-[0-9]*\]" tests/Kit/{VersaoNoRodape,AvatarDeIniciais,AlertaDeAlteracoesNaoSalvas,TrilhaDeEstadoDaConta,PacotesRodada2,CabecalhoDoMenuDoUsuario}Test.php | sort -u`.

### Sentido 2 — caso de teste → CT

Os casos desta feature que **não** implementam nenhum CT deste arquivo. Todos mantidos: nenhum é
redundante, e três deles cobrem proposições que a derivação não alcançou.

| Caso | Arquivo | Por que não tem CT |
|---|---|---|
| `mantem as iniciais com byte utf-8 invalido no nome` | `AvatarDeIniciaisTest` | nasceu de um `/code-review` do diff, e mata um mutante que este `04` não previu: `htmlspecialchars()` com flags explícitas devolve string VAZIA diante de byte inválido, e o avatar vira um quadrado sem letra. É partição de `name` que a varredura D de SFDIPOT não listou |
| `desenha texto branco sobre fundo escuro fixo` | `AvatarDeIniciaisTest` | a pergunta nº 7 decide que **aparência não é requisito**, e nenhum cenário afirma cor de propósito. O caso guarda uma decisão do `02` (não usar a cor primária, porque dentro de `/app/{slug}` a paleta é da organização e o texto branco viraria aposta) |
| `nasce ligado nos tres paineis` | `AlertaDeAlteracoesNaoSalvasTest` | mede o default do ARQUIVO chegando aos três painéis — um degrau entre CT-16 (que para em `config/kit.php`) e CT-14 (que grava antes de perguntar). Nenhum dos dois o cobre |
| `mantem fora da trilha a coluna tecnica que ninguem declarou` | `TrilhaDeEstadoDaContaTest` | CT-31 faz a mesma proposição nos quatro models **sem** override; este cobre o model que **tem** override, que é o caso que aquele `Esquema` não alcança |
| `audita o fillable mais as colunas de fronteira de acesso do usuario` e `mantem a auditoria no fillable puro para quem nao estende a lista` | `FundacaoTest` | são os dois casos que a `## Degradação declarada` desqualifica: afirmam sobre a LISTA devolvida por `getAuditInclude()`, não sobre a linha gravada. Quem afirma sobre a linha é CT-26 e CT-31. Mantidos porque a asserção de ORDEM é barata e pega a substituição antes de o auditor entrar em cena |
| a âncora `toHaveCount(54)` | `KitInfoTest` › `[CT-06]` | aquele `[CT-06]` é da wiki `kit-info`, não desta. A âncora subiu de 51 para 54 por causa das três propriedades desta entrega, e é o ponto em que uma propriedade nova obriga a decisão |

### Divergências encontradas, com destino

| # | O que | Destino |
|---|---|---|
| D1 | **Premissa nº 2 negada pela implementação.** Com a versão do sistema vazia e o toggle do kit ligado, o rodapé **mostra** a versão do kit (`resources/views/filament/versao-do-kit.blade.php` monta `$partes` com `array_filter` e renderiza o que sobrar). O `04` adotara a direção fechada e escrevera o `Se negado`; a linha 3 de CT-01 está invertida aqui e no teste. O invariante continua valendo e agora **é asserível**, o que fecha a pergunta nº 9: o rótulo `kit ` existe e distingue as duas versões | **especificação** (decisão do solicitante: confirmar a direção aberta ou pedir a fechada) |
| D2 | **M11 vivo.** `config/kit.php:250` lê a chave com `(bool) env('KIT_EXIBIR_VERSAO', false)`. Medido: `off`, `no` e `ligar` resolvem para **true** — isto é, LIGAM a exibição da versão do kit. O comentário do arquivo justifica o `(bool)` pelo caso da chave presente-e-vazia, que de fato converge com default `false`; a partição do vocabulário (`off`/`no`) é outra e não está coberta. A chave irmã usa `BooleanoDoEnv` e passa em CT-17 | **implementação**. CT-09 ficou sem teste porque escrevê-lo deixaria a suíte vermelha por defeito de produção, e esta reconciliação não tinha mandato para mexer em `config/`. **Desfecho (ciclo 2)**: o CT foi escrito primeiro e ficou vermelho em `off`/`no`/`talvez`; só então `config/kit.php` passou a `BooleanoDoEnv::comPadrao(env('KIT_EXIBIR_VERSAO'), false)`. M11 morto |
| D3 | **CT-10, linha "espaços nas bordas".** O `04` esperava ` 2.4.0 ` gravado como `2.4.0`. O kit não apara, e **não aparar é o comportamento coerente com a própria regra** — ela se chama *sem alteração silenciosa*, e aparar é uma alteração silenciosa. A linha do `04` foi corrigida para esperar o valor com os espaços | **especificação** (o `04` estava errado) |
| D4 | **CT-24, "o único texto dentro dela é AZ".** O nome `<b>Ana</b> "Zé" & Cia` produz as iniciais `<"`, não `AZ`: o kit **escapa**, não **remove** marcação, e nada no requisito pede remoção. Os datasets do teste usam as três cargas que discriminam (`<`, `&`, aspas) e afirmam a inicial CRUA lida de volta pelo parser | **especificação** (o `Então` assumia stripping) |
| D5 | **CT-25 é inexpressável.** O provider não lê `name` do registro: recebe o nome de `Filament::getNameForDefaultAvatar()` (`FilamentManager.php:323`), declarado `: string`, que para usuário passa por `User::getFilamentName(): string`. Nome nulo estoura `TypeError` **antes** de chegar ao kit, e nenhuma implementação do provider muda isso. A partição alcançável é a string vazia, que está em CT-23 | **especificação**: CT-25 fundido em CT-23 |
| D6 | **Célula `excluída × excluir` de CT-28 sem linha.** `delete()` sobre um registro já excluído logicamente roda o `UPDATE` de novo, dispara `deleted` de novo, e o observer da lixeira estoura `UNIQUE constraint failed: recycle_bin_items.model_type, recycle_bin_items.model_id`. Pela tela o estado é inalcançável (a ação de exclusão não vê registro excluído, e a lixeira restaura antes), então não é defeito observável hoje — é lacuna de idempotência do model | **implementação** (severidade baixa; nenhum caminho de UI o alcança) |
| D7 | **CT-35, "sem número de linha".** Lido como propriedade do ORÁCULO, não como exigência sobre os arquivos do kit: os quatro citam `user-menu.blade.php:43`/`:97`, e casar o número tornaria o caso refém do vendor num kit obrigado a aceitar toda atualização da série. A asserção é a presença do caminho | **especificação** (leitura fixada) |
| D8 | **CT-36 na forma estrutural.** L8 previa as duas formas e proibia adotar a fraca em silêncio: adotada a estrutural, que já existe em `ConfiguracoesDoKitTest` desde a wiki `settings-do-kit`, com a justificativa escrita no próprio caso (o falsificador comportamental exigiria um processo artisan separado). Não duplicado | **não-defeito**, declarado |
| D9 | **CT-43 fora do arquivo sugerido.** O índice manda `tests/Kit/KitInfoTest.php`, que carrega os `[CT-nn]` da wiki `kit-info` (até CT-17): um `[CT-43]` no meio deles seria ambíguo nos dois sentidos da reconciliação. Mora em `VersaoNoRodapeTest`, junto com o resto de R3 | **não-defeito** (colisão de namespace de IDs entre wikis) |
| D10 | **Premissa nº 3 resolvida pela implementação.** O campo declara `->maxLength(50)` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:266`), então o kit escolheu o **ramo da recusa**. O `Então` disjuntivo de CT-44 continua correto e o teste exerce os dois ramos, mas a pergunta nº 3 deixa de estar em aberto | **especificação** (pergunta respondida pelo código; registrar o limite no `00` se ele for para valer) |
| D11 | **CT-45 sem a listagem.** Nenhuma listagem do kit renderiza avatar padrão: as duas usam `ImageColumn::make('avatar_url')` **sem** `defaultImageUrl()`, de propósito, para que a célula de quem não enviou foto fique vazia (`app/Filament/Admin/Resources/Users/UserResource.php:179`). Não existe hoje superfície renderizada em que "a pessoa da linha" seja diferente de quem está autenticado. O par discriminante foi montado onde M62 vive: duas pessoas sem foto, uma terceira autenticada, o provider chamado com cada registro | **especificação** (o cenário arranja uma superfície que o produto não tem) |

### `[CT-38]` rotula três casos em dois arquivos, e isso é deliberado

Achado do ciclo 2 do quality gate (QA-11). Os três implementam **a mesma regra** — *o banco vence o
`.env`, inclusive quando o banco está vazio* — em **camadas diferentes**, e é a camada que justifica
a repetição do ID:

| Arquivo | Caso | Camada |
|---|---|---|
| `AlertaDeAlteracoesNaoSalvasTest` | deixa o banco vencer o env, mesmo vazio | config (`aplicarNaConfig()`) |
| `AlertaDeAlteracoesNaoSalvasTest` | leva a versão gravada na tela até a config | config, sentido inverso |
| `VersaoNoRodapeTest` | apaga o rodapé ao limpar o campo, mesmo com a versão do ambiente preenchida | HTTP (o efeito visível) |

Renumerar os dois primeiros inventaria IDs que o `04` não tem, e fundi-los num só perderia o par —
o segundo existe justamente para impedir que o primeiro fique verde numa implementação que ignore a
propriedade. Fica registrado aqui em vez de corrigido.

### O que a reconciliação NÃO mudou

As oito lacunas declaradas (L1…L8) continuam como estão. Nenhuma delas ganhou cenário, e duas
ganharam forma operacional no teste: L5 e L6 estão citadas nos docblocks de `rodapeDe()` e de
CT-37, para que a próxima pessoa saiba o que aquele recorte não prova. A pergunta nº 9 saiu do
impasse por medição (ver D1); as nº 3 e nº 5 foram respondidas pelo código (D10 e D6); as demais
continuam com o solicitante.

---

## Pós-implementação

- [ ] `vendor/bin/pest tests/Kit --compact` — os 45 CT
- [ ] `composer test:kit` — regressão obrigatória (a entrega toca os três `PanelProvider`, a trait
      usada por cinco models e a classe de Settings)
- [ ] `composer test:browser` — os 2 CT-B, em série
- [ ] `vendor/bin/pest tests/Kit --mutate --path=app/Support` e `--path=app/Traits` — o mutation
      score é **piso de qualidade de assertion**, não medida de cobertura do requisito. Ele é cego
      às três lacunas declaradas acima, porque nenhuma delas tem código para mutar
- [ ] Mutante sobrevivente traduzido de volta em lacuna de derivação e convertido em cenário novo
      **aqui**, antes de virar código de teste
- [x] Sincronia nos dois sentidos: todo `[CT-nn]` do arquivo de teste existe neste `04`, e todo CT
      deste índice aponta um teste existente ou declara "fundido em CT-nn". **Atenção especial aos
      casos que já existem em `tests/Kit/FundacaoTest.php`** — ver `## Degradação declarada`.
      **Feita**: `## Reconciliação`, com as duas tabelas e as onze divergências (D1…D11)
- [ ] Contagem do cabeçalho recalculada

**Estado da reconciliação**: 45 CT · 28 com teste novo · 14 já cobertos, etiquetados e nove deles
reforçados · 1 coberto por caso de outra wiki (CT-36) · 2 sem teste (CT-09, por defeito de
produção; CT-25, fundido em CT-23). Dois achados de implementação saíram dela e **não** foram
consertados aqui, por falta de mandato: **M11 vivo** em `config/kit.php:250` (D2) e a exclusão
lógica repetida estourando no observer da lixeira (D6).
