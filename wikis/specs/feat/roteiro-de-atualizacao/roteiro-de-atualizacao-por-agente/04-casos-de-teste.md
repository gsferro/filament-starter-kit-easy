# Casos de Teste — Roteiro de atualização por prompt para agentes

> Requisito: `00-requisito.md` (Texto Original, Adendos 1 a 4, P-01) · Plano: `01-plano-acao.md` (só paths, lista de seções do contrato, `## Superfície de UI` e as decisões D7–D19) · `02`: só `## Superfície Livewire`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (a feature ainda não existe; `app/` não foi lido — a única linha consultada é a citação de fato de R8).
> Revisão adversarial: **FEITA — 2 rodadas, 37 achados (rodada 1: 18, 5 altos; rodada 2: ADV-19…ADV-37), teto de 2 rodadas atingido**. Destino de cada achado e lacunas restantes em `## Fechamento da Revisão Adversarial`. Não há rodada 3: o que o fechamento da rodada 2 criou (CT-25…CT-29, mutantes M95…M122) não foi revisto por contexto independente — débito declarado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Guarda do CI — existência, contrato, origem/destino, cópia local (R1, R2, R3, R7, R8) | 2 | 2 | 4 | padrão |
| Conteúdo do roteiro que o agente executa no projeto do dono — link, padrão, decisões, conflito de arquivo editado, autossuficiência, dados do banco (R4, R5, R6, R15, R16, R17, R19) | 2 | 3 | 6 | padrão (Impacto 3: o agente altera o projeto e o banco de um terceiro) |
| Workflow de release na tag (R9) | 2 | 2 | 4 | padrão |
| Checklist de release — redação das obrigações (R10, R11, R12, R18) | 1 | 2 | 2 | mínimo |
| Pontos de entrada — padrão do link e pedido (R13) | 1 | 1 | 1 | mínimo |
| Cenário pós-release com agente, processo manual (R14) | 3 | 3 | 9 | completo |

- Técnicas aplicadas: EP (partições inválidas isoladas, uma por linha), BVA sobre a sequência de versões publicadas e sobre a exceção da P-01, tabela de decisão (link raw, padrão do link, link revisado), exemplo discriminante (cópia local, ordenação de versão, corpo copiado), não-efeito com alvo real (R14)
- Técnica escalada: R3 usa BVA 3-valores numa área `padrão` — BVA 2-valores não separa "aceita salto" de "aceita a própria versão".
- Cenários: 29 · Regras: 19 · Mutantes previstos: 122 · Sem matador: 13
- Cenários de processo (fora da suíte, não contados acima): 4 (`CP-01`…`CP-04`)
- Numeração: as regras R15–R19 nasceram no fechamento da revisão e ficam ao lado da regra-mãe (R15 e R16 depois de R4, R19 depois de R5, R17 depois de R6, R18 depois de R11); os IDs de CT e de mutante antigos não mudaram.
<!-- derivado por grep -c (comandos ao fim do arquivo, seção "Contagem do cabeçalho"); recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | documentos: contrato `wikis/atualizacoes/README.md`, roteiro `wikis/atualizacoes/v0.52.0.md`, seção com marcadores em `wikis/agentes-e-skills.md`, `wikis/checklist-de-release.md`, `docs/{pt,en}/comecar/atualizando-o-projeto.md`; um teste `tests/Kit/RoteiroDeAtualizacaoTest.php`; um passo em `.github/workflows/release.yml`. Nenhum model, migration, rota ou classe da aplicação (RQ-16) | CT-01, CT-04 |
| F | (1) a guarda do CI reprova o commit de versão sem roteiro válido; (2) o workflow de release reprova a tag sem roteiro ou sem o contrato; (3) o checklist obriga a etapa, a lista de defaults mudados e o cenário pós-release; (4) o roteiro guia um agente sem destruir dados | CT-02…CT-15, CT-20…CT-23, CT-16…CT-18, CT-24, CP-01…CP-04 |
| D | marcador `config('kit.version')` (pode vir vazio); seções `## [x.y.z]` do `CHANGELOG.md` (com e sem `[Unreleased]`, dígito duplo, sem a seção do marcador, com registro de revisão); texto do roteiro (seções, link, padrão, decisões 0/1/N, comandos destrutivos, corpo igual ao da versão anterior); cópia entre marcadores; dados do dono no banco | CT-02, CT-05, CT-06, CT-08, CT-10, CT-13, CT-20, CT-21, CT-23, CP-01 |
| I | CI da `main` (suíte Pest); workflow de release no push da tag; o agente lendo a URL raw (dada no pedido ou montada pelo padrão da cópia local); o dono lendo `wikis/agentes-e-skills.md` e `docs/` | CT-01, CT-15, CT-09, CT-19, CP-01, CP-04 |
| P | Windows do mantenedor (Git Bash/PowerShell, EOL CRLF) × Ubuntu do CI; `bash` para executar o passo do workflow; `git` para `check-attr`; arquivos que não viajam (`CHANGELOG.md`, `.github/`, `docs/`, `wikis/specs/` são `export-ignore` no `.gitattributes`); ambiente do agente (memória de usuário, skills globais) | CT-13, CT-14, CT-15, CT-17; skip fora da árvore em todo caso |
| O | mantenedor bumpando o marcador (release e hotfix); agente de capacidade variável em sessão nova; dono que não responde, ou que responde diferente do default; projeto fora da versão de origem; pedido sem link | CT-02, CT-10, CT-13, CP-01…CP-04 |
| T | o intervalo entre o bump e a tag (a guarda age antes, RQ-22; o workflow depois, RQ-23/RQ-27); a v0.52.0 cujo roteiro nasce depois da tag (P-01); revisão do roteiro depois da publicação (RQ-24, RQ-28, RQ-32); `[Unreleased]` empurrando as seções | CT-06, CT-08, CT-18, CT-21, CT-22 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Todo marcador de versão tem o seu roteiro e a sua seção no CHANGELOG, release ou hotfix; os roteiros publicados continuam na pasta; na árvore do kit a guarda roda | guarda (padrão) | RQ-01, RQ-05, RQ-10, RQ-22, RQ-28, RQ-29, P-01 | EP | CT-01, CT-02, CT-27, CT-28 |
| R2 — O roteiro carrega o contrato: as nove seções, com corpo, na ordem que o agente segue, e não copiado da versão anterior | guarda (padrão) | RQ-02, RQ-03, RQ-04, RQ-22, RQ-25; D13 | EP + exemplo discriminante | CT-03, CT-04, CT-20 |
| R3 — A origem declarada é a versão anterior publicada e o destino é o marcador | guarda (padrão) | RQ-18, RQ-22 | BVA 3-valores + EP | CT-05, CT-06, CT-07 |
| R4 — A `## Identificação` traz o link raw do próprio roteiro, fixado na tag destino (main só na v0.52.0) | conteúdo (padrão, I=3) | RQ-19, P-01; D9 | tabela de decisão + BVA na exceção | CT-08 |
| R15 — O roteiro ensina o padrão do link da próxima versão, com a conferência na main e a parada na dúvida | conteúdo (padrão, I=3) | RQ-20, RQ-32; D9 | tabela de decisão | CT-09, CT-22 |
| R16 — Depois de uma revisão registrada, o link aponta para a correção e o roteiro traz a marca da revisão | conteúdo (padrão, I=3) | RQ-24, RQ-28, RQ-32; D17, D19 | tabela de decisão | CT-21 |
| R5 — Toda decisão do dono traz pergunta, recomendação da release e default seguro, todo default mudado vira pergunta, e o relatório final registra a escolha | conteúdo (padrão, I=3) | RQ-25, RQ-26, RQ-30; D10, D15 | EP + cardinalidade 0/1/N | CT-10, CT-11, CT-25 |
| R19 — Conflito do `kit:update` num arquivo do kit editado pelo dono é decisão do dono, e sem resposta fica a versão do dono | conteúdo (padrão, I=3) | RQ-35 | EP | CT-26 |
| R6 — O roteiro é autossuficiente, invoca o `kit:update`, confere a versão de origem e não promete funcionar em todo agente | conteúdo (padrão, I=3) | RQ-06, RQ-13, RQ-15, RQ-17, RQ-18; D16 | EP | CT-12 |
| R17 — Nenhuma seção que o agente executa traz comando que destrói dados do banco | conteúdo (padrão, I=3) | RQ-31, RQ-33, RQ-34; D18 | EP | CT-23 |
| R7 — A cópia local entre os marcadores é idêntica ao roteiro da versão marcada | guarda (padrão) | RQ-21, RQ-15; D11 | EP + exemplo discriminante | CT-13 |
| R8 — O arquivo da cópia local viaja pelas duas rotas de entrega | guarda (padrão) | RQ-21 | EP sobre as rotas | CT-14 |
| R9 — O workflow de release reprova a tag sem roteiro ou sem as nove seções | workflow (padrão) | RQ-10, RQ-23, RQ-27; D8 | EP | CT-15, CT-29 |
| R10 — O checklist torna a etapa do roteiro obrigatória antes da tag, em release e hotfix, a partir do CHANGELOG e da wiki reconciliados, com a lista de defaults mudados | checklist (mínimo) | RQ-10, RQ-14, RQ-23, RQ-30 | EP | CT-16 |
| R11 — O checklist exige o cenário pós-release com agente sem contexto, em dois projetos, com cadência | checklist (mínimo) | RQ-11, RQ-13; D12 | EP | CT-17 |
| R18 — O checklist fixa o critério do cenário pós-release: estado dos cenários 3/4, customizações e dados preservados, cada default mudado perguntado | checklist (mínimo) | RQ-12, RQ-30, RQ-31 | EP | CT-24 |
| R12 — O checklist diz o que fazer quando o cenário pós-release reprova | checklist (mínimo) | RQ-24, RQ-28 | EP | CT-18 |
| R13 — Os pontos de entrada ensinam o padrão do link, a conferência na main e o pedido | pontos de entrada (mínimo) | RQ-01, RQ-20, RQ-32 | EP | CT-19 |
| R14 — Um agente em sessão nova, só com o roteiro, atualiza o projeto da versão anterior sem perder nada do dono, e para fora da origem | pós-release (completo) | RQ-06, RQ-11, RQ-12, RQ-17, RQ-18, RQ-20, RQ-25, RQ-26, RQ-30, RQ-31, RQ-35; D14 | EP (multi-organização, resposta do dono, versão de partida) + BVA da versão de partida + não-efeito com alvo real | CP-01…CP-04 (fora da suíte) |

Cláusulas sem regra própria, com o motivo:

- RQ-07 — substituída em parte por RQ-16; o resto ("+1 etapa pré-release") está em R10.
- RQ-08 — substituída por RQ-11 (R11, R14). RQ-09 — substituída por RQ-14 (R10).
- RQ-16 — cobertura parcial: a entrega (roteiro, guarda, checklist, workflow) está nas regras acima; o recorte "nada muda na aplicação nem no `kit:update`" é afirmação sobre o **diff desta entrega**, não comportamento que dura: não vira CT de suíte, e quem confere é o quality gate pelo `git diff --stat` contra a `main`.
- RQ-30 — coberta: CT-16 (o checklist exige a lista), CT-25 (a guarda confere cada item contra uma `Pergunta:`, e a lista ausente reprova — D15), CT-24 (critério do cenário), CP-01 (o estado do projeto). A **completude** da lista (todo default que mudou está nela) só o confronto manual com o diff entre as tags prova — lacuna declarada em R14 (M111).
- RQ-31, RQ-33, RQ-34 — cobertas: CT-23 (os cinco comandos, qualquer prefixo, texto bruto, toda seção fora de `## Cuidados`), CP-01 (registro do dono intacto).
- RQ-35 — coberta: CT-26 (a decisão existe no roteiro), CP-01 (o arquivo do dono intacto, sem resposta).

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Guarda de redação dos roteiros | R1, R2, R3, R4, R5, R6, R7, R15, R16, R17, R19 | Pest feature HTTP | existente — `tests/Kit/ChecklistDeReleaseTest.php` (suíte `Kit`, `TestCase` ligado em `tests/Pest.php`, `->skip(fn (): bool => ! naArvoreDoKit(), …)` por caso) | precisa de `config('kit.version')` com a aplicação de pé e lê arquivos da árvore; os controles negativos chamam os mesmos detectores, funções puras sobre o conteúdo (D7) | |
| Rotas de entrega da cópia | R8 | Pest feature HTTP | existente — `tests/Kit/ChecklistDeReleaseTest.php` `'[CT-07]'` (usa `git check-attr` com skip fora da árvore) | o observável é o atributo do git e a constante do comando de atualização | |
| Passo do workflow de release | R9 | Pest feature HTTP | nova no arquivo — `Process` executando com `bash` o bloco `run` do passo numa árvore temporária; sem `bash`, falha (D8) | o requisito afirma que o workflow **confere** (reprova): ler o YAML não falsifica "avisa e sai 0" | |
| Redação do checklist e dos pontos de entrada | R10, R11, R12, R13, R18 | Pest feature HTTP | existente — `tests/Kit/ChecklistDeReleaseTest.php` (mesmo molde de asserção de redação com skip) | é texto de processo lido por pessoa; a camada mais barata é ler o arquivo | |
| Cenário pós-release (processo) | R14 | — (fora da suíte) | nova — procedimento manual registrado no checklist, evidência no registro da release | o oráculo exige um agente LLM em sessão nova e `create-project` reais de tags publicadas; não há arnês determinístico (lacuna em R14) | |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Os títulos exatos das nove seções (`## Identificação` … `## Relatório final`) | o requisito determina as categorias (RQ-02, RQ-03, RQ-04, RQ-19, RQ-25, RQ-26); o PRD só deu o nome | aceito como **nome**; o oráculo é presença + corpo + ordem, não o conteúdo de cada seção |
| "Versão anterior publicada = a seção `## [` logo abaixo da do marcador no `CHANGELOG.md`" | mecanismo de RQ-18 | `@premissa` em CT-05/CT-06; o mecanismo descartado (tags do git) não é exercitado — o checkout do CI pode vir sem tags |
| `kit:update --from vORIGEM` e conferência "pela versão **e** pelos arquivos" | escolha de implementação do roteiro | o oráculo de RQ-17 é só "os passos invocam o `kit:update`" (CT-12) |
| Comandos para Git Bash e PowerShell | não está no `00` | fora do conjunto |
| "Sem citação `arquivo:símbolo:linha` no roteiro" | regra do projeto, não cláusula | já protegido por `tests/Kit/CitacoesDeCodigoTest.php` (existente) |
| Marcadores `<!-- roteiro-de-atualizacao:inicio -->` / `:fim` | nome do mecanismo de RQ-21 | detalhe de CT-13 |
| As formas das linhas `Origem:`/`Destino:`/`Link:` e dos rótulos das decisões (D9, D10) | mecanismo de RQ-18, RQ-19, RQ-25 | `@premissa` em CT-05, CT-08, CT-10 |
| D13 (corpo igual ao da versão anterior reprova) | decisão de desenho que fecha ADV-05; o requisito diz "o que foi feito **na versão**" (RQ-02) | CT-20, com a normalização de D11 |
| D14 (canários de arquivo do kit editado e de registro no banco) | mecanismo de RQ-12/RQ-31 | `@premissa` em CP-01 |
| D15 (`### Defaults mudados` no CHANGELOG), D16 (invocação em bloco de código), D17 (registro de revisão e SHA de 40), D18 (regex de comando destrutivo), D19 (marca `Roteiro revisado em {data}: {motivo}`) | mecanismos de RQ-30, RQ-17, RQ-28, RQ-34 e RQ-32 | `@premissa` em CT-25, CT-12, CT-21, CT-23 |
| Os canários concretos (um canal no `config/logging.php`, uma linha no `.env`) e os diretórios dos projetos | mecanismo de "canais e customizações preservados" (RQ-12) | `@premissa` em CP-01 |
| A cadência "a cada release menor, um segundo agente" | RQ-13 exige **uma** cadência declarada, não este valor | CT-17 afirma que a cadência existe, não o valor |
| Entrada em `CHANGELOG.md` `[Unreleased]` | asserção sobre o topo do CHANGELOG expira sozinha (`.ai/rules/testes.md`) | sem CT |
| Passo novo "no mesmo job e no mesmo `run` que define `tag`" | mecanismo de RQ-23 | detalhe de CT-15 |
| `docs/{pt,en}/comecar/atualizando-o-projeto.md` com o padrão do link | o requisito pede que o pedido traga o link (RQ-20), não onde o dono aprende | linhas de CT-19, perfil mínimo |

