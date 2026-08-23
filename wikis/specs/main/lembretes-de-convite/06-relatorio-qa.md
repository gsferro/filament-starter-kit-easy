# Relatório de QA — Lembretes de convite

**Data**: 2026-08-23 · **Ciclo**: 1 de 3
**Oráculo**: `01-plano-acao.md` — **fraco**, e declarado

> Sem `00-requisito.md` e sem seção com a fala do usuário. Das 48 passagens entre aspas do
> plano, as que mais parecem citação são do próprio autor: *"mandar e-mail para quem eu quiser"*
> (`01:109`) é a descrição de um risco que ele **recusa**, e *"o link anterior deixa de
> funcionar"* (`01:337`) é a cópia da modal de confirmação. Nenhuma é pedido do usuário.

**Veredito: APROVADO.** Nenhum achado de implementação nesta feature. Dois de observação,
declarados abaixo sem virar achado. **Mas a varredura que começou aqui encontrou um Major na
retenção de exceções**, corrigido nesta rodada e documentado ao fim.

---

## Cobertura: completa, e a mais densa das auditadas

| Verificação | Resultado |
|---|---|
| CT especificados | 11 |
| Onde vivem os testes | `tests/Kit/ConviteTest.php` — o `04` nomeia esse arquivo, e ele existe |
| Casos que tocam lembrete, reenvio ou token | **13** dos 27 do arquivo |

Correspondência conferida caso a caso:

| CT | Caso |
|---|---|
| CT-01 | `lembra conforme o cronograma, um lembrete por convite por execucao` |
| CT-02 | `lembra com um link novo sem invalidar o do envio` |
| CT-03 | `mantem vivos apenas o link do envio e o do ultimo lembrete` |
| CT-04 | `nao aceita token de lembrete de convite aceito, recusado nem expirado` |
| CT-05, CT-06 | `nao lembra convite ja aceito`, `nao lembra convite fora de jogo` |
| CT-07 | `registra a execucao no channel autenticacao sem vazar token` |
| CT-08 | `manda o e-mail de lembrete com assunto proprio` |
| CT-09 | `reinicia o relogio de lembretes quando o convite e reenviado` + `reenvia com token novo e mata o anterior` |
| CT-11 | `termina com sucesso sem convite pendente e com os lembretes desligados` |

### O que sustenta a nota

A feature mantém **dois tokens válidos ao mesmo tempo** — o do envio e o do último lembrete — e
é a única do kit com essa propriedade. Era o motivo de ela estar na fila de risco. Três coisas
foram conferidas no código:

1. **O par de tokens não escapa dos filtros de estado.** `Convite::valido()` envolve o par num
   `where(closure)`, e não num `orWhere` solto — sem o parêntese, o `OR` partiria o `WHERE` e um
   convite aceito, recusado ou expirado voltaria a valer pelo token de lembrete. CT-04 é caso
   próprio para isso, com os três estados no mesmo teste.
