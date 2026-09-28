> Referência da feature-test-design 1.16.0. Lida em: passo 6 (operadores que servem de fonte dos
> mutantes previstos) e no pós-implementação (comandos do `pest --mutate`, lançador do Windows,
> tradução do sobrevivente em lacuna). Fonte única de: os comandos do mutation testing com Pest e
> o detalhe das armadilhas (mecanismo, sintoma, números). A regra de cada armadilha fica no
> `SKILL.md` §Fechamento do Ciclo com Mutation Testing.

# Mutation testing — operadores, comandos e tradução do sobrevivente

As obrigações (driver com prova negativa, plugin declarado no `composer.json`, `pest()->mutate()`
inexistente, uma suíte por comando, escopo, `Duration` plausível) e a conclusão sobre o que o
score não responde estão no `SKILL.md` §Fechamento do Ciclo com Mutation Testing.

## Fonte dos mutantes (passo 6)

**Fonte dos mutantes** — os operadores que as ferramentas de mutação usam de verdade, porque são
os erros que os humanos cometem:

| Operador | Mutante | Lacuna de derivação correspondente |
|---|---|---|
| relacional | `>` ↔ `>=`, `<` ↔ `<=`, `==` ↔ `!=` | falta BVA na fronteira |
| lógico | `&&` ↔ `\|\|`, condição negada | falta linha da tabela de decisão |
| retorno | `return $x` → `return null` / `true` → `false` | assertion ausente ou fraca sobre o retorno |
| **remoção de chamada** | o `Mail::send`, o `->increment()`, o `event()` disparado some | falta assertion de efeito colateral |
| literal | número → `0`/`1`, string → `''`, array → `[]` | valor mágico não verificado |
| aritmético | `+` ↔ `-`, `*` ↔ `/` | falta assertion sobre o **valor** calculado |

> Este é o passo que responde à pergunta que motiva a skill. Um conjunto de CT que não declara
> o que ele mata não tem como ser auditado — e é assim que o requisito fica ✅ na Matriz de
> Rastreabilidade com o defeito passando batido.

## O experimento que mostrou a cegueira à omissão

Medido em experimento controlado, contra a **mesma** implementação: a suíte derivada por gabarito
e a suíte derivada pelo pipeline reportaram o **mesmo** mutation score na classe sob teste e
detectaram quantidades diferentes dos defeitos plantados. Os números estão em
`experimentos/README.md` do repositório da coletânea
(<https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md>), com a ressalva de
que o score daquela medição foi tirado no Windows sem `Duration` registrada e não está verificado;
o argumento estrutural (a blockquote do `SKILL.md`) não depende dele.

A razão da diferença de detecção:
os defeitos que as separam são **comportamentos ausentes** — validação que ninguém escreveu,
transição que ninguém barrou. Não existe linha para mutar.

## Como rodar (comandos verificados)

```bash
vendor/bin/pest tests/Feature/{Feature} --mutate --path=app/Services
vendor/bin/pest tests/Feature/{Feature} --mutate --path=app/Services --min=70
```

- **Filtro: `--path=` é o verificado; `--class=` é o fallback.** `--path=` funcionou nas medições
  de 2026-09-21 com Pest 5, embora não conste na referência de CLI do Pest (que lista `--class`,
  `--ignore`, `--covered-only`, `--min`, `--everything`, `--parallel`). Se a versão instalada não
  aceitar `--path`, usar `--class='App\Services\X'`. É o mesmo conselho da dimensão K do
  `feature-quality-gate`
- **No Windows, `pest --mutate` dá 100 % falso.** O plugin relança `argv[0]` (`vendor/bin/pest`,
  proxy sem extensão — script sh ou PHP, conforme a versão do Composer) por Symfony Process; o `cmd`
  não o executa, cada subprocesso sai com código 1 em ~30 ms e o plugin conta saída não-zero como
  mutante morto. Sintoma: *206 mutantes em 3 s* para uma suíte de 200 s. **Score só vale com
  `Duration` compatível com N × tempo dos testes cobridores e com a lista de sobreviventes.**
  Solução: o `.cmd` poliglota `{skills}/feature-wiki/scripts/pestw.cmd` (batch que chama `php` sobre
  si mesmo e, como PHP, faz `require` do `vendor/pestphp/pest/bin/pest` do diretório corrente),
  rodado da raiz do projeto — no Git Bash,
  `cmd //c "$(cygpath -w {skills}/feature-wiki/scripts/pestw.cmd)" … --mutate --path=… --covered-only --parallel`
  (com Xdebug, prefixar `XDEBUG_MODE=coverage`; o `cygpath -w` é obrigatório, e o nome solto
  `pestw.cmd` não é achado no Bash do Claude Code). A forma do PowerShell e o porquê de cada detalhe
  estão em `{skills}/feature-wiki/references/pest-5.md`; a do `cmd`, no cabeçalho do próprio
  `pestw.cmd` (`{skills}`: ver o Glossário do `SKILL.md`). Medido de verdade: 206 mutantes, 196
  mortos, 7 timeout, 3 sobreviventes, 98,54 % em 594 s
- **Armadilha medida em `experimentos/` (<https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md>): `covers(X::class)` restringe o que conta como coberto.** Mutantes em
  qualquer classe fora do `covers()` são reportados como `uncovered` e o score vai a **0%** —
  mesmo que os testes executem aquele código em toda chamada. Para medir uma classe vizinha,
  declare-a em `covers()`/`mutates()` ou meça em execução separada

## Tradução do sobrevivente em lacuna de derivação

**Cada mutante sobrevivente é traduzido de volta para a lacuna de derivação** e vira cenário novo:

| Mutante sobreviveu | Lacuna | O que escrever |
|---|---|---|
| `>` → `>=` | BVA faltando | cenário na borda exata |
| `&&` → `\|\|` | linha da tabela de decisão faltando | cenário da combinação |
| `return $x` → `return null` | oráculo fraco | assertion sobre o **valor** |
| chamada removida | efeito colateral não verificado | cenário de rastreio de efeito |
