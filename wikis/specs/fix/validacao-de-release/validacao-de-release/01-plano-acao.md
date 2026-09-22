# Plano de Ação — Validação de release em projeto instalado

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: **correção**
- **Wiki ancestral**: `wikis/specs/feat/kit-install-host-local/` — é de lá que vem o
  `HostLocalTest` e o `[CT-12]` defeituoso
- **Motivo**: a validação de release da `v0.38.0` encontrou um caso que quebra em **toda**
  instalação nova
- **Toca infra compartilhada?**: **sim** — `KitUpdate::CAMINHOS_DO_KIT` é consumido por todo
  projeto que roda `kit:update`. **Regressão obrigatória.**

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | instalação limpa sem tenancy validada | **já executado** | `validacao-v0380/v0380-sem-tenant` |
| RQ-02 | instalação limpa com tenancy validada | **já executado** | `validacao-v0380/v0380-com-tenant` |
| RQ-03 | `kit:update` sem tenancy validado | **já executado** | `validacao-v0380/v0371-sem-tenant` |
| RQ-04 | `kit:update` com tenancy validado | **já executado** | `validacao-v0380/v0371-com-tenant` |
| RQ-05 | roteiro documentado dentro do pacote | 2, 3 | `wikis/checklist-de-release.md` + links |
| RQ-06 | roteiro obrigatório a cada tag | 2, 3 | declarado no topo do checklist e no `CONTRIBUTING.md` |
| RQ-07 | erros **entendidos** | 1, 2 | a seção *"por que nenhum gate pegou"* e a ADR-02 |
| RQ-08 | versão de correção | 5 | `v0.38.1` |
| RQ-09 | roda liso nos quatro cenários | **6 — o critério de aceite** | reexecutar contra a `v0.38.1` publicada |

> **RQ-01 a RQ-04 já estão atendidas** — a execução aconteceu antes desta wiki e é o que a
> originou. O que elas produziram (os números e o defeito) é entrada do `00`, não trabalho a fazer.

## Objetivo

Corrigir o defeito que a validação encontrou e transformar a validação, que foi um pedido pontual,
em **processo declarado do kit**.

## Contexto

O defeito é pequeno — uma linha de `->skip()`. O que não é pequeno é a **classe** dele: um teste
que viaja para o projeto instalado lendo um arquivo que não viaja. Ele atravessou três ciclos de
quality gate, um `/code-review`, um `fw-revisor-diff` e dois `fw-qa-gate`, e nenhum tinha como
vê-lo: todos rodam **na árvore do kit**, onde o arquivo existe.

## Superfície de UI

**Sem superfície de UI.** A entrega é uma linha de teste e três arquivos de documentação.

## Modelo de Execução

Um request, sem trabalho adiado. Nada aqui roda em runtime do produto.

## Impacto em Features Existentes

- **`kit:update`**: a lista `CAMINHOS_DO_KIT` ganha `wikis/checklist-de-release.md`. É aditivo —
  quem rodar o update passa a receber um arquivo a mais
- **`HostLocalTest`**: um caso passa a ser pulado fora da árvore do kit. Nenhum outro caso muda

## Rollback

Reverter os commits. Nada de schema, nada de config, nada de dado.

## Riscos

- **O checklist cair em desuso** — é trabalho manual, e trabalho manual se esquece. Mitigação na
  ADR-02: ele vive em `wikis/`, é linkado de dois lugares, e carrega um caso real de *"o que já
  quebrou aqui"* em vez de ser roteiro genérico
- **RQ-09 não fechar** — se a `v0.38.1` ainda quebrar em algum cenário, a entrega não está pronta,
  por definição do próprio requisito

## Channel de Log da Feature

**Nenhum.** A entrega não executa nada em runtime.

## Estrutura de Implementação

### 1. A correção do `[CT-12]` (RQ-07, RQ-08)

> Skills: `pest-testing`

- **Path**: `tests/Kit/HostLocalTest.php`
- `->skip(fn (): bool => ! naArvoreDoKit(), 'O site de documentação não viaja no projeto instalado.')`
- O docblock ganha a seção explicando **por que só roda na árvore do kit**, com a mensagem de erro
  literal e a observação de que a guarda já existia no próprio arquivo (`tests/Kit/HostLocalTest.php:[CT-34]:1354`)
- Ver ADR-01 para as três alternativas descartadas

### 2. `wikis/checklist-de-release.md` (RQ-05, RQ-06, RQ-07)

> Skills: — (documentação)

- Abre declarando que é **do mantenedor**, não de quem instala — mesma solução do `roadmap.md`
  (ADR-03)
- Tabela dos quatro cenários com **o que cada um cobre que os outros não**
- Roteiro executável, com as três armadilhas medidas: o Packagist demora a indexar, o
  `kit:tenancy` exige `git`, o `--force` é destrutivo fora de projeto novo
- Tabela do que conferir, com o critério de "liso" da ADR-04
- Seção **"O que já quebrou aqui"** com os dois casos da `v0.38.0`, cada um com a causa e o
  *por que nenhum gate pegou*

### 3. `CONTRIBUTING.md` e os links (RQ-05, RQ-06)

> Skills: — (documentação)

- `CONTRIBUTING.md` novo — não existia no repositório (decidido com o usuário)
- Seção **"Antes de lançar uma tag"** apontando o checklist como **obrigatório**
- Linha 11 no índice de `wikis/README.md`
- Contadores dos READMEs sincronizados

### 4. A lista de entrega (RQ-05)

> Skills: —

- `wikis/checklist-de-release.md` entra em `KitUpdate::CAMINHOS_DO_KIT`
- O comentário do `.gitattributes` passa de "onze" para "doze documentos de topo"

> **Este passo nasceu de um vermelho, não do plano.** O `KitUpdateTest` reprovou assim que o
> arquivo foi criado, com a mensagem *"Documentação do kit fora de `KitUpdate::CAMINHOS_DO_KIT`"*
> — a mesma classe de defeito que o próprio checklist documenta no caso do roadmap. A guarda
> funcionou dentro do ciclo que a descreve.

### 5. Release `v0.38.1` (RQ-08)

- `config/kit.php` e as quatro menções nas docs
- `CHANGELOG.md` com a seção `Corrigido`
- Tag anotada + release

### 6. Reexecutar os quatro cenários (RQ-09) — **critério de aceite**

- Contra a `v0.38.1` **publicada**, não contra a árvore local
- Os dois de update partem da `v0.38.0`, que é agora a versão anterior
- **Zero erro e zero falha** nos quatro, com a contagem de pulados registrada

## Testes

> Ver `04-casos-de-teste.md`. A superfície testável é pequena — a maior parte da entrega é
> documentação, e o oráculo dela é o passo 6.

## Verificação Final

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest tests/Kit/HostLocalTest.php --compact`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — regressão obrigatória (toca infra)
- [ ] `vendor/bin/phpstan analyse` · `vendor/bin/filacheck`
- [ ] `/code-review high main...HEAD` + passe de eixos (step 6.5)
- [ ] `feature-quality-gate` (step 8)
- [ ] **Os quatro cenários contra a `v0.38.1` publicada** — o critério de RQ-09
