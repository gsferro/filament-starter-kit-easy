# Plano de Ação — Host local no final do `kit:install`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: etapa nova no fluxo do `kit:install`
- **Toca infra compartilhada?**: **sim, parcialmente** — escreve `APP_URL` no `.env`, que é lido por
  login social (callbacks), Vite/CORS e pelo `banner()`. A regressão obrigatória é contra os casos
  de `tests/Kit/CustomizadorDaInstalacaoTest.php`, que exercitam a escrita de `.env`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Etapa no final do `kit:install` | 3, 4 | Ver ADR-02: "final do trabalho", **antes** do `banner()` |
| RQ-02 | Pergunta com exemplo de URL | 2, 4 | Exemplo derivado do nome (ADR-04) |
| RQ-03 | Resposta "não" encerra sem efeito colateral | 4 | CT de ramo negativo |
| RQ-04 | Segunda pergunta captura a URL | 2, 4 | |
| RQ-05 | Sugestão derivada do nome escolhido antes | 2 | `Str::slug(config('app.name')).'.test'` |
| RQ-06 | Executa o procedimento documentado | 2, 4 | ADR-01 (elevação por UAC), ADR-03 (plataforma) |
| RQ-07 | Ajusta `APP_URL` no `.env` | 2, 4 | + `config()` em memória, ver ADR-02 |
| RQ-08 | Documentação de instalação atualizada | 5 | pt **e** en |
| RQ-09 | Decidir sobre `--force` | ADR-05 | **Decidido: não usa `--force`** |

## Objetivo

Fechar a última lacuna manual da instalação local: hoje o `kit:install` termina, imprime
`http://localhost:8000/app` e deixa para a pessoa descobrir, na página `dominio-local.md`, como
trocar isso por um domínio de verdade. São dois passos manuais (linha no `hosts` com elevação, e
duas chaves no `.env`) que o comando tem informação suficiente para oferecer sozinho.

A etapa é **opcional, idempotente e não-abortante**: quem apertar Enter segue com `localhost:8000`,
exatamente como hoje.

## Contexto

`docs/pt/comecar/dominio-local.md` documenta o procedimento completo, incluindo as armadilhas
(`-Encoding ascii`, `ipconfig /flushdns` que mente, Herd/Valet que já resolvem `*.test`). Nada
disso está no comando — a página existe porque o passo é manual.

## Análise dos Arquivos Existentes

### `app/Console/Commands/KitInstall.php`
`handle()` (`:66-123`) termina em `publicarAssets()` (107) → `construirFrontend()` (110) →
`desvincularDoSnyk()` (114) → `banner()` (117) → `resumoDaCustomizacao()` (118) →
`oferecerTestes()` (119) → `oferecerEstrela()` (120).

Dois pontos que o plano depende:
- `banner()` (`:416`) monta as URLs de acesso a partir de **`config('app.url')` em memória**
- `temTerminal()` (`:144-148`) é o gate de toda pergunta opcional; no Windows o Composer **nunca**
  repassa TTY ao script
- `$this->avisos[]` (`:53-54`): nenhum passo aborta a instalação; o que falha vira aviso

### `app/Support/CustomizadorDaInstalacao.php`
- A 1ª pergunta é `text('Nome do projeto', ...)` (`:136-141`) → `$respostas['nome']`
- Vira `APP_NAME` (`:281`), `COMPOSE_PROJECT_NAME = Str::slug($nome) ?: 'starter-kit'`
  (`nomeDeProjetoDocker():544-547`) e `config(['app.name' => …])` (`alinharConfigEmMemoria():562`)
- O diretório-base é **injetável** (`:32-37`) de propósito, para a suíte não reescrever o `.env`
  da máquina. **Toda escrita nova segue esse padrão.**

### `app/Support/SubstituicaoEmArquivo.php`
`definirNoEnv($env, 'CHAVE', $valor)` (`:71-81`) trata os três estados (preenchida / comentada /
ausente) e escapa `\ " $ \r \n` (`escaparValorDeEnv():91-98`). **Nada de `file_put_contents` cru.**

### `docs/pt/comecar/dominio-local.md`
Fonte canônica do procedimento. O comando de auto-elevação já está escrito lá (`:100-105`).

## Autorização

Não se aplica — comando de console, sem superfície HTTP.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** É comando de console; a interação é por Laravel Prompts no terminal.
Consequência: **não haverá `05-casos-de-teste-browser.md`** — nenhum cenário afirma sobre algo que
só o navegador prova.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `APP_URL` | `http://localhost:8000` | **já existe**; passa a ser reescrita pela etapa |

