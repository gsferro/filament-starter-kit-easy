# Progresso — A arte do login exibe o nome da aplicação

> Evolução de `wikis/specs/feature/identidade-visual-da-organizacao/`.
> `IdentidadeDoKit::arteDoLogin()` é consumida por 10 `->media()` nos três painéis, e mais duas telas
> herdam a chave de outra → regressão obrigatória.

## 1. O SVG vira uma view Blade, com o nome e sem a segunda linha

- [x] `resources/views/svg/arte-do-login.blade.php` — o desenho de hoje, com `{{ $nome }}` no `<text>`
- [x] O `<text>` de 20px (`Laravel 13 · Filament 5 · pronto para uso`) removido
- [x] Gradiente, brilho e os cinco círculos preservados (premissa do `00`)
- [x] `public/images/auth/login.svg` removido, e `public/images/` junto — ficou vazia

## 2. `arteDoLogin()` devolve o data URI da view

- [x] `doDisco('kit.identidade.arte_do_login')` continua **primeiro** (RQ-06)
- [x] Fallback: `'data:image/svg+xml;base64,'.base64_encode(view('svg.arte-do-login', …)->render())`
- [x] Escape pelo `{{ }}` do Blade, sem `e()` nem `htmlspecialchars()` — escape duplo é mutante previsto
- [x] `ARTE_PADRAO` **removida**: só era usada por si mesma e pelos dois testes reancorados
- [x] Docblock da classe corrigido — dizia "doze pontos de consumo" e "nove `media()`"; são 16 e 10

## 3. Verificação da suíte

- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check` — 0 erros
- [x] `php artisan test tests/Kit/IdentidadeDoKitTest.php … ConviteTest … TelasDeAutenticacaoTest` — 116/116
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — **1796/1796**

## 4. README

- [x] `README.md` — as três ocorrências de `public/images/auth/login.svg` substituídas
- [x] `README.en.md` — idem, incluindo a linha 6 da tabela "Personalize seu projeto"
- [x] Zero menções ao arquivo removido nos dois

## 5. Capturas

- [x] `composer art` — 14/14 na primeira passada
- [x] Conferência visual, abrindo cada PNG: as três mostram o nome da aplicação e nenhuma segunda linha
- [x] `art/login.png` **adotada** no `kit:arte` — cenário novo + linha em `KitArte::IMAGENS`
- [x] As seis suítes que leem README rodadas de novo **depois** da edição — 274/274

## 6. Testes

- [x] `04-casos-de-teste.md` derivado pela `feature-test-design`, cego à implementação
- [x] CT-01, CT-02, CT-03, CT-05 (9 linhas), CT-06, CT-07, CT-08, CT-09 (6 rotas), CT-10 (4 telas)
- [x] CT-11 em `ConfiguracoesDoKitDocumentacaoTest.php`, onde `readmeSemCitacao()` já mora
- [x] CT-B01 por reancoragem de `tests/Browser/IdentidadeVisualPadraoTest.php` — sem arquivo novo, sem `05`
- [x] As 4 asserções ancoradas no arquivo removido, reancoradas

## Auditoria Pré-Implementação

### Revisão profunda — premissas contra o código real

| Premissa da wiki | O código real diz | Correção aplicada |
|---|---|---|
| "11 chamadas de `arteDoLogin()`" | **10** — `/admin` 3, `/app` 4, `/infra` 3 | `01` corrigido; e o docblock da própria classe, que dizia 9 |
| duas telas a mais consomem sem `media()` próprio | bloqueio herda `login`, 2FA herda `password-reset` | CT-10 ganhou as duas linhas |
| "a arte aparece quebrada nas capturas" | **falso** — as três capturas mostram a arte **pintada** | `00` corrigido, ADR-01 teve a "positiva colateral" retirada |
| `art/login-anti-robo.png` é a evidência | **não existe** — a da tela anti-robô é `admin-anti-robo.png`, e é Settings | `00` e `02` corrigidos |
| o data URI é aceito pelo Auth Designer | `MediaDetector::isVideo()` decide por **extensão**; base64 não tem ponto → `""` → ramo `<img>` | verificado por execução antes de codar; registrado na ADR-01 |
| **Sem CT-B** (gate do `01`) | **errado** — ver abaixo | gate revisado; **1 CT-B** |

### O erro que mais custou, e o que ele contaminou

O `00` afirmava que a arte aparecia **quebrada** nas capturas do `composer art`, com o navegador
mostrando o `alt`, e citava um arquivo inexistente como evidência. **Nada disso era verdade.** A
afirmação não veio do requisito nem do código: foi introduzida por quem escreveu o `00`, e de lá
desceu coerente para dois lugares —

- a **ADR-01** ganhou uma "consequência positiva colateral" que não existe;
- o **`04`** construiu a justificativa central do CT-B01 em cima dela ("o defeito que o próprio `00`
  registra como vivo hoje").

Os dois foram corrigidos. É o modo de falhar que o `00-requisito.md` existe para impedir — e desta
vez ele mesmo foi o vetor, o que só foi pego porque alguém **abriu os três PNG**.

### Auditoria do `04` (sub-agente independente) — o que foi aceito e o que foi contestado

| Achado do `04` | Veredito |
|---|---|
| `assertSee(config('app.name'))` já passa hoje pelo `alt` — todo oráculo tem de decodificar | **aceito**, e é o que estrutura a suíte inteira |
| a contagem de 11 chamadas do `01` não fecha | **aceito** — são 10 |
| bloqueio e 2FA herdam a chave e escapavam da matriz | **aceito** — CT-10 |
| `//text` do SimpleXML ignora `<tspan>` filho | **aceito** — o helper usa `textContent` sobre todos os nós |
| `art/login-anti-robo.png` não existe | **aceito** — era erro meu |
| **o gate "Sem CT-B" do `01` está errado** | **aceito**, com a justificativa reescrita: não é conserto, é guarda de regressão |
| M26 (mime errado) seria "o único mutante sem matador fora do CT-B" | **refutado por experimento** — ver abaixo |

