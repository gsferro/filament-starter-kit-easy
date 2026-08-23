---
paths:
  - 'config/**'
---

# Config

## Valor vazio no .env vira 0 — nunca (int) env() direto
`(int) env('CHAVE', 100)` é padrão defeituoso. O segundo argumento do `env()` só vale para chave **ausente**: com `CHAVE=` (presente, valor vazio — o que sobra quando alguém apaga o número e esquece o `=`), `env()` devolve string vazia, `(int) ''` é **0**, e o default nunca entra.

Use `App\Support\NumeroDoEnv`, e escolha a regra pelo significado do zero naquela chave:

- `positivo($bruto, $padrao)` — grandeza que **precisa** de número. Vazio, `0` e ausente caem no default; negativo e texto caem em 1.
- `diasOuDesligado($bruto, $padrao)` — prazo em que **zero é escolha legítima** (as retenções). Só vazio e ausente caem no default; `0` escrito à mão desliga.

Unificar as duas é o erro: obriga a escolher um significado para o zero, e as retenções precisam do oposto de um limite de lote. Há caso de teste só para esse contraste (`tests/Kit/NumeroDoEnvTest.php`).

**Medido, não hipotético.** Cinco chaves do kit nasceram com o padrão, e o zero significava coisa diferente em cada uma: `KIT_CONVITE_VALIDADE_DIAS=` fazia o convite nascer expirado (v0.18.4); `KIT_CONVITE_LIMITE_LOTE=` fazia o convite em massa recusar **todo** lote, culpando a entrada da pessoa; e `KIT_RETENCAO_EXCECOES_DIAS=` **apagava a trilha de exceções inteira** — `subDays(0)` é hoje, e `Exception::prunable()` faz `whereDate('created_at', '<=', $corte)`, que casa com a tabela toda (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:44`).

**E quando achar um destes, varra o resto antes de consertar.** A v0.18.4 corrigiu uma chave, escreveu no comentário que era fronteira de confiança que já havia falhado, e não varreu — o defeito da poda ficou vivo duas releases a mais. `grep -rn '(int) env(' config/` custa segundos.

Prazo que aceita "desligado" precisa do guarda no **consumidor** também: três podas de `routes/console.php` têm `if ($dias <= 0) return;` e a de exceções não tinha, apesar de o `config/kit.php` prometer por escrito que zero desliga. Ver `App\Support\RetencaoDeExcecoes`.
