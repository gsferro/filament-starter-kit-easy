# Plano de Ação — Roteiro de atualização por prompt para agentes

> Requisito: `00-requisito.md` (Texto Original + Adendos 1 a 4) · Decisões: `02-decisoes-arquiteturais.md` · Progresso: `03-progresso.md`

## Natureza da Wiki

nova — sem wiki ancestral. Toca o processo de release (`wikis/checklist-de-release.md`, `.github/workflows/release.yml`) e uma wiki entregue a quem instala (`wikis/agentes-e-skills.md`); não toca código da aplicação nem o `kit:update` (RQ-16). Regressão: a suíte `Kit` (guardas do checklist e das duas rotas de entrega).

## Cobertura do Requisito

| RQ | Passo(s) | Observação |
|----|----------|------------|
| RQ-01 | 1, 2 | o roteiro e o pedido-modelo que o agente recebe |
| RQ-02 | 1 | seções obrigatórias do contrato: o que mudou, cuidados, processo |
| RQ-03 | 1 | seções obrigatórias do contrato: o que mudou, cuidados, processo |
| RQ-04 | 1 | seções obrigatórias do contrato: o que mudou, cuidados, processo |
| RQ-05 | 1, 3 | um arquivo por versão; a guarda exige o da versão marcada |
| RQ-06 | 1 | contrato em passos observáveis (condição → ação → evidência → parada) |
| RQ-07 | — | substituída (parcial) por RQ-16 |
| RQ-08 | — | substituída por RQ-11 |
| RQ-09 | — | substituída por RQ-14 |
| RQ-10 | 3, 4, 5 | guarda no CI, workflow de release e linha obrigatória do checklist |
| RQ-11 | 5 | cenário 5 do checklist (pós-release, agente em sessão nova) |
| RQ-12 | 5 | cenário 5 do checklist (pós-release, agente em sessão nova) |
| RQ-13 | 5 | cenário 5 do checklist (pós-release, agente em sessão nova) |
| RQ-14 | 5 | etapa pré-release: fontes CHANGELOG + wiki, reconciliadas |
| RQ-15 | 1 | o roteiro não remete o agente a CHANGELOG, `docs/` nem wiki de spec |
| RQ-16 | 1–6 | nenhum passo toca `app/`, `config/` ou o comando |
| RQ-17 | 1 | os passos chamam o `kit:update` |
| RQ-18 | 1 | cada roteiro declara origem = versão anterior; salto longo encadeia |
| RQ-19 | 1, 2, 6 | padrão do link raw no contrato, na cópia local e na doc de atualização |
| RQ-20 | 1, 2, 6 | padrão do link raw no contrato, na cópia local e na doc de atualização |
| RQ-21 | 2 | cópia do roteiro da versão instalada em `wikis/agentes-e-skills.md` |
| RQ-22 | 3 | `tests/Kit/RoteiroDeAtualizacaoTest.php` |
| RQ-23 | 4, 5 | `release.yml` e checklist |
| RQ-24 | 5 | política de reprovação no checklist |
| RQ-25 | 1 | seção "Decisões do dono do projeto" do contrato |
| RQ-26 | 1 | seção "Decisões do dono do projeto" do contrato |
| RQ-27 | 4 | o passo do `release.yml` confere existência e as nove seções (Adendo 3) |
| RQ-28 | 1, 3 | link fora da v0.52.0 na `main` só com o registro da revisão no CHANGELOG (D17) |
| RQ-29 | 3 | marcador sem seção no CHANGELOG reprova a guarda |
| RQ-30 | 1, 3, 5 | `### Defaults mudados` no CHANGELOG (D15); cada item vira `Pergunta:`; etapa pré-release do checklist |
| RQ-31 | 1, 3, 5 | canário de registro no cenário com agente; guarda de comando destrutivo |
| RQ-32 | 1, 2, 6 | o padrão do link manda conferir a `main` e preferir a revisão marcada (D19) |
| RQ-33 | 3 | `migrate:rollback` na lista da guarda (D18) |
| RQ-34 | 3 | guarda no texto bruto, com comentários e prefixo, em toda seção fora de `## Cuidados` (D18) |
| RQ-35 | 1, 5 | decisão de conflito em todo roteiro (D20); o cenário com agente confere o arquivo do dono intacto |
| P-01 | 1, 2 | roteiro da v0.52.0 nasce depois da tag: o link dele aponta para a `main` |

