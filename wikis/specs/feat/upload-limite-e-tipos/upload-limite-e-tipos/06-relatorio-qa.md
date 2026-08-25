# Relatório de QA — Limite e tipos de upload

**Ciclo**: 1 de 3. **Data**: 2026-08-25. **Base**: `origin/main` em `21cbb80` (v0.19.4).
**Veredito**: `APROVADO COM DÉBITO` — dois achados encontrados e **corrigidos** dentro do ciclo,
um débito declarado.

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo do PRD | Casos | Código | Situação |
|----|----------|--------------|-------|--------|----------|
| RQ-01 | todo upload aceita até 10 MB | 1, 3, 4, 5 | CT-02, CT-04, CT-10, CT-12, CT-21 | `config/kit.php`, os cinco campos | ✅ |
| RQ-02 | acima do teto é recusado | 3, 4, 5 | CT-04, CT-05, CT-06, CT-10, CT-12, CT-21 | `->maxSize()` + regra global do Livewire | ✅ |
| RQ-03 | upload recusa SVG | 3, 4 | CT-07, CT-07b, CT-09, CT-11, CT-13, CT-16 | `mimes:` nos de imagem, regra de recusa no `anexos` | ✅ |
| RQ-04 | qualquer outro tipo de imagem é aceito | 3 | CT-08 (12 partições), CT-23 | `FORMATOS_DE_IMAGEM` | ✅ **após correção — ver QA-01** |
| RQ-05 | o teto vive na config do kit | 1, 2 | CT-01, CT-02, CT-21, CT-22 | `kit.uploads.maximo_em_kb`, `TetoDeUpload` | ✅ |
| RQ-06 | documentado para mudar com facilidade | 2, 6 | CT-18, CT-19, CT-20 | `.env.example`, os dois READMEs | ✅ |

**Nenhuma cláusula órfã.** Todo `RQ` tem passo, caso e código, e nenhum caso existe sem `RQ` de
origem.

## Achados

### QA-01 — `.ico` recusado no campo de favicon, com o kit embarcando um `.ico`

- **Severidade**: **Major** — RQ-04 violada num caso concreto e provável.
- **Destino**: **implementação** (e, de tabela, **especificação**: a ADR justificava o defeito).
- **Como apareceu**: dimensão *segurança e adequação da superfície nova* cruzada com o disco. Não
  saiu de caso de teste nenhum — saiu de um `ls public/`.
- **Reprodução**: um `.ico` real (cabeçalho de ícone + PNG embutido) tem
  `getMimeType() === 'image/vnd.microsoft.icon'` e `guessExtension() === 'ico'`; a regra `image` do
  Laravel **reprova**, porque a lista dela tem nove extensões e `ico` não é uma.
- **Por que é achado e não trade-off**: ADR-02 aceitava a perda com a frase *"favicon moderno é
  PNG, e é o que o kit já usa"*. **O kit serve `public/favicon.ico`.** A frase foi escrita a partir
  do que se esperava encontrar — o padrão que `.ai/rules/specs.md` manda desconfiar — e desta vez a
  conclusão também estava errada, não só o motivo.
- **Corrigido**: barreira trocada para `mimes:` com `ConfiguracoesDoKit::FORMATOS_DE_IMAGEM` (os
  nove + `ico`, `tif`, `tiff`). Mesmo mecanismo de detecção por conteúdo, então a resistência a
  arquivo renomeado não mudou (medido). ADR-02 corrigida com a medição; CT-23 acrescentado.

### QA-02 — a mensagem do campo era inalcançável quando os dois tetos coincidiam

- **Severidade**: **Major** — UX de erro, e a decisão de desenho estava errada.
- **Destino**: **implementação** (ADR-04 reescrita junto).
- **Como apareceu**: dimensão *UX de erro*, medida com sonda antes de escrever a asserção.
- **Reprodução**: com `livewire.temporary_file_upload.rules` igual ao `->maxSize()` do campo, um
  favicon de 20 MB devolve `erros=[]` e `gravado=NULL` — o Livewire recusa antes de o formulário
  existir. No navegador é 422 no XHR e erro genérico do FilePond; num teste é indistinguível de
  aceito e ignorado.
- **Corrigido**: `TetoDeUpload::emKbComFolgaDoLivewire()` dá 1 MB de folga. Arquivo pouco acima do
  teto é recusado pelo **campo**, com mensagem em português; arquivo muito acima é cortado pelo
  Livewire. CT-05 e CT-06 cobrem os dois lados.

### QA-03 — regra de validação passada crua era avaliada como closure do Filament

- **Severidade**: **Blocker** (era) — a tela abria e **quebrava no envio**.
- **Destino**: **implementação**. Corrigido.
- **Reprodução**: `CanBeValidated::getValidationRules()` faz `$rule = $this->evaluate($rule)`
  (`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:872`), então um `Closure`
  entregue a `->rule()` é avaliado com injeção de utilitários em vez de repassado ao validador:
  `"An attempt was made to evaluate a closure for [SpatieMediaLibraryFileUpload], but [$atributo]
  was unresolvable."`
- **Quem pegou**: o par de CT-12, que **envia** de verdade. Nenhum caso de "a tela abre" pegaria —
  é a regra *uma tela aberta não é uma tela que grava* aplicada ao anexo.
- **Corrigido**: a regra vem dentro de um `Closure` externo.

### QA-04 — `public/favicon.ico` do repositório tem 0 byte

- **Severidade**: **Minor**. **Destino**: **não-defeito desta feature** — pré-existente, fora do
  diff, e não é superfície de upload.