## Notas de Implementação

- **A mutação achou o que a previsão não achou.** O `04` previa M26 (mime `image/svg`) como o mutante
  exclusivo do CT-B01. Ele **não é**: o oráculo de CT-02 afirma o prefixo exato e o mata em HTTP. O
  mutante realmente exclusivo apareceu ao procurar um que sobrevivesse a tudo — **o `<svg>` sem
  `xmlns`**:

  | Mutante | HTTP (`tests/Kit`) | Navegador (CT-B01) |
  |---|---|---|
  | M9 — `{!! $nome !!}`, sem escape | 37/39 ❌ | — |
  | M14 — a segunda linha permanece | 26/39 ❌ | — |
  | M31 — o `<text>` perde `x`/`y` | ❌ | — |
  | M2 — data URI cru, sem base64 | 13/39 ❌ | — |
  | M26 — mime `image/svg` | ❌ (CT-02) | ❌ |
  | **M32 — `<svg>` sem `xmlns`** | **39/39 ✅ passa** | **❌ `found 1 broken image`** |

  M32 é XML válido, com um nó de texto, o nome exato, os cinco círculos, o `viewBox` certo e base64
  correto. **Toda a camada HTTP fica verde e a tela não tem arte.** É a prova empírica de que o gate
  "Sem CT-B" do `01` estava errado — e vale mais que o argumento original do `04`, que se apoiava
  numa premissa falsa.

- **O oráculo de geometria de CT-06 quase passou por fraco.** A primeira rodada de mutação reportou
  M31 sobrevivendo; a causa era **escaping do shell**, e a mutação nunca fora aplicada. Aplicada de
  verdade, o caso morre em `Failed asserting that 0.0 is greater than 0.0`. Mutação que "passa" merece
  a mesma desconfiança que teste que passa.

- **`->with()` do Pest não avalia closure aninhada.** `fn (): Closure => function () {…}` chega ao
  caso como a closure **externa**, e `$abrir($this)` devolve a interna em vez da resposta
  (`Call to undefined method Closure::assertSuccessful()`). CT-10 passou a usar `match` sobre uma
  string — mais legível, e sem a armadilha.

- **`public/images/` deixou de existir.** Era o único arquivo lá dentro.

## Desvios do Plano

- **O gate de CT-B foi revertido**: o `01` dizia "Sem CT-B" e a implementação entregou 1, por
  reancoragem de um cenário existente. Justificativa em `01` → `## Superfície de UI`, e a prova
  empírica em M32, acima.
- **`ARTE_PADRAO` foi removida**, e o `01` só mandava "conferir com `grep` antes". O grep mostrou
  dois usos, ambos em testes que esta wiki reancora.
- **A premissa da arte quebrada foi retirada, não respondida** — ela era falsa. Ver a auditoria.

## Blockers

- Nenhum.

## Retrospectiva

- **Funcionou**: verificar a viabilidade da ADR-01 **por execução** antes de escrever código. O
  `MediaDetector` escolhe `<img>`/`<video>` por extensão, e um data URI sem extensão poderia ter caído
  no ramo de vídeo — o que tornaria a decisão inteira inviável, e só se descobriria na tela.
- **Funcionou**: abrir os PNG em vez de confiar no que a wiki dizia sobre eles. Foi o único jeito de a
  premissa falsa aparecer, e ela já tinha contaminado dois documentos.
- **Faltou no plano**: contar as chamadas antes de escrever "11", e conferir o nome do arquivo antes de
  citá-lo como evidência. Os dois eram um `grep` e um `ls`.
- **Faltou no plano**: prever que a camada HTTP não distingue "pintou" de "quebrou". O gate "Sem CT-B"
  foi escrito com convicção e estava errado; quem o corrigiu foi a derivação cega, e a prova veio da
  mutação — não da discussão.

## Decisão do passo 5, registrada

`art/login.png` foi **adotada no comando**, e não regerada à mão. As três alternativas do `01` eram
regerar, adotar ou aposentar. Aposentar estava fora: ela é a **primeira imagem dos dois READMEs**, a
vitrine do kit. Entre regerar à mão e adotar, adotar é o que impede o problema de voltar — foi
justamente por estar fora do `kit:arte` que ela envelheceu até mostrar `starter-kit-easy` numa arte
que já mudou. O par exigido por `.ai/rules/testes-browser.md` está completo: o
`->screenshot(filename: 'login')` no cenário e a linha `'login'` em `KitArte::IMAGENS`.