## Objetivo

Toda publicação do kit (release ou hotfix) passa a ter um **roteiro de atualização** em português, em
`wikis/atualizacoes/vX.Y.Z.md`, que um agente qualquer segue para levar um projeto derivado da versão
anterior para a nova: o que mudou, os cuidados, as decisões que são do dono do projeto e o processo
inteiro, usando o `kit:update` como ferramenta. O roteiro é lido pela URL raw do GitHub antes de
qualquer alteração, tem cópia local num arquivo já entregue, e a sua existência é exigida por teste no
CI antes da tag. Depois da release, um agente em sessão nova executa o roteiro em dois projetos da
versão anterior, e o resultado entra no registro da publicação.

## Contexto

Hoje a atualização assistida depende de quem pede saber o que ler: `docs/` e o `CHANGELOG.md` não
viajam para o projeto (`.gitattributes:CHANGELOG.md:21`, `.gitattributes:/docs:40`), e os cuidados
finos (canal de log não entregue, primeira rodada com a classe antiga, versão marcada mesmo em
atualização parcial — `app/Console/Commands/KitUpdate.php:marcarVersao:1140`) vivem em wikis de spec
que também não viajam (`.gitattributes:/wikis/specs:32`). Um agente atualizando sozinho repete erros
que o checklist já registrou.

## Análise dos Arquivos Existentes

### `wikis/checklist-de-release.md`
Roteiro do mantenedor com os quatro cenários; recebe a etapa pré-release do roteiro e o cenário 5. É entregue pelas duas rotas e diz a quem instala que não é tarefa dele (`wikis/checklist-de-release.md:Checklist:1`).

### `.github/workflows/release.yml`
Roda só no push da tag e compara marcador × CHANGELOG (`.github/workflows/release.yml:CHANGELOG:28`); ganha a conferência do roteiro.

### `wikis/agentes-e-skills.md`
Já entregue pelo `kit:update` (`app/Console/Commands/KitUpdate.php:'wikis/agentes-e-skills.md':330`) e pelo create-project; recebe a seção "Atualizar o projeto pelo agente" com a cópia do roteiro.