**Perguntas da derivação anterior — destino** (renumeradas pela sessão):

| Provisória | Destino | Efeito neste `04` |
|---|---|---|
| Q?1 | Q12 → RQ-27 | CT-15 ganha as linhas das nove seções (M82, M83) |
| Q?2 | Q13 → RQ-28 | R16, CT-21 |
| Q?3 | D7 | costura "Guarda de redação dos roteiros" |
| Q?4 | D8 | costura "Passo do workflow de release" |
| Q?5 | D9 (+ ADV-09: a ref do link exige o `v`) | CT-08, CT-09 |
| Q?6 | D10 (+ ADV-15: `Pergunta:` termina em `?`, todo rótulo tem valor) | CT-10 |
| Q?7 | D11 | CT-13, CT-20 |
| Q?8 | **retirada — resolvida por fato**: `wikis/agentes-e-skills.md` está na lista do comando, `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93`, entrada em `app/Console/Commands/KitUpdate.php:'wikis/agentes-e-skills.md':330` (conferido pela sessão e por `grep -n` nesta derivação) | CT-14 linha `kit:update` fica como guarda de regressão |
| Q?9 | Q14 → RQ-29 | CT-02 ganha a linha "sem a seção do marcador" (M4) |
| Q?10 | Q15 → D12 | M48 continua morto pelos testes existentes, que ficam intactos |

**Perguntas da rodada 1 da revisão — destino** (renumeradas pela sessão):

| Provisória | Destino | Efeito neste `04` |
|---|---|---|
| Q?1 | D15 | CT-25 (a guarda confere a lista); M89 ganha matador automatizado |
| Q?2 | D16 | `@premissa` de CT-12 |
| Q?3 | D17 (SHA de 40 hexadecimais, com host, repositório e caminho conferidos) | CT-21 ganha as linhas de SHA curto, branch e repositório errado |
| Q?4 | Q19 → RQ-33 (sim, o `migrate:rollback` é afim) | linha `migrate:rollback` de CT-23, M102 |
| (rodada 2) | Q20 → RQ-34 + D18; Q21 → RQ-35; D19 | CT-23 reescrito; R19/CT-26 e CP-01; CT-21 com a marca de revisão |

**Perguntas novas desta revisão** (provisórias `Q?n`; a sessão renumera a partir de Q22):

❓ Q?11 · raia: desenho · afeta: RQ-25, RQ-35 · depende de: —
D10 diz que versão sem decisão traz a frase "Nenhuma decisão do dono nesta versão.". Com RQ-35, todo roteiro tem ao menos a decisão do conflito em arquivo do kit editado pelo dono, e a frase fica falsa.
➡️ Recomendação: a frase passa a ser "Nenhuma outra decisão do dono nesta versão.", ao lado da decisão de conflito; CT-10 linha "0 decisões da versão" é escrita assim.

❓ Q?12 · raia: desenho · afeta: RQ-22 (ADV-31) · depende de: —
Como CT-27 prova que, na árvore do kit, nenhum caso do arquivo pula: inspeção da fonte (todo `->skip(` do arquivo é exatamente `fn (): bool => ! naArvoreDoKit()`), apoiada no `tests/Kit/ChecklistDeReleaseTest.php` `'[CT-19]'` (a sentinela vale `true` na árvore do kit), ou um subprocesso do Pest sobre o arquivo contando os pulados?
➡️ Recomendação: inspeção da fonte — o subprocesso roda o próprio CT-27 (recursão) e, no Windows, o Pest em subprocesso já deu falso resultado no kit.

## Setup Global

### Personas
- Nenhuma: não há usuário, papel nem rota. Os atores do Gherkin são a guarda do CI, o workflow de release, o mantenedor (leitor do checklist), o dono do projeto e o agente (só nos `CP-`).

### Fixtures
- **Árvore real**: os arquivos do checkout, lidos **dentro do corpo do caso** (nunca no escopo global nem no dataset — o `skip` roda depois da coleta). Só CT-01, CT-04 e CT-14 leem a árvore real (D7).
- **Texto corrompido**: um roteiro (ou CHANGELOG, ou cópia) conforme, com **uma** alteração por linha de `Exemplos` (partição inválida isolada), passado aos detectores como string.
- Valor do marcador: em CT-01 é o **valor efetivo lido** de `config('kit.version')`, sem ajuste do teste; nos controles negativos, o marcador é parâmetro do detector.

### Fakes
- Nenhum. CT-15 usa `Process` real com `bash`; CT-14 usa `git check-attr` real.

### Forma obrigatória (regras do projeto)
- Todo caso leva `->skip(fn (): bool => ! naArvoreDoKit(), '…')`: `CHANGELOG.md`, `.github/`, `docs/` e `wikis/specs/` são `export-ignore`, e o arquivo de teste viaja (`.ai/rules/testes.md` §Caso que lê `docs/`…).
- Asserção de **ausência** de CT-12 roda sobre o texto sem comentários `<!-- … -->`; a de presença, sobre o texto cru (`.ai/rules/testes.md` §Asserção de ausência). **Exceção: CT-23 roda sobre o texto bruto, com comentários** (RQ-34, D18) — ver Precedência.
- `toContain()` não recebe mensagem (`.ai/rules/testes.md`): mensagem de falha vai por `assertStringContainsString` ou `expect(...)->toBeTrue('…')`.

### Estratégia de DB
- A suíte `Kit` liga `RefreshDatabase` (`tests/Pest.php`, `->in('Kit')`); nenhum caso desta feature toca o banco.

### Precedência
- Nenhuma divergência entre a skill e `.ai/rules/`: as regras de `testes.md`, `kit.md` e `specs.md` lidas foram aplicadas como forma acima.
- **Divergência declarada, rule × requisito**: `.ai/rules/testes.md` §Asserção de ausência manda filtrar comentário; RQ-34 (literal: "Busca no texto bruto, com comentários") e D18 mandam o contrário em CT-23. Vence o requisito: a rule existe para o teste não reprovar pela documentação que cita o proibido, e aqui o agente lê o arquivo raw, comentário incluído — a citação legítima mora em `## Cuidados`, que CT-23 exclui.

---

## Regra R1 — Todo marcador de versão tem o seu roteiro e a sua seção no CHANGELOG, release ou hotfix; os roteiros publicados continuam na pasta; na árvore do kit a guarda roda

> `RQ-01`, `RQ-05`, `RQ-10`, `RQ-22`, `RQ-28`, `RQ-29`, `P-01` · perfil **padrão** · técnica: **EP** (classe da versão: menor, hotfix; estado do roteiro: ausente, vazio, só o da anterior; entrada ausente: pasta, marcador, seção do CHANGELOG; detector × árvore real)
> Estouro do teto de cenários (3 do perfil padrão + 1): CT-27 e CT-28 nasceram da rodada 2 da revisão adversarial (ADV-31, ADV-30), e cada um mata um mutante que nenhum outro alcança. Mecanismo de CT-27: Q?12.

```gherkin
# language: pt
Funcionalidade: Roteiro de atualização por agente

  Regra: o commit que declara uma versão no marcador fica vermelho sem o roteiro e sem a seção dela

    Esquema do Cenário: [CT-01] a árvore do kit passa em cada detector da guarda
      Dado a árvore do kit como está no commit
      E o marcador de versão lido de config('kit.version'), sem ajuste do teste
      Quando a guarda do CI aplica o detector "<detector>" ao roteiro "wikis/atualizacoes/v{marcador}.md" real
      Então o detector aprova

      Exemplos:
        | detector                                  | # regra |
        | existência e conteúdo                     | R1      |
        | seção do marcador no CHANGELOG            | R1      |
        | as nove seções, com corpo e ordem         | R2      |
        | corpo diferente do roteiro anterior       | R2      |
        | origem e destino                          | R3      |
        | link da identificação                     | R4      |
        | padrão do link e suas instruções          | R15     |
        | link e marca depois de revisão            | R16     |
        | decisões do dono                          | R5      |
        | defaults mudados viraram pergunta         | R5      |
        | relatório final                           | R5      |
        | decisão de conflito em arquivo do dono    | R19     |
        | autossuficiência e invocação do kit:update | R6     |
        | comando destrutivo do banco               | R17     |
        | cópia local idêntica                      | R7      |

    Cenário: [CT-27] na árvore do kit, nenhum caso da guarda pula
      Dado a árvore do kit, com ".github/" presente
      Quando a suíte do kit coleta os casos de "tests/Kit/RoteiroDeAtualizacaoTest.php"
      Então a condição de pulo de cada caso é só estar fora da árvore do kit
      E nenhum caso do arquivo sai como pulado

    Esquema do Cenário: [CT-28] o roteiro de toda versão publicada desde a v0.52.0 continua na pasta
      Dado o marcador de versão "0.53.0" e o CHANGELOG com "[0.53.0]", "[0.52.0]" e "[0.51.2]"
      E em "wikis/atualizacoes/" existe <conteudo_da_pasta>
      Quando a guarda do CI confere os roteiros publicados
      Então o resultado é "<resultado>"

      Exemplos:
        | conteudo_da_pasta                   | resultado | # partição                                     |
        | "v0.52.0.md" e "v0.53.0.md"         | aprova    | conforme (a v0.51.2 não tem roteiro: anterior ao mecanismo) |
        | só "v0.53.0.md"                     | reprova   | roteiro publicado removido, cujo link aponta para a main (P-01) |
```

    Esquema do Cenário: [CT-02] o commit de versão sem roteiro ou sem seção no CHANGELOG reprova, e diz o que falta
      Dado o marcador de versão "<marcador>"
      E em "wikis/atualizacoes/" existe <conteudo_da_pasta>
      E o CHANGELOG <changelog>
      Quando a guarda do CI confere a árvore
      Então a guarda reprova
      E a mensagem nomeia <falta>

      Exemplos:
        | marcador | conteudo_da_pasta           | changelog                     | falta                                                         | # partição                    |
        | 0.53.0   | só "v0.52.0.md", conforme   | com "[0.53.0]" e "[0.52.0]"   | "wikis/atualizacoes/v0.53.0.md" e "wikis/atualizacoes/README.md" | release menor, só a anterior  |
        | 0.52.1   | só "v0.52.0.md", conforme   | com "[0.52.1]" e "[0.52.0]"   | "wikis/atualizacoes/v0.52.1.md" e "wikis/atualizacoes/README.md" | hotfix, só a anterior         |
        | 0.53.0   | "v0.53.0.md" vazio          | com "[0.53.0]" e "[0.52.0]"   | "wikis/atualizacoes/v0.53.0.md" e "wikis/atualizacoes/README.md" | roteiro vazio                 |
        | 0.53.0   | nada: a pasta não existe    | com "[0.53.0]" e "[0.52.0]"   | "wikis/atualizacoes/v0.53.0.md" e "wikis/atualizacoes/README.md" | entrada ausente: pasta        |
        |          | só "v0.52.0.md", conforme   | com "[0.52.0]" e "[0.51.2]"   | o marcador de versão vazio                                    | entrada ausente: marcador     |
        | 0.53.0   | "v0.53.0.md", conforme      | só com "[0.52.0]" e "[0.51.2]" | a seção "## [0.53.0]" do CHANGELOG                          | entrada ausente: seção (RQ-29) |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a guarda só exige roteiro em versão `x.y.0` (hotfix escapa) | CT-02 (linha hotfix) | marcador `0.52.1` sem `v0.52.1.md`: "reprova"; o mutante aprova |
| M2 | a guarda aceita qualquer roteiro existente na pasta, sem cruzar com o marcador | CT-02 (linhas "só a anterior") | marcador `0.53.0` com só `v0.52.0.md`: "reprova"; o mutante aprova |
| M3 | a guarda confere só `file_exists`, sem conteúdo | CT-02 (linha vazio) | `v0.53.0.md` vazio: "reprova"; o mutante aprova |
| M4 | entrada ausente (pasta inexistente, marcador vazio ou seção do marcador ausente no CHANGELOG) faz a guarda pular em vez de reprovar | CT-02 (linhas de entrada ausente) | as três linhas: "reprova", e a da seção nomeia `## [0.53.0]`; o mutante sai verde sem asserção |
| M5 | a guarda monta o caminho sem o `v` (`wikis/atualizacoes/0.52.0.md`) | CT-01 | na árvore real, `v{marcador}.md` existe e o caminho do mutante não: o mutante reprova a árvore conforme |
| M113 — revisão adversarial (ADV-19) | um detector existe nos controles negativos mas nunca é aplicado ao roteiro real | CT-01 (a linha daquele detector) | a linha roda o detector sobre o arquivo real; sem ela, a árvore real nunca é conferida por ele — a lista de `Exemplos` é a das regras, uma linha por detector |
| M114 — revisão adversarial (ADV-31) | a condição de pulo vale também na árvore do kit (pulo fixo, ou condição extra como "sem `CHANGELOG.md`" invertida), e a guarda nunca roda no CI | CT-27 | toda condição de pulo é `! naArvoreDoKit()`; o mutante deixa a guarda verde pulando |
| M120 — revisão adversarial (ADV-30) | a guarda olha só o roteiro do marcador, e remover um roteiro publicado passa — o link para a main da v0.52.0 vira 404 | CT-28 (linha removido) | "reprova" nomeando `v0.52.0.md`; o mutante aprova |