> **Nenhuma chave nova.** `.ai/rules/config.md` — "uma pergunta, uma dona": `APP_URL` já tem dona
> (`config('app.url')`). Inventar `KIT_HOST_LOCAL` seria criar uma segunda fonte para a mesma
> pergunta. `FORWARD_APP_PORT` fica **fora** desta entrega (ver Fora de Escopo do `00`).

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a etapa custa? | **zero** — é comando de console |
| O que é adiado? | nada |
| Custo | duas perguntas, uma escrita em `.env`, um `Start-Process` opcional |

## Impacto em Features Existentes

- **Login social**: trocar `APP_URL` invalida os callbacks `APP_URL + /auth/{provider}/callback`
  já registrados no console do provedor (`dominio-local.md:118-121`). **A pergunta precisa avisar.**
- **Vite / `npm run dev`**: serve de `localhost:5173` e restringe CORS (`:122-124`)
- **`banner()`**: lê `config('app.url')` — ver ADR-02
- **`tests/Kit/CustomizadorDaInstalacaoTest.php`**: mesma família de escrita em `.env`

## Rollback

Sem migration. Desfazer é: remover a linha do `hosts` (documentado em `dominio-local.md:126-129`) e
voltar `APP_URL`. A etapa é opt-in — não fazer nada já é o estado anterior.

## Dependências

Nenhuma nova. Laravel Prompts já é usado.

## Riscos

| Risco | Mitigação |
|---|---|
| UAC negado ou fechado pelo usuário | Conferir com `Select-String` depois; se não entrou, vira aviso com o comando para colar |
| `Start-Process -Verb RunAs` não devolve código confiável | **Não confiar nele**: o oráculo é reler o `hosts` |
| Antivírus / Acesso Controlado a Pastas bloqueia | Vira aviso, não aborta (`dominio-local.md:106-108`) |
| Linha já existe no `hosts` | Conferir antes de anexar — idempotência (`:72-73`) |
| Herd/Valet já resolvem `*.test` | Sondar e avisar que a linha pode ser desnecessária (`:42-44`) |
| Teste reescrever o `.env` da máquina | Diretório-base injetável, como o `CustomizadorDaInstalacao` |
| Config em cache | Imprimir "rode `php artisan config:clear`", como `KitInstall.php:184` já faz |

## Channel de Log da Feature

`config/logging.php` tem `ai` (114), `tenancy` (123), `autenticacao` (132), `configuracoes` (153).
**Não há canal `instalacao`**, e o `KitInstall` usa o `Log::` default.

**Decisão**: usar o canal **`configuracoes`**, que já é o canal de "reescrita de `.env` que vale no
próximo request" — é exatamente o que esta etapa faz (`CustomizadorDaInstalacao.php:363-367`).
Não criar canal novo: seria uma quinta dona para a mesma pergunta.

## Estrutura de Implementação

### 1. `App\Support\HostLocal` — a classe

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/HostLocal.php` (novo)
- Construtor com **diretório-base injetável** (`public function __construct(private string $base)`),
  espelhando `CustomizadorDaInstalacao.php:32-37`
- **Executor injetável** para o `Start-Process`, senão o teste eleva de verdade na máquina de quem
  roda a suíte. Assinatura: `__construct(private string $base, private ?Closure $executor = null)`

Métodos:

| Método | Responsabilidade |
|---|---|
| `dominioSugerido(): string` | `Str::slug(config('app.name')) ?: 'starter-kit'`, + `.test` |
| `urlSugerida(): string` | `'http://'.$this->dominioSugerido()` |
| `caminhoDoHosts(): string` | `match (PHP_OS_FAMILY)` — Windows vs `/etc/hosts` |
| `jaResolve(string $dominio): bool` | lê o `hosts` e procura o domínio — idempotência |
| `comandoDeElevacao(string $dominio): string` | monta o `Start-Process pwsh -Verb RunAs …` |
| `cadastrar(string $dominio): bool` | executa e **confere relendo o arquivo** |

- **Logs**:
  - `Log::channel('configuracoes')->info('[HostLocal@cadastrar] Host local cadastrado | dominio: {d}', ['dominio' => $d, 'caminho' => $this->caminhoDoHosts(), 'so' => PHP_OS_FAMILY])`
  - `Log::channel('configuracoes')->warning('[HostLocal@cadastrar] Host nao entrou no arquivo | dominio: {d}', ['dominio' => $d, 'motivo' => 'elevacao negada ou bloqueada', 'comando' => $cmd])`

### 2. Reescrita do `.env` e do config em memória

> Skills: `laravel-best-practices`

- `SubstituicaoEmArquivo::definirNoEnv($this->base.DIRECTORY_SEPARATOR.'.env', 'APP_URL', $url)`
- **E** `config(['app.url' => $url])` — ver ADR-02
- **Log**: `Log::channel('configuracoes')->info('[HostLocal@aplicarNoEnv] APP_URL ajustada | url: {u}', ['url' => $u, 'anterior' => $anterior])`

### 3. Ponto de chamada no `KitInstall`

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitInstall.php`
- Novo método `oferecerHostLocal()`, chamado em `handle()` **entre `desvincularDoSnyk()` (114) e
  `banner()` (117)** — ADR-02