### `tests/Kit/ChecklistDeReleaseTest.php`
Molde das guardas de redação de roteiro (`skip(! naArvoreDoKit())`) e do helper de leitura (`tests/Kit/ChecklistDeReleaseTest.php:roteiroDeRelease:242`).

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Roteiro canônico em `wikis/atualizacoes/vX.Y.Z.md`, fora do site (sem pt/en nem navegação) | Q9 | difícil de reverter | gsferro, 2026-10-23 |
| D2 | Só português | Q10 | trade-off | gsferro, 2026-10-23 |
| D3 | Cópia local entre marcadores `<!-- roteiro-de-atualizacao:inicio -->`/`fim` em `wikis/agentes-e-skills.md`, idêntica ao arquivo da versão marcada; a guarda compara | RQ-21 | surpreendente | sessão, 2026-10-23 |
| D4 | Contrato do roteiro em `wikis/atualizacoes/README.md`: seções obrigatórias com os títulos exatos que a guarda procura | RQ-02..04, RQ-25 | surpreendente | sessão, 2026-10-23 |
| D5 | Guarda só na árvore do kit (`skip(! naArvoreDoKit())`): `wikis/atualizacoes/` não está em `CAMINHOS_DO_KIT`, e projeto atualizado não tem os arquivos | RQ-22, RQ-16 | surpreendente | sessão, 2026-10-23 |
| D6 | Link raw: `https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/vX.Y.Z/wikis/atualizacoes/vX.Y.Z.md`; quando o roteiro é corrigido depois da tag (RQ-24) ou nasceu depois dela (P-01), `main` no lugar da tag | RQ-19, RQ-24 | trade-off | sessão, 2026-10-23 |
| D7 | Detectores da guarda como funções puras sobre o conteúdo, no próprio arquivo de teste; só os casos de presença leem os arquivos reais | 04 Q?3 | trade-off | sessão, 2026-10-23 |
| D8 | O caso do workflow roda com `bash` o bloco `run` do `release.yml` numa árvore temporária; sem `bash`, o caso falha em vez de pular | 04 Q?4 | surpreendente | sessão, 2026-10-23 |
| D9 | Linhas `Origem:`, `Destino:` e `Link:` com o `v` da tag; a comparação de versão ignora o prefixo, mas a ref do link exige o `v` (a tag é `vX.Y.Z`) | 04 Q?5, ADV-09 | surpreendente | sessão, 2026-10-23 |
| D10 | Cada decisão é um `###` com `Pergunta:` (termina em `?`), `Recomendação da release:` e `Sem resposta:`, todos com valor; versão sem decisão traz "Nenhuma decisão do dono nesta versão." | 04 Q?6, ADV-15, RQ-30 | surpreendente | sessão, 2026-10-23 |
| D11 | A cópia local "idêntica" normaliza só o fim de linha (CRLF/LF) e as quebras nas bordas do bloco | 04 Q?7 | surpreendente | sessão, 2026-10-23 |
| D12 | O cenário com agente no checklist fica em seção própria, fora da tabela, e remete aos comandos dos cenários 3 e 4 sem repetir a linha `create-project` literal | Q15 | trade-off | gsferro, 2026-10-23 |
| D13 | A guarda reprova `## O que muda nesta versão` ou `## Cuidados` com corpo igual ao do roteiro da versão anterior | ADV-05 | surpreendente | sessão, 2026-10-23 |
| D14 | O cenário com agente ganha um terceiro canário, num arquivo de `CAMINHOS_DO_KIT` editado pelo dono, e um quarto, um registro do dono no banco (RQ-31); o relatório do agente lista conflito e escolha | ADV-03, RQ-31 | surpreendente | sessão, 2026-10-23 |
| D15 | A lista de defaults mudados fica numa subseção `### Defaults mudados` da seção da versão no CHANGELOG; a guarda confere que cada item aparece numa `Pergunta:` do roteiro | 04 Q?1 (rodada 1), RQ-30 | surpreendente | sessão, 2026-10-23 |
| D16 | Invocação do `kit:update` conta só em linha de bloco de código que começa por `php artisan kit:update`; menção em prosa, inclusive em proibição, não conta | 04 Q?2 (rodada 1), RQ-17 | surpreendente | sessão, 2026-10-23 |
| D17 | Registro de revisão no CHANGELOG: linha que começa por `Roteiro de atualização revisado`, dentro de `## [x.y.z]` da própria versão; commit no link só como SHA completo de 40 hexadecimais, com host, repositório e caminho conferidos | 04 Q?3 (rodada 1), ADV-21, RQ-28 | surpreendente | sessão, 2026-10-23 |
| D18 | A guarda de comando destrutivo casa `artisan` seguido de `migrate:fresh`, `db:wipe`, `migrate:refresh`, `migrate:reset` ou `migrate:rollback` em qualquer ponto da linha, no texto bruto com comentários, fora de `## Cuidados`; aqui a regra de remover `<!-- -->` cede, porque o agente lê o raw | ADV-22..24, Q20, RQ-34 | surpreendente | sessão, 2026-10-23 |
| D19 | Marca de revisão dentro do roteiro corrigido: linha `Roteiro revisado em {data}: {motivo}` em `## Identificação`; a guarda exige a linha quando o CHANGELOG registra a revisão, e reprova a linha sem o registro | 04 Q?8 (rodada 2), ADV-20, RQ-32 | surpreendente | sessão, 2026-10-23 |
| D20 | Com a RQ-35 todo roteiro tem ao menos a decisão de conflito: a versão sem outra decisão traz "Nenhuma outra decisão do dono nesta versão." ao lado dela (corrige D10) | 04 Q?11 (fechamento da rodada 2) | surpreendente | sessão, 2026-10-23 |
| D21 | O caso que prova "nenhum caso pula na árvore do kit" inspeciona a fonte: todo `->skip(` do arquivo é exatamente `fn (): bool => ! naArvoreDoKit()`, apoiado no caso do `ChecklistDeReleaseTest` que prova a sentinela verdadeira; sem subprocesso do Pest (recursão, e resultado falso já visto no Windows) | 04 Q?12 (fechamento da rodada 2) | surpreendente | sessão, 2026-10-23 |