---

## Regra R2 — O roteiro carrega o contrato: as nove seções, com corpo, na ordem que o agente segue, e não copiado da versão anterior

> `RQ-02`, `RQ-03`, `RQ-04`, `RQ-22`, `RQ-25` ("pergunta explícita **antes do passo**"), D13 · perfil **padrão** · técnica: **EP** (cada seção ausente isolada; presença falsa; corpo vazio; ordem) + **exemplo discriminante** (corpo copiado)

```gherkin
  Regra: o roteiro só passa com todas as seções do contrato, cada uma com corpo próprio da versão, e com o pré-voo e as decisões antes dos passos

    Esquema do Cenário: [CT-03] o roteiro fora do contrato reprova, nomeando a seção
      Dado um roteiro conforme da versão marcada
      E nele <defeito>
      Quando a guarda do CI confere o contrato do roteiro
      Então a guarda reprova
      E a mensagem nomeia "<secao>"

      Exemplos:
        | defeito                                                                    | secao                            | # partição               |
        | falta o título "## Identificação"                                          | ## Identificação                 | seção ausente            |
        | falta o título "## Antes de começar"                                       | ## Antes de começar              | seção ausente            |
        | falta o título "## O que muda nesta versão"                                | ## O que muda nesta versão       | seção ausente (RQ-02)    |
        | falta o título "## Cuidados"                                               | ## Cuidados                      | seção ausente (RQ-03)    |
        | falta o título "## Decisões do dono do projeto"                            | ## Decisões do dono do projeto   | seção ausente (RQ-25)    |
        | falta o título "## Passos"                                                 | ## Passos                        | seção ausente (RQ-04)    |
        | falta o título "## Como conferir"                                          | ## Como conferir                 | seção ausente (RQ-04)    |
        | falta o título "## Quando parar"                                           | ## Quando parar                  | seção ausente (RQ-04)    |
        | falta o título "## Relatório final"                                        | ## Relatório final               | seção ausente (RQ-26)    |
        | "Cuidados" só aparece citado numa frase do corpo de "## O que muda nesta versão" | ## Cuidados                | presença falsa           |
        | "## Cuidados" existe e o próximo título vem logo abaixo, sem corpo         | ## Cuidados                      | corpo vazio              |
        | "## Decisões do dono do projeto" vem depois de "## Passos"                 | ## Decisões do dono do projeto   | ordem (RQ-25)            |
        | "## Antes de começar" vem depois de "## Passos"                            | ## Antes de começar              | ordem do pré-voo (ADV-07) |

    Cenário: [CT-04] o contrato publicado e a guarda exigem as mesmas seções
      Dado o contrato "wikis/atualizacoes/README.md" da árvore do kit
      Quando a guarda do CI compara as seções que o contrato declara com as que ela exige
      Então os dois conjuntos são iguais, com as nove seções

    Esquema do Cenário: [CT-20] o roteiro que repete o conteúdo da versão anterior reprova
      Dado o roteiro conforme da versão anterior "v0.52.0"
      E um roteiro da versão marcada "0.53.0" em que <corpo>
      Quando a guarda do CI compara o roteiro com o da versão anterior
      Então o resultado é "<resultado>"

      Exemplos:
        | corpo                                                                                     | resultado | # partição                                |
        | "## O que muda nesta versão" e "## Cuidados" têm corpo próprio                            | aprova    | conforme                                  |
        | "## O que muda nesta versão" tem o mesmo corpo do roteiro "v0.52.0", só com CRLF no lugar de LF | reprova | copiado: "O que muda" (normalização D11) |
        | "## Cuidados" tem o mesmo corpo do roteiro "v0.52.0"                                      | reprova   | copiado: "Cuidados"                       |
        | as duas seções têm corpo próprio, e não existe roteiro da versão anterior na pasta         | aprova    | primeiro roteiro: nada a comparar         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | a seção é procurada por `str_contains` do nome em qualquer lugar do texto, não como título no início da linha | CT-03 (linha presença falsa) | "Cuidados" só em prosa: "reprova"; o mutante acha a palavra e aprova |
| M7 | a guarda confere o título e não o corpo | CT-03 (linha corpo vazio) | `## Cuidados` sem corpo: "reprova"; o mutante aprova |
| M8 | a guarda ignora a ordem entre decisões e passos | CT-03 (linha ordem) | decisões depois de passos: "reprova"; o mutante aprova |
| M9 | a lista da guarda esquece uma seção (ex.: `## Relatório final` ou `## Quando parar`) | CT-03 (a linha daquela seção) | roteiro sem a seção: "reprova" e nomeia a seção; o mutante aprova |
| M10 | o contrato publicado e a lista da guarda divergem (um título renomeado só num lado) | CT-04 | conjunto do README ≠ conjunto da guarda: o caso falha com a diferença |
| M59 — revisão adversarial (ADV-07) | a guarda confere só a ordem das decisões, e o pré-voo pode vir depois dos passos | CT-03 (linha ordem do pré-voo) | "reprova" e nomeia `## Antes de começar`; o mutante aprova |
| M60 — revisão adversarial (ADV-05) | a guarda não compara com o roteiro da versão anterior (roteiro copiado passa) | CT-20 (linha copiado: "Cuidados") | "reprova"; o mutante aprova o roteiro que fala da versão errada |
| M61 — revisão adversarial (ADV-05) | a guarda compara só uma das duas seções, ou compara bytes sem a normalização de D11 | CT-20 (linha copiado: "O que muda", com CRLF) | "reprova"; o mutante que olha só `## Cuidados`, ou que vê CRLF ≠ LF, aprova |
| M62 — revisão adversarial (ADV-05) | a guarda exige o roteiro da versão anterior para comparar | CT-20 (linha primeiro roteiro) e CT-01 | "aprova"; o mutante reprova — e reprova a árvore real, em que a v0.51.2 não tem roteiro |

- Estouro do teto de mutantes (5 do perfil padrão + 4): os quatro excedentes vêm da revisão adversarial e não contam para o teto (gate do passo 6, item 1). Estouro de cenários: nenhum (3).

---

## Regra R3 — A origem declarada é a versão anterior publicada e o destino é o marcador

> `RQ-18`, `RQ-22` · perfil **padrão** · técnica: **BVA 3-valores** sobre a sequência de seções publicadas do CHANGELOG (escalada: ver Perfil) + **EP** da forma do histórico
> `@premissa` (mecanismo, raia desenho): versão anterior publicada = a seção `## [x.y.z]` imediatamente abaixo da seção do marcador no `CHANGELOG.md`. Forma das linhas: D9.

```gherkin
  Regra: o roteiro cobre só a passagem da versão anterior publicada para a versão do marcador

    Esquema do Cenário: [CT-05] a origem é exatamente a versão anterior publicada
      Dado o marcador de versão "0.52.0"
      E o CHANGELOG com as seções "[Unreleased]", "[0.52.0]", "[0.51.2]" e "[0.51.1]", nessa ordem
      E um roteiro conforme que declara a origem "<origem>" e o destino "v0.52.0"
      Quando a guarda do CI confere a origem do roteiro
      Então o resultado é "<resultado>"

      Exemplos:
        | origem  | resultado | # borda                                 |
        | v0.51.2 | aprova    | borda: a anterior imediata              |
        | v0.51.1 | reprova   | borda−1: salto de duas versões          |
        | v0.52.0 | reprova   | borda+1: a própria versão destino       |
        | v0.51.3 | reprova   | fora da sequência: versão não publicada |

    Esquema do Cenário: [CT-06] a versão anterior sai da sequência publicada, qualquer que seja a forma do histórico
      Dado o marcador de versão "<marcador>"
      E o CHANGELOG com as seções <secoes>, nessa ordem
      E um roteiro conforme que declara a origem "<origem>" e o destino "v<marcador>"
      Quando a guarda do CI confere a origem do roteiro
      Então a guarda aprova

      Exemplos:
        | marcador | secoes                                        | origem  | # forma                                  |
        | 0.52.0   | "[Unreleased]", "[0.52.0]", "[0.51.2]"        | v0.51.2 | com [Unreleased] no topo                 |
        | 0.52.0   | "[0.52.0]", "[0.51.2]", "[0.51.1]"            | v0.51.2 | sem [Unreleased]                         |
        | 0.52.1   | "[Unreleased]", "[0.52.1]", "[0.52.0]"        | v0.52.0 | hotfix                                   |
        | 0.52.10  | "[0.52.10]", "[0.52.9]", "[0.52.8]"           | v0.52.9 | dígito duplo: 0.52.9 < 0.52.10           |

    Cenário: [CT-07] o destino declarado diferente do marcador reprova
      Dado o marcador de versão "0.52.0" e o CHANGELOG com "[0.52.0]" e "[0.51.2]"
      E um roteiro em "wikis/atualizacoes/v0.52.0.md" que declara a origem "v0.51.2" e o destino "v0.51.3"
      Quando a guarda do CI confere o destino do roteiro
      Então a guarda reprova
      E a mensagem nomeia o destino esperado "v0.52.0"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | a origem esperada é a 2ª seção `## [` do arquivo (a posição, não a seção abaixo do marcador) | CT-06 (linha com [Unreleased]) | o mutante espera `0.52.0` e reprova o roteiro conforme com `v0.51.2`; esperado "aprova" |
| M12 | a guarda só confere que a origem é menor que o destino | CT-05 (linhas borda−1 e fora da sequência) | `v0.51.1` e `v0.51.3`: "reprova"; o mutante aprova as duas |
| M13 | a guarda aceita origem igual ao destino (`<=` no lugar de `<`) | CT-05 (linha borda+1) | `v0.52.0` → `v0.52.0`: "reprova"; o mutante aprova |
| M14 | a versão anterior é calculada por ordenação de string das versões | CT-06 (linha dígito duplo) | o mutante põe `0.52.9` acima de `0.52.10` e reprova; esperado "aprova" com `v0.52.9` |
| M15 | a guarda confere a origem e não o destino | CT-07 | destino `v0.51.3` com marcador `0.52.0`: "reprova"; o mutante aprova |

---

## Regra R4 — A `## Identificação` traz o link raw do próprio roteiro, fixado na tag destino (main só na v0.52.0)

> `RQ-19`, `P-01`, D9 · perfil **padrão** (Impacto 3) · técnica: **tabela de decisão** (versão × ref × host × repositório × caminho × seção onde o link está) + **BVA** na exceção da P-01 (`0.52.0` × `0.52.1`)
> Repositório: `gsferro/filament-starter-kit-easy` (o `git remote` da árvore). Versão com revisão registrada: R16.

```gherkin
  Regra: sem revisão registrada, o agente chega ao roteiro certo pela URL raw fixada na tag destino, escrita na identificação

    Esquema do Cenário: [CT-08] o link da identificação aponta para o arquivo bruto da versão, na ref permitida
      Dado o marcador de versão "<versao>" e o CHANGELOG sem registro de revisão do roteiro
      E um roteiro conforme cujo link é <link>
      Quando a guarda do CI confere o link do roteiro
      Então o resultado é "<resultado>"

      Exemplos:
        | versao | link                                                                                                                         | resultado | # regra da tabela                            |
        | 0.53.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.53.0/wikis/atualizacoes/v0.53.0.md", em "## Identificação" | aprova    | tag destino (RQ-19)                          |
        | 0.52.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/wikis/atualizacoes/v0.52.0.md", em "## Identificação"    | aprova    | v0.52.0 na main (P-01), borda da exceção     |
        | 0.52.1 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/wikis/atualizacoes/v0.52.1.md", em "## Identificação"    | reprova   | borda+1 da exceção: 0.52.1 na main (ADV-12)  |
        | 0.52.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.52.0/wikis/atualizacoes/v0.52.0.md", em "## Identificação" | reprova   | v0.52.0 na tag, que não tem o arquivo (P-01) |
        | 0.53.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/wikis/atualizacoes/v0.53.0.md", em "## Identificação"    | reprova   | main fora da v0.52.0, sem revisão (RQ-28)    |
        | 0.53.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/0.53.0/wikis/atualizacoes/v0.53.0.md", em "## Identificação"  | reprova   | ref sem o "v" (D9, ADV-09)                   |
        | 0.53.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.52.0/wikis/atualizacoes/v0.53.0.md", em "## Identificação" | reprova   | tag de outra versão                          |
        | 0.53.0 | "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.53.0/wikis/atualizacoes/v0.52.0.md", em "## Identificação" | reprova   | caminho de outra versão                      |
        | 0.53.0 | "https://raw.githubusercontent.com/outro/filament-starter-kit-easy/v0.53.0/wikis/atualizacoes/v0.53.0.md", em "## Identificação"   | reprova   | outro repositório                            |
        | 0.53.0 | "https://github.com/gsferro/filament-starter-kit-easy/blob/v0.53.0/wikis/atualizacoes/v0.53.0.md", em "## Identificação"           | reprova   | página HTML, não o arquivo bruto             |
        | 0.53.0 | o link conforme da primeira linha, só em "## Passos"                                                                         | reprova   | fora da identificação (ADV-13)               |
        | 0.53.0 | nenhum                                                                                                                       | reprova   | sem link                                     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M16 | a guarda aceita qualquer URL do GitHub com o caminho certo, inclusive `github.com/…/blob/…` | CT-08 (linha página HTML) | `blob`: "reprova"; o mutante aprova, e o agente leria HTML |
| M17 | a guarda aceita `main` em qualquer versão (ou tag em qualquer versão), sem cruzar a ref com a versão | CT-08 (linhas "main fora da v0.52.0" e "v0.52.0 na tag") | `0.53.0` + `main`: "reprova"; `0.52.0` + `v0.52.0`: "reprova"; o mutante aprova as duas |
| M18 | a guarda exige a tag destino sempre, sem a exceção da P-01 | CT-08 (linha v0.52.0 na main) e CT-01 | `0.52.0` + `main`: "aprova"; o mutante reprova — e reprova a árvore real |
| M19 | a guarda confere o domínio raw e não o repositório, a ref nem o caminho da versão | CT-08 (linhas tag de outra versão, caminho de outra versão, outro repositório) | as três: "reprova"; o mutante aprova |
| M63 — revisão adversarial (ADV-09) | a guarda compara a ref sem o `v`, como compara as versões | CT-08 (linha ref sem o "v") | "reprova"; o mutante aprova uma ref que não existe no GitHub (a tag é `v0.53.0`) |
| M64 — revisão adversarial (ADV-12) | a exceção da P-01 é aplicada por prefixo ou faixa (`0.52.x`, `<= 0.52.*`) | CT-08 (linha 0.52.1 na main) | "reprova"; o mutante aprova o hotfix na main |
| M65 — revisão adversarial (ADV-13) | o link é procurado no arquivo inteiro, não em `## Identificação` | CT-08 (linha fora da identificação) | "reprova"; o mutante acha o link em `## Passos` e aprova |

