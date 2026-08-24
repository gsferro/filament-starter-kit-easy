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

## Interruptor de env que abre superfície pública falha FECHADO, e a chave tem uma dona só
Duas lições da mesma rodada, as duas sobre fronteira de configuração.

**1. Falhe fechado.** Chave que abre superfície pública (registro aberto, login social, OAuth) usa `filter_var(env('X', false), FILTER_VALIDATE_BOOLEAN)`: `false`, `0`, `off`, `no`, vazio e qualquer valor irreconhecível mantêm desligado; só `true` e `1` ligam. Interruptor de segurança não liga por acidente. Para o resto, `(bool) env()` basta — o Laravel já converte a string `"false"`, e o que separa os dois casos é a direção do erro, não a coerção.

**2. Uma pergunta, uma dona.** Não invente nome de chave para uma configuração que outra parte do sistema já governa. O login social leu `kit.registro.aberto`; a feature de registro criou `kit.registro.habilitado`. Chave inexistente devolve o default, então o consumidor respondia `false` para sempre — e ligar o registro na tela liberaria o cadastro pelo formulário e **não** pelo login social, sem erro nenhum, do lado que não tem tela para conferir. A correção foi delegar para a classe dona da pergunta.

**O detalhe que faz isso valer registro é o teste**: `config()->set()` aceita QUALQUER chave. Dois casos que exercitavam a criação de conta setavam a chave imaginária e ficavam **verdes**, enquanto a produção recusava. Teste verde sobre configuração que nunca existiu.

Sinal de alerta: `config()->set('chave.que.voce.acha.que.existe')` em teste, sem `grep` no `config/` confirmando que ela existe. E na revisão: duas classes respondendo a mesma pergunta de config por caminhos diferentes.

Vale também o irmão já documentado neste arquivo: o segundo argumento do `env()` só cobre chave AUSENTE, não vazia — use `?:` para texto e `NumeroDoEnv` para inteiro.