## Autorização

Não se aplica: nenhuma rota, policy ou gate.

## Rotas

Nenhuma.

## Superfície de UI

Sem superfície de UI.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

Sem execução em runtime: documentos, um teste de CI e um passo de workflow.

## Impacto em Features Existentes

- `wikis/agentes-e-skills.md` muda a cada release (a cópia do roteiro): o `kit:update` passa a oferecer esse arquivo a cada versão — esperado.
- `tests/Kit/ChecklistDeReleaseTest.php` exige a matriz de exatamente quatro cenários: o cenário com agente fica em seção própria. `tests/Kit/SiteDeDocumentacaoTest.php` confere os READMEs: arquivos de teste 207/244 → 208/245, badge de casos +B (blocos `it()` novos), e specs 88 → 89 (esta wiki já conta) *(alterado em 2026-10-23: revisão profunda, step 5)*.
- Pulados nos cenários do checklist: o teste novo viaja (`tests/Kit` está em `CAMINHOS_DO_KIT`) e pula fora da árvore do kit — o teto sobe de 942 para 942 + N (N = casos do teste novo), medido e justificado na próxima validação *(alterado em 2026-10-23: revisão profunda, step 5)*.
- `tests/Kit/PageHeaderTest.php` e `SiteDeDocumentacaoTest.php` exigem paridade de seções pt/en em `atualizando-o-projeto.md`: o parágrafo entra numa seção existente, nas duas línguas *(alterado em 2026-10-23: revisão profunda, step 5)*.
- `docs/{pt,en}/comecar/atualizando-o-projeto.md` ganha a seção do agente (guardas de documentação do site).

## Rollback

Reverter o commit: remove os documentos, o teste e o passo do workflow; nenhum dado nem projeto instalado é afetado.

## Dependências

Nenhuma.

## Riscos

- Roteiro que envelhece em relação ao que a release entrega: mitigado pelo cenário 5 (RQ-11) e pela reconciliação pré-release (RQ-14).
- Link raw indisponível (rede, fork): o contrato manda parar e pedir o texto colado (RQ-15, parada).
- Roteiro corrigido na `main` depois da tag (RQ-24) diverge da cópia que a tag entregou: o registro do cenário identifica o commit executado; o `kit:update` não corrige a cópia de uma tag já publicada *(alterado em 2026-10-23: revisão profunda, step 5)*.

## Channel de Log da Feature

### Verificação de Channel Existente
Não há código de aplicação nesta feature.

### Decisão
Sem channel de log: nada roda em runtime.

## Estrutura de Implementação

### 1. Contrato e primeiro roteiro

> Skills: nenhuma específica.

