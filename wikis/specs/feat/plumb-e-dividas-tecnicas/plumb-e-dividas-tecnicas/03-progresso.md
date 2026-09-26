# Progresso — Plumb a 100 e as dívidas técnicas aparadas

## 1. Limpeza da árvore (RQ-01, RQ-02)

- [x] Worktrees `starter-kit-easy-compact` e `starter-kit-easy-tenant-link` removidos — os dois
  limpos (0 alterações) e 0 commits à frente de `main`, conferido antes
- [x] Branches `feat/layout-compact` e `feat/link-painel-do-tenant` apagadas
- [x] Restou só `main` — `git worktree list` tem uma linha, `git branch` tem uma linha
- [x] Zero commits locais não enviados: `git log --branches --not --remotes` volta vazio

## 2. Plumb: o que é acionável, medido antes de tocar (RQ-03)

- [x] Score de partida: **79/100, "Fair"** — Security 55 %, Maintenance 30 %, Ecosystem 15 %
- [x] Relatório extraído e **cada check classificado** em acionável × não acionável — tabela no `00`
- [x] **`security/actions-sha-pinned`**: 13 de 23 pinadas, **10 não**. Corrigido: **23 de 23**
  - seis `actions/cache@v4` no `ci.yml`
  - `checkout@v4`, `setup-node@v4`, `upload-pages-artifact@v3`, `deploy-pages@v4` no `pages.yml`
- [x] **`security/dependency-update-cooldown`**: não configurado. Corrigido nos três ecossistemas,
  com janelas **escalonadas por risco do salto** (major 14 d, minor 7 d, patch 3 d, default 5 d) e
  não uniformes
- [x] **`tests/Kit/AcoesPinadasPorShaTest.php`** — a guarda que fecha a **classe**, não a ocorrência
  - `[CT-01]` toda action de terceiro pinada por SHA
  - `[CT-02]` todo SHA com o rótulo da versão ao lado, senão o diff de bump é ilegível
  - verificados com dois mutantes: voltar uma para `@v4` reprova; tirar o rótulo reprova
- [ ] Rescan do Plumb depois do merge — o score novo só sai quando ele reler o repositório

### O que **não** é acionável, e fica declarado

`security/gitlab-ci-pinned` e `security/renovate-mr-responsiveness` só se aplicam a repositório
**GitLab**. Nenhum esforço neste repositório os move, e prometer 100 redondo seria promessa que
envelhece no primeiro rescan.

## 3. Dívida técnica: o middleware que não chega a quem atualiza (RQ-04, RQ-05)

> Item **7** do `wikis/roadmap.md`, que o próprio roadmap classifica como *"débito com consequência
> medida, não melhoria"*. É o único dos oito que é **defeito em produção**, e por isso foi o
> escolhido.

- [x] Defeito confirmado: `RaizDeUrlSemPublic` entrou na **v0.36.1**; `bootstrap/` **não viaja** por
  nenhuma das duas rotas de entrega. Quem instalou entre a v0.16.0 e a v0.36.0 e vem rodando
  `kit:update` **tem a classe** e **não tem o registro** — a correção de URL não funciona, e o
  sintoma é silencioso
- [x] **Rede de segurança, e não realocação** — `KitServiceProvider::garantirRaizDeUrlSemPublic()`
- [x] A ordem preservada, e ela é **requisito**: o middleware precisa rodar depois do
  `TrustProxies`, senão leria host e porta sem os cabeçalhos `X-Forwarded-*`
- [x] Verificado no stack real: **posição 9**, `TrustProxies` na 3, **uma ocorrência só** —
  `pushMiddleware()` é idempotente (`Kernel.php:364`), então em instalação nova o método é no-op e
  a posição original fica intacta
- [x] **`tests/Kit/RaizDeUrlRegistradaTest.php`**, 4 casos

### O mutante que sobreviveu, e o caso que nasceu dele

Com três casos escritos, rodei o mutante que **apaga a chamada de dentro do `boot()`**. Ele
**sobreviveu**: o `[CT-02]` invoca o método privado por reflexão, então provava que o método
funciona e **não** que alguém o chama.

É a forma mais barata de a correção inteira sumir — um `boot()` reorganizado, uma linha perdida num
merge, e o defeito volta exatamente como estava, em silêncio, só para quem atualiza.

Daí nasceu o **`[CT-04]`**, que lê o fonte do `boot()` **sem comentário** (citar não é executar) e
exige a chamada. Os dois mutantes morrem agora: apagar a linha, e comentá-la.

## 4. As duas dívidas que eu devia da v0.40.0

- [x] **Teto de pulados justificado por causa.** Devido desde a v0.40.0 e não entregue: eu havia
  tentado estimar por `grep naArvoreDoKit` e errado (+11 previsto, +9 medido), porque a chamada
  também aparece dentro de corpo de caso e dataset de N linhas conta N pulados. Medido agora com
  `--log-junit`:

  | Causa | Pulados |
  |---|---:|
  | `DuasRotasDeEntregaTest` (novo na v0.40.0) | 3 |
  | `RecorteDaCoberturaTest` (novo) | 2 |
  | `ArquiteturaDoCodigoTest` (novo) | 1 |
  | `ConstraintDeDependenciaTest` (a sentinela da v0.40.1) | 1 |
  | `SiteDeDocumentacaoTest` — `[CT-49]`, `[CT-50]`, `[CT-51]` | 3 |
  | **total** | **10** |

  Teto da v0.39.1: **180**. Medido na v0.40.1: **190**. O `+10` fecha **exatamente**, e todas as
  causas são casos que só valem na árvore do kit. **Novo teto: 190.**

- [x] **Cenários 3 e 4 re-rodados por inteiro** na v0.40.1, não só o caso que falhava

## Verificação Final

- [x] `vendor/bin/pint --test --format agent` — `passed`
- [x] `vendor/bin/phpstan analyse --no-progress` — `0 erros`
- [x] Guardas novas mortas com mutante, **nas duas direções**, as quatro
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — a rodar
- [ ] Quatro cenários — a reconfirmar depois do merge

## Blockers

- Nenhum.

## Notas de Implementação

**O cenário 4 falhou uma vez e não era defeito.** `AdminDaOrganizacaoTest` reprovou num run cuja
**duração foi de 3 h 25 min** — inanição de recursos, porque rodou concorrente com a medição de
cobertura e com os outros cenários. Isolado, o arquivo passa em 52 s com 28 casos e 151 asserções.

Registrado porque a tentação de anotar *"1 falha no cenário 4"* e seguir é real, e seria falso.
**Duração implausível é sintoma do arnês, não resultado** — a mesma regra que o `pestw.cmd` aplica
ao mutation score, do outro lado.