- **Observado ao investigar QA-01**: o arquivo existe, é referenciado, e tem tamanho zero.
- **Encaminhamento**: dívida do kit, não desta wiki. Vale um card próprio, porque um favicon de 0
  byte é indistinguível de favicon ausente para o navegador — e a tela de configurações agora
  aceita `.ico`, então há caminho para substituí-lo.

### QA-05 — arquivo de 0 byte é aceito em todos os campos

- **Severidade**: **Minor**. **Destino**: **especificação** — lacuna do requisito, não defeito.
- **Reprodução**: um arquivo de 0 KB com nome `.png` passa `max`, passa `mimes` (o MIME vem do nome
  no arnês de teste) e grava no disco público. Um favicon de 0 byte quebra o `<head>` de toda
  página sem erro em lugar nenhum.
- **Por que não foi corrigido**: o requisito define teto e **não define piso**. Inventar `min:1`
  seria chutar valor num campo que o usuário não pediu.
- **Registrado** em `## Ambiguidades` do `00-requisito.md`, com o mutante correspondente declarado
  sem matador no `04`. Débito declarado, não defeito escondido — é o que faz o veredito ser
  `APROVADO COM DÉBITO`.

## Dimensões verificadas

| # | Dimensão | Resultado |
|---|---|---|
| 1 | **Fronteiras** | ✅ BVA de três valores no teto (10239 / 10240 / 10241) e nos três campos; fronteira do `.env` em sete valores (ausente, vazio, `0`, negativo, texto, mínimo, legítimo) |
| 2 | **Matriz de permissão** | ✅ **nada muda** — nenhuma policy, gate, middleware, guard, permission ou Action nova. `tests/Kit/PermissoesDeAcoesTest.php` (o inventário que fica vermelho com Action nova) segue verde |
| 3 | **Log real** | ✅ nenhum log, por decisão (ADR-05). Recusa de upload é resposta ao usuário, não anomalia; o que **entra** já é auditado por `filament-auditing` |
| 4 | **N+1** | ✅ nenhuma query nova. A feature é regra de validação declarativa mais uma chave de config |
| 5 | **UX de erro** | ⚠️→✅ era o achado QA-02. Agora: mensagem em MB no campo para o caso comum, e o corte de fora só acima da folga |
| 6 | **Tema / dark mode** | ✅ nada de CSS, nada de cor. O que muda na tela é texto de `helperText` e mensagem de validação, ambos componentes nativos do Filament |
| 7 | **Acessibilidade** | ✅ `helperText` e `validationMessages` são os canais nativos do Filament, já associados ao campo por `aria-describedby`. Nenhum texto novo em imagem, nenhuma cor como único portador de significado |
| 8 | **Segurança da superfície nova** | ✅ é o objeto da feature. A recusa lê o MIME do **conteúdo** (medido), então resiste a renomear; o `mimetypes:image/*` que existia antes **não** resistia, e CT-09 asserta os dois lados |
| 9 | **Regressão adjacente** | ✅ `settings-do-kit` (67 casos), `anexos-privados`, organizações e a suíte inteira. O teto global do upload temporário caiu de 12 para 11 MB — declarado em ADR-04 como consequência negativa, atinge o CSV do `ImportAction` |
| 10 | **Adequação da suíte** | ✅ ver abaixo |
| 11 | **Oráculo fraco** | ⚠️→✅ **dois oráculos fracos corrigidos** durante o ciclo |

### Sobre os oráculos fracos (dimensão 11)

Os dois valem registro porque nenhum deles falharia — os dois ficariam **verdes** medindo a coisa
errada:

1. **`assertHasNoFormErrors()` como prova de que o arquivo entrou.** Arquivo recusado pela camada
   do Livewire chega ao teste como "sem erro e sem gravação". Trocado por
   `configuracaoDeUploadGravada()`, que afirma o caminho gravado.
2. **`assertHasFormErrors(['logo' => 'image'])` como prova de recusa por tipo.** As regras de
   arquivo do Filament rodam num validador aninhado e a falha volta como
   `$fail($validator->errors()->first())` (`BaseFileUpload.php:752-772`), o que **descarta o nome da
   regra**. A asserção reprovaria com a barreira funcionando. Trocada por asserção sobre a
   mensagem.

### Sobre a adequação da suíte (dimensão 10)

Cada regra declara os mutantes plausíveis e o caso que os mata; os que importam:

| Mutante | Morre em |
|---|---|
| `->maxSize()` removido de um dos três campos | CT-04 (dataset por campo) |
| `->maxSize(10240)` literal no lugar da config | CT-21, CT-22 |
| teto do Livewire com número cravado | CT-03 (segundo `Quando`) |
| folga do Livewire removida (tetos iguais) | CT-05 |
| teto do Livewire afrouxado para um número enorme | CT-06 |
| `rule('image')` de volta no lugar da lista | CT-23 |
| lista de formatos estreitada | CT-08 (uma partição por formato) |
| regra de SVG do `anexos` olhando só o primeiro arquivo | CT-16 (`SVG atrás`) |
| allow-list de imagem colada no `anexos` | CT-13 (a metade do PDF) |
| `validationMessages()` removido | CT-05, CT-07b |
| default do kit mudado sem mudar os READMEs | CT-20 |
| `NumeroDoEnv::positivo()` trocado por `(int) env()` | CT-02 (`vazia`) |

**Mutante declarado sem matador**: arquivo de 0 byte (QA-05).

## Fora de escopo confirmado

O `00-requisito.md` declara fora: antivírus, sanitização de SVG, teto por campo, dimensões de
imagem e o CSV do importador. Nenhum deles foi implementado, e o quality gate **não** os acusa como
omissão. A arte padrão do kit (`public/images/auth/login.svg`) continua sendo um SVG servido de
`public/` — não é upload, não passa pelo campo, e portanto não é inconsistência: o que a feature
recusa é SVG **enviado por alguém**.