- **Paths**: `wikis/atualizacoes/README.md` (contrato), `wikis/atualizacoes/v0.52.0.md` (primeiro roteiro, origem `v0.51.2`).
- Contrato — seções obrigatórias de todo roteiro, com estes títulos: `## Identificação` (origem, destino, data, link raw), `## Antes de começar` (como pedir, pré-voo: git limpo, versão instalada = origem, banco acessível), `## O que muda nesta versão`, `## Cuidados`, `## Decisões do dono do projeto` (cada uma: pergunta, recomendação da release, default seguro sem resposta — RQ-25, RQ-26), `## Passos` (numerados, cada um com condição → ação → evidência), `## Como conferir` (estado esperado, o mesmo dos cenários 3/4 do checklist: `kit.version`, migrations, arquivos novos, canais e customizações preservados), `## Quando parar` (condições de parada com diagnóstico), `## Relatório final` (o que o agente entrega).
- Regras de escrita do contrato: sem "idem", sem remeter a CHANGELOG, `docs/` ou wiki de spec (RQ-15); sem citação `arquivo:símbolo:linha` (a cópia local em `wikis/agentes-e-skills.md` é conferida pelo `tests/Kit/CitacoesDeCodigoTest.php`, e a linha envelhece) *(alterado em 2026-10-23: revisão profunda, step 5)*; comandos para Git Bash e PowerShell quando diferem; o `kit:update` é chamado com `--from vORIGEM` e conferido pela versão **e** pelos arquivos (versão sozinha não prova atualização completa).
- Encadeamento (RQ-18): roteiro cobre só anterior → nova; salto longo = os roteiros em ordem, conferindo a versão entre cada um.
- Primeiro roteiro `v0.52.0`: fontes `CHANGELOG.md` `[0.52.0]` e a wiki `wikis/specs/feat/onboarding-guiado/` (P-24, D7b, a view publicada, migration de settings, decisões: semear o tutorial, ligar o interruptor). Link com `main` (P-01).
- **Atende**: RQ-01..06, RQ-15, RQ-17..20, RQ-25, RQ-26, P-01

### 2. Cópia local em `wikis/agentes-e-skills.md`

- Seção `## Atualizar o projeto pelo agente`: o padrão do link raw, o pedido-modelo ("Atualize este projeto seguindo o roteiro em …") e, entre os marcadores de D3, a cópia integral do roteiro da versão marcada em `config/kit.php`.
- **Atende**: RQ-20, RQ-21

### 3. Guarda no CI

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/RoteiroDeAtualizacaoTest.php` (todo caso com `skip(! naArvoreDoKit())`, D5).
- Toda leitura de arquivo dentro do corpo do caso, nunca no escopo global nem no dataset (o skip roda depois da coleta) *(alterado em 2026-10-23: revisão profunda, step 5)*.
- Afirma: (a) existe `wikis/atualizacoes/v{kit.version}.md` não vazio; (b) ele tem as seções obrigatórias do contrato; (c) a origem declarada é a versão anterior publicada (a seção seguinte do `CHANGELOG.md` depois da do marcador) e o destino é o marcador; (d) a cópia em `wikis/agentes-e-skills.md` entre os marcadores é idêntica ao arquivo. O link raw entra como linha obrigatória do `## Identificação` (item b), não como detector à parte *(alterado em 2026-10-23: Ponytail, step 6)*. Controle positivo de cada detector contra texto corrompido.
- Depois dos Adendos 3 e 4 e do step 9, a guarda tem 15 detectores ligados à árvore real pelo `'[CT-01]'`: além de (a)–(d), seção do CHANGELOG (RQ-29), ordem, corpo e título único das nove seções, link e marca de revisão (RQ-28, D17, D19), padrão do link com a conferência na `main` (RQ-32), decisões com default seguro e a decisão de conflito (RQ-30, RQ-35), `### Defaults mudados` contra as perguntas (D15), autossuficiência e `--from`/`--tag` do `kit:update`, comando destrutivo do banco fora de `## Cuidados` (RQ-31, RQ-33, RQ-34), roteiros publicados que continuam na pasta e a ausência de pulo na árvore do kit *(alterado em 2026-10-23: quality gate, QA-01)*.
- **Atende**: RQ-05, RQ-10, RQ-22, RQ-28, RQ-29, RQ-30, RQ-31, RQ-33, RQ-34, RQ-35

