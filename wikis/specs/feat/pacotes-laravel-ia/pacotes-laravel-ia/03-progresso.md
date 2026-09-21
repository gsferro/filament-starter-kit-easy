# Progresso — Pacotes Laravel para o ecossistema de IA

## Análise (RQ-01)

- [x] `laravel/vet` analisado — plugin do Composer, ganchos, `vet.json`, comandos, integração com agentes, veredito sobre `create-project` — ADR-03, 2026-09-21
- [x] `laravel/pao` analisado — mecanismo de detecção lido no **código** (o README não documenta), efeito por ferramenta, `PAO_DISABLE`/`PAO_FORCE` — ADR-01/02, 2026-09-21
- [x] `laravel/moat` analisado — CLI em Rust, Homebrew, auditoria read-only do GitHub — ADR-05, 2026-09-21

## Decisões (RQ-02)

- [x] ADR-01 — `pao` já instalado; entrega é documentação, 2026-09-21
- [x] ADR-02 — `pao` **deve** viajar no `create-project`; é o caso oposto ao do Blueprint, 2026-09-21
- [x] ADR-03 — `vet` **adiado até a 1.0**, 2026-09-21
- [x] ADR-04 — `collision` `^8.6` → `^8.9.3`, 2026-09-21
- [x] ADR-05 — `moat` documentado como ferramenta do mantenedor, 2026-09-21
- [x] ADR-06 — `php` `^8.3` → `^8.4`, 2026-09-21

## Implementação

- [x] `composer.json` — `collision` `^8.9.3` — commit `5807818`, 2026-09-21
- [x] `composer.json` — `php` `^8.4` — commit `3e2903f`, 2026-09-21
- [x] `composer.lock` — hash atualizado, **zero pacote alterado** (`git diff | grep '"version"'` vazio) — commit `634dece`, 2026-09-21
- [x] `docs/{pt,en}/referencia/pacotes-instalados.md` — descrição real do `pao` — commit `6ada8ef`, 2026-09-21
- [x] `docs/{pt,en}/operacao/agentes-de-ia.md` — seção do `pao` + seção do `moat` — commit `6ada8ef`, 2026-09-21
- [x] Contadores dos readmes sincronizados (62 → 63) — commit `b096dbc`, 2026-09-21

## Verificação Final

- [x] `composer validate --strict` — válido; resta **só** o aviso pré-existente de `spatie/laravel-backup: "*"`, conferido em `HEAD~3`, 2026-09-21
- [x] `composer update --lock` — lock consistente, sem mudança de pacote, 2026-09-21
- [x] `composer why-not php 8.3.0 --locked` — **24 pacotes de produção** e 31 de dev exigem ≥ 8.4, 2026-09-21
- [x] `php artisan test --compact` docs guards — **74/74, 283 asserções**, 2026-09-21
- [x] `composer test:kit` — **2.614/2.614, 10.164 asserções**, 2026-09-21
- [x] `vendor/bin/pint --dirty --format agent` — `passed`, 2026-09-21
- [x] **Rebase sobre a `main`** — a worktree nasceu da branch da wiki `host-local` e carregava 2 commits que não eram desta entrega. Refeita com `git rebase --onto origin/main`; agora são **7 commits, todos desta wiki**, 2026-09-21
- [ ] `/code-review` no diff (step 7.5)
- [ ] `feature-quality-gate` (step 8)

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `general.md` — dependência privada não viaja no pacote | `composer.json` | **aplicada** | ADR-02 mostra que o veto é sobre **repositório privado**; o `pao` é Packagist público e MIT, então fica |
| `specs.md` — citação de vendor exige ler o `vendor/` e citar `file:line` | `wikis/specs/**` | **aplicada** | Todo comportamento do `pao` afirmado nas ADRs veio de `vendor/laravel/pao/src/` e de execução real, **não** do README |
| `config.md` | `config/**` | **n.a.** | Nenhum arquivo de `config/` tocado |

## Notas de Implementação

- **O `laravel/pao` já estava instalado**, e essa é a descoberta que reformulou a wiki. O requisito
  pedia "instalar"; `git log -S'laravel/pao' -- composer.json` devolve **um único commit**, o do
  esqueleto. Sem essa verificação, a entrega teria sido um `composer require` de algo já presente.
- **O README do `pao` é vago justamente no ponto que mais importa.** Ele diz "hooks … automatically
  through Composer's autoloader" e não documenta nem `PAO_DISABLE` nem `PAO_FORCE`. Tudo o que as
  ADRs afirmam veio do código do pacote e de execução medida nesta máquina.
- **`raw.githubusercontent.com/laravel/pao/main/README.md` dá 404** — o branch padrão é `1.x`.
- **Defeito do `pao` no Windows, para issue no upstream**: nos `failures[]`, o caminho do arquivo
  **perde a letra do drive** (`\PROJECTS\...` em vez de `C:\PROJECTS\...`), porque o regex de
  extração não inclui `:` na classe de caracteres. Impacto baixo (o arquivo ainda é identificável),
  mas um agente que tente abrir o caminho literal falha. **Só afeta Windows**, logo não afeta o CI.

## Desvios do Plano

- **Nenhum `composer require` nesta entrega.** O requisito diz "instalar" três pacotes; a análise
  que o próprio requisito pediu primeiro concluiu que um já está instalado, um deve esperar e um
  não é pacote PHP. Confirmado com o usuário em 2026-09-21 ("pode seguir assim mesmo").
- **A contagem de "29 pacotes exigindo 8.4" que eu havia afirmado estava errada** — o regex inicial
  casava `^8.2|^8.3|^8.4`, que **admite** 8.3. Refeito com `composer why-not php 8.3.0 --locked`,
  que é o oráculo autoritativo: **24** de produção. Corrigido antes de entrar no commit.

## Débitos Registrados

- **`laravel/vet` — reavaliar quando sair a 1.0.** Gatilho explícito: a release estável. A ADR-03
  já tem as duas formas de adoção avaliadas (`require-dev` com proteções, ou o padrão
  `vet:on`/`vet:off`), então a reavaliação é decisão, não pesquisa nova.
- **`spatie/laravel-backup: "*"`** — constraint sem limite, **pré-existente** (conferida em
  `HEAD~3`). Fora do escopo desta entrega para não misturar com a correção das outras duas.
- **O portão `runningUnitTests()` é do vendor.** É ele que impede o `pao` de quebrar as 15
  asserções sobre texto de saída do kit. Se um upgrade do `pao` o remover, essas asserções quebram
  **só sob agente**. Não vale guarda hoje — a guarda teria de rodar sob agente para significar algo.

## Retrospectiva

- **Funcionou bem**: ter mandado os subagentes lerem o **código do pacote**, e não o README. Os dois
  achados que mais mudaram a entrega (`pao` já instalado; `PAO_DISABLE` lido de `$_SERVER`) estão
  invisíveis na documentação oficial.
- **Faltou**: eu afirmei "29 pacotes de produção exigem 8.4" a partir de um regex frouxo, e repeti
  o número para o usuário antes de conferir. O oráculo certo (`composer why-not`) levava segundos e
  devolveu 24. Número que sai de regex sobre constraint de versão precisa ser conferido pela
  ferramenta que entende a gramática.