---

## Regra R15 — O roteiro ensina o padrão do link da próxima versão, com a conferência na main e a parada na dúvida

> `RQ-20`, `RQ-32`, D9 · perfil **padrão** (Impacto 3) · técnica: **tabela de decisão** (host × repositório × ref do padrão × caminho do padrão), a mesma de CT-08 aplicada ao padrão (ADV-08)
> `@premissa` (mecanismo, D9): o padrão é `https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md`, com o marcador `{versão}`.

```gherkin
  Regra: o roteiro anterior ensina a montar o link da próxima, e o padrão leva à correção quando houver

    Esquema do Cenário: [CT-09] o padrão do link da próxima versão é conferido como o link
      Dado um roteiro conforme da versão marcada
      E nele o padrão do link é <padrao>
      Quando a guarda do CI confere o padrão do link
      Então o resultado é "<resultado>"

      Exemplos:
        | host                              | repositorio                         | ref        | caminho                               | resultado | # regra da tabela               |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | v{versão}  | wikis/atualizacoes/v{versão}.md       | aprova    | conforme                        |
        | (sem padrão: só o link concreto da própria versão) | —                  | —          | —                                     | reprova   | sem padrão para a próxima       |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | v0.52.0    | wikis/atualizacoes/v{versão}.md       | reprova   | ref fixa numa versão (ADV-08)   |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | main       | wikis/atualizacoes/v{versão}.md       | reprova   | ref fixa na main (ADV-08)       |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | {versão}   | wikis/atualizacoes/v{versão}.md       | reprova   | ref sem o "v" (ADV-08, ADV-09)  |
        | github.com, com "/blob/"          | gsferro/filament-starter-kit-easy   | v{versão}  | wikis/atualizacoes/v{versão}.md       | reprova   | página HTML (ADV-08)            |
        | raw.githubusercontent.com         | outro/filament-starter-kit-easy     | v{versão}  | wikis/atualizacoes/v{versão}.md       | reprova   | outro repositório (ADV-08)      |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | v{versão}  | wikis/atualizacoes/v0.52.0.md         | reprova   | caminho fixo numa versão (ADV-25) |
        | raw.githubusercontent.com         | gsferro/filament-starter-kit-easy   | v{versão}  | wikis/v{versão}.md                    | reprova   | caminho fora da pasta dos roteiros (ADV-25) |

    Esquema do Cenário: [CT-22] o padrão manda conferir a main e parar na dúvida
      Dado um roteiro conforme da versão marcada
      E junto do padrão do link <defeito>
      Quando a guarda do CI confere as instruções do padrão
      Então o resultado é "<resultado>"

      Exemplos:
        | defeito                                                                                         | resultado | # partição                     |
        | nenhum: ler também o arquivo na main, preferi-lo quando ele registrar a revisão, parar na dúvida | aprova    | conforme                       |
        | falta a instrução de ler também o arquivo na main                                               | reprova   | sem conferir a main (RQ-32)    |
        | falta preferir o arquivo da main quando ele registrar a revisão                                 | reprova   | sem regra de preferência (RQ-32) |
        | falta parar na dúvida                                                                           | reprova   | sem parada (RQ-32)             |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | o roteiro traz só o próprio link, e a guarda não exige o padrão da próxima | CT-09 (linha sem padrão) | "reprova"; o mutante aprova, e RQ-20 fica sem efeito |
| M69 — revisão adversarial (ADV-08) | a guarda só confere que o padrão contém `{versão}`, e aceita ref fixa | CT-09 (linhas ref fixa numa versão e na main) | as duas: "reprova"; o mutante aprova, e o agente monta sempre o link da mesma versão |
| M70 — revisão adversarial (ADV-08) | a guarda não confere host nem repositório do padrão | CT-09 (linhas página HTML e outro repositório) | as duas: "reprova"; o mutante aprova |
| M71 — revisão adversarial (ADV-08, ADV-09) | o padrão aceita ref sem o `v` | CT-09 (linha ref sem o "v") | "reprova"; o mutante aprova uma ref que não existe |
| M115 — revisão adversarial (ADV-25) | a guarda confere host, repositório e ref do padrão, e não o caminho | CT-09 (linhas caminho fixo e caminho fora da pasta) | as duas: "reprova"; o mutante aprova, e o agente monta o link de outro arquivo |
| M72 | o padrão manda ler só a tag (RQ-32 esquecida), e o agente lê o roteiro reprovado | CT-22 (linha sem conferir a main) | "reprova"; o mutante aprova |
| M73 | o padrão confere a main mas não manda preferi-la nem parar na dúvida | CT-22 (linhas sem preferência, sem parada) | as duas: "reprova"; o mutante aprova |

---

## Regra R16 — Depois de uma revisão registrada, o link aponta para a correção e o roteiro traz a marca da revisão

> `RQ-24`, `RQ-28`, `RQ-32`, D17, D19 · perfil **padrão** (Impacto 3) · técnica: **tabela de decisão** (registro no CHANGELOG × marca no roteiro × ref × host/repositório/caminho do link de commit)
> `@premissa` (mecanismo, D17): o registro é uma linha que começa por `Roteiro de atualização revisado`, dentro da seção `## [x.y.z]` da própria versão; o commit no link só vale como SHA completo de 40 hexadecimais, com host, repositório e caminho conferidos como em CT-08.
> `@premissa` (mecanismo, D19): a marca é a linha `Roteiro revisado em {data}: {motivo}` em `## Identificação`; é ela que o agente vê na main quando o padrão manda conferi-la (RQ-32).

```gherkin
  Regra: o link de uma versão que não é a v0.52.0 sai da tag só quando o CHANGELOG registra a revisão, e então aponta para a correção, que se identifica como revisada

    Esquema do Cenário: [CT-21] o link e a marca da revisão andam com o registro no CHANGELOG
      Dado o marcador de versão "0.53.0"
      E o CHANGELOG com o registro de revisão do roteiro <registro>
      E um roteiro conforme cuja identificação traz <marca> e o link <link>
      Quando a guarda do CI confere o link e a marca do roteiro
      Então o resultado é "<resultado>"

      Exemplos:
        | registro               | marca                                         | link                                                                                                   | resultado | # regra da tabela                              |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "main", host, repositório e caminho da v0.53.0                                            | aprova    | revisão registrada, main (RQ-28)               |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "3f1c9e2a7b4d6e8f0a1b2c3d4e5f60718293a4b5", host, repositório e caminho da v0.53.0          | aprova    | revisão registrada, commit (RQ-28, D17)        |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "v0.53.0"                                                                                 | reprova   | link na tag reprovada (RQ-24)                  |
        | só na seção "[0.52.0]" | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "main"                                                                                    | reprova   | registro de outra versão (RQ-28)               |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "3f1c9e2", host, repositório e caminho da v0.53.0                                         | reprova   | SHA curto (D17, ADV-21)                        |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "develop", host, repositório e caminho da v0.53.0                                         | reprova   | branch que não é a main (D17, ADV-21)          |
        | na seção "[0.53.0]"    | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "3f1c9e2a7b4d6e8f0a1b2c3d4e5f60718293a4b5", repositório "outro/filament-starter-kit-easy"   | reprova   | commit em outro repositório (D17, ADV-21)      |
        | na seção "[0.53.0]"    | nenhuma                                       | na ref "main", host, repositório e caminho da v0.53.0                                            | reprova   | revisão registrada sem marca (D19, ADV-20)     |
        | nenhum                 | "Roteiro revisado em 2026-11-02: passo de tenancy" | na ref "v0.53.0", host, repositório e caminho da v0.53.0                                         | reprova   | marca sem registro (D19, ADV-20)               |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M66 | o registro em qualquer seção do CHANGELOG libera a `main` | CT-21 (linha registro de outra versão) | "reprova"; o mutante aprova |
| M67 | a guarda ignora o registro e reprova toda ref que não é a tag | CT-21 (linhas main e commit com registro) | "aprova"; o mutante reprova a correção legítima e obriga a reescrever a tag |
| M68 | a guarda continua aceitando a tag depois da revisão registrada | CT-21 (linha link na tag reprovada) | "reprova"; o mutante aprova, e o agente lê o roteiro reprovado |
| M98 — revisão adversarial (ADV-21) | a guarda aceita como commit qualquer ref que não é tag (SHA curto, branch) | CT-21 (linhas SHA curto e branch) | as duas: "reprova"; o mutante aprova uma ref que pode mudar ou colidir |
| M99 — revisão adversarial (ADV-21) | no link de commit, a guarda confere só a ref e não host, repositório e caminho | CT-21 (linha commit em outro repositório) | "reprova"; o mutante aprova |
| M100 — revisão adversarial (ADV-20) | a guarda não exige a marca no roteiro revisado, e quem chega pela main não sabe que é a correção | CT-21 (linha registrada sem marca) | "reprova"; o mutante aprova |
| M101 — revisão adversarial (ADV-20) | a guarda aceita a marca sem o registro no CHANGELOG | CT-21 (linha marca sem registro) | "reprova"; o mutante aprova uma revisão que ninguém registrou |

---

## Regra R5 — Toda decisão do dono traz pergunta, recomendação da release e default seguro, todo default mudado vira pergunta, e o relatório final registra a escolha

> `RQ-25`, `RQ-26`, `RQ-30`, D10, D15 · perfil **padrão** (Impacto 3) · técnica: **EP** + cardinalidade **0 / 1 / N** das decisões e dos defaults mudados
> `@premissa` (mecanismo, D10): cada decisão é um `###` com `Pergunta:` (termina em `?`), `Recomendação da release:` e `Sem resposta:`, todos com valor. A decisão de conflito em arquivo do dono (RQ-35) é conferida em R19; a frase da versão sem outra decisão depende de Q?11.
> `@premissa` (mecanismo, D15): a lista é a subseção `### Defaults mudados` da seção `## [x.y.z]` da versão no CHANGELOG, um item por linha; "nenhum" é item explícito.

```gherkin
  Regra: o roteiro para e pergunta ao dono em todo ponto de decisão, inclusive em todo default que a versão mudou, e sem resposta segue o default seguro registrado

    Esquema do Cenário: [CT-10] cada decisão do dono traz os três elementos, com valor
      Dado um roteiro conforme da versão marcada
      E a seção "## Decisões do dono do projeto" com <decisoes>
      Quando a guarda do CI confere as decisões do dono
      Então o resultado é "<resultado>"

      Exemplos:
        | decisoes                                                                 | resultado | # partição                      |
        | uma decisão com pergunta, recomendação da release e default sem resposta | aprova    | 1, completa                     |
        | só a decisão de conflito em arquivo do dono, sem decisão própria da versão | aprova  | 0 decisões da versão            |
        | uma decisão sem a recomendação da release                                | reprova   | 1, falta recomendação           |
        | uma decisão sem o default para quando não há resposta                    | reprova   | 1, falta default seguro         |
        | uma decisão que dá a ordem ("Ligue o interruptor") sem a pergunta        | reprova   | 1, falta pergunta explícita     |
        | uma decisão com "Recomendação da release:" sem nada depois do rótulo     | reprova   | 1, rótulo vazio (ADV-15)        |
        | uma decisão com "Pergunta: ligar o interruptor." sem "?"                 | reprova   | 1, pergunta sem "?" (ADV-15)    |
        | duas decisões: a primeira completa, a segunda sem o default              | reprova   | N, defeito só na segunda        |

    Cenário: [CT-11] o relatório final pede o registro de cada decisão e do default aplicado
      Dado um roteiro conforme da versão marcada
      E a seção "## Relatório final" sem pedir o registro das decisões do dono
      Quando a guarda do CI confere o relatório final
      Então a guarda reprova
      E a mensagem nomeia "## Relatório final"

    Esquema do Cenário: [CT-25] cada default que a versão mudou vira uma pergunta do roteiro
      Dado o marcador de versão "0.53.0"
      E a seção "[0.53.0]" do CHANGELOG com <lista>
      E um roteiro conforme da v0.53.0 em que <perguntas>
      Quando a guarda do CI confere os defaults mudados contra as decisões do dono
      Então o resultado é "<resultado>"

      Exemplos:
        | lista                                                         | perguntas                                                                  | resultado | # partição                         |
        | "### Defaults mudados" com "chat de IA desligado" e "tutorial guiado ligado" | cada item aparece numa "Pergunta:"                             | aprova    | N itens, todos perguntados         |
        | "### Defaults mudados" com o item "nenhum"                    | só a decisão de conflito em arquivo do dono                                | aprova    | 0 itens, explícito                 |
        | "### Defaults mudados" com "chat de IA desligado" e "tutorial guiado ligado" | só "chat de IA desligado" aparece numa "Pergunta:"             | reprova   | N, um item calado (ADV-01)         |
        | "### Defaults mudados" com "tutorial guiado ligado"           | "tutorial guiado ligado" aparece só em "## Cuidados"                       | reprova   | item citado fora de uma pergunta   |
        | sem a subseção "### Defaults mudados"                         | cada decisão completa                                                      | reprova   | lista ausente (RQ-30)              |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | a guarda confere só que a seção tem subtítulos, não os três elementos (pergunta e recomendação) | CT-10 (linhas falta recomendação, falta pergunta) | "reprova"; o mutante aprova |
| M22 | a guarda não exige o default seguro sem resposta | CT-10 (linha falta default seguro) | "reprova"; o mutante aprova, e o agente sem resposta decide sozinho |
| M23 | a guarda confere só a primeira decisão | CT-10 (linha N, defeito só na segunda) | "reprova"; o mutante aprova |
| M24 | a guarda exige ao menos uma decisão própria da versão | CT-10 (linha 0 decisões da versão) | "aprova"; o mutante reprova o hotfix sem decisão |
| M25 | o relatório final não pede o registro (RQ-26 "registra") e a guarda não confere | CT-11 | "reprova"; o mutante aprova |
| M74 — revisão adversarial (ADV-15) | a guarda confere a presença do rótulo, não o valor | CT-10 (linha rótulo vazio) | "reprova"; o mutante aprova uma recomendação em branco |
| M75 — revisão adversarial (ADV-15) | a guarda aceita qualquer texto em `Pergunta:` | CT-10 (linha pergunta sem "?") | "reprova"; o mutante aprova uma ordem disfarçada de pergunta |
| M95 — revisão adversarial (ADV-01, D15) | a guarda não confere a lista de defaults mudados contra as perguntas | CT-25 (linha um item calado) | "reprova" nomeando "tutorial guiado ligado"; o mutante aprova |
| M96 — revisão adversarial (ADV-01, RQ-30) | lista ausente no CHANGELOG faz a guarda pular a conferência | CT-25 (linha lista ausente) | "reprova"; o mutante aprova sem conferir |
| M97 — revisão adversarial (ADV-01, D15) | o item é procurado no roteiro inteiro, não numa `Pergunta:` | CT-25 (linha item citado fora de uma pergunta) | "reprova"; o mutante acha o item em `## Cuidados` e aprova |