### 4. Workflow de release

- **Path**: `.github/workflows/release.yml` — passo novo no mesmo job e no mesmo `run` que define `tag`: falha se `wikis/atualizacoes/v$tag.md` não existe no checkout da tag, ou se não traz as nove seções com corpo, na ordem do contrato e fora de bloco cercado (RQ-27) *(alterado em 2026-10-23: quality gate, QA-01)*. Detecta **depois** do push da tag; quem age antes é a guarda do CI na `main` (passo 3) *(alterado em 2026-10-23: revisão profunda, step 5)*.
- **Atende**: RQ-10, RQ-23, RQ-27

### 5. Checklist de release

- **Path**: `wikis/checklist-de-release.md`.
- Etapa pré-release obrigatória (release e hotfix): escrever o roteiro pelo contrato, a partir do CHANGELOG da versão e da wiki da feature, reconciliado com o diff entregue (RQ-14); atualizar a cópia local.
- Cenário pós-release com agente (RQ-11, RQ-12), em **seção própria**, fora da matriz dos quatro (o `tests/Kit/ChecklistDeReleaseTest.php` exige exatamente quatro linhas e quatro `create-project`): agente em sessão nova, sem o contexto de quem escreveu, recebe só o pedido-modelo e o link; atualiza **dois projetos novos** da versão anterior (`agente-sem-tenant`, `agente-com-tenant`, criados para isso — os `velho-*` dos cenários 3/4 já estão na versão nova); passa com o mesmo estado dos cenários 3/4 (versão, tenancy, arquivo novo, migration, suíte com zero falha) **e** com os canários do dono preservados, preparados antes: um canal próprio no `config/logging.php`, uma linha própria no `.env`, um arquivo de `CAMINHOS_DO_KIT` editado, um registro no banco, um valor das Configurações e uma jornada editada (D14, RQ-31, RQ-35) *(alterado em 2026-10-23: quality gate, QA-01)*. Registrar agente/modelo, o commit do roteiro executado, intervenções e resultado *(alterado em 2026-10-23: revisão profunda, step 5)*.
- Cadência de outros agentes (RQ-13): a cada release menor, um segundo agente; sem prometer todos.
- Reprovação (RQ-24): a tag não é reescrita; roteiro corrigido na `main` com nova evidência e link para a `main`; CHANGELOG registra a revisão; falha do código vira hotfix.
- **Atende**: RQ-10, RQ-11..14, RQ-23, RQ-24

### 6. Documentação de usuário e CHANGELOG

- **Paths**: `docs/pt/comecar/atualizando-o-projeto.md` e `docs/en/comecar/atualizando-o-projeto.md` — um parágrafo com o padrão do link e o pedido-modelo, sem repetir o contrato *(alterado em 2026-10-23: Ponytail, step 6)*; `CHANGELOG.md` `[Unreleased]`.
- **Atende**: RQ-19, RQ-20

## Filosofia de Implementação

Ponytail: documentos e uma guarda; nenhum gerador automático de roteiro (o contrato é escrito à mão e conferido por teste).

## Testes

Ver `04-casos-de-teste.md`.

## Verificação Final

- `vendor/bin/pest tests/Kit/RoteiroDeAtualizacaoTest.php tests/Kit/ChecklistDeReleaseTest.php tests/Kit/SiteDeDocumentacaoTest.php`
- `vendor/bin/pint --dirty --format agent`
- suíte `Kit` com `--parallel --processes=4`

## Commits

- `:memo: docs(atualizacao): roteiro de atualização por agente — contrato, v0.52.0, guarda e checklist`