2. **O reenvio mata os dois links.** `enviar()` grava `token_lembrete = null` no mesmo
   `forceFill`, e o docblock diz por quê: sem essa linha, a promessa da modal (*"o link anterior
   deixa de funcionar"*) seria mentira pela metade. CT-09 cobre.
3. **Os dois hashes ficam fora do `$fillable`**, logo fora da trilha de `/infra/audits`, de
   `toArray()` e de `dd()`. CT-07 assere ausência de token no contexto do log — oráculo de
   ausência, que é o mais difícil de escrever e o único que serve aqui.

O teto é `count($dias)` no lado do banco, e a lógica é "quantos eram devidos até hoje? mandou
menos? manda um" — um por convite por execução **por construção**, não por contador. É o que faz
cron parado uma semana se recuperar sem rajada, e CT-01 mede isso em dataset.

---

## Duas observações que **não** viraram achado

Registradas porque foram investigadas e a decisão de não escalar é informação.

**Dia negativo na lista.** `KIT_CONVITE_LEMBRETES_DIAS=-3` sobrevive ao parsing
(`array_filter` remove o zero, não o negativo), e `now()->subDays(min($dias))` com valor negativo
põe o corte no futuro — o primeiro lembrete sai no mesmo dia do envio. **Não é defeito**: o teto
de `count($dias)` continua valendo, então não há rajada, e o efeito é um lembrete adiantado, não
um convite quebrado. O `.env.example` já exige que cada dia seja menor que a validade; um número
negativo é configuração absurda com consequência proporcional.

**Dias duplicados inflam o teto.** `KIT_CONVITE_LEMBRETES_DIAS=3,3,5` produz `[3,3,5]` — o
parsing não deduplica —, então o teto passa a 3 para dois dias distintos: alguém recebe um
lembrete a mais. Medido. **Não escalei** porque o dano é um e-mail extra e o `array_unique`
mudaria o significado da chave (a lista é a intenção declarada, e deduplicar silenciosamente é a
mesma classe de "corrigir sem avisar" que este relatório cobra dos outros). Fica como decisão
disponível, não como dívida.

---

## O Major que a varredura encontrou — fora desta feature

Investigar como esta wiki lê `lembretes_dias` levou a varrer **todo** `(int) env(...)` do
`config/`. São 13 ocorrências; 5 são do kit. E uma delas apagava dado.

**`KIT_RETENCAO_EXCECOES_DIAS` vazio, `0` ou negativo apagava a trilha de exceções inteira.**

- `modelPruneInterval()` recebe uma **data**, e `Exception::prunable()` faz
  `whereDate('created_at', '<=', $intervalo)`
  (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:44`). `whereDate` compara só
  a data.
- Com valor vazio → `(int) ''` = 0 → `subDays(0)` = **hoje** → o corte casa com a tabela inteira,
  inclusive as linhas do dia. Negativo é pior: `subDays(-5)` põe o corte no futuro.
- E o bloco `retencao` do `config/kit.php` **promete por escrito** que "zero ou negativo desliga
  a poda daquela trilha". As três podas de `routes/console.php` honram com
  `if ($dias <= 0) return;`. Esta era a quarta, e fazia o **oposto** do documentado.

O que se perde é a trilha que responde "qual exception está estourando" — exatamente a evidência
que se procuraria depois. Severidade **Major**: perda de dado silenciosa, contra uma garantia
documentada.

**Correção**: a decisão saiu do provider para `App\Support\RetencaoDeExcecoes::corte()`, e as
cinco chaves do kit passaram por `App\Support\NumeroDoEnv`, com duas regras nomeadas —
`positivo()` recusa o zero, `diasOuDesligado()` o respeita. Guardas em
`tests/Kit/NumeroDoEnvTest.php` (16 casos) e `tests/Kit/PaginasInfraTest.php` (5), as duas vistas
falhando.

> **A primeira versão da guarda da poda não provava nada**, e o registro importa: ela media
> `FilamentExceptionsPlugin::get()->getModelPruneInterval()` depois de mudar a config — mas o
> provider registra o plugin **uma vez, no boot**, então o corte medido era o do boot. Ficava
> vermelho pelo motivo errado. É o terceiro caso nesta auditoria em que a verificação media
> plumbing em vez da regra, e a saída foi a mesma: puxar a decisão para onde ela se prova.

---

## Dimensões

| | Dimensão | Estado | Nota |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | 11/11 CT cobertos; o ⚠️ é do **oráculo** |
| B | Fronteiras | ✅ | aceito, recusado, expirado, `enviado_em` nulo, teto, banco vazio |
| C | Matriz de permissão | n/a | comando de console: a fronteira é quem roda `artisan` |
| D | Log real | ✅ | CT-07 assere **ausência** de token nos dois logs |
| E | N+1 | ✅ | `chunkById(100)` — e o `04` cita o defeito do pacote que não fez isso |
| I | Segurança da superfície nova | ✅ | dois tokens vivos, com o `where(closure)` provado em CT-04 |
| J | Regressão adjacente | ✅ | CT-09 amarra com o reenvio da wiki de convite |
| K | Adequação da suíte | ✅ | 13 casos para 11 CT, com oráculo de ausência onde importa |

## Ações

Nenhuma nesta feature. As duas observações acima ficam registradas como decisão disponível.
As correções do Major estão creditadas aqui porque a varredura nasceu deste gate.