- Estouro do teto de mutantes (5 do perfil padrão): os cinco excedentes vêm da revisão adversarial e não contam.

---

## Regra R19 — Conflito do `kit:update` num arquivo do kit editado pelo dono é decisão do dono, e sem resposta fica a versão do dono

> `RQ-35` · perfil **padrão** (Impacto 3) · técnica: **EP**
> O estado do arquivo (intacto) é oráculo de CP-01; aqui a guarda confere que o roteiro traz a decisão, com o default certo, antes do passo.

```gherkin
  Regra: o roteiro pergunta ao dono antes de sobrescrever um arquivo do kit que ele editou, e sem resposta mantém a versão do dono

    Esquema do Cenário: [CT-26] o roteiro traz a decisão de conflito em arquivo do kit editado pelo dono
      Dado um roteiro conforme da versão marcada
      E em "## Decisões do dono do projeto" <decisao>
      Quando a guarda do CI confere a decisão de conflito
      Então o resultado é "<resultado>"

      Exemplos:
        | decisao                                                                                      | resultado | # partição                     |
        | a decisão de conflito, com "Sem resposta:" mantendo a versão do dono e registrando           | aprova    | conforme                       |
        | nenhuma decisão de conflito                                                                  | reprova   | decisão ausente (RQ-35)        |
        | a decisão de conflito, com "Sem resposta:" sobrescrevendo com a versão do kit                | reprova   | default errado (RQ-35)         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M108 | a guarda não exige a decisão de conflito, e o conflito fica fora das perguntas | CT-26 (linha decisão ausente) | "reprova"; o mutante aprova |
| M109 | a guarda aceita qualquer default sem resposta na decisão de conflito | CT-26 (linha default errado) | "reprova"; o mutante aprova o roteiro que sobrescreve o arquivo do dono calado |

---

## Regra R6 — O roteiro é autossuficiente, invoca o `kit:update`, confere a versão de origem e não promete funcionar em todo agente

> `RQ-13`, `RQ-15`, `RQ-17`, `RQ-18` · perfil **padrão** (Impacto 3) · técnica: **EP** (cada defeito isolado)
> Fato do `.gitattributes` (lido): `CHANGELOG.md`, `/docs`, `/wikis/specs`, `/.github` são `export-ignore`. RQ-15 ("lê só ele") vale também para o que viaja (ADV-10): o link do próprio roteiro e o padrão `v{versão}` não são remissão.
> `@premissa` (mecanismo, D16): invocação = linha de bloco de código que começa por `php artisan kit:update`; menção em prosa, inclusive em proibição, não conta.

```gherkin
  Regra: o agente do projeto lê só o roteiro, atualiza invocando o kit:update e não aplica o roteiro fora da versão de origem

    Esquema do Cenário: [CT-12] o roteiro que depende de outro documento, ou que não invoca o kit:update, reprova
      Dado um roteiro conforme da versão marcada
      E nele <defeito>
      Quando a guarda do CI confere a autossuficiência do roteiro
      Então o resultado é "<resultado>"

      Exemplos:
        | defeito                                                                          | resultado | # partição                         |
        | nenhum                                                                           | aprova    | conforme                           |
        | a frase "veja o CHANGELOG para os detalhes", sem link                            | reprova   | remete ao CHANGELOG (RQ-15)        |
        | um link para "docs/pt/comecar/atualizando-o-projeto.md"                          | reprova   | remete a docs/ (RQ-15)             |
        | um link para "wikis/specs/feat/onboarding-guiado/"                               | reprova   | remete à wiki de spec (RQ-15)      |
        | um link para "wikis/agentes-e-skills.md"                                         | reprova   | remete a documento que viaja (ADV-10) |
        | um link para "README.md"                                                         | reprova   | remete a documento que viaja (ADV-10) |
        | um link "https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.52.0/CHANGELOG.md" | reprova | URL raw do próprio repositório como remissão (ADV-26) |
        | a frase "os cuidados de wikis/atualizacoes/v0.51.2.md continuam valendo"         | reprova   | remete ao roteiro anterior (ADV-10) |
        | um link para "https://github.com/gsferro/filament-starter-kit-easy/pull/174"     | reprova   | remete a PR ou issue (ADV-10)      |
        | "## Passos" sem bloco de código "php artisan kit:update", citado só em "## Cuidados" | reprova | não invoca nos passos (RQ-17)     |
        | "## Passos" cita o kit:update só na frase "não rode php artisan kit:update antes do backup" | reprova | só proibição (ADV-14)       |
        | a frase "funciona com qualquer agente"                                           | reprova   | promete todo agente (RQ-13)        |
        | "## Antes de começar" sem conferir que a versão instalada é a origem             | reprova   | aplicável fora da origem (RQ-18)   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | a remissão é procurada só como link markdown `](…)`, não pelo nome solto | CT-12 (linha CHANGELOG sem link) | "reprova"; o mutante aprova |
| M27 | a lista do que não viaja esquece um caminho (`docs/` ou `wikis/specs/`) | CT-12 (linhas docs/ e wiki de spec) | as duas: "reprova"; o mutante aprova a que esqueceu |
| M28 | o `kit:update` é procurado no arquivo inteiro, não em `## Passos` | CT-12 (linha citado só em Cuidados) | "reprova"; o mutante acha a citação e aprova |
| M29 | a guarda não confere promessa de todo agente (RQ-06 diz "qualquer agente", e o redator copia) | CT-12 (linha promete todo agente) | "reprova"; o mutante aprova |
| M30 | o pré-voo não confere a versão instalada contra a origem, e a guarda não exige | CT-12 (linha aplicável fora da origem) | "reprova"; o mutante aprova, e o agente aplica o roteiro sobre outra versão |
| M76 — revisão adversarial (ADV-10) | a lista de remissão proibida cobre só o que não viaja | CT-12 (linhas agentes-e-skills e README) | as duas: "reprova"; o mutante aprova |
| M77 — revisão adversarial (ADV-10) | a remissão ao roteiro anterior ou a uma URL de PR/issue é aceita (é "do kit") | CT-12 (linhas roteiro anterior e PR) | as duas: "reprova"; o mutante aprova |
| M78 — revisão adversarial (ADV-14) | a invocação é reconhecida por `str_contains('kit:update')` em `## Passos` | CT-12 (linha só proibição) | "reprova"; o mutante acha o nome na proibição e aprova |
| M116 — revisão adversarial (ADV-26) | a isenção do link do próprio roteiro vale para toda URL raw do repositório do kit | CT-12 (linha URL raw do próprio repositório) | "reprova"; o mutante aprova o link raw para o CHANGELOG, que o agente leria fora do roteiro |

- Limite declarado: a linha de RQ-13 só pega as expressões que o detector lista; outra redação da mesma promessa passa. O cenário que a pega sem lista é o CP-01 (o mantenedor lê o roteiro executado).

---

## Regra R17 — Nenhuma seção que o agente executa traz comando que destrói dados do banco

> `RQ-31`, `RQ-33`, `RQ-34`, D18 · perfil **padrão** (Impacto 3) · técnica: **EP** (um comando por linha; prefixo; posição na linha; comentário; seção)
> `@premissa` (mecanismo, D18): casa `artisan` seguido — admitidas opções `--…` entre os dois (ADV-23) — de `migrate:fresh`, `db:wipe`, `migrate:refresh`, `migrate:reset` ou `migrate:rollback`, em qualquer ponto da linha, no texto **bruto com comentários**, em toda seção exceto `## Cuidados` (que vai até o próximo título `##`, com os seus `###`).

```gherkin
  Regra: nenhum passo, pré-voo, conferência ou parada do roteiro apaga os dados do dono do projeto

    Esquema do Cenário: [CT-23] comando destrutivo do banco fora de "## Cuidados" reprova
      Dado um roteiro conforme da versão marcada
      E <onde>
      Quando a guarda do CI confere os comandos do roteiro
      Então o resultado é "<resultado>"

      Exemplos:
        | onde                                                                                         | resultado | # partição                                |
        | "## Passos" com o bloco "php artisan migrate --force"                                        | aprova    | migration sem apagar                      |
        | "## Passos" com o bloco "php artisan migrate:fresh --force"                                  | reprova   | migrate:fresh (literal de RQ-31)          |
        | "## Passos" com o bloco "php artisan db:wipe"                                                | reprova   | db:wipe (literal de RQ-31)                |
        | "## Passos" com o bloco "php artisan migrate:refresh"                                        | reprova   | afim: apaga todas as tabelas              |
        | "## Passos" com o bloco "php artisan migrate:reset"                                          | reprova   | afim: apaga todas as tabelas              |
        | "## Passos" com o bloco "php artisan migrate:rollback"                                       | reprova   | afim (RQ-33)                              |
        | "## Passos" com o bloco "php artisan migrate:fresh --seed"                                   | reprova   | variação com opção depois                 |
        | "## Passos" com o bloco "./vendor/bin/sail artisan migrate:fresh"                            | reprova   | prefixo sail (RQ-34, ADV-22)              |
        | "## Passos" com o bloco "docker compose exec app php artisan db:wipe"                        | reprova   | prefixo docker (RQ-34, ADV-22)            |
        | "## Passos" com o bloco "php artisan --env=production migrate:fresh"                         | reprova   | opção antes do comando (ADV-23)           |
        | "## Passos" com a frase "se travar, rode `php artisan migrate:reset`" em prosa               | reprova   | inline no meio da linha (ADV-22)          |
        | "## Passos" com "<!-- php artisan db:wipe -->"                                               | reprova   | dentro de comentário (RQ-34, D18)         |
        | "## Passos" com a subseção "### Se falhar" e o bloco "php artisan migrate:fresh"             | reprova   | subseção dentro dos passos (ADV-24)       |
        | "## Quando parar" com o bloco "php artisan migrate:rollback"                                 | reprova   | outra seção que o agente executa (RQ-34)  |
        | "## Cuidados" com a frase "nunca rode php artisan migrate:fresh no projeto"                  | aprova    | proibição em Cuidados (RQ-34)             |
        | "## Cuidados" com a subseção "### Banco" e a frase "nunca rode php artisan db:wipe"          | aprova    | subseção de Cuidados (ADV-24)             |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M79 | a lista de comandos proibidos tem só um dos literais (`migrate:fresh`) | CT-23 (linhas db:wipe, refresh, reset) | as três: "reprova"; o mutante aprova |
| M80 | a guarda procura os comandos no arquivo inteiro, inclusive `## Cuidados` | CT-23 (linhas proibição em Cuidados) | "aprova"; o mutante reprova o roteiro que explica a proibição |
| M81 | a guarda compara a linha inteira com o comando exato | CT-23 (linha variação com opção depois) | `migrate:fresh --seed`: "reprova"; o mutante aprova |
| M102 | a lista de afins não tem o `migrate:rollback` (RQ-33) | CT-23 (linhas rollback em Passos e em Quando parar) | "reprova"; o mutante aprova |
| M103 | a guarda confere só `## Passos` (RQ-34: toda seção) | CT-23 (linha outra seção que o agente executa) | `## Quando parar`: "reprova"; o mutante aprova |
| M104 — revisão adversarial (ADV-22, D18) | a guarda remove `<!-- -->` antes de procurar, como manda a rule de ausência | CT-23 (linha dentro de comentário) | "reprova"; o mutante não vê o comando que o agente lê no raw |
| M105 — revisão adversarial (ADV-22) | a guarda exige `php artisan` no início da linha | CT-23 (linhas sail, docker e inline) | as três: "reprova"; o mutante aprova |
| M106 — revisão adversarial (ADV-23) | a guarda exige `artisan` e o comando colados | CT-23 (linha opção antes do comando) | "reprova"; o mutante aprova |
| M107 — revisão adversarial (ADV-24) | a seção acaba no próximo título de qualquer nível | CT-23 (linhas subseção dentro dos passos e subseção de Cuidados) | Passos com `###`: "reprova"; Cuidados com `###`: "aprova"; o mutante inverte as duas |

- Estouro do teto de mutantes (5 do perfil padrão): os quatro excedentes vêm da revisão adversarial e não contam.

---

## Regra R7 — A cópia local entre os marcadores é idêntica ao roteiro da versão marcada

> `RQ-21`, `RQ-15` · perfil **padrão** · técnica: **EP** + **exemplo discriminante** (mesma extensão, um caractere)
> `@premissa` (mecanismo, D11): a comparação normaliza só o fim de linha e as quebras das bordas do bloco.

```gherkin
  Regra: a cópia que viaja no projeto é o roteiro da versão marcada, inteiro e sem divergência

    Esquema do Cenário: [CT-13] a cópia entre os marcadores é conferida contra o roteiro da versão marcada
      Dado o roteiro conforme "wikis/atualizacoes/v0.52.0.md" e o marcador "0.52.0"
      E em "wikis/agentes-e-skills.md" <copia>
      Quando a guarda do CI confere a cópia local
      Então o resultado é "<resultado>"

      Exemplos:
        | copia                                                                             | resultado | # partição                     |
        | o roteiro inteiro entre os dois marcadores                                        | aprova    | idêntica                       |
        | o roteiro inteiro com CRLF, e o roteiro com LF                                    | aprova    | só o fim de linha difere       |
        | o roteiro inteiro com "v0.51.2" trocado por "v0.51.1" numa linha do meio          | reprova   | 1 caractere, mesma extensão    |
        | o roteiro da v0.51.2, entre os dois marcadores                                    | reprova   | cópia de outra versão          |
        | o roteiro inteiro sem o marcador de início                                        | reprova   | marcador ausente               |
        | o roteiro inteiro sem o marcador de fim                                           | reprova   | marcador ausente               |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M31 | a guarda compara tamanho ou só os títulos | CT-13 (linha 1 caractere) | "reprova"; o mutante vê o mesmo tamanho e os mesmos títulos e aprova |
| M32 | marcador ausente faz a guarda pular a comparação | CT-13 (linhas marcador ausente) | as duas: "reprova"; o mutante aprova sem comparar |
| M33 | a guarda confere que a cópia é **um** roteiro conforme, não o da versão marcada | CT-13 (linha cópia de outra versão) | "reprova"; o mutante aprova o roteiro válido da v0.51.2 |
| M34 | a guarda compara bytes sem normalizar o fim de linha | CT-13 (linha só o fim de linha difere) | "aprova"; o mutante reprova no checkout com CRLF (falso vermelho no Windows) |

---

## Regra R8 — O arquivo da cópia local viaja pelas duas rotas de entrega

> `RQ-21` ("uma cópia também viaja no projeto") · perfil **padrão** · técnica: **EP** sobre as rotas de entrega (glossário: `create-project` e `kit:update`)
> Fato (Q?8 retirada): `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93`, entrada `app/Console/Commands/KitUpdate.php:'wikis/agentes-e-skills.md':330`. A linha `kit:update` de CT-14 é guarda de regressão: verde hoje, vermelha se a entrada sair da lista.

```gherkin
  Regra: a cópia local chega a quem instala e a quem atualiza

    Esquema do Cenário: [CT-14] "wikis/agentes-e-skills.md" viaja pela rota de entrega
      Dado a árvore do kit
      Quando a guarda do CI confere a rota de entrega "<rota>"
      Então <oraculo>

      Exemplos:
        | rota                    | oraculo                                                                                   |
        | composer create-project | o atributo "export-ignore" de "wikis/agentes-e-skills.md" não está ligado no git          |
        | php artisan kit:update  | a lista de caminhos do kit:update inclui "wikis/agentes-e-skills.md", ou uma pasta que o contém |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M35 | a cópia vai para um arquivo que o `create-project` não entrega (dentro de `wikis/specs/` ou `docs/`) | CT-14 (linha create-project) | `git check-attr export-ignore` do arquivo da cópia: esperado "unspecified"; o mutante dá "set" |