- Gate: `if (! $this->temTerminal()) { return; }`, igual a `oferecerTestes():515`
- Falha vira `$this->avisos[]`, nunca `return self::FAILURE`

### 4. As duas perguntas

> Skills: `laravel-best-practices`

```php
// 1ª — com o exemplo derivado do nome (RQ-02 + RQ-05)
confirm("Cadastrar um domínio local para rodar o projeto? (ex.: {$hostLocal->urlSugerida()})", default: false)

// 2ª — só se sim (RQ-04), com a sugestão como default (RQ-05)
text('Qual domínio?', default: $hostLocal->dominioSugerido())
```

- `default: false` na 1ª: Enter reproduz a instalação de sempre
  (`CustomizadorDaInstalacao.php:21-24`)
- Antes de executar, **avisar sobre login social e Vite** (`note(...)`), porque trocar `APP_URL`
  quebra callbacks já registrados
- Validar o domínio: sem esquema, sem barra, sem espaço; recusar `.local` (RFC 6762 — a doc já
  proíbe em `:110-114`)

### 5. Documentação

> Skills: —

- `docs/pt/comecar/instalacao-avancada.md` e `docs/en/…`: a etapa nova no fluxo do `kit:install`
- `docs/pt/comecar/dominio-local.md` e `docs/en/…`: nota de que o `kit:install` **oferece** fazer
  isso, com link cruzado
- `README.md:312-314` e `README.en.md:312-314`: uma linha
- `CHANGELOG.md`: entrada em `[Unreleased] → Adicionado`
- **Links internos em markdown de `docs/` são RELATIVOS** — regra recém-estabelecida nesta mesma
  sessão (`[CT-45]` de `tests/Kit/SiteDeDocumentacaoTest.php`); caminho absoluto dá 404 sob o
  `base` do GitHub Pages

### 6. Testes

> Skills: `pest-testing`, `feature-test-design`

- **Path**: `tests/Kit/HostLocalTest.php`
- Padrão obrigatório de `tests/Kit/CustomizadorDaInstalacaoTest.php:24-34`: `beforeEach` cria
  diretório temporário e copia `.env.example` para lá
- Executor **mockado** — nenhum teste eleva de verdade
- Cenários derivados pela `feature-test-design` a partir do `00-requisito.md`

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Reaproveitar `SubstituicaoEmArquivo` e o padrão de
> `match (PHP_OS_FAMILY)` que já existe em `KitInstall.php:565-570`. Não criar `.ps1` novo na raiz
> (sujaria o repositório de quem instala). Não criar canal de log novo.

## Testes

Ver `04-casos-de-teste.md`. **Sem `05-casos-de-teste-browser.md`** — sem superfície de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --filter=HostLocal --compact`
- [ ] `vendor/bin/pest --filter=CustomizadorDaInstalacao --compact` (regressão da escrita de `.env`)
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] `vendor/bin/filacheck --fix` — **n.a.**, não toca `app/Filament`
- [ ] Teste manual do UAC numa instalação real (o único caminho que a suíte não cobre por desenho)
- [ ] `/code-review` no diff (step 7.5)
- [ ] `feature-quality-gate` (step 8)

## Commits

Individualizados (PR-03 do `00`):

- `:memo: docs(wiki): requisito, plano e ADRs do host local no kit:install`
- `:sparkles: feat(install): App\Support\HostLocal`
- `:sparkles: feat(install): oferece o host local ao final do kit:install`
- `:white_check_mark: test(install): casos do host local`
- `:memo: docs(install): a etapa de host local na documentacao de instalacao`
