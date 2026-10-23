# Plano de Ação — Roteiro de atualização por prompt para agentes

> Requisito: `00-requisito.md` (Texto Original + Adendos 1 e 2) · Decisões: `02-decisoes-arquiteturais.md` · Progresso: `03-progresso.md`

## Natureza da Wiki

nova — sem wiki ancestral. Toca o processo de release (`wikis/checklist-de-release.md`, `.github/workflows/release.yml`) e uma wiki entregue a quem instala (`wikis/agentes-e-skills.md`); não toca código da aplicação nem o `kit:update` (RQ-16). Regressão: a suíte `Kit` (guardas do checklist e das duas rotas de entrega).

## Cobertura do Requisito

| RQ | Passo(s) | Observação |
|----|----------|------------|
| RQ-01 | 1, 2 | o roteiro e o pedido-modelo que o agente recebe |
| RQ-02, RQ-03, RQ-04 | 1 | seções obrigatórias do contrato: o que mudou, cuidados, processo |
| RQ-05 | 1, 3 | um arquivo por versão; a guarda exige o da versão marcada |
| RQ-06 | 1 | contrato em passos observáveis (condição → ação → evidência → parada) |
| RQ-07 | — | substituída (parcial) por RQ-16 |
| RQ-08 | — | substituída por RQ-11 |
| RQ-09 | — | substituída por RQ-14 |
| RQ-10 | 3, 4, 5 | guarda no CI, workflow de release e linha obrigatória do checklist |
| RQ-11, RQ-12, RQ-13 | 5 | cenário 5 do checklist (pós-release, agente em sessão nova) |
| RQ-14 | 5 | etapa pré-release: fontes CHANGELOG + wiki, reconciliadas |
| RQ-15 | 1 | o roteiro não remete o agente a CHANGELOG, `docs/` nem wiki de spec |
| RQ-16 | 1–6 | nenhum passo toca `app/`, `config/` ou o comando |
| RQ-17 | 1 | os passos chamam o `kit:update` |
| RQ-18 | 1 | cada roteiro declara origem = versão anterior; salto longo encadeia |
| RQ-19, RQ-20 | 1, 2, 6 | padrão do link raw no contrato, na cópia local e na doc de atualização |
| RQ-21 | 2 | cópia do roteiro da versão instalada em `wikis/agentes-e-skills.md` |
| RQ-22 | 3 | `tests/Kit/RoteiroDeAtualizacaoTest.php` |
| RQ-23 | 4, 5 | `release.yml` e checklist |
| RQ-24 | 5 | política de reprovação no checklist |
| RQ-25, RQ-26 | 1 | seção "Decisões do dono do projeto" do contrato |
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
- `tests/Kit/ChecklistDeReleaseTest.php` e `tests/Kit/SiteDeDocumentacaoTest.php` leem o checklist e os READMEs: as contagens do README (arquivos de teste, casos) mudam com o teste novo.
- `docs/{pt,en}/comecar/atualizando-o-projeto.md` ganha a seção do agente (guardas de documentação do site).

## Rollback

Reverter o commit: remove os documentos, o teste e o passo do workflow; nenhum dado nem projeto instalado é afetado.

## Dependências

Nenhuma.

## Riscos

- Roteiro que envelhece em relação ao que a release entrega: mitigado pelo cenário 5 (RQ-11) e pela reconciliação pré-release (RQ-14).
- Link raw indisponível (rede, fork): o contrato manda parar e pedir o texto colado (RQ-15, parada).

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
- Regras de escrita do contrato: sem "idem", sem remeter a CHANGELOG, `docs/` ou wiki de spec (RQ-15); comandos para Git Bash e PowerShell quando diferem; o `kit:update` é chamado com `--from vORIGEM` e conferido pela versão **e** pelos arquivos (versão sozinha não prova atualização completa).
- Encadeamento (RQ-18): roteiro cobre só anterior → nova; salto longo = os roteiros em ordem, conferindo a versão entre cada um.
- Primeiro roteiro `v0.52.0`: fontes `CHANGELOG.md` `[0.52.0]` e a wiki `wikis/specs/feat/onboarding-guiado/` (P-24, D7b, a view publicada, migration de settings, decisões: semear o tutorial, ligar o interruptor). Link com `main` (P-01).
- **Atende**: RQ-01..06, RQ-15, RQ-17..20, RQ-25, RQ-26, P-01

### 2. Cópia local em `wikis/agentes-e-skills.md`

- Seção `## Atualizar o projeto pelo agente`: o padrão do link raw, o pedido-modelo ("Atualize este projeto seguindo o roteiro em …") e, entre os marcadores de D3, a cópia integral do roteiro da versão marcada em `config/kit.php`.
- **Atende**: RQ-20, RQ-21

### 3. Guarda no CI

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/RoteiroDeAtualizacaoTest.php` (todo caso com `skip(! naArvoreDoKit())`, D5).
- Afirma: (a) existe `wikis/atualizacoes/v{kit.version}.md` não vazio; (b) ele tem as seções obrigatórias do contrato; (c) a origem declarada é a versão anterior publicada (a seção seguinte do `CHANGELOG.md` depois da do marcador) e o destino é o marcador; (d) a cópia em `wikis/agentes-e-skills.md` entre os marcadores é idêntica ao arquivo; (e) o link raw do roteiro aponta para o próprio arquivo, na tag do destino ou na `main`. Controle positivo de cada detector contra texto corrompido.
- **Atende**: RQ-05, RQ-10, RQ-22

### 4. Workflow de release

- **Path**: `.github/workflows/release.yml` — passo novo no job existente: falha se `wikis/atualizacoes/v$tag.md` não existe na tag.
- **Atende**: RQ-10, RQ-23

### 5. Checklist de release

- **Path**: `wikis/checklist-de-release.md`.
- Etapa pré-release obrigatória (release e hotfix): escrever o roteiro pelo contrato, a partir do CHANGELOG da versão e da wiki da feature, reconciliado com o diff entregue (RQ-14); atualizar a cópia local.
- Cenário 5 pós-release (RQ-11, RQ-12): agente em sessão nova, sem o contexto de quem escreveu, recebe só o pedido-modelo e o link; atualiza `velho-sem-tenant` e `velho-com-tenant` da versão anterior; passa com o mesmo estado dos cenários 3/4 e com um canário de customização preservado. Registrar agente/modelo, intervenções e resultado.
- Cadência de outros agentes (RQ-13): a cada release menor, um segundo agente; sem prometer todos.
- Reprovação (RQ-24): a tag não é reescrita; roteiro corrigido na `main` com nova evidência e link para a `main`; CHANGELOG registra a revisão; falha do código vira hotfix.
- **Atende**: RQ-10, RQ-11..14, RQ-23, RQ-24

### 6. Documentação de usuário e CHANGELOG

- **Paths**: `docs/pt/comecar/atualizando-o-projeto.md` e `docs/en/comecar/atualizando-o-projeto.md` — seção "Atualizar pelo agente" com o padrão do link e o pedido-modelo; `CHANGELOG.md` `[Unreleased]`.
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