| M36 | a cópia vai para um arquivo fora da lista de caminhos do `kit:update` (ou a entrada sai da lista) | CT-14 (linha kit:update) | a lista não contém o caminho: o caso falha; o mutante passa por CT-13, que só olha a árvore do kit |

---

## Regra R9 — O workflow de release reprova a tag sem roteiro ou sem as nove seções

> `RQ-10`, `RQ-23`, `RQ-27`, D8 · perfil **padrão** · técnica: **EP** (roteiro presente, ausente, sem cada seção; tag de release menor e de hotfix)
> Executa o bloco `run` do passo do `release.yml` numa árvore temporária em que marcador e CHANGELOG concordam com a tag; só o roteiro varia.

```gherkin
  Regra: o push da tag sem o roteiro da versão, ou com o roteiro fora do contrato, fica vermelho no workflow de release

    Esquema do Cenário: [CT-15] o passo do workflow confere o roteiro da tag
      Dado uma árvore com o marcador "<versao>" e a seção "[<versao>]" no CHANGELOG
      E em "wikis/atualizacoes/" <roteiro>
      Quando o passo do workflow de release roda para a tag "v<versao>"
      Então o código de saída é <saida>
      E <anotacao>

      Exemplos:
        | versao | roteiro                                      | saida          | anotacao                                                          | # partição             |
        | 0.53.0 | existe "v0.53.0.md" conforme                 | 0              | nenhuma linha "::error::"                                        | presente               |
        | 0.53.0 | não existe "v0.53.0.md"                      | diferente de 0 | uma linha "::error::" que nomeia "wikis/atualizacoes/v0.53.0.md" | ausente, menor         |
        | 0.52.1 | não existe "v0.52.1.md"                      | diferente de 0 | uma linha "::error::" que nomeia "wikis/atualizacoes/v0.52.1.md" | ausente, hotfix        |
        | 0.53.0 | "v0.53.0.md" sem "## Identificação"          | diferente de 0 | uma linha "::error::" que nomeia "## Identificação"              | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Antes de começar"       | diferente de 0 | uma linha "::error::" que nomeia "## Antes de começar"           | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## O que muda nesta versão" | diferente de 0 | uma linha "::error::" que nomeia "## O que muda nesta versão"   | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Cuidados"               | diferente de 0 | uma linha "::error::" que nomeia "## Cuidados"                   | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Decisões do dono do projeto" | diferente de 0 | uma linha "::error::" que nomeia "## Decisões do dono do projeto" | sem seção (RQ-27) |
        | 0.53.0 | "v0.53.0.md" sem "## Passos"                 | diferente de 0 | uma linha "::error::" que nomeia "## Passos"                     | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Como conferir"          | diferente de 0 | uma linha "::error::" que nomeia "## Como conferir"              | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Quando parar"           | diferente de 0 | uma linha "::error::" que nomeia "## Quando parar"               | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" sem "## Relatório final"        | diferente de 0 | uma linha "::error::" que nomeia "## Relatório final"            | sem seção (RQ-27)      |
        | 0.53.0 | "v0.53.0.md" em que "## Cuidados" só aparece no meio de uma linha ("veja ## Cuidados abaixo") | diferente de 0 | uma linha "::error::" que nomeia "## Cuidados" | presença sem âncora (ADV-29) |

    Cenário: [CT-29] a conferência do roteiro não encerra o passo antes das outras conferências da tag
      Dado uma árvore com o marcador "0.52.0", a seção "[0.53.0]" no CHANGELOG e o roteiro "v0.53.0.md" conforme
      Quando o passo do workflow de release roda para a tag "v0.53.0"
      Então o código de saída é diferente de 0
      E uma linha "::error::" diz que a tag 0.53.0 saiu sem o bump do marcador
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M37 | o passo monta o caminho sem o `v` (`wikis/atualizacoes/$tag.md`, com `tag` já sem o `v`) | CT-15 (linha presente) | saída 0 esperada; o mutante não acha `0.53.0.md` e sai ≠ 0 |
| M38 | o passo só avisa (`::warning::`, ou `continue-on-error`) e sai 0 | CT-15 (linhas ausente) | saída ≠ 0 e `::error::`; o mutante sai 0 |
| M39 | o passo só roda para tag `x.y.0` | CT-15 (linha hotfix) | `v0.52.1` sem roteiro: saída ≠ 0; o mutante sai 0 |
| M82 | o passo confere só a existência do arquivo (RQ-27 esquecida) | CT-15 (linhas sem seção) | saída ≠ 0 em cada uma; o mutante sai 0 |
| M83 | a lista de títulos do passo esquece uma seção | CT-15 (a linha daquela seção) | saída ≠ 0 e `::error::` com o título; o mutante sai 0 |
| M118 — revisão adversarial (ADV-29) | o bloco novo termina com `exit 0` quando o roteiro está certo, e encerra o `run` antes da conferência marcador × tag | CT-29 | saída ≠ 0 e `::error::` do bump; o mutante sai 0 com o marcador errado |
| M119 — revisão adversarial (ADV-29) | o passo procura o título com `grep` sem âncora de início de linha | CT-15 (linha presença sem âncora) | saída ≠ 0; o mutante acha "## Cuidados" no meio da frase e sai 0 |

---

## Regra R10 — O checklist torna a etapa do roteiro obrigatória antes da tag, em release e hotfix, a partir do CHANGELOG e da wiki reconciliados, com a lista de defaults mudados

> `RQ-10`, `RQ-14`, `RQ-23` ("linha obrigatória no checklist"), `RQ-30` · perfil **mínimo** · técnica: **EP** (um elemento por linha)

```gherkin
  Regra: o checklist de release obriga a escrever o roteiro antes da tag, em toda publicação

    Esquema do Cenário: [CT-16] a etapa do roteiro no checklist declara cada obrigação
      Dado o checklist de release da árvore do kit
      Quando a guarda de redação lê a etapa do roteiro de atualização
      Então a etapa contém <elemento>

      Exemplos:
        | elemento                                                           | # origem |
        | que ela é obrigatória, sem atenuação ("quando possível", "recomendado") | RQ-10 |
        | que vale para hotfix                                               | RQ-10    |
        | que vale para release nova                                         | RQ-10    |
        | que acontece antes da tag                                          | RQ-07, RQ-23 |
        | o CHANGELOG da publicação como fonte                               | RQ-14    |
        | a wiki da feature como fonte                                       | RQ-14    |
        | a reconciliação das duas fontes com o que foi entregue             | RQ-14    |
        | a lista de todo default que a versão mudou, feita na reconciliação | RQ-30    |
        | que cada item da lista vira pergunta no roteiro                    | RQ-30    |
        | a atualização da cópia local                                       | RQ-21    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M40 | a etapa fala só de "release" e o hotfix fica de fora | CT-16 (linha hotfix) | a etapa não contém "hotfix": o caso falha |
| M41 | a etapa é escrita como recomendação ("quando possível") | CT-16 (linha obrigatória) | a etapa traz atenuação: o caso falha |
| M42 | a etapa cita só o CHANGELOG (ou só a wiki) | CT-16 (linhas das fontes) | a fonte ausente: o caso falha |
| M43 | a etapa manda copiar o CHANGELOG sem reconciliar com o entregue | CT-16 (linha reconciliação) | a etapa não fala de reconciliar: o caso falha |
| M84 | a etapa não pede a lista de defaults mudados, ou não diz que cada item vira pergunta | CT-16 (linhas RQ-30) | o caso falha sem a lista ou sem a frase |

---

## Regra R11 — O checklist exige o cenário pós-release com agente sem contexto, em dois projetos, com cadência

> `RQ-11`, `RQ-13`, D12 · perfil **mínimo** · técnica: **EP** (um elemento por linha). O critério de aprovação mudou para R18.

```gherkin
  Regra: toda publicação passa por um agente em sessão nova, sem o contexto de quem escreveu, que atualiza dois projetos da versão anterior

    Esquema do Cenário: [CT-17] a seção do cenário pós-release declara cada obrigação
      Dado o checklist de release da árvore do kit
      Quando a guarda de redação lê a seção do cenário pós-release com agente
      Então a seção contém <elemento>

      Exemplos:
        | elemento                                                                  | # origem |
        | que ele é obrigatório em toda publicação                                  | RQ-11    |
        | que o agente roda em sessão nova, sem o contexto de quem escreveu o roteiro | RQ-11  |
        | que o registro declara o ambiente do agente: sem memória de usuário nem skills globais do mantenedor | RQ-11 (ADV-17) |
        | que o agente recebe só o pedido com o link do roteiro                     | RQ-11, RQ-15 |
        | dois projetos da versão anterior, um sem e um com multi-organização       | RQ-11    |
        | a cadência declarada de outros agentes e hosts                            | RQ-13    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M44 | o cenário aceita o mesmo agente ou a mesma sessão de quem escreveu | CT-17 (linha sessão nova) | a seção não exige sessão nova: o caso falha |
| M45 | o cenário atualiza um projeto só (sem multi-organização) | CT-17 (linha dois projetos) | a seção não cita os dois: o caso falha |
| M47 | a cadência de outros agentes não é declarada | CT-17 (linha cadência) | o caso falha |
| M48 | o cenário entra como 5ª linha da tabela dos quatro, ou repete o `composer create-project` literal (D12) | `tests/Kit/ChecklistDeReleaseTest.php` `'[CT-01]'` e `'[CT-12]'` (existentes, outra wiki, intactos) | `toHaveCount(4)` nas linhas da tabela e nos `create-project`: o mutante dá 5 ou 6 |
| M85 — revisão adversarial (ADV-17) | "sessão nova" com a memória de usuário e as skills globais do mantenedor carregadas (o contexto volta por outro caminho) | CT-17 (linha ambiente do agente) | a seção não pede a declaração do ambiente: o caso falha |

---

## Regra R18 — O checklist fixa o critério do cenário pós-release: estado dos cenários 3/4, customizações e dados preservados, cada default mudado perguntado

> `RQ-12`, `RQ-30`, `RQ-31` · perfil **mínimo** · técnica: **EP** (um elemento por linha)

```gherkin
  Regra: o cenário pós-release só passa com o estado dos cenários 3 e 4, sem perder nada do dono, e com cada default mudado perguntado

    Esquema do Cenário: [CT-24] o critério do cenário pós-release declara cada condição
      Dado o checklist de release da árvore do kit
      Quando a guarda de redação lê o critério do cenário pós-release com agente
      Então o critério contém <elemento>

      Exemplos:
        | elemento                                                              | # origem |
        | o mesmo estado dos cenários 3 e 4                                     | RQ-12    |
        | canais e customizações do projeto preservados                         | RQ-12    |
        | dados do dono no banco preservados                                    | RQ-31    |
        | que cada item da lista de defaults mudados virou pergunta no roteiro  | RQ-30    |
        | que sem a lista de defaults mudados o cenário reprova                 | RQ-30    |
        | que o registro confronta a lista com o diff de "config/", das migrations de settings e dos seeders entre a tag de origem e a de destino | RQ-30 (ADV-35) |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M46 | o critério é só "a suíte passou", sem canais e customizações | CT-24 (linha canais e customizações) | o caso falha |
| M86 | o critério não inclui os dados do banco | CT-24 (linha dados do dono) | o caso falha |
| M87 | o critério não confere a lista de defaults mudados, ou aceita a falta dela | CT-24 (linhas RQ-30) | o caso falha |
| M110 — revisão adversarial (ADV-35) | o checklist não manda confrontar a lista com o diff entre as tags, e a lista incompleta passa como completa | CT-24 (linha do confronto) | o caso falha sem a frase |

---

## Regra R12 — O checklist diz o que fazer quando o cenário pós-release reprova

> `RQ-24`, `RQ-28` · perfil **mínimo** · técnica: **EP** (um elemento por linha)

```gherkin
  Regra: reprovação do roteiro corrige na main sem reescrever a tag, e falha do código vira hotfix

    Esquema do Cenário: [CT-18] a reprovação do cenário pós-release tem destino escrito
      Dado o checklist de release da árvore do kit
      Quando a guarda de redação lê o que fazer quando o cenário pós-release reprova
      Então o texto contém <elemento>

      Exemplos:
        | elemento                                                  |
        | que a tag não é reescrita                                 |
        | que o roteiro é corrigido na main, com evidência nova     |
        | que o link do roteiro passa a apontar para a correção     |
        | que a seção da versão no CHANGELOG registra que o roteiro foi revisado |
        | que falha do código vira hotfix                           |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M49 | o texto manda apagar e recriar a tag com o roteiro corrigido | CT-18 (linha tag não reescrita) | o caso falha sem a frase |
| M50 | a correção entra sem registro na seção da versão no CHANGELOG | CT-18 (linha CHANGELOG) | o caso falha |
| M51 | toda reprovação vira correção de roteiro, inclusive falha do código | CT-18 (linha hotfix) | o caso falha |

---

## Regra R13 — Os pontos de entrada ensinam o padrão do link, a conferência na main e o pedido

> `RQ-01`, `RQ-20`, `RQ-32` · perfil **mínimo** · técnica: **EP** (um ponto de entrada por linha)

```gherkin
  Regra: quem pede a atualização encontra o padrão do link, a conferência na main e o pedido para o agente

    Esquema do Cenário: [CT-19] o ponto de entrada traz o padrão do link e o pedido
      Dado o arquivo "<arquivo>" da árvore do kit
      Quando a guarda de redação lê <trecho>
      Então ele contém o padrão do link raw do roteiro e o pedido ao agente
      E as três instruções de RQ-32: ler também o arquivo na main, preferi-lo quando ele registrar a revisão, parar na dúvida

      Exemplos:
        | arquivo                                     | trecho                                                                   |
        | wikis/agentes-e-skills.md                   | a seção "## Atualizar o projeto pelo agente", fora dos marcadores da cópia |
        | docs/pt/comecar/atualizando-o-projeto.md    | a página inteira                                                         |
        | docs/en/comecar/atualizando-o-projeto.md    | a página inteira                                                         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M52 | o padrão do link só existe dentro da cópia (entre os marcadores), e some quando a cópia é trocada | CT-19 (linha agentes-e-skills) | o trecho fora dos marcadores não contém o padrão: o caso falha |
| M53 | só a página pt ganha o padrão e o pedido | CT-19 (linha en) | a página en não contém: o caso falha |
| M88 | o ponto de entrada ensina o padrão sem a conferência na main (RQ-32) | CT-19 (qualquer linha) | o trecho não contém a instrução: o caso falha |
| M112 — revisão adversarial (ADV-37) | o ponto de entrada manda ler a main, mas não manda preferi-la nem parar na dúvida | CT-19 (qualquer linha) | o caso confere as três instruções separadas e falha na que falta |

---

## Regra R14 — Um agente em sessão nova, só com o roteiro, atualiza o projeto da versão anterior sem perder nada do dono, e para fora da origem

> `RQ-06`, `RQ-11`, `RQ-12`, `RQ-17`, `RQ-18`, `RQ-20`, `RQ-25`, `RQ-26`, `RQ-30`, `RQ-31`, `RQ-35`, D14 · perfil **completo** · técnica: **EP** (multi-organização × resposta do dono × forma do pedido) + **BVA** da versão de partida (origem−1, origem, destino, destino+1) + **não-efeito com alvo real** (os canários existem no `Dado`)
> **Cenários de processo, fora da suíte.** Executados e registrados pelo mantenedor a cada publicação (R11), com agente/modelo, ambiente, commit do roteiro executado, transcrição, intervenções e resultado. Não têm `it()`; o ID `CP-` não entra na contagem de `CT`.
> **O oráculo é o estado do projeto, não o relatório** (ADV-34, RQ-35): o relatório entra só onde RQ-26 pede o registro.
> `@premissa` (mecanismo, D14 + ADV-28): os canários são um canal de log próprio em `config/logging.php`, uma linha própria no `.env`, uma edição do dono num arquivo da lista de caminhos do `kit:update`, um registro do dono no banco, um valor que o dono mudou nas Configurações da aplicação e uma jornada do tutorial guiado editada por ele — os dois últimos em tabelas que o próprio kit gerencia —, preparados antes do pedido.
> Antes do pedido, o registro da release confronta a lista `### Defaults mudados` com o diff de `config/`, das migrations de settings e dos seeders entre a tag de origem e a de destino (ADV-35); default que mudou no diff e falta na lista reprova o cenário antes do agente rodar.

```gherkin
  Regra: o roteiro, sozinho, basta para um agente sem contexto atualizar o projeto sem apagar o que é do dono

    Esquema do Cenário de processo: [CP-01] o agente atualiza um projeto da versão anterior seguindo só o roteiro
      Dado um projeto novo criado da versão anterior publicada (a origem do roteiro), <multi_organizacao>
      E os seis canários do dono preparados antes
      E o dono do projeto não responde às perguntas de decisão
      E um agente em sessão nova, sem memória de usuário nem skills globais do mantenedor, que recebe só o pedido com o link raw
      Quando o agente executa o pedido
      Então o projeto passa em cada conferência da tabela de verificação do checklist para o cenário <cenario>
      E os seis canários estão intactos, inclusive o arquivo do kit editado pelo dono (sem resposta, fica a versão do dono — RQ-35)
      E, para cada item da lista de defaults mudados, o valor efetivo no projeto é o default seguro
      E o relatório final registra cada decisão e o default aplicado

      Exemplos:
        | multi_organizacao                            | cenario |
        | sem multi-organização                        | 3       |
        | com multi-organização ligada antes do pedido | 4       |

    Esquema do Cenário de processo: [CP-02] o dono responde, e o projeto segue a resposta, perguntada antes do passo
      Dado um projeto novo criado da versão anterior publicada, sem multi-organização
      E o dono do projeto responde a cada pergunta de decisão com <resposta>
      E um agente em sessão nova, sem memória de usuário nem skills globais do mantenedor, que recebe só o pedido com o link raw
      Quando o agente executa o pedido
      Então a transcrição mostra cada pergunta antes do passo que ela governa
      E o valor efetivo no projeto, em cada decisão, é a escolha do dono

      Exemplos:
        | resposta                                  | # partição                                   |
        | a escolha oposta ao default seguro        | o agente não pode cair no default            |
        | a escolha oposta à recomendação da release | o agente não pode cair na recomendação (ADV-33) |

    Esquema do Cenário de processo: [CP-03] o projeto fora da versão de origem faz o agente parar sem alterar nada
      Dado um projeto novo criado da versão publicada <versao_de_partida>
      E o git do projeto sem mudança pendente e um registro do dono no banco
      E um agente em sessão nova, sem memória de usuário nem skills globais do mantenedor, que recebe só o pedido com o link raw
      Quando o agente executa o pedido
      Então o agente para antes do primeiro passo que altera o projeto
      E o git do projeto continua sem mudança, a versão do kit é a de partida e o registro do banco está intacto
      E o relatório final diz a versão instalada, a origem do roteiro e o que falta para chegar à origem, quando falta

      Exemplos:
        | versao_de_partida                       | # borda                         |
        | anterior à origem                       | origem−1                        |
        | igual ao destino                        | destino: já atualizado          |
        | posterior ao destino                    | destino+1                       |

    Cenário de processo: [CP-04] sem link no pedido, o agente monta a URL pelo padrão da cópia local
      Dado um projeto na versão anterior publicada, cuja cópia local traz o roteiro dessa versão com o padrão do link
      E um agente em sessão nova, sem memória de usuário nem skills globais do mantenedor, que recebe o pedido de atualizar para a versão destino, sem link
      Quando o agente executa o pedido
      Então a transcrição mostra a URL raw da versão destino montada pelo padrão e a conferência do arquivo na main
      E o projeto passa em cada conferência da tabela de verificação do checklist para o cenário 3
```

- A borda "origem" de CP-03 é CP-01 (segue e atualiza).
- CP-04 não se aplica à v0.52.0: a v0.51.2 não tem roteiro nem cópia local. Vale a partir da v0.53.0.
- CP-02 só tem decisão a responder quando a versão traz decisão própria; sempre há ao menos a de conflito em arquivo do dono (RQ-35), que só aparece se houver conflito — sem conflito e sem decisão própria, o registro declara CP-02 não exercido naquela release. A linha "oposta à recomendação" só discrimina de "oposta ao default" quando recomendação ≠ default; se coincidirem, o registro diz isso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M54 | o roteiro omite o passo das migrations da versão | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual) | "migration nova rodou" da tabela do checklist; o mutante deixa a migration pendente |
| M55 | o roteiro manda sobrescrever `config/logging.php` ou o `.env` com os do kit | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual) | canal e linha do `.env` intactos; o mutante os apaga |
| M56 | o roteiro só funciona sem multi-organização (esquece o passo ou a ordem da tenancy) | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual), linha com multi-organização | tenancy `SIM` e suíte com zero falha no cenário 4; o mutante quebra |
| M57 | o roteiro manda aplicar a recomendação da release sem perguntar ao dono | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual); CT-10 pega só a forma | numa decisão cuja **recomendação difere do default seguro**, o valor efetivo no projeto é o default; o mutante deixa a recomendação. Decisão com recomendação igual ao default não discrimina e não conta (ADV-16); versão sem decisão assim deixa M57 sem matador naquela release, declarado no registro |
| M58 | o roteiro depende de contexto que o agente novo não tem (passo implícito, termo sem definição) | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual); CT-12 pega só as remissões | o agente em sessão nova não chega ao estado; o mutante passa em toda a guarda |
| M89 — revisão adversarial (ADV-01) | o roteiro omite a pergunta de um default que a versão mudou, e o agente segue o default novo calado | CT-25 (o item da lista sem `Pergunta:`) e CP-01 (o estado) | CT-25: "reprova" nomeando o item; CP-01: o valor efetivo daquele item não é o default seguro |
| M90 — revisão adversarial (ADV-04) | um passo apaga dados do dono no banco | CT-23 (comandos nomeados, qualquer seção e prefixo) e CP-01 (o resto) | o comando no roteiro: "reprova"; por outro caminho, o registro do banco some e CP-01 reprova |
| M91 — revisão adversarial (ADV-03, ADV-32) | o agente sobrescreve o arquivo do kit editado pelo dono, mesmo com a decisão de conflito no roteiro | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual); CT-26 pega só a decisão ausente ou com default errado | o arquivo editado pelo dono não está intacto; o relatório não salva o mutante (ADV-34) |
| M92 — revisão adversarial (ADV-02) | o agente faz a pergunta depois do passo que ela governa, ou aplica o default apesar da resposta | ⚠️ **sem matador** na suíte — morto só por CP-02 (manual) | na transcrição, a pergunta vem depois do passo, ou o valor efetivo é o default e não a escolha do dono |
| M93 — revisão adversarial (ADV-06) | o pré-voo existe mas só avisa, e o agente segue fora da origem | ⚠️ **sem matador** na suíte — morto só por CP-03 (manual); CT-12 pega só o pré-voo ausente | o git do projeto mostra mudança, ou a versão do kit mudou; esperado: nada alterado |
| M94 — revisão adversarial (ADV-11) | o padrão da cópia local não diz de onde vem a versão destino, e o agente monta outra URL | ⚠️ **sem matador** na suíte — morto só por CP-04 (manual); CT-09 pega só a forma do padrão | a URL da transcrição não é a da versão destino; o projeto não chega ao cenário 3 |
| M111 — revisão adversarial (ADV-35) | a lista `### Defaults mudados` está incompleta: um default mudou no diff entre as tags e não está nela | ⚠️ **sem matador** na suíte — morto só pelo confronto do registro antes de CP-01 (manual); CT-25 confere a lista contra o roteiro, não contra o diff | o confronto acha o default do diff fora da lista: o cenário reprova antes de o agente rodar |
| M117 — revisão adversarial (ADV-27) | o pré-voo confere uma faixa (`>=` origem, ou `<=` destino) em vez da igualdade com a origem | ⚠️ **sem matador** na suíte — morto só por CP-03 (manual), linhas destino e destino+1 | o agente para nas três bordas; o mutante segue em destino ou destino+1 e reaplica ou rebaixa o projeto |
| M121 — revisão adversarial (ADV-28) | o roteiro manda ressemear ou regerar uma tabela que o kit gerencia (Configurações, jornadas do tutorial guiado) | ⚠️ **sem matador** na suíte — morto só por CP-01 (manual); CT-23 pega só os cinco comandos destrutivos | o valor mudado nas Configurações e a jornada editada pelo dono não estão intactos |
| M122 — revisão adversarial (ADV-33) | o agente trata qualquer resposta diferente do default como "aplicar a recomendação" | ⚠️ **sem matador** na suíte — morto só por CP-02 (manual), linha oposta à recomendação | o valor efetivo é a recomendação, não a escolha do dono |

- Estouro do teto de mutantes do perfil completo (6): M54…M58 são da derivação; os demais vêm da revisão adversarial e não contam para o teto.
- **Lacuna declarada (arnês)**: tentado — rodar o agente na suíte é inviável (LLM não determinístico, rede, `create-project` reais de tags publicadas, minutos por execução); simular o agente executando `## Passos` como script trocaria RQ-06 ("qualquer agente, mais ou menos capaz") por "um interpretador". Os mutantes marcados ficam contados como sem matador **automatizado**; a evidência dos `CP-` vai para o registro da release.

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota, recurso nem usuário (`01` `## Superfície de UI`: "Sem superfície de UI") | — |
| Autorização exercida na ação | não se aplica: sem policy nem permissão | — |
| Idempotência | não se aplica: a guarda e o passo do workflow só leem; rodar duas vezes não muda estado | — |
| Concorrência | não se aplica: sem contador nem recurso compartilhado | — |
| Fronteira no ponto de entrada | CT-05 (origem: anterior, duas atrás, a própria, não publicada), CT-08 (exceção da P-01: `0.52.0` × `0.52.1`), CT-02 (marcador vazio), CP-03 (versão de partida: origem−1, destino, destino+1) | Guarda de redação dos roteiros |
| Domínio condicionado | CT-08 (a ref permitida depende da versão), CT-21 (a ref permitida depende do registro de revisão) | Guarda de redação dos roteiros |
| Estado × operação de escrita | CT-21 (o roteiro revisado: a tag deixa de valer como link, e a marca acompanha o registro), CT-28 (roteiro publicado removido) | Guarda de redação dos roteiros |
| Cardinalidade 0 / 1 / N | CT-10 (0, 1 e 2 decisões; defeito só na segunda) | Guarda de redação dos roteiros |
| Ausente ≠ null ≠ vazio | CT-02 (pasta inexistente, arquivo vazio, marcador vazio, seção do CHANGELOG ausente — cada um uma linha), CT-10 (rótulo sem valor), CT-25 (lista ausente × item "nenhum") | Guarda de redação dos roteiros |
| Paginação / ordenação | CT-06 (ordem de versões com dígito duplo; `[Unreleased]` no topo), CT-03 (ordem das seções) | Guarda de redação dos roteiros |
| Timezone / DST | não se aplica: a data da identificação não é comparada com nada | — |
| Texto livre (acento, unicode) | CT-03 (os títulos têm acento) · lacuna declarada: forma Unicode NFD dos títulos não exercitada | Guarda de redação dos roteiros |
| Unicidade + soft delete | não se aplica: sem persistência | — |
| CRUD combinado | não se aplica: sem persistência | — |
| Mass assignment | não se aplica: sem formulário nem payload | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| Superfície Livewire | não se aplica: `02` `## Superfície Livewire` — "a feature não cria página, widget nem componente" | — |
| Estado do framework usado sem validar | não se aplica: sem página nem widget | — |
| IDOR / mass assignment por entidade | não se aplica: a feature não persiste entidade | — |
| Escopo com discriminante nulo | CT-02 (marcador vazio e seção ausente: a guarda **fecha**, reprova) | Guarda de redação dos roteiros |
| Saída do estado de erro | CT-02 (a mensagem nomeia o arquivo ou a seção que falta e o contrato), CT-03 (nomeia a seção), CT-07 (nomeia o destino esperado), CT-15 (`::error::` nomeia o arquivo ou a seção), CP-03 (o relatório diz o encadeamento) | Guarda de redação dos roteiros; Passo do workflow de release; Cenário pós-release |
| Dado do dono destruído por comando do roteiro (RQ-31, RQ-33, RQ-34) | CT-23, CP-01 (registro do banco e tabelas que o kit gerencia) | Guarda de redação dos roteiros; Cenário pós-release |
| Teste que viaja lendo arquivo que não viaja (do projeto, `.ai/rules/testes.md`) | não se aplica como CT: forma obrigatória do `## Setup Global` (skip por caso); a prova real é o cenário 1 do checklist de release, que roda a suíte num projeto instalado | — |
| Asserção de ausência sobre arquivo comentado (do projeto) | CT-12 (roda sobre o texto sem `<!-- -->`); CT-23 roda **com** comentários por RQ-34/D18 — divergência declarada em `## Setup Global` §Precedência | Guarda de redação dos roteiros |
| Plataforma: fim de linha Windows × CI | CT-13 e CT-20 (linhas com CRLF) | Guarda de redação dos roteiros |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a árvore do kit passa em cada detector da guarda | R1 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M5, M18, M62, M113 |
| CT-02 | o commit de versão sem roteiro ou sem seção no CHANGELOG reprova, e diz o que falta | R1 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M1, M2, M3, M4 |
| CT-27 | na árvore do kit, nenhum caso da guarda pula | R1 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M114 |
| CT-28 | o roteiro de toda versão publicada desde a v0.52.0 continua na pasta | R1 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M120 |
| CT-03 | o roteiro fora do contrato reprova, nomeando a seção | R2 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M6, M7, M8, M9, M59 |
| CT-04 | o contrato publicado e a guarda exigem as mesmas seções | R2 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M10 |
| CT-20 | o roteiro que repete o conteúdo da versão anterior reprova | R2 | exemplo discriminante | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M60, M61, M62 |
| CT-05 | a origem é exatamente a versão anterior publicada | R3 | BVA 3-valores | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M12, M13 |
| CT-06 | a versão anterior sai da sequência publicada, qualquer que seja a forma do histórico | R3 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M11, M14 |
| CT-07 | o destino declarado diferente do marcador reprova | R3 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M15 |
| CT-08 | o link da identificação aponta para o arquivo bruto da versão, na ref permitida | R4 | tabela de decisão + BVA | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M16, M17, M18, M19, M63, M64, M65 |
| CT-09 | o padrão do link da próxima versão é conferido como o link | R15 | tabela de decisão | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M20, M69, M70, M71, M115 |
| CT-22 | o padrão manda conferir a main e parar na dúvida | R15 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M72, M73 |
| CT-21 | o link e a marca da revisão andam com o registro no CHANGELOG | R16 | tabela de decisão | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M66, M67, M68, M98, M99, M100, M101 |
| CT-10 | cada decisão do dono traz os três elementos, com valor | R5 | EP + 0/1/N | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M21, M22, M23, M24, M74, M75 |
| CT-11 | o relatório final pede o registro de cada decisão e do default aplicado | R5 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M25 |
| CT-25 | cada default que a versão mudou vira uma pergunta do roteiro | R5 | EP + 0/1/N | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M89, M95, M96, M97 |
| CT-26 | o roteiro traz a decisão de conflito em arquivo do kit editado pelo dono | R19 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M108, M109 |
| CT-12 | o roteiro que depende de outro documento, ou que não invoca o kit:update, reprova | R6 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M26, M27, M28, M29, M30, M76, M77, M78, M116 |
| CT-23 | comando destrutivo do banco fora de "## Cuidados" reprova | R17 | EP | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M79, M80, M81, M90, M102, M103, M104, M105, M106, M107 |
| CT-13 | a cópia entre os marcadores é conferida contra o roteiro da versão marcada | R7 | EP + discriminante | Guarda de redação dos roteiros | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M31, M32, M33, M34 |
| CT-14 | "wikis/agentes-e-skills.md" viaja pela rota de entrega | R8 | EP | Rotas de entrega da cópia | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M35, M36 |
| CT-15 | o passo do workflow confere o roteiro da tag | R9 | EP | Passo do workflow de release | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M37, M38, M39, M82, M83, M119 |
| CT-29 | a conferência do roteiro não encerra o passo antes das outras conferências da tag | R9 | EP | Passo do workflow de release | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M118 |
| CT-16 | a etapa do roteiro no checklist declara cada obrigação | R10 | EP | Redação do checklist e dos pontos de entrada | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M40, M41, M42, M43, M84 |
| CT-17 | a seção do cenário pós-release declara cada obrigação | R11 | EP | Redação do checklist e dos pontos de entrada | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M44, M45, M47, M85 |
| CT-24 | o critério do cenário pós-release declara cada condição | R18 | EP | Redação do checklist e dos pontos de entrada | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M46, M86, M87, M110 |
| CT-18 | a reprovação do cenário pós-release tem destino escrito | R12 | EP | Redação do checklist e dos pontos de entrada | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M49, M50, M51 |
| CT-19 | o ponto de entrada traz o padrão do link e o pedido | R13 | EP | Redação do checklist e dos pontos de entrada | Pest feature HTTP | `tests/Kit/RoteiroDeAtualizacaoTest.php` | M52, M53, M88, M112 |
| CP-01 | o agente atualiza um projeto da versão anterior seguindo só o roteiro | R14 | EP + não-efeito | Cenário pós-release (processo) | — (fora da suíte) | registro da release no checklist | M54, M55, M56, M57, M58, M89, M90, M91, M111, M121 |
| CP-02 | o dono responde, e o projeto segue a resposta, perguntada antes do passo | R14 | EP | Cenário pós-release (processo) | — (fora da suíte) | registro da release no checklist | M92, M122 |
| CP-03 | o projeto fora da versão de origem faz o agente parar sem alterar nada | R14 | BVA + não-efeito | Cenário pós-release (processo) | — (fora da suíte) | registro da release no checklist | M93, M117 |
| CP-04 | sem link no pedido, o agente monta a URL pelo padrão da cópia local | R14 | EP | Cenário pós-release (processo) | — (fora da suíte) | registro da release no checklist | M94 |

M48 é morto por testes existentes de outra wiki (`tests/Kit/ChecklistDeReleaseTest.php` `'[CT-01]'`, `'[CT-12]'`), por isso não está no índice.

## Fechamento da Revisão Adversarial

Duas rodadas, 37 achados; teto de 2 rodadas atingido. Não haverá rodada 3.

### Rodada 1 — 18 achados (5 altos, classificação do relatório da revisão)

| Achado | Virou |
|---|---|
| ADV-01 — decisão omitida, mudança de default calada | RQ-30: CT-16, CT-24, CT-25 (D15, a guarda confere a lista), CP-01; M84, M87, M89, M95, M96, M97 |
| ADV-02 — CP-01 sem a partição "dono responde diferente do default" | CP-02 (a transcrição mostra a pergunta antes do passo); M92 |
| ADV-03 — canário fora do caminho do kit:update | D14 e RQ-35: CP-01 confere o arquivo do kit editado pelo dono intacto; CT-26; M91 |
| ADV-04 — comando destrutivo do banco | RQ-31, RQ-33, RQ-34: R17, CT-23, CP-01; M79…M81, M86, M90, M102…M107 |
| ADV-05 — roteiro copiado de outra versão | D13: CT-20; M60, M61, M62 |
| ADV-06 — CP-01 sem a partição "projeto fora da origem" | CP-03; M93 |
| ADV-07 — ordem `## Antes de começar` × `## Passos` | CT-03 linha "ordem do pré-voo"; M59 |
| ADV-08 — o padrão do link sem a tabela de decisão do CT-08 | R15: CT-09 em colunas host × repositório × ref × caminho; M69, M70, M71 |
| ADV-09 — ref sem `v` | D9: CT-08 e CT-09; M63, M71 |
| ADV-10 — remissão a documento que viaja, ao roteiro anterior ou a PR/issue | CT-12; M76, M77 |
| ADV-11 — partição "pedido sem link, via padrão da cópia local" | CP-04 (a partir da v0.53.0); M94 |
| ADV-12 — BVA na exceção P-01 | CT-08 linha "0.52.1 na main"; M64 |
| ADV-13 — o link precisa estar em `## Identificação` | CT-08 linha "fora da identificação"; M65 |
| ADV-14 — kit:update citado só numa frase de proibição | D16: CT-12 linha "só proibição"; M78 |
| ADV-15 — rótulo vazio ou `Pergunta:` sem `?` | D10: CT-10; M74, M75 |
| ADV-16 — o oráculo do M57 não diverge quando recomendação = default | asserção de M57: só decisão com recomendação ≠ default conta, sobre o valor efetivo no projeto |
| ADV-17 — o registro não declara o ambiente do agente | CT-17 linha "ambiente do agente"; `Dado` dos quatro `CP-`; M85 |
| ADV-18 — o padrão leva à tag reprovada depois de uma revisão | RQ-32: CT-22, CT-19; M72, M73, M88; RQ-28: R16, CT-21 |

### Rodada 2 — 19 achados

| Achado | Virou |
|---|---|
| ADV-19 — o `Então` de CT-01 não liga cada detector à árvore real | CT-01 virou Esquema com uma linha por detector (15 linhas); M113 |
| ADV-20 — marca de revisão no roteiro | D19: CT-21 linhas "revisão registrada sem marca" e "marca sem registro"; M100, M101 |
| ADV-21 — commit no link: repositório errado, SHA curto, branch | D17: CT-21 com as três linhas; M98, M99 |
| ADV-22 — comando destrutivo com prefixo docker/sail, inline ou em comentário | RQ-34, D18: CT-23 linhas sail, docker, inline, comentário; M104, M105 |
| ADV-23 — opção antes do comando | D18: CT-23 linha "opção antes do comando"; M106 |
| ADV-24 — subseção `###` e outra seção | RQ-34, D18: CT-23 linhas subseção nos passos, subseção de Cuidados, `## Quando parar`; M103, M107 |
| ADV-25 — coluna do caminho no CT-09 | CT-09 com coluna `caminho` e duas linhas; M115 |
| ADV-26 — URL raw do próprio repositório como remissão | CT-12 linha "URL raw do próprio repositório"; M116 |
| ADV-27 — BVA do pré-voo (origem−1, origem, destino, destino+1) | CP-03 virou Esquema com origem−1, destino e destino+1 (origem = CP-01); M117 |
| ADV-28 — canário em tabela que o kit gerencia | CP-01 ganha dois canários (valor nas Configurações, jornada do tutorial guiado editada); M121 |
| ADV-29 — tag ≠ marcador e presença sem âncora no CT-15 | CT-15 linha "presença sem âncora" (M119); CT-29, marcador ≠ tag com o roteiro certo (M118) |
| ADV-30 — roteiros antigos linkados na main continuam existindo | CT-28; M120 |
| ADV-31 — na árvore do kit, a guarda não pula | CT-27 (mecanismo em Q?12); M114 |
| ADV-32 — RQ-35, com o oráculo no estado | R19/CT-26 (a decisão no roteiro); CP-01 confere o arquivo intacto; M108, M109, M91 |
| ADV-33 — o dono responde o oposto da recomendação | CP-02 virou Esquema com a linha "oposta à recomendação"; M122 |
| ADV-34 — o oráculo é o estado do projeto, não o relatório | `Então` dos `CP-` reescritos sobre o valor efetivo e os canários; relatório só onde RQ-26 pede; asserções de M57, M89, M91, M92 |
| ADV-35 — o registro confronta a lista com o diff de config/, settings e seeders entre as tags | CT-24 linha do confronto (M110); confronto no registro antes de CP-01 (M111) |
| ADV-36 — o `Dado` de ambiente também em CP-03 e CP-04 | `Dado` de ambiente nos quatro `CP-` |
| ADV-37 — CT-19 com as três partes da RQ-32 | CT-19 confere as três instruções separadas; M112 |

### Lacunas declaradas (não fechadas por teste automatizado)

| Lacuna | Motivo |
|---|---|
| Os mutantes M54–M58, M91–M94, M111, M117, M121, M122 (13) só morrem nos `CP-` manuais | arnês: o agente LLM não roda na suíte (ver R14); a evidência vai para o registro da release |
| Completude da lista `### Defaults mudados` (M111) | o confronto com o diff entre as tags é manual: o checkout do CI pode vir sem tags e "default mudado" exige leitura humana de `config/`, settings e seeders |
| O mutante M57 e a linha "oposta à recomendação" de CP-02 | só discriminam em versão com decisão de recomendação ≠ default; sem ela, o registro declara a release sem evidência |
| CP-04 na v0.52.0 | a v0.51.2 não tem roteiro nem cópia local; vale a partir da v0.53.0 |
| Forma Unicode NFD dos títulos | não exercitada (Checklist de Taxonomia) |
| O que o fechamento da rodada 2 criou (CT-25…CT-29, M95…M122) | não foi revisto por contexto independente: o teto de 2 rodadas foi atingido |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Guarda exigindo roteiro para toda versão já publicada (v0.49…v0.51.2) | RQ-05 é "cada **nova** release"; RQ-18 cobre só anterior → nova; exigir o passado reprovaria a árvore conforme |
| "O diff da entrega não toca `app/` nem o `kit:update`" como CT | afirmação sobre o diff desta branch, não comportamento que dura — fica com o quality gate (RQ-16) |
| Entrada no `CHANGELOG.md` `[Unreleased]` | `.ai/rules/testes.md`: asserção sobre o topo do CHANGELOG expira |
| Arch test "todo caso deste arquivo tem `naArvoreDoKit()`" (como `ChecklistDeReleaseTest` `'[CT-21]'`) | sem `RQ` de origem; é forma obrigatória do Setup, e o cenário 1 do checklist de release já executa a suíte num projeto instalado |
| Cenário de encadeamento de salto longo executado (v0.50 → v0.52 em dois roteiros) | RQ-18 diz que o salto é encadeamento **manual**; o que é verificável — parar fora da origem e dizer o encadeamento — é CT-12 e CP-03 |
| Cenário de processo para "na dúvida, parar" de RQ-32 (main e tag divergindo sem registro) | exige montar uma revisão falsa num repositório público; a instrução é conferida por CT-22 e o uso, por CP-04 quando houver revisão real |
| Valor da cadência ("a cada release menor") | o requisito exige cadência declarada, não o valor (Fronteira) |
| CT-B de qualquer tipo | sem superfície de UI (ver abaixo) |

## Sem CT-B

- Motivo: `01-plano-acao.md` `## Superfície de UI` declara "Sem superfície de UI" e o `02` `## Superfície Livewire` declara "Não se aplica"; nenhum cenário afirma JavaScript executado, console, acessibilidade, cor ou layout. Nenhuma linha de `## Costuras de Teste` tem costura `browser`, então o `05` não existe.

## Contagem do cabeçalho

```bash
grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md   # Cenários
grep -cE '^## Regra R[0-9]+' 04-casos-de-teste.md                                          # Regras
grep -cE '^\| M-?[0-9]+' 04-casos-de-teste.md                                              # Mutantes previstos
grep -cE '^\| M-?[0-9]+.*sem matador' 04-casos-de-teste.md                                 # Sem matador
```
