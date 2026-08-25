# Casos de Teste — Limite e tipos de upload

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando a implementação da feature — ver
> `## Higiene de Contexto` para o que foi visto por acidente e como isso foi neutralizado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — barreira de **tipo** (SVG fora, o resto dentro) | 3 | 3 | **9** | **completo** |
| A2 — a chave de configuração e a conversão de unidade | 3 | 2 | 6 | padrão |
| A3 — barreira de **tamanho** nos cinco campos | 3 | 2 | 6 | padrão |
| A4 — alinhamento do teto global do upload temporário do Livewire | 2 | 2 | 4 | padrão |
| A5 — documentação (`.env.example` e os dois READMEs) | 1 | 2 | 2 | mínimo |

**Por que A1 é `completo`**: o impacto é XSS armazenado servido pelo **mesmo origin** da aplicação
(I=3, segurança), e a probabilidade é alta porque o MIME atravessa **três** camadas que discordam
entre si — o `accept` do seletor, o `mimetypes:image/*` do Filament e a regra `image` do Laravel —
e uma quarta, medida, que divergem entre teste e produção (ADR-03). Não é código novo isolado.

**Por que A2 é P=3**: `.ai/rules/config.md` registra que este padrão de defeito já apareceu em
**cinco** chaves deste repositório, e que num caso apagou uma trilha inteira. Não é hipótese.

- **Técnicas aplicadas**: EP (partições da env e do domínio de formatos), **BVA 3-valores** (o teto,
  em KB), tabela de decisão (campo × formato), rastreio de efeito (arquivo no disco / mídia criada /
  config efetiva do consumidor), varredura de código (número cravado), asserção de ausência com
  filtro de comentário.
- **Cenários**: 23 (`CT-01`…`CT-23`) · **Regras**: **11** · **Mutantes previstos**: **52** ·
  **Sem matador**: 2 (declarados — M17 e M44).
- **Estouro do teto**: R3 tem 3 (no limite do perfil `padrão`); R6 tem 5 com CT-22 (no limite do
  `completo`); R9 tem 4, justificado na própria regra. Os estouros vêm do gate de mutantes, que
  vence o teto.
- **Rodadas de revisão adversarial**: **2 (teto da skill)**. A rodada 1 produziu 16 achados
  (A-01…A-16) e 5 implementações defeituosas que passavam pelo conjunto inteiro. A rodada 2, sobre a
  superfície nova, produziu 9 achados (R2-01…R2-09) e 3 implementações — **incluindo uma que derrubou
  o arranjo de CT-22 usando o argumento deste próprio documento contra ele**. Todos fechados; a
  resolução estrutural foi separar R6 em R6 + R10 e extrair R11 de R9. Ver `## Revisão Adversarial`.
- **Revisão adversarial**: obrigatória (há área `completo`) — resultado em `## Revisão Adversarial`.
- **Sem `05-casos-de-teste-browser.md`** — ver `## Sem CT-B`.

### Escalada de técnica acima do perfil da área

A3 é `padrão` (BVA 2-valores bastaria), e os cenários usam **BVA 3-valores**. Motivo: o requisito
escreve a fronteira literalmente ("ate 10mb"), e 2-valores não distingue `<` de `<=` — o único
defeito que RQ-01 e RQ-02 existem para separar. O perfil é orçamento, não teto de rigor.

### Divergências declaradas entre a skill e o arnês/rules deste projeto

1. **A skill sugere `pest --parallel --tia`; as rules do projeto vencem.** Não há CT-B nesta
   feature, então o comando é um só: `php artisan test --testsuite=Kit,Tenancy --compact
   --filter=UploadLimite`, e a regressão completa é
   `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`.
2. **A escada `Unit < Feature < componente` começa em `tests/Kit` neste projeto, não em
   `tests/Unit`.** Verificado em `tests/Pest.php`: `pest()->extend(TestCase::class)` cobre
   `Feature`, `Kit`, `Tenancy`, `Browser` e `BrowserTenancy` — **`tests/Unit` não recebe extensão
   nenhuma**, e o `phpunit.xml` a declara como a suíte "do SEU projeto" (o comentário do
   `tests/Unit/ExampleTest.php` é explícito). Um caso "unitário" ali rodaria sem container, e
   `config()`, `Validator::make()` e `base_path()` não resolveriam. Logo **nenhum CT desta feature
   vai para `tests/Unit`**; a camada mais barata que o arnês sustenta é `tests/Kit`. É a mesma
   escolha que `tests/Kit/NumeroDoEnvTest.php`, `BooleanoDoEnvTest.php` e `TextoDoEnvTest.php` já
   fizeram para lógica pura do kit.
3. **Helper compartilhado**: `configuracaoGravada()` hoje vive dentro de
   `tests/Kit/ConfiguracoesDoKitTelaTest.php`. Se `tests/Kit/UploadLimiteETiposTest.php` o usar,
   `.ai/rules/testes.md` **obriga** movê-lo para `tests/Pest.php` (e
   `tests/Kit/HelpersDeTesteTest.php` reprova se não for movido). É passo de implementação dos
   testes, não escolha.

### Conformidade com `.ai/rules/specs.md`

A rule que cobre `wikis/specs/**` tem duas exigências, e as duas mudaram o conteúdo deste arquivo:

1. **"Justificativa de comportamento de pacote se escreve depois de ler o vendor, com `file:line`."**
   Toda afirmação sobre Livewire, Filament ou Laravel neste arquivo foi medida antes de escrita, e a
   `## A armadilha de camada` existe porque a leitura ingênua do arnês estava errada em dois pontos —
   incluindo um que ADR-03 descreve com a conclusão certa pelo mecanismo incompleto (falta o salto do
   meta JSON). É exatamente o padrão que a rule descreve: *a conclusão certa por outro motivo*, que é
   o que torna o erro invisível.
2. **"Ao encontrar defeito numa fronteira, varra o padrão no repo inteiro antes de consertar."** A
   rule registra que `grep -rn '(int) env(' config/` rendeu dois defeitos em minutos, e que **nenhuma
   das seis wikis auditadas tinha caso de teste para o próprio `.env`**. CT-01 é esse caso, e CT-07 e
   CT-23 são as varreduras de fronteira desta feature (número cravado no código, teto de infra mais
   estreito que a chave). Não são cenários de cortesia: são os três que a rule prevê que faltariam.

---

## Higiene de Contexto

A skill proíbe derivar cenário lendo a implementação. Neste worktree a implementação **já existe**
(o `config/kit.php` já tem o bloco `uploads`, e há um `app/Support/TetoDeUpload.php`), o que foi
descoberto durante a varredura de superfície. O que foi feito:

- as **regras** e os **oráculos** vêm do `00-requisito.md`, e nenhum corpo de método da feature foi
  lido — só nomes de arquivo e assinatura de chamada, que são superfície, não comportamento;
- todo cenário afirma sobre a **chave de config** (`kit.uploads.maximo_em_kb`, que RQ-05 determina)
  ou sobre o comportamento observável do campo, **nunca** sobre `TetoDeUpload::emKb()`;
- CT-06 existe justamente para que a existência de um acessor não seja assumida como correta: ele
  mata o mutante "o acessor devolve um número próprio e ignora a config".

**Achado a registrar**: o PRD (passos 3, 4 e 5) prescreve `(int) config('kit.uploads.maximo_em_kb')`
direto nos campos; a implementação introduziu a classe `App\Support\TetoDeUpload`. Divergência
PRD × código, não coberta por ADR nenhum. Não muda nenhum oráculo, e está em
`## Perguntas para o 00-requisito.md`.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | chave nova em `config/kit.php` (`uploads.maximo_em_kb`); uma linha em `KitServiceProvider::configureDefaults()`; três arquivos de formulário Filament (`ConfiguracoesDoKit`, `TenantForm`, `ProjetoResource`); `.env.example`; `README.md` e `README.en.md`. **Nenhuma** migration, model, policy, job, evento ou channel de log (ADR-05) | CT-01, CT-02, CT-07, CT-08, CT-18…CT-20 |
| **F**unction | converter MB→KB; recusar por tamanho; recusar por formato; aceitar e gravar; alinhar o default global do upload temporário; compor mensagem de erro | CT-01…CT-17 |
| **D**ata | valor cru da env (**ausente ≠ vazio ≠ `0` ≠ negativo ≠ texto ≠ legítimo**); tamanho do arquivo em KB (fronteira); extensão; MIME declarado; **bytes** do arquivo (o caso renomeado); nome com acento e emoji; arquivo de **0 byte** (⚠️ pergunta aberta); os cinco campos, em dois domínios (imagem × documento) | CT-01, CT-03…CT-06, CT-09…CT-17 |
| **I**nterfaces | três formulários Filament (componente Livewire); o **endpoint de upload temporário do Livewire**, que valida antes do formulário; o `ImportAction` do Filament, consumidor indireto do teto global (ADR-04); `config/kit.php` lido no boot; `.env` | CT-08, CT-09 |
| **P**latform | `finfo` do PHP (o detector de MIME por conteúdo); disco `public` fake; disco `tmp-for-tests` do Livewire; `sqlite :memory:`; e o gate `app()->runningUnitTests()` do `TemporaryUploadedFile`, que é **a razão** pela qual o MIME no teste vem do nome — e que CT-22 contorna sem desligar. `upload_max_filesize` (PHP) e `client_max_body_size` (nginx) não são exercitáveis por teste, mas a **relação** deles com a chave é (CT-23) | CT-12, CT-13, CT-18, CT-22, CT-23 |
| **O**perations | quem sobe é `admin`/`master_global` (configurações), quem administra organizações (`/admin/organizacoes`) e quem opera um projeto (`/app/{tenant}`, suíte `Tenancy`). Uso indevido explícito: subir SVG com `<script>` (CT-10, CT-11, CT-16) e **renomear** o SVG para `.png` (**CT-22** — os outros não provam isso) | CT-10…CT-17, CT-21, CT-22 |
| **T**ime | **não se aplica**: nenhum oráculo temporal, nenhuma expiração, nenhum agendamento, nenhum contador concorrente — o teto é **por arquivo**, não acumulado. O único toque em tempo é o `@touch(now())` que o `TemporaryUploadedFile` faz sob `runningUnitTests()` para o `cleanupOldUploads()` não apagar o arquivo do próprio teste; nenhum cenário depende dele | — |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — nenhum valor de `.env` leva o teto a zero, e vazio é tratado como ausente | A2 (padrão) | RQ-01, RQ-05 | EP / tabela de decisão | CT-01 |
| R2 — o teto de fábrica é o número do requisito: 10 MB, expostos como 10240 KB | A2 (padrão) | RQ-01, RQ-05 | valor literal do requisito | CT-02 |
| R3 — todo campo aceita **no** teto e recusa **acima** dele | A3 (padrão) | RQ-01, RQ-02 | **BVA 3-valores** (incremento 1 KB) | CT-03, CT-04, CT-05 |
| R4 — o teto efetivo de cada campo é **lido** da config, não cravado | A3 (padrão) | RQ-05 | rastreio de efeito + varredura de código | CT-06, CT-07 |
| R5 — o upload temporário do Livewire usa **o mesmo** teto | A4 (padrão) | RQ-01, RQ-02, RQ-05 | rastreio de efeito no **consumidor** | CT-08, CT-09 |
| R6 — os campos de imagem recusam SVG **declarado**, e recusam antes de gravar | A1 (completo) | RQ-03 | tabela de decisão + rastreio do não-efeito | CT-10, CT-11 |
| **R10 — a recusa vale para SVG DISFARÇADO, em todo campo, porque olha o conteúdo** | A1 (completo) | RQ-03 | detecção por conteúdo × por nome | CT-12, CT-13 (premissa), **CT-22** |
| R7 — os campos de imagem aceitam os demais formatos de imagem | A1 (completo) | RQ-04 | EP exaustiva do domínio de formatos | CT-14, CT-15 |
| R8 — o campo de **documento** recusa SVG e continua aceitando documento | A1 (completo) | RQ-03 (sob premissa) | tabela de decisão (domínio condicionado) | CT-16, CT-17 |
| R9 — o teto e a recusa de SVG estão documentados nos dois idiomas e no `.env.example` | A5 (mínimo) | RQ-06 | presença + ausência com filtro de comentário | CT-18, CT-19, CT-20 |
| **R11 — nenhuma camada de infra do kit é mais estreita que a chave** | A4 (padrão) | RQ-01, RQ-06 | asserção cruzada config × infra | **CT-23** |

**Cobertura de `RQ`**: RQ-01 → R1, R2, R3, R5, R11 · RQ-02 → R3, R5 · RQ-03 → R6, R8, **R10** ·
RQ-04 → R7 · RQ-05 → R1, R2, R4, R5 · RQ-06 → R9, **R11**. Nenhuma cláusula sem regra.

### Por que R6 virou duas regras, e R11 nasceu (resolução da rodada 2)

A skill diz: se a segunda rodada de revisão adversarial **ainda** traz achado estrutural, "o problema
não é o conjunto — é a regra, que provavelmente deveria ser duas. Registrar e escalar." Foi o caso, e
duas vezes:

- **R6 escondia duas regras.** "Recusa SVG" tem duas metades com **mecanismos e camadas diferentes**:
  recusar o arquivo que **se declara** SVG (nome/extensão/MIME declarado — provável por componente) e
  recusar o que **é** SVG por dentro apesar do nome (só provável na camada da regra, com MIME por
  conteúdo). Tratá-las como uma regra foi o que deixou a rodada 1 creditar M28/M35 a cenários de
  vendor (A-07) e a rodada 2 achar que a segunda metade não existia para quatro dos cinco campos
  (R2-02). Separadas, cada uma tem os seus mutantes e a sua camada, e a lacuna fica visível.
- **R11 estava escondida dentro de R9.** Documentar a escada de tetos (R9) e **a escada estar
  correta** (R11) são regras diferentes: a primeira é sobre texto, a segunda sobre configuração de
  infraestrutura. Enfiar as duas em "documentado" foi o que produziu IMPL-5 na rodada 1 e o buraco do
  `post_max_size` na rodada 2. R11 é derivada de RQ-01 (o menor manda), não de RQ-06.

**Escalado ao dono do kit**, porque nenhuma das duas se resolve dentro desta wiki: (a) a pergunta do
piso de 1 MB (R2-08) muda `NumeroDoEnv`, que é infra compartilhada; (b) a pergunta do `.ico`/`.tiff`
contraria a letra de RQ-04. As duas estão em `## Perguntas para o 00-requisito.md`.

⚠️ **Onde ficam os mutantes de R10 e R11**: nas tabelas de `## Regra R6` e `## Regra R9`, onde eles
já estavam quando as regras eram fundidas. A separação foi feita **sem renumerar** os mutantes nem
mover as tabelas, de propósito — a skill avisa que desdobrar uma regra no fechamento da revisão não
justifica renumerar toda a rastreabilidade por motivo cosmético. O que importa é que cada mutante
tenha matador nomeado, e M28/M35/M46/M50 (R10) e M49/M51/M52 (R11) têm.

**Regra que atravessa área**: R3 e R4 pertencem a A3, mas o campo `anexos` e a logo da organização as
exercitam na suíte `Tenancy` — o perfil não muda, o arquivo de teste muda.

---

## A armadilha de camada (medida, e ela decide cinco cenários)

Esta seção existe porque a leitura ingênua do arnês produz dois cenários errados e um cenário
declarado impossível sem razão. Tudo abaixo foi **medido** neste worktree, com `file:line`.

### 1. Em teste de componente, o arquivo **é** um `TemporaryUploadedFile`

`Livewire\Features\SupportTesting\Testable::setProperty()` (`Testable.php:189-200`) desvia para
`upload()` (`:282-314`) quando o valor é um `UploadedFile` — e `upload()` roda o fluxo **real** de
upload temporário: `FileUploadController::validateAndStore()`
(`vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadController.php:38-42`) e depois
`_finishUpload`, que devolve `TemporaryUploadedFile::createFromLivewire()`.

**Consequência**: a regra `Closure` do `anexos`, que testa `$arquivo instanceof
TemporaryUploadedFile`, **é** exercitada por um teste de componente. Sem esta medição, a suposição
natural (*"o valor é o `Testing\File` que o teste passou, o `instanceof` nunca casa"*) levaria a
declarar CT-16 inexpressável e a empurrar R8 para o navegador sem necessidade.

### 2. O MIME que a validação lê, em teste, vem do **nome** — por dois saltos, não um

ADR-03 registra o primeiro salto: `Illuminate\Http\Testing\File::getMimeType()` devolve
`MimeType::from($this->name)`
(`vendor/laravel/framework/src/Illuminate/Http/Testing/File.php:132-135`). Falta o segundo, e ele é
o que fecha o argumento:

`FileUploadConfiguration::storeTemporaryFile()` (`FileUploadConfiguration.php:128-143`) grava um
`.json` de meta ao lado do arquivo temporário com `'type' => $file->getMimeType()` e
`'size' => $file->getSize()` — isto é, **o MIME derivado do nome do arquivo falso é persistido**. E
`TemporaryUploadedFile::getMimeType()` (`TemporaryUploadedFile.php:63-77`), **sob
`app()->runningUnitTests()`**, devolve `metaFileData()['type']` e só cai no
`$this->storage->mimeType($this->path)` (conteúdo, via Flysystem) fora de teste. O mesmo vale para
`getSize()` (`:47-60`).

Medido, com `php -r`:

```
fake()->create('a.svg', 10)->getMimeType()                        → image/svg+xml   (nome)
fake()->createWithContent('logo.png', $bytesDeSvg)->getMimeType() → image/png       (nome — MENTE)
arquivo REAL .png com os mesmos bytes: getMimeType()              → image/svg+xml   (conteúdo)
arquivo REAL .png com os mesmos bytes: guessExtension()           → svg
```

E a regra `image` do Laravel compara **`guessExtension()`**, não o MIME declarado: `validateImage()`
monta os nove formatos (`ValidatesAttributes.php:1531-1540`) e delega a `validateMimes()`, cuja
última linha é `in_array($value->guessExtension(), $parameters)` (`:1746-1761`).

**Consequências, uma por cenário**:

- **CT-10 é possível por componente**: `fake()->create('logotipo.svg', 8)` tem MIME `image/svg+xml`
  derivado do nome, persistido no meta, e a regra `image` o recusa. Prova RQ-03 no caminho de quem
  escolhe um `.svg` de verdade — que é o caso comum.
- **CT-12 é impossível por componente**: o SVG **renomeado** para `.png` teria MIME `image/png` no
  meta e seria **aceito** no teste, com a produção recusando. Escrever esse cenário por componente
  produziria vermelho por defeito da fixture, que é a pressão para relaxar a asserção que ADR-03 já
  antecipou. Ele desce para a camada da regra, com `UploadedFile` **real**.
- **CT-13 existe porque CT-12 não prova produção**: ele asserta a **premissa** — o detector por
  conteúdo classifica bytes de SVG como `image/svg+xml` mesmo com extensão `.png`. É o cenário que
  morre se o Livewire trocar o detector do disco temporário, que é o risco declarado em ADR-03.
- **CT-14 é expressável nas nove partições** justamente por causa desta armadilha: o MIME vindo do
  nome cobre `avif`, `heic` e `bmp`, que o GD não gera.

### 3. O teto do campo e o teto global do Livewire são o **mesmo número** — e o de fora dispara primeiro

`validateAndStore()` aplica `FileUploadConfiguration::rules()`
(`FileUploadConfiguration.php:114-121`, default `['required','file','max:12288']`) **antes** de o
arquivo virar propriedade do componente. Com o alinhamento de ADR-04, essa regra passa a ser
`max:10240` — exatamente o `->maxSize()` do campo.

Então um arquivo de 10241 KB **nunca chega** à validação do formulário: `_uploadErrored()`
(`WithFileUploads.php:73-95`) lança `ValidationException` sobre `data.{campo}`, e
`assertHasFormErrors(['favicon' => 'max'])` **não** se sustenta.

Isso não é obstáculo, é a descoberta de que **há duas barreiras** e cada uma precisa do seu par:

| Barreira | Como isolar no cenário | Cenários |
|---|---|---|
| `->maxSize()` do **campo** | **alargar** a regra global no arranjo: `config(['livewire.temporary_file_upload.rules' => ['required','file','max:1048576']])` | CT-03, CT-04, CT-05, CT-06, CT-21 |
| regra global do **upload temporário** | manter a regra de fábrica do processo | CT-08, CT-09 |

Sem o alargamento em CT-03, o cenário mediria a barreira de fora e o `->maxSize()` do campo poderia
estar **ausente** com tudo verde — que é literalmente o defeito que esta feature paga.

---

## Fronteira com o Plano

| Item do PRD / ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\TetoDeUpload::emKb()` (classe que o código introduziu e o PRD não prevê) | escolha de implementação, e divergência PRD × código | detalhe do cenário; CT-06 mata o mutante correspondente; a divergência vira pergunta |
| `->rule('image')` vs `acceptedFileTypes([...])` (ADR-02) | mecanismo, não comportamento | detalhe; os cenários afirmam formato **aceito/recusado** |
| Textos de `validationMessages()` ("O arquivo passa de 10 MB.", "SVG não é aceito. …") | comportamento **visível** que o requisito não determina | **pergunta**; os cenários afirmam a regra violada (`max`, `image`), nunca a frase |
| Texto do `helperText` ("Até 10 MB, e SVG não é aceito.") | idem | **pergunta**; nenhum `Então` o usa |
| `config()->set()` em `configureDefaults()` vs publicar `config/livewire.php` (ADR-04) | escolha de implementação | detalhe; CT-08 afirma sobre o **consumidor**, o que vale para as duas formas |
| Os **valores** de `docker/php/uploads.ini` e `docker/nginx/nginx.conf` | quem os escolhe é infraestrutura, e o requisito não os determina | detalhe — mas o **README citá-los** é oráculo (RQ-06 pede "documentado"), e a **relação** deles com a chave é oráculo por RQ-01 (o menor manda): infra mais estreita que a chave contradiz "aceita até 10 MB". Ver CT-23 |
| `.ico` e `.tiff` deixarem de ser aceitos (ADR-02, `## Consequências`) | ⚠️ **contradiz RQ-04** — `.ico` e `.tiff` são tipos de imagem, e RQ-04 diz "qualquer outro tipo de image" | **pergunta**; CT-15 escrito e marcado `@premissa` |

---

## Perguntas para o 00-requisito.md

> Bloco pronto para colagem em `## Ambiguidades e Perguntas Abertas`. **Desvio declarado**: as
> perguntas foram escritas aqui em vez de no `00`, porque a instrução desta rodada restringe a
> escrita ao `04`. Elas continuam bloqueando o que dependem delas.

- **RQ-04 × `.ico` e `.tiff`** — a regra `image` do Laravel é uma allow-list de nove formatos
  (`ValidatesAttributes.php:1533`), e `.ico` e `.tiff` não estão nela. Os dois **são** tipos de
  imagem, então recusá-los contraria a letra de RQ-04, e `.ico` é plausível num campo de favicon.
  - **Assumido**: o trade-off de ADR-02 vale — a lista mantida pelo framework é preferível a nove
    strings congeladas, e favicon moderno é PNG. CT-15 fixa a premissa por escrito.
  - **Se negado**: trocar `->rule('image')` por
    `->acceptedFileTypes([... 'image/x-icon', 'image/tiff'])` no campo `favicon`, e CT-15 inverte de
    sinal.

- **Arquivo de 0 byte** — o requisito define teto e **não define piso**. Medido: um arquivo de 0 KB
  com nome `.png` passa `max` e passa `image` (o MIME vem do nome), grava no disco `public`, e um
  favicon de 0 byte quebra o `<head>` de toda página **sem erro em lugar nenhum**.
  - **Assumido**: fora de escopo — não há cláusula, e inventar `min:1` seria chutar valor.
  - **Consequência declarada**: o item `Upload / 0 byte` do checklist de taxonomia fica como
    **lacuna declarada**, e o mutante M44 fica **sem matador**.
  - **Se confirmado como defeito**: um cenário por família de campo, na fronteira `0 KB` / `1 KB`.

- **O piso de 1 MB quando a env é inválida** (achado R2-08 da revisão adversarial) — o requisito diz
  "aceita até 10mb" e não diz o que fazer com valor **inválido**. `NumeroDoEnv::positivo()`, que
  `.ai/rules/config.md` obriga a usar, manda negativo e texto para **1 MB** — não para o default. Um
  typo plausível (`KIT_UPLOAD_MAXIMO_MB=10 MB`, com a unidade escrita junto) derruba o teto de toda a
  instalação para 1 MB, silenciosamente, e viola RQ-01.
  - **Assumido**: é o comportamento documentado da classe, e o pior caso é um teto curto e **visível**
    que faz alguém corrigir o `.env` — o argumento de ADR-01. As duas linhas de CT-01 ficam marcadas
    `@premissa`.
  - **Se negado**: valor inválido cai no **default** (10240) em vez do piso, e as duas linhas de CT-01
    mudam de `1024` para `10240`. Isso exigiria uma regra nova em `NumeroDoEnv` — ou seja, muda
    infra compartilhada, e por isso é decisão do dono do kit, não desta wiki.

- **Texto das mensagens de erro e do `helperText`** — o requisito não determina nenhuma frase. O PRD
  as escreve, e são comportamento visível ao usuário.
  - **Assumido**: são detalhe de implementação, e nenhum `Então` depende delas. Se o texto passar a
    ser requisito ("a mensagem tem de dizer o número em MB"), CT-03 e CT-10 ganham uma asserção.

- **`TetoDeUpload` × `config()` direto** — o PRD prescreve `(int) config('kit.uploads.maximo_em_kb')`
  nos cinco campos; a implementação introduziu `App\Support\TetoDeUpload::emKb()`. **Nenhum ADR
  registra a mudança**, embora os dois READMEs já a documentem (`README.md:1651`,
  `README.en.md:1617`) — a decisão foi escrita para o usuário do kit e não para a wiki.
  - **Assumido**: é refactor neutro, e RQ-05 só exige que o teto viva na config. CT-06 e CT-07 provam
    isso sem nomear a classe.
  - **Se negado**: a classe sai e os campos leem `config()` direto; **nenhum cenário muda**.
  - **Encaminhamento sugerido**: virar ADR-06 no `02`, não porque muda comportamento, mas porque a
    wiki e o código já discordam em texto — e é o tipo de discordância que o `feature-quality-gate`
    encontra como achado de especificação.

---

## Setup Global

### Personas

- `admin` — `usuarioDoKit('admin')` (`tests/Pest.php`). É quem opera `/admin/configuracoes-do-kit`
  (permissão `View:ConfiguracoesDoKit`).
- `master_global` — `usuarioComPapel('master_global')`, para as telas de organização em `Tenancy`
  (é o padrão de `tests/Tenancy/IdentidadeVisualTenancyTest.php`).
- `panel_user` numa organização — `usuarioComPapel('panel_user', $organizacao)`, para o `anexos`.

⚠️ `.ai/rules/testes.md`: `admin_app` só existe em `tests/Tenancy`. Nenhum cenário desta feature
precisa dele.

### Fixtures

- `UploadedFile::fake()->image('logo.png')->size($kb)` — imagem real com **tamanho declarado**. O
  `size()` alimenta `sizeToReport`, que é o que o meta do upload temporário grava e o `max` lê.
- `UploadedFile::fake()->create("arquivo.{$ext}", $kb)` — para os formatos que o GD não gera
  (`avif`, `heic`, `bmp`) e para SVG e documento. O MIME sai do **nome**, e é isso que se quer aqui.
- **`new Illuminate\Http\UploadedFile($caminhoReal, 'logo.png', null, null, true)`** — só em CT-12:
  arquivo **real** em `sys_get_temp_dir()` com bytes de SVG e nome `.png`. O quinto argumento
  (`$test = true`) é o que faz `isValid()` devolver `true` fora de um upload HTTP.
- **Upload temporário SEM metadados** — só em CT-22, e é a fixture que torna o cenário possível:
  gravar os bytes de SVG em `livewire-tmp/logo.png` no disco `tmp-for-tests`
  (`FileUploadConfiguration::disk()`/`directory()`, `FileUploadConfiguration.php:30-37, 63-66`)
  **sem** escrever o `.json` ao lado, e então `TemporaryUploadedFile::createFromLivewire('logo.png')`
  (`TemporaryUploadedFile.php:256-259`). Sem meta, `metaFileData()` devolve `[]` (`:233-247`) e
  `getMimeType()` cai em `$this->storage->mimeType()` — **detecção por conteúdo dentro do teste**.
  É o oposto do que `fillForm()` produz, e é deliberado.
- `Tenant::factory()` / o helper `tenant()` — nos cenários de `Tenancy`.

### Fakes

- `Storage::fake('public')` nos cenários dos três campos de `/admin/configuracoes-do-kit` — é o que
  permite o **rastreio do não-efeito** (`Storage::disk('public')->allFiles()` vazio).
- ⚠️ **Nenhum `Storage::fake()` no cenário do `anexos`**: `tests/Tenancy/AnexosPrivadosTest.php`
  documenta por quê (o fake não substitui a rota `storage.{disk}`, e o cenário passaria medindo
  outra coisa). Herdar dali o arranjo de **disco real em diretório temporário**.
- Nenhum `Mail::fake`, `Queue::fake` ou `Event::fake`: a feature não tem efeito colateral desses
  tipos (ADR-05 — nenhum log e nenhum channel).

### Estratégia de DB

`RefreshDatabase` global (`tests/Pest.php`), em `sqlite :memory:`. `tests/Tenancy` usa
`Tests\TenancyTestCase`, que fixa `permission.teams` antes das migrations.

### Arquivos de teste

| Arquivo | Novo? | Cenários |
|---|---|---|
| `tests/Kit/UploadLimiteETiposTest.php` | novo | CT-01…CT-04, CT-06 (3 linhas), CT-07…CT-10, CT-12…CT-15 |
| `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` | novo | CT-18, CT-19, CT-20, CT-23 |
| `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | novo | CT-05, CT-06 (2 linhas), CT-11, CT-16, CT-17, CT-21, CT-22 |

Três arquivos e não um: `tests/Tenancy` é obrigatório para `CreateTenant`/`EditTenant` e para o
`ProjetoResource` (o resource de demo só existe com tenancy ligada — ver o comentário de
`telasDoKit()` em `tests/Pest.php`), e a separação da documentação segue o padrão que o kit já tem
(`ConfiguracoesDoKitDocumentacaoTest`, `AnexosPrivadosDocumentacaoTest`).

---

## Regra R1 — nenhum valor de `.env` leva o teto a zero, e vazio é tratado como ausente

> `RQ-01`, `RQ-05` · A2, perfil **padrão** · técnica: **EP / tabela de decisão** sobre o valor cru
> da env · arnês: `kitConfigCom()` (`tests/Pest.php`), que relê o `config/kit.php` com a variável
> forçada — a config do processo já foi resolvida no boot, e `putenv()` depois não a reavalia.

```gherkin
# language: pt

Funcionalidade: Limite e tipos de upload

  Regra: nenhum valor escrito no .env pode levar o teto de upload a zero

    Esquema do Cenário: [CT-01] o valor cru do .env vira um teto em KB sempre maior que zero
      Dado que quem instala o kit escreve <bruto> em KIT_UPLOAD_MAXIMO_MB
      Quando o config/kit.php é lido
      Então kit.uploads.maximo_em_kb vale <em_kb>

      Exemplos:
        | bruto   | em_kb | # partição                                          |
        | ausente | 10240 | chave ausente cai no default                        |
        | ""      | 10240 | chave PRESENTE e vazia — o defeito medido 5x no kit  |
        | "0"     | 10240 | zero não é configuração, é a feature desligada      |
        | "-5"    |  1024 | negativo cai no piso de 1 MB — `@premissa`           |
        | "abc"   |  1024 | texto cai no piso de 1 MB — `@premissa`              |
        | "1"     |  1024 | menor valor legítimo                                |
        | "25"    | 25600 | valor legítimo, e o que discrimina 1024 de 1000     |
```

⚠️ **As duas linhas de piso são `@premissa`, e a rodada 2 estava certa em cobrar** (achado
**R2-08**). O piso de 1 MB não vem de cláusula nenhuma: é a semântica documentada de
`NumeroDoEnv::positivo()`, que `.ai/rules/config.md` **obriga** a usar. E ele tem um efeito que
contraria RQ-01: um typo plausível no `.env` (`KIT_UPLOAD_MAXIMO_MB=10 MB`) derruba o teto para
**1 MB**, e o conjunto declara isso correto — "aceita até 10 MB" violado, tudo verde.

O documento estava incoerente aqui: em `## Cogitado e cortado` recusou `min:1` como "valor chutado",
e ao mesmo tempo elevava outro valor de implementação a oráculo. Marcar as duas linhas como premissa
e mandar a pergunta ao `00` é o que a skill manda fazer com regra que o requisito não determina.
Ver `## Perguntas para o 00-requisito.md`.

**Por que `"25"` e não `"20"`**: `25 × 1024 = 25600`, e `25 × 1000 = 25000`. A linha distingue a
multiplicação correta de um `* 1000` — que é o mutante que qualquer número redondo em decimal
esconderia. `"1"` fecha a outra ponta: `1024` contra `1000`.

**Camada**: `tests/Kit` (`UploadLimiteETiposTest.php`). Não é `tests/Unit` — ver
`### Divergências declaradas`, item 2.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `(int) env('KIT_UPLOAD_MAXIMO_MB', 10) * 1024` — o padrão que `.ai/rules/config.md` proíbe | CT-01, linha `""` (daria `0`) |
| M2 | `NumeroDoEnv::diasOuDesligado()` no lugar de `positivo()` | CT-01, linha `"0"` (daria `0`) |
| M3 | default diferente de 10 (o `12` herdado do Livewire é o erro natural) | CT-01, linha `ausente` · CT-02 |
| M4 | multiplicação por 1000 em vez de 1024 | CT-01, linhas `"25"` e `"1"` |
| M5 | multiplicação ausente — chave em MB com nome `_em_kb` | CT-01, linha `"25"` · CT-02 · CT-03 (10239 KB seria recusado) |

---

## Regra R2 — o teto de fábrica é o número do requisito: 10 MB, expostos como 10240 KB

> `RQ-01`, `RQ-05` · A2, perfil **padrão** · técnica: **valor literal do requisito**, sem injeção de
> config — é o único cenário em que o `10` do card aparece escrito.

```gherkin
  Regra: o teto de fábrica é o número escrito no requisito

    Cenário: [CT-02] a instalação de fábrica limita o upload em 10 MB
      Dado que o phpunit.xml NÃO define KIT_UPLOAD_MAXIMO_MB
      Quando o processo de teste lê a configuração do kit
      Então kit.uploads.maximo_em_kb vale 10240, que é o 10 MB do requisito em KB
```

**A linha `10240 / 1024 é 10` foi removida** por achado da revisão adversarial (A-06): afirmar
aritmética é tautologia, não oráculo. O número do requisito entra na asserção como o literal
`10240`, e a conversão que interessa é a que CT-01 exercita com valor de env variável.

**CT-02 não duplica a linha `ausente` de CT-01**, e a diferença é o sujeito: CT-01 relê o arquivo
`config/kit.php` com `kitConfigCom()` e prova a **expressão**; CT-02 lê a config **do processo**, já
resolvida no boot, e prova que nada no arnês nem em `configureDefaults()` a sobrescreveu. São dois
mutantes distintos (M3 na expressão, M6 no processo).

**O `Dado` afirma o ambiente porque o cenário depende dele.** Verificado: o `phpunit.xml` deste
projeto fixa sete chaves `KIT_*`, e **`KIT_UPLOAD_MAXIMO_MB` não está entre elas**. Se algum dia
estiver, este cenário passa a medir o ambiente em vez do default — a asserção do `Dado`
(`expect(env('KIT_UPLOAD_MAXIMO_MB'))->toBeNull()`) é o que faz o cenário ficar **vermelho** nesse
dia, em vez de ficar verde mentindo. É a armadilha que a skill nomeia: `Dado a configuração de
fábrica` é vácuo se o arnês definir a chave.

**Camada**: `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | default `12` (alinhar o kit ao Livewire em vez do contrário) | CT-02 |
| M7 | chave nascida com outro nome (`kit.upload.maximo_em_kb`, no singular) e consumidor lendo a inexistente — `config()` devolve `null`, `maxSize(null)` é **sem teto**, e nada estoura | CT-02 (afirma a chave exata) · CT-03 (borda+1 passaria) |

---

## Regra R3 — todo campo aceita **no** teto e recusa **acima** dele

> `RQ-01`, `RQ-02` · A3, perfil **padrão**, técnica **escalada** para **BVA 3-valores** · fronteira:
> tamanho em **KB**, incremento **1 KB** — é a granularidade que a regra `max` compara
> (`getSize() / 1024`, `ValidatesAttributes.php:2822`).

```gherkin
  Regra: o teto é inclusivo — 10240 KB entra, 10241 KB não

    Esquema do Cenário: [CT-03] a fronteira de tamanho no campo de favicon
      Dado que a regra global do upload temporário está alargada para max:1048576
      E que favicon já tem gravado o caminho "kit/anterior.png", com o arquivo no disco
      E o administrador do kit na tela /admin/configuracoes-do-kit
      Quando ele salva um favicon PNG de <kb> KB
      Então o resultado é "<resultado>"
      E o caminho gravado em favicon é "<gravado>"
      E o arquivo "kit/anterior.png" <ainda> no disco público

      Exemplos:
        | kb    | resultado                  | gravado          | ainda | # borda |
        | 10239 | aceito                     | um caminho novo  | está  | borda−1 |
        | 10240 | aceito                     | um caminho novo  | está  | borda   |
        | 10241 | recusado com a regra "max" | kit/anterior.png | está  | borda+1 |
```

**O `Dado` do alargamento não é decoração** — sem ele o arquivo de 10241 KB é recusado pela regra
global do Livewire, e o `->maxSize()` do campo poderia estar **ausente** com o cenário verde. Ver
`## A armadilha de camada`, item 3. A barreira global tem par próprio em CT-08 e CT-09.

**A linha `recusado` afirma o não-efeito**, e o `Dado` que grava `kit/anterior.png` é o que dá
sentido a ela — achado **R2-05** da rodada 2: sem o valor anterior arranjado, `o caminho gravado é o
valor anterior` é vácuo num campo vazio, e a prosa creditava a M12 uma asserção de disco que o
`Então` não tinha. Agora as três linhas afirmam que o arquivo anterior **continua** no disco, o que
distingue as três implementações: aceitou, recusou preservando, e recusou destruindo.

```gherkin
    Esquema do Cenário: [CT-04] o par teto/acima-do-teto nos três campos da tela de configurações
      Dado que a regra global do upload temporário está alargada para max:1048576
      E que <campo> já tem gravado o caminho "kit/anterior.png", com o arquivo no disco
      Quando o administrador do kit salva um PNG de <kb> KB em <campo>
      Então o resultado em <campo> é "<resultado>"
      E o caminho gravado em <campo> é "<gravado>"
      E o arquivo apontado pelo caminho gravado existe no disco público

      Exemplos:
        | campo         | kb    | resultado                  | gravado               |
        | logo          | 10240 | aceito                     | diferente do anterior |
        | logo          | 10241 | recusado com a regra "max" | kit/anterior.png      |
        | favicon       | 10240 | aceito                     | diferente do anterior |
        | favicon       | 10241 | recusado com a regra "max" | kit/anterior.png      |
        | arte_do_login | 10240 | aceito                     | diferente do anterior |
        | arte_do_login | 10241 | recusado com a regra "max" | kit/anterior.png      |
```

**O valor anterior é ARRANJADO, e é o achado A-02 da revisão adversarial.** Sem ele, `nada é gravado`
fica verde num campo que já estava vazio — e, pior, uma implementação que **apaga** a logo existente
ao recusar o upload passaria com folga. O `Dado` grava `kit/anterior.png` com arquivo no disco, e as
duas últimas linhas do `Então` distinguem as três implementações: aceitou (caminho novo, arquivo
existe), recusou preservando (caminho antigo intacto) e recusou destruindo (caminho vazio ou arquivo
sumido).

⚠️ **O oráculo é o arquivo apontado, não a contagem de arquivos no disco** — achado cosmético da
rodada 2, acatado: contar `2` na linha aceita reprovaria uma implementação que **apaga** o arquivo
antigo ao substituir, comportamento plausível e que nenhuma cláusula proíbe. O cenário mediria uma
decisão de limpeza de disco em vez da fronteira de tamanho.

```gherkin
    Esquema do Cenário: [CT-05] o mesmo par fora da tela de configurações
      Dado que a regra global do upload temporário está alargada para max:1048576
      Quando quem opera <tela> envia um arquivo de <kb> KB em <campo>
      Então o resultado é "<resultado>"
      E o erro, quando há, é atribuído à regra "max" em <campo>

      Exemplos:
        | tela                          | campo  | kb    | resultado                             |
        | /admin/organizacoes/create    | logo   | 10240 | organização criada com a logo gravada |
        | /admin/organizacoes/create    | logo   | 10241 | recusado, organização não criada      |
        | /app/{tenant}/projetos/create | anexos | 10240 | mídia criada na coleção anexos        |
        | /app/{tenant}/projetos/create | anexos | 10241 | recusado, nenhuma mídia criada        |
```

**A regra é nomeada, e é o achado A-03.** `a operação é recusada` sozinho fica verde quando a recusa
vem de outro motivo — um campo obrigatório faltando no arranjo, um slug inválido, uma permissão. O
cenário provaria "algo recusou", não "o teto recusou". Em Filament isso é
`assertHasFormErrors([$campo => 'max'])`; a linha `10240` exige `assertHasNoFormErrors()` mais a
asserção de persistência, para o arranjo não estar recusando por outra razão desde o início.

**Por que CT-04 e CT-05 existem ao lado de CT-03**: a varredura do `00-requisito.md` achou **cinco**
campos, e "todo upload do kit" (RQ-01) é uma cláusula sobre o conjunto. Um cenário num campo só deixa
o sexto campo nascer sem teto — e quatro dos cinco não tinham teto nenhum antes desta feature. Uma
linha por campo num `Esquema do Cenário` custa quase nada e é o que fecha a omissão.

**Por que cada campo tem as DUAS linhas, e não só a de recusa.** Uma versão anterior deste conjunto
tinha, em CT-04 e CT-05, só a linha `10241 → recusado`. Isso deixava vivo o mutante mais fácil de
introduzir corrigindo a feature: `->maxSize(1)` — ou `maxSize()` recebendo MB — num dos campos
recusaria **tudo**, e as quatro linhas de recusa continuariam verdes. RQ-01 é *aceita* até 10 MB e
RQ-02 é *recusa* acima: o `00-requisito.md` as separou de propósito, e uma coluna sem nenhuma célula
válida exercitada só prova a segunda. A regra do par vale **por campo**, não por feature.

**Camada**: CT-03 e CT-04 em `tests/Kit`; CT-05 em `tests/Tenancy`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | teto montado como `maximo − 1` (ou os MB da mensagem usados como valor: `intdiv` aplicado duas vezes) | CT-03, linha `borda` |
| M9 | `->maxSize()` esquecido em **um** dos cinco campos | CT-04 / CT-05, na linha `10241` daquele campo |
| M10 | `->maxSize()` recebendo **MB** (`maxSize(10)`) em vez de KB | CT-03, linha `borda−1` (10239 KB seria recusado) · CT-04 / CT-05, linha `10240` de **cada** campo |
| M11 | `->maxSize()` recebendo **bytes** (`maxSize(10485760)`) | CT-03, linha `borda+1` (10241 KB passaria) |
| M12 | recusa **depois** da gravação (grava e valida em seguida) | CT-03, linha `borda+1`, pela asserção do valor anterior e do disco |
| M45 | teto absurdamente curto num campo (`maxSize(1)`, ou o campo lendo outra chave) — recusa **tudo** e passa em todo cenário que só prova recusa | CT-04 / CT-05, linha `10240` de cada campo · CT-14 · CT-17 · CT-21 |
| M44 | **nenhum piso**: arquivo de 0 byte é aceito e gravado (um favicon vazio quebra o `<head>` sem erro) | ⚠️ **sem matador — lacuna declarada.** O requisito define teto e não define piso; `min:1` seria valor chutado. Virou pergunta em `## Perguntas para o 00-requisito.md` |

---

## Regra R4 — o teto efetivo de cada campo é **lido** da config, não cravado

> `RQ-05` · A3, perfil **padrão** · técnica: **rastreio de efeito** (mudar a config muda a
> fronteira) + **varredura de código** com asserção de ausência filtrada.

```gherkin
  Regra: mudar a chave de config muda a fronteira de todos os campos

    Esquema do Cenário: [CT-06] a fronteira acompanha a config em cada campo, e não um número no código
      Dado que kit.uploads.maximo_em_kb foi ajustado para 1500
      E a regra global do upload temporário está alargada para max:1048576
      Quando quem opera <tela> envia um PNG de <kb> KB em <campo>
      Então o resultado é "<resultado>"
      E o erro, quando há, é atribuído à regra "max" em <campo>

      Exemplos:
        | tela                          | campo         | kb   | resultado | # borda |
        | /admin/configuracoes-do-kit   | favicon       | 1500 | aceito    | borda   |
        | /admin/configuracoes-do-kit   | favicon       | 1501 | recusado  | borda+1 |
        | /admin/configuracoes-do-kit   | arte_do_login | 1500 | aceito    | borda   |
        | /admin/organizacoes/create    | logo          | 1501 | recusado  | borda+1 |
        | /app/{tenant}/projetos/create | anexos        | 1501 | recusado  | borda+1 |
```

**1500 KB é escolhido para discriminar, não por conveniência.** Não é múltiplo de 1024, então mata
também o mutante em que o consumidor deriva os MB e volta a multiplicar
(`intdiv(1500, 1024) * 1024 = 1024`, e 1500 KB seria **recusado** na linha `borda`). Um valor como
2048 deixaria esse mutante vivo.

**A coluna `campo` é a segunda dimensão, e é o achado A-01 da revisão adversarial.** A versão
anterior deste cenário rodava só no `favicon`, e sustentava R4 inteira num campo — um teto cravado
num dos outros quatro (`->maxSize(1024)`, por exemplo) ficaria invisível, porque nenhum outro cenário
manda arquivo *grande e legítimo* fora do `favicon`. Percorrer `estado × operação` com o campo fixo
é exatamente a matriz "100% coberta" com uma dimensão intocada que a skill descreve.

⚠️ **Este cenário atravessa duas suítes** (`/admin/organizacoes` e `/app/{tenant}` exigem `Tenancy`).
Na implementação ele é **dois** arquivos com o mesmo ID: as três primeiras linhas em
`tests/Kit/UploadLimiteETiposTest.php`, as duas últimas em
`tests/Tenancy/UploadLimiteETiposTenancyTest.php`. Um `Esquema do Cenário` não atravessa `TestCase`.

```gherkin
  Regra: nenhum tamanho de upload fica cravado no código dos formulários

    Cenário: [CT-07] nenhum maxSize do kit recebe um número literal
      Dado o código dos três arquivos que declaram campo de upload
      Quando os comentários são removidos do texto
      Então nenhuma chamada a maxSize recebe um literal numérico
      E os três continuam contendo "->maxSize("
```

**A asserção é `->maxSize(` seguido de dígito, não a busca por `10240`** — achado A-04 da revisão
adversarial. A versão anterior proibia as strings `10240` e `10 * 1024`, e deixava passar `1024`,
`5120`, `20480` e `10_240`: um teto cravado com **qualquer outro número** ficava verde num teste que
existe justamente para proibir número cravado. O padrão é
`preg_match('/->maxSize\(\s*\d/', $codigoSemComentario)` — falso nos três arquivos.

**As duas metades continuam obrigatórias.** A asserção de **ausência** precisa remover comentário
antes de afirmar — `.ai/rules/testes.md` documenta três casos deste repositório em que a própria
documentação do arquivo reprovou o teste, e aqui é garantido acontecer: os três arquivos **citam** o
número e a unidade na explicação de por que `maxSize()` recebe KB. Filtro:
`preg_replace('~/\*.*?\*/~s', '', $codigo)` mais as linhas que começam com `//`. A asserção de
**presença** roda no texto cru e existe porque a ausência sozinha passa num arquivo que **apagou** o
`maxSize()` — que é o defeito de partida.

⚠️ CT-07 é **varredura de código, não de comportamento**, e não substitui CT-06: um `maxSize()` que
lê a chave errada passa aqui e morre lá. Os dois juntos é que fecham R4.

**Camada**: `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | o acessor devolve um número próprio (`return 10240;`) e ignora a config | CT-06 |
| M14 | um dos campos mantém `->maxSize(10 * 1024)` cravado | CT-06 · CT-07 |
| M15 | o acessor lê chave inexistente → `null` → `maxSize(null)` = **sem teto** | CT-03 borda+1 · CT-04 · CT-06 |
| M16 | o acessor deriva MB e volta a multiplicar (`intdiv($kb, 1024) * 1024`) | CT-06, linha `borda` |
| M17 | o número sai do código do campo mas fica cravado no `helperText`, e a config diz outra coisa | ⚠️ **sem matador por escolha** — o texto do `helperText` não é oráculo (`## Fronteira com o Plano`). Cobertura parcial por CT-20, que cruza README × config |

---

## Regra R5 — o upload temporário do Livewire usa **o mesmo** teto

> `RQ-01`, `RQ-02`, `RQ-05` · A4, perfil **padrão** · técnica: **rastreio de efeito no consumidor** —
> e "no consumidor" é o ponto inteiro da regra.

```gherkin
  Regra: a camada logo acima da tela nunca é mais estreita nem mais larga que a chave do kit

    Esquema do Cenário: [CT-08] o upload temporário do Livewire é limitado pela chave do kit
      Dado que kit.uploads.maximo_em_kb vale <em_kb>
      E que o alinhamento de defaults do kit foi executado
      Quando o Livewire monta as regras do upload temporário
      Então elas são exatamente ["required", "file", "max:<em_kb>"]

      Exemplos:
        | em_kb | # partição                                  |
        | 10240 | de fábrica — o valor do requisito           |
        |  1500 | config injetada — prova que o valor é LIDO  |
```

**A linha `1500` é o achado A-05, e é a que fecha a regra.** Com apenas a linha de fábrica, o
cenário não distingue `'max:'.config('kit.uploads.maximo_em_kb')` de
`config()->set(..., ['required','file','max:10240'])` **literal** — sob a config de fábrica os dois
produzem o mesmo array. Quem instalasse com `KIT_UPLOAD_MAXIMO_MB=25` ganharia campos com teto
25600 KB e upload temporário barrando em 10240, e RQ-05 morreria em silêncio na camada que ADR-04
existe para alinhar. É o mesmo truque de CT-06, aplicado ao consumidor.

**Arnês, com precedente na casa**: `configureDefaults()` é `protected`
(`app/Providers/KitServiceProvider.php:75`), e o kit já resolve isso — `alinharConfiguracoesDoKit()`
em `tests/Pest.php` chama um passo protegido do boot via `Closure::call()`, com o docblock
explicando por que o método não é API. A linha `1500` injeta a chave, chama o alinhamento pelo mesmo
padrão e então consulta o consumidor. Se esse helper for usado por mais de um arquivo de teste, ele
vai para `tests/Pest.php` (`.ai/rules/testes.md`).

**A asserção é sobre `FileUploadConfiguration::rules()`, não sobre `config()`.** Esta é a diferença
que mata o mutante mais barato de escrever e o mais difícil de ver: `config()->set()` **aceita
qualquer chave** (`.ai/rules/config.md`, §"Uma pergunta, uma dona" — dois casos deste repositório
ficaram verdes setando uma chave que nunca existiu, enquanto a produção recusava). Afirmar sobre o
consumidor real
(`vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:114-121`) é a
única forma de provar que a linha do provider escreveu na chave certa. O array **inteiro** é
comparado, não só o `max:` — perder o `'file'` ou o `'required'` é mutante próprio.

```gherkin
    Esquema do Cenário: [CT-09] com o teto de fábrica, o arquivo acima dele não chega ao formulário
      Dado a regra de fábrica do upload temporário, sem ajuste do teste
      Quando o administrador do kit escolhe um favicon PNG de <kb> KB
      Então o resultado é "<resultado>"
      E o caminho gravado em favicon é "<gravado>"

      Exemplos:
        | kb    | resultado                     | gravado          | # borda |
        | 10240 | aceito e gravado              | um caminho novo  | borda   |
        | 10241 | recusado, e nada gravado      | o valor anterior | borda+1 |
```

CT-09 é o **par completo da pilha alinhada**: prova que 10240 KB atravessa as duas barreiras e que
10241 KB não atravessa nenhuma. Na linha `borda+1` o arquivo nem vira `TemporaryUploadedFile`
(`_uploadErrored`, `WithFileUploads.php:73-95`, lança `ValidationException` sobre `data.favicon`), e
o oráculo primário é o **não-efeito** — nada gravado, nenhum arquivo no disco — com o erro no campo
como asserção de apoio. Marcado `@premissa` quanto à **chave** do erro (`data.favicon`): a mensagem
sai do Livewire, não do kit, e a asserção que importa é a que não depende dela.

⚠️ **O `Então` não promete mais "recusado no upload temporário"** — achado cosmético da rodada 2,
acatado: com a chave do erro em premissa, o oráculo restante não distingue a recusa do upload
temporário da recusa do `->maxSize()` do campo. Prometer a **camada** no `Então` seria afirmar uma
localização que nenhuma asserção observa. Quem prova a camada é CT-08, pelo consumidor.

**Camada**: `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | a linha do provider não existe — vale o default de 12 MB do Livewire | CT-08 |
| M19 | escrita em chave vizinha errada (`livewire.temporary_file_upload.rule`, singular) | CT-08, por afirmar sobre o consumidor |
| M20 | a regra montada perde `'file'` ou `'required'` | CT-08, por comparar o array inteiro |
| M21 | `max:` montado com o valor em **MB** (`max:10`) | CT-09, linha `borda` (10240 KB seria recusado) |
| M22 | a linha roda **antes** de a config do kit estar disponível e monta `max:` com `null` | CT-08 |

---

## Regra R6 — os campos de imagem recusam SVG, e recusam **antes** de gravar

> `RQ-03` · A1, perfil **completo** · técnica: **tabela de decisão** (campo × formato) +
> **rastreio do não-efeito**. Quatro cenários; o teto do perfil `completo` é 5.

```gherkin
  Regra: SVG é recusado em todo campo de imagem, e nenhum byte dele chega ao disco

    Esquema do Cenário: [CT-10] SVG recusado nos três campos da tela de configurações
      Dado o administrador do kit na tela /admin/configuracoes-do-kit
      Quando ele salva um arquivo "logotipo.svg" de 8 KB em <campo>
      Então o formulário acusa a regra "image" em <campo>
      E nada é gravado em <campo>
      E o disco público não recebe nenhum arquivo

      Exemplos:
        | campo         |
        | logo          |
        | favicon       |
        | arte_do_login |
```

**A terceira e a quarta linha do `Então` são a regra, não enfeite.** "Recusado" sozinho fica verde
numa implementação que grava o SVG no disco público e **depois** reprova o formulário — e o arquivo
gravado é exatamente a superfície de XSS que a cláusula existe para fechar. `Storage::fake('public')`
mais `allFiles()` vazio é o que distingue as duas implementações.

```gherkin
    Cenário: [CT-11] SVG recusado também na logo da organização
      Dado quem administra organizações em /admin/organizacoes/create
      Quando ele envia "logotipo.svg" de 8 KB como logo
      Então o formulário acusa a regra "mimetypes" no campo logo
      E a organização não é criada
      E o disco público não recebe nenhum arquivo
```

**A regra é nomeada** — achado **R2-06** da rodada 2: `erro de tipo de arquivo` era o mesmo
`a operação é recusada` que A-03 condenou em CT-05 e A-13 em CT-15, e o mecanismo deste campo é
allow-list, então uma troca por regra de **extensão** (`mimes:png,jpeg,webp`) ficaria verde. A regra
que o `acceptedFileTypes()` gera é `mimetypes` (`BaseFileUpload.php:258-268`), e é ela que o cenário
atribui.

**A terceira linha é o achado A-08**: R6 promete no título que a recusa acontece **antes** de gravar,
e sem a asserção de disco o cenário fica verde numa implementação que grava o SVG no disco público e
só depois reprova o formulário — deixando o arquivo servível pelo mesmo origin, que é o buraco
inteiro. A mesma linha que CT-10 já tinha, e que faltava aqui.

CT-11 é **guarda de regressão**, não cobertura nova: aquele campo já recusava SVG por allow-list
(`TenantForm:119-133`), e RQ-03 vale para todo campo do kit. Sem ele, trocar a allow-list de lá pelo
`->image()` "para uniformizar com os outros campos" reabriria o buraco sem nada ficar vermelho.

```gherkin
    Cenário: [CT-12] SVG renomeado para .png é recusado pela regra de imagem
      Dado um arquivo real cujos bytes são SVG e cujo nome é "logo.png"
      Quando ele é validado contra a regra "image"
      Então a validação falha
```

**Por que este cenário não é de componente** — ver `## A armadilha de camada`, item 2: no arnês de
teste o MIME vem do **nome**, e um `fake()->createWithContent('logo.png', $svg)` seria **aceito**,
com a produção recusando. Escrevê-lo por componente produziria vermelho por defeito da fixture, e é
o cenário que ADR-03 já antecipou como "pressão para relaxar a asserção". Ele desce para a camada da
regra, com `Illuminate\Http\UploadedFile` real (`$test = true`), que é onde a detecção por conteúdo
acontece — o mesmo caminho de detecção que a produção usa.

```gherkin
    Cenário: [CT-13] o detector por conteúdo não se deixa enganar pela extensão
      Dado um arquivo real cujos bytes são SVG e cuja extensão é ".png"
      Quando o detector do Flysystem é consultado sobre esse arquivo
      Então a detecção pelo CONTEÚDO devolve "image/svg+xml"
      E a detecção pelo CAMINHO devolve "image/png"
```

**O contraste é o oráculo.** CT-13 asserta a **premissa de produção** de ADR-03, e é o cenário que
morre se o Livewire trocar o detector do disco temporário por um baseado em extensão — o risco que o
próprio ADR declarou e para o qual disse que "nenhum teste desta feature reprova". Este reprova.
Afirmar só a primeira metade não bastaria: sem a segunda linha, um detector que devolvesse
`image/svg+xml` para tudo passaria.

⚠️ **CT-12 e CT-13 exercitam VENDOR, não o kit** — achado A-07 da revisão adversarial, e o mais
importante dela. CT-12 valida a regra `image` do Laravel; CT-13 consulta o finfo/Flysystem. Nenhum
dos dois toca uma linha do kit, e por isso **não podem** matar M28/M35 sozinhos: uma regra de recusa
escrita como `str_ends_with(strtolower($arquivo->getClientOriginalName()), '.svg')` passaria pelos
dois **e** por CT-10 e CT-16 (onde o arquivo de fato *se chama* `.svg`), deixando o SVG renomeado
entrar — exatamente o uso indevido que a linha **O** do SFDIPOT declara coberto. Os dois cenários
ficam (são a guarda da premissa e do trade-off), e a lacuna é fechada por CT-22.

```gherkin
    Esquema do Cenário: [CT-22] SVG renomeado para .png é recusado pelas regras DO KIT
      Dado um upload temporário do Livewire cujos bytes são SVG e cujo nome é "logo.png"
      E que esse upload NÃO tem arquivo de metadados ao lado
      Então o MIME que ele reporta é "image/svg+xml", vindo do conteúdo
      Quando ele é validado contra as regras que o campo <campo> do kit declara
      Então a validação falha

      Exemplos:
        | campo                        | mecanismo do kit sob teste       |
        | anexos do Projeto            | a regra Closure de recusa de SVG |
        | favicon                       | a regra `image` do Laravel       |
        | logo da organização          | a allow-list acceptedFileTypes   |
```

⚠️ **A rodada 2 derrubou a primeira versão deste cenário, e ela estava errada.** Ele dizia
`componente (Tenancy)` e mandava a fixture pelo formulário. Mas `Testable::setProperty()` desvia
**todo** `UploadedFile` para `upload()` (`Testable.php:189-200`), e um `TemporaryUploadedFile` **é**
um `UploadedFile` — então `storeTemporaryFile()` reescreveria o meta JSON com o MIME derivado do
nome (`FileUploadConfiguration.php:128-143`), o arquivo voltaria a ser `image/png`, seria **aceito**,
e o cenário ficaria vermelho por defeito da própria fixture. Exatamente o argumento com que este
documento expulsou CT-12 da camada de componente — aplicado contra ele mesmo (achado **R2-01**).

**A correção é a camada, não o arranjo**: CT-22 valida o arquivo contra as **regras que o campo
declara** (`getValidationRules()` do componente Filament), sem passar pelo ciclo de upload do
Livewire. É a camada de CT-12, mas apontada para **código do kit** em vez de vendor — que era o
ponto de A-07. A linha `Então o MIME que ele reporta é "image/svg+xml"` vem **antes** da validação de
propósito: sem ela, o cenário não distingue "a regra do kit recusou" de "a fixture perdeu o
conteúdo", e é ela que mata M46 (achado **R2-09**: CT-13 nunca matou M46, porque asserta o Flysystem
e não o que o `TemporaryUploadedFile` reporta).

**As três linhas fecham R2-02**: a versão anterior cobria só o `anexos`, e nenhum cenário mandava
arquivo renomeado aos quatro campos de **imagem** — onde CT-10/CT-11/CT-16 usam arquivo que de fato
se chama `.svg`. A logo da organização é a linha que mais importa: o mecanismo dela é
`acceptedFileTypes()`, então não herda nem a cobertura acidental da regra `image`.

**A segunda linha do `Então` original saiu**: `nenhuma mídia é criada na coleção anexos` media o
caminho de gravação, que este cenário não percorre. Manter a frase seria prometer um oráculo que as
asserções não observam.

**O arnês, e por que ele é possível.** A skill é explícita: *impossibilidade de arnês é hipótese, não
conclusão — tente mudar o arnês antes de declarar a lacuna.* Feito, e medido no `vendor`:

`TemporaryUploadedFile::getMimeType()` (`TemporaryUploadedFile.php:63-88`) só devolve o MIME do
**nome** porque lê `metaFileData()['type']`, e `metaFileData()`
(`TemporaryUploadedFile.php:233-247`) devolve `[]` quando **não existe** o `.json` ao lado do
arquivo. Sem meta, o método cai em `$this->storage->mimeType($this->path)` — **detecção por
conteúdo**, o mesmo caminho da produção, dentro do teste.

O arnês, com as três peças verificadas: `FileUploadConfiguration::disk()` devolve `'tmp-for-tests'`
sob `runningUnitTests()` (`FileUploadConfiguration.php:30-37`), `directory()` devolve `livewire-tmp`
(`:63-66`), e `TemporaryUploadedFile::createFromLivewire($path)` é **público** (`:256-259`). Então:
gravar os bytes de SVG em `livewire-tmp/logo.png` naquele disco, **sem** escrever o `.json`, e
construir o `TemporaryUploadedFile` — que é a única classe que a regra `Closure` do `anexos` aceita
(`instanceof`), e agora com MIME vindo do conteúdo.

É a diferença entre provar que **o Laravel** recusa SVG renomeado e provar que **o kit** recusa. O
segundo é o que a cláusula pede.

⚠️ **Pergunta que este cenário levanta** — ver `## Perguntas para o 00-requisito.md`: se a regra do
`anexos` é guardada por `instanceof TemporaryUploadedFile`, ela **não roda** para nenhum outro
caminho de escrita (`addMedia()` programático, import, seeder). CT-22 prova a regra no caminho da
tela; a cláusula RQ-03 não diz se vale só ali.

**Camada**: CT-10, CT-12, CT-13 em `tests/Kit`; CT-11 em `tests/Tenancy`; **CT-22 na camada da
regra**, em `tests/Tenancy` (as regras do `anexos` e da logo da organização precisam do painel e do
contexto de organização para o schema resolver).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | `->image()` sozinho, sem a regra `image` do Laravel — **é o estado de hoje** | CT-10 |
| M24 | `->rule('image:allow_svg')`, ou allow-list com `image/svg+xml` dentro | CT-10 |
| M25 | recusa aplicada **depois** da gravação no disco | CT-10 e CT-11, pela linha do disco |
| M26 | barreira só no seletor de arquivo do cliente (`accept="image/*"`), sem regra de servidor | CT-10 (teste de componente não passa pelo seletor) |
| M27 | a allow-list do `TenantForm` trocada por `->image()` "para uniformizar" | CT-11 · CT-21 (no `save`) |
| M28 | detecção de tipo por **extensão** ou pelo nome original (`str_ends_with(..., '.svg')`) em vez de conteúdo | **CT-22**, nas três linhas (CT-12 e CT-13 só provam o vendor — ver o aviso acima) |
| M29 | a regra aplicada em **um** dos três campos (o helper `arquivo()` mudado num call site só) | CT-10, nas outras duas linhas |
| M46 | o `TemporaryUploadedFile` passa a derivar o MIME da extensão mesmo sem meta (o risco declarado em ADR-03) | **CT-22**, pela linha `Então o MIME que ele reporta é "image/svg+xml"` — ⚠️ **não** CT-13, que asserta o Flysystem e nunca observou essa escolha (achado R2-09) |
| M50 | a recusa de disfarce existe só no `anexos` e não nos campos de imagem — ou o contrário | **CT-22**, pelas três linhas de mecanismo (achado R2-02) |
| M51 | `post_max_size` apertado abaixo da chave, com as outras duas diretivas corretas | **CT-23**, linha `post_max_size` (achado R2-03) |
| M52 | a diretiva de infra **removida**, caindo no default do PHP (2M) | **CT-23**, pela metade de presença (achado R2-04) |

---

## Regra R7 — os campos de imagem aceitam os demais formatos de imagem

> `RQ-04` · A1, perfil **completo** · técnica: **EP exaustiva** do domínio de formatos. Domínio
> fechado e pequeno (nove formatos), então não se amostra: uma linha por partição.

```gherkin
  Regra: qualquer formato de imagem que não seja SVG é aceito

    Esquema do Cenário: [CT-14] os formatos de imagem aceitos, um por partição
      Dado o administrador do kit na tela /admin/configuracoes-do-kit
      E que <campo> já tem gravado o caminho "kit/anterior.png"
      Quando ele salva um arquivo "<nome>" de 8 KB como <campo>
      Então o formulário não acusa erro
      E o caminho gravado em <campo> é diferente de "kit/anterior.png"
      E esse caminho aponta para um arquivo no disco público

      Exemplos:
        | campo         | nome            | # partição                        |
        | favicon       | imagem.jpg      | jpg                               |
        | favicon       | imagem.jpeg     | jpeg                              |
        | favicon       | imagem.png      | png                               |
        | favicon       | imagem.gif      | gif                               |
        | favicon       | imagem.bmp      | bmp                               |
        | favicon       | imagem.webp     | webp                              |
        | favicon       | imagem.avif     | avif                              |
        | favicon       | imagem.heic     | heic                              |
        | favicon       | imagem.heif     | heif                              |
        | favicon       | ícone-ção🙂.png | nome com acento e emoji (4 bytes) |
        | logo          | imagem.webp     | RQ-04 fora do favicon             |
        | arte_do_login | imagem.avif     | RQ-04 fora do favicon             |
```

A linha do nome com acento e emoji é o item `Unicode` do checklist de taxonomia, e custa uma linha em
vez de um cenário. Ela mata a regra de recusa escrita como comparação de sufixo de string, que quebra
ou passa errado com nome multibyte.

**As duas últimas linhas são o achado A-11**: RQ-04 tinha **um** cenário positivo, num campo só. Em
`logo` e `arte_do_login` a garantia era indireta — o nome da regra em CT-10 — e não pegaria um
estreitamento que ainda passe por `image` num daqueles dois. Os formatos escolhidos ali não são
redundantes: `webp` e `avif` são justamente os que uma allow-list escrita à mão esqueceria.

**O `Dado` do valor anterior é o achado A-12**: `o caminho gravado aponta para um arquivo no disco`
ficava verde quando o campo já tinha um caminho válido de antes — o cenário não distinguia "gravou
este upload" de "não gravou nada e o valor antigo continua lá". A comparação com o caminho arranjado
é o que fecha isso.

**Verificado que as nove partições são expressáveis no arnês** (o MIME derivado do nome cobre todas,
inclusive `avif` e `heic`, que o GD não gera):

```
a.avif → image/avif  · guessExtension avif
a.heic → image/heic  · guessExtension heic
a.heif → image/heic  · guessExtension heic
a.bmp  → image/bmp   · guessExtension bmp
```

```gherkin
    Esquema do Cenário: [CT-15] .ico e .tiff ficam de fora, e é decisão registrada
      Dado o administrador do kit na tela /admin/configuracoes-do-kit
      Quando ele salva um arquivo "<nome>" de 4 KB como favicon
      Então o formulário acusa a regra "image" no campo favicon
      E nada é gravado em favicon

      Exemplos:
        | nome        | # formato sacrificado |
        | favicon.ico | ico                   |
        | imagem.tiff | tiff                  |
```

**A regra é nomeada** (achado A-13): `acusa erro no campo favicon` ficava verde num campo quebrado
que recusa **tudo** — era o M32 sobrevivendo por uma porta lateral, num cenário que existe para
documentar um trade-off e não para esconder um defeito. Atribuir o erro à regra `image` é o que
distingue "recusou por formato" de "recusou por qualquer motivo".

As duas linhas, e não só a do `.ico`: são **dois** formatos que ADR-02 sacrifica, e o título do
cenário prometeria os dois com apenas um exercitado. `.tiff` é o caso menos óbvio e o mais fácil de
alguém acrescentar à allow-list "de passagem". Nota de arnês verificada: o MIME derivado do nome é
`application/ico` para `.ico` e `image/tiff` para `.tiff` — o primeiro não casa nem com
`mimetypes:image/*`, o segundo casa; os dois caem na regra `image`, que é a barreira.

⚠️ **`@premissa`** — este cenário **contraria a letra de RQ-04** (`.ico` é tipo de imagem) e existe
porque ADR-02 aceitou o trade-off por escrito. Ele está aqui, e não omitido, por dois motivos: a
premissa fica falsificável (se o usuário responder que `.ico` precisa passar, é este cenário que
inverte, e não uma descoberta em produção), e a allow-list não pode **crescer em silêncio** — sem
ele, alguém acrescenta `image/x-icon` sem que a decisão de ADR-02 seja reaberta. Ver
`## Perguntas para o 00-requisito.md`.

**Camada**: `tests/Kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | allow-list curta o bastante para atender RQ-03 e violar RQ-04 (`['image/png']`, ou os três tipos do `TenantForm`) | CT-14, linhas jpg, gif, webp, avif, heic, heif |
| M31 | `->rule('mimes:png,jpg')` no lugar de `image` | CT-14, linhas gif, bmp, webp, avif, heic |
| M32 | o campo recusa tudo (`maxSize(0)`, allow-list vazia, `image` mais `mimetypes` incompatíveis) | CT-14, qualquer linha · CT-03 linha `borda` |
| M33 | recusa por sufixo de string, que quebra com nome multibyte | CT-14, última linha |

---

## Regra R8 — o campo de **documento** recusa SVG e continua aceitando documento

> `RQ-03` (sob a premissa registrada no `00-requisito.md`) · A1, perfil **completo** · técnica:
> **tabela de decisão** — é o caso de **domínio condicionado**: o conjunto de formatos aceitos
> depende do **campo** (imagem × documento), e tratar "formato" como um domínio único faz o teto da
> família de documento desaparecer.

| Campo | Domínio | SVG | PDF / planilha |
|---|---|---|---|
| `logo`, `favicon`, `arte_do_login`, `logo` da organização | imagem | recusado | recusado (não é imagem) |
| `anexos` do Projeto | documento | **recusado** (RQ-03, sob premissa) | **aceito** |

```gherkin
  Regra: no campo de anexos, SVG é o único formato recusado

    Esquema do Cenário: [CT-16] SVG é recusado no anexo do projeto, em qualquer posição do envio
      Dado quem opera um projeto de uma organização
      Quando ele anexa <envio>
      Então a operação é recusada
      E nenhuma mídia é criada na coleção anexos

      Exemplos:
        | envio                                                   | # posição do inválido |
        | "desenho.svg" de 8 KB                                   | único                 |
        | "contrato.pdf" de 12 KB e "desenho.svg" de 8 KB          | segunda               |
        | "desenho.svg" de 8 KB e "contrato.pdf" de 12 KB          | primeira              |

    Cenário: [CT-17] documento continua sendo aceito no anexo do projeto
      Dado quem opera um projeto de uma organização
      Quando ele anexa "contrato.pdf" de 12 KB e "planilha.xlsx" de 10240 KB
      Então duas mídias são criadas na coleção anexos
      E os nomes gravados são "contrato.pdf" e "planilha.xlsx"
```

**As três linhas de CT-16 são o achado A-09, e ele é grave.** A versão anterior enviava **um** SVG,
sempre na primeira posição de um campo `->multiple()`. Isso não distingue "valida cada arquivo" de
"valida o primeiro e segue" — uma regra escrita com `$arquivos[0]`, `head()` ou um `break` no laço
passaria, e `[contrato.pdf, desenho.svg]` entraria **inteiro**. A partição aqui não é o formato, é a
**posição do arquivo inválido no array**, e é ela que prova a mecânica de `$fileRules` que a nota
técnica desta regra afirma.

A linha `primeira` não é redundante com `único`: um envio de dois arquivos passa por um caminho de
código diferente do envio de um só, e o oráculo é o mesmo — **nenhuma** mídia criada, nem a do PDF
válido. Uma implementação que recusa o SVG e grava o PDF viola a recusa do formulário.

**CT-17 ganhou o valor discriminante que faltava** (achado A-10): 12 KB nos dois arquivos não tocava
a fronteira de tamanho do `anexos` em nenhum ponto, e nada era afirmado sobre **o que** ficou
gravado. Agora um dos arquivos está exatamente **no teto** (10240 KB), o que exercita a célula válida
do campo, e os nomes gravados são asseridos — sem isso, "duas mídias criadas" fica verde com os dois
arquivos trocados, truncados ou vazios.

⚠️ CT-16 é **`@premissa`**: o `00-requisito.md` registra em `## Ambiguidades` que RQ-03 vale no campo
de documento por suposição, não por texto. Se a suposição for negada, CT-16 sai e CT-17 fica.

**CT-17 não é caminho feliz redundante — é a metade que impede a regra de ser atendida errado.** Uma
allow-list (`acceptedFileTypes([...])`) ou um `->rule('image')` copiado dos campos de imagem
atenderia RQ-03 no `anexos` **fechando o campo**, que é a razão de o campo existir. Sem CT-17, o
mutante mais provável desta regra fica vivo.

**A regra `Closure` é exercitável por componente** — verificado, ver `## A armadilha de camada`,
item 1: `Testable::setProperty()` roda o fluxo real de upload temporário, então a propriedade é um
`TemporaryUploadedFile` de verdade e o `instanceof` da regra casa. O campo é `->multiple()`, e a
regra é validada **por arquivo** (`BaseFileUpload::getValidationRules()` classifica um `Closure`
como regra de arquivo, não de array) — CT-17, com dois arquivos, é o que prova isso.

**Camada**: `tests/Tenancy` (o `ProjetoResource` é o resource de demo, que só existe com tenancy
ligada — ver `telasDoKit()` em `tests/Pest.php`). Arranjo de disco herdado de
`tests/Tenancy/AnexosPrivadosTest.php`: **disco real em diretório temporário**, não `Storage::fake()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | a regra recebe o **array** de arquivos e o `instanceof` nunca casa | CT-16 |
| M35 | a comparação usa o nome original ou a extensão em vez do MIME do arquivo | **CT-22** |
| M36 | `->rule('image')` copiado dos campos de imagem | CT-17 |
| M37 | `acceptedFileTypes()` com allow-list de imagem | CT-17 |
| M38 | a regra recusa e a mídia é criada de todo modo (validação fora do caminho de `save`) | CT-16, pela segunda linha do `Então` |
| M47 | a regra inspeciona só o **primeiro** arquivo do campo `->multiple()` (`$arquivos[0]`, `head()`, `break` no laço) | CT-16, linhas `segunda` e `primeira` |
| M48 | a regra recusa o SVG e **grava** os demais arquivos do mesmo envio | CT-16, linhas `segunda` e `primeira`, pelo "nenhuma mídia" |

---

## Regra R9 — o teto e a recusa de SVG estão documentados nos dois idiomas e no `.env.example`

> `RQ-06` · A5, perfil **mínimo** · técnica: presença sobre o texto cru + **ausência com filtro de
> comentário**. **Estouro do teto justificado**: o perfil `mínimo` permite 1 cenário por regra, e
> RQ-06 tem três artefatos distintos (dois READMEs e o `.env.example`) mais um mutante — README
> prometendo um default diferente do efetivo — que nenhum dos outros mata. O gate vence o teto.

```gherkin
  Regra: quem instala o kit acha o número, a unidade e o que mais mudar para subir muito

    Esquema do Cenário: [CT-18] cada README documenta a chave, a unidade e a escada de tetos
      Dado o arquivo <readme>
      Quando ele é lido
      Então ele cita "KIT_UPLOAD_MAXIMO_MB"
      E ele cita "kit.uploads.maximo_em_kb"
      E ele cita "<unidade>"
      E ele cita "docker/php/uploads.ini"
      E ele cita "post_max_size"
      E ele cita "client_max_body_size"
      E ele cita "<svg>"

      Exemplos:
        | readme       | unidade   | svg         |
        | README.md    | MEGABYTES | recusa SVG  |
        | README.en.md | MEGABYTES | refuses SVG |
```

**As âncoras são strings que existem, verificadas** (`README.md:1640-1649`, `README.en.md:1606-1615`)
— não prosa inventada. Cada uma corresponde a um item que RQ-06 exige: o nome da chave, a chave de
config com a outra unidade, a palavra que desambigua a unidade, os dois paths de infra e a recusa de
SVG. O que NÃO é oráculo é a frase em volta delas.

`README.en.md` numa linha própria porque é o que costuma ficar para trás — o kit já tem este padrão
em `ConfiguracoesDoKitDocumentacaoTest` e `AnexosPrivadosDocumentacaoTest`, e RQ-06 não distingue
idioma. As duas citações de infraestrutura (`uploads.ini` e `client_max_body_size`) são o item 4 da
lista de RQ-06 no PRD e, mais que isso, são o que separa uma documentação que **funciona** de uma que
funciona até 12 MB.

```gherkin
    Cenário: [CT-19] a chave aparece no .env.example, e aparece COMENTADA
      Dado o arquivo .env.example
      Quando as linhas de comentário são removidas do texto
      Então o texto restante não contém "KIT_UPLOAD_MAXIMO_MB"
      E o texto cru contém "KIT_UPLOAD_MAXIMO_MB"
```

**As duas metades, e nesta ordem.** A ausência (no texto sem comentário) é o que prova que a chave
nasceu **comentada**, como as outras chaves de default do kit (verificado: `.env.example:89-92`
tem as quatro `KIT_TABELA_*` comentadas, e é o padrão da casa): uma chave ativa no
`.env.example` fixaria o valor em toda instalação nova e o default do `config/kit.php` **nunca
entraria** — e nada ficaria vermelho. A presença (no texto cru) é o que distingue "comentada" de
"não existe". É o filtro de `.ai/rules/testes.md` usado na direção afirmativa.

```gherkin
    Cenário: [CT-20] o default que o README promete é o default que o kit tem
      Dado o valor efetivo de kit.uploads.maximo_em_kb dividido por 1024
      Quando os dois READMEs são lidos
      Então cada um contém a linha "KIT_UPLOAD_MAXIMO_MB=" com esse número e nada depois dele
```

Este é o cenário que mata "mudaram o default e esqueceram o README", que é o modo pelo qual RQ-06
regride sem ninguém notar — a documentação continua lá, escrita, e passa a mentir.

**O número está ANCORADO na chave** (achado A-14): a versão anterior procurava o numeral solto, e um
README com um `10` em qualquer outra frase passaria sem que nada ligasse o número à chave. Asserir a
linha `KIT_UPLOAD_MAXIMO_MB={n}` — construída a partir da config, não escrita à mão — é o que faz o
cenário falar da chave certa. Verificado que a âncora existe: `README.md:1646` e `README.en.md:1612`.

⚠️ **E o casamento é de linha inteira, não de prefixo** — achado **R2-07**. `contém
"KIT_UPLOAD_MAXIMO_MB=10"` casa com `KIT_UPLOAD_MAXIMO_MB=10240`, que é **o erro de unidade mais
plausível desta feature** (a chave de config chama-se `_em_kb`, e copiar o número dela para a env é o
deslize natural). Casaria também com `=100` e `=105`. Quem instalasse copiando a linha receberia teto
de 10240 MB, e M41 sobreviveria na variante que mais importa. A asserção é sobre a linha delimitada:
`preg_match('/^KIT_UPLOAD_MAXIMO_MB='.$n.'$/m', $texto)`.

```gherkin
    Esquema do Cenário: [CT-23] nenhum teto de infra do kit é mais estreito que a chave
      Dado o teto de fábrica de kit.uploads.maximo_em_kb
      Quando <arquivo> é lido
      Então a diretiva <diretiva> está declarada nele
      E o valor dela, convertido para KB, não é menor que o teto da chave

      Exemplos:
        | arquivo                 | diretiva             | # degrau da escada      |
        | docker/php/uploads.ini  | upload_max_filesize  | tamanho do arquivo      |
        | docker/php/uploads.ini  | post_max_size        | corpo inteiro do POST   |
        | docker/nginx/nginx.conf | client_max_body_size | corpo aceito pelo proxy |
```

**CT-23 é o achado A-15, e é o único que falsifica a metade "funciona" de RQ-06.** CT-18, CT-19 e
CT-20 provam que existem textos e que um número casa; nenhum deles prova que **aumentar o valor
aumenta o teto**. Com `upload_max_filesize=10M` no Docker do kit, os três ficariam verdes, os READMEs
citariam os dois paths numa instrução correta em texto e inútil na prática, e quem subisse a chave
para 25 receberia 413 do nginx sem nenhuma mensagem do kit — "aumentar ou diminuir de forma facil"
viraria mentira documentada.

**A `## Fronteira com o Plano` estava errada neste ponto, e a linha foi corrigida.** Os *valores* de
`uploads.ini` e `nginx.conf` continuam não sendo oráculo — quem os escolhe é infraestrutura. Mas a
**relação** entre eles e a chave é derivável direto de RQ-01: o requisito diz que o kit aceita até
10 MB, e "o menor manda" é fato medido da escada de tetos. Uma camada de infra mais estreita que a
chave **contradiz RQ-01**, e isso é oráculo.

**A linha do `post_max_size` é o achado R2-03, e era um buraco real.** A escada de tetos tem **três**
degraus de infra, não dois, e o `post_max_size` trava **antes** do `upload_max_filesize`: com
`post_max_size=8M` os 23 cenários ficariam verdes, os READMEs continuariam corretos em texto, e
nenhum upload acima de ~8 MB chegaria ao Laravel. RQ-01 morreria numa diretiva que o conjunto não
lia — a mesma IMPL-5 uma linha ao lado. O documento **media** esse valor e o cenário não o assertava.

**A metade de presença é o achado R2-04.** `não é menor que ele` sozinho fica indefinido se a
diretiva for **removida**: fora do Docker do kit o PHP costuma vir com `upload_max_filesize=2M`, e
apagar a linha do `uploads.ini` é a forma silenciosa de cair nisso. CT-19 e CT-07 já exigem as duas
metades; CT-23 passa a exigir também.

Valores de hoje, verificados: `upload_max_filesize=52M` e `post_max_size=60M`
(`docker/php/uploads.ini:2-3`) e `client_max_body_size 60M` (`docker/nginx/nginx.conf:11`) — todos
acima de 10 MB, então o cenário nasce verde. Ele existe para o dia em que alguém apertar um dos três.

**Camada**: `tests/Kit/UploadLimiteETiposDocumentacaoTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M39 | só o `README.md` documentado, `README.en.md` esquecido | CT-18, linha inglês |
| M40 | a chave documentada sem a unidade, ou sem a escada de infra | CT-18 |
| M41 | o README promete um default diferente do efetivo | CT-20 |
| M42 | a chave nasce **ativa** no `.env.example` | CT-19 |
| M43 | a chave não aparece no `.env.example` (só no README) | CT-19, pela metade de presença |
| M49 | a instrução do README é correta em texto e **inútil na prática** — o Docker do kit trava antes da chave (`upload_max_filesize=10M`) | **CT-23** |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou
> `lacuna declarada: {o que foi tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota nova, nenhum `{id}` novo, nenhuma policy, gate, middleware ou guard tocado (`01-plano-acao.md`, `## Autorização` e `## Rotas`). A autorização das três telas continua guardada por `tests/Kit/PermissoesDeTelasTest.php` e `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: mesmo motivo |
| **Criação ≠ edição ≠ uso** | CT-21 — a fronteira e o tipo valem também no `edit` |
| Idempotência (ancorada no agregado) | **não se aplica**: a feature não acrescenta operação de escrita, ela **restringe** uma que já existe. A idempotência do envio repetido é comportamento pré-existente do `FileUpload` e do media-library, coberta por `tests/Tenancy/AnexosPrivadosTest.php` — nenhum mutante desta feature a afeta |
| Concorrência | **não se aplica**: nenhum contador, saldo, estoque ou limite acumulado. O teto é **por arquivo**, e duas gravações simultâneas não o somam |
| **Fronteira no ponto de entrada** (gravação) | CT-03, CT-04, CT-05, CT-06, CT-09, CT-21 |
| **Domínio condicionado** (campo × formato) | CT-10 + CT-14 (imagem) contra CT-16 + CT-17 (documento) — a tabela de R8 |
| Estado × operação de escrita | **não se aplica**: nenhuma entidade com `status` nesta feature |
| Ausente ≠ `null` ≠ vazio | CT-01, com `ausente`, `""` e `"0"` em linhas próprias e semânticas declaradas |
| Paginação / ordenação | **não se aplica**: nenhuma listagem tocada |
| Timezone / DST | **não se aplica**: nenhum oráculo temporal (ver `T` da varredura SFDIPOT) |
| Unicode / limite de varchar | CT-14, última linha (acento + emoji de 4 bytes no nome do arquivo) |
| Unicidade + soft delete | **não se aplica**: nenhuma coluna única nem `SoftDeletes` tocado |
| CRUD combinado | CT-21 |
| Mass assignment | **não se aplica**: nenhum campo novo em model nem em payload; a feature só restringe campos existentes |
| **Upload — acima do limite** | CT-03, CT-04, CT-05, CT-09 |
| **Upload — extensão que mente sobre o conteúdo** | CT-12, CT-13 |
| **Upload — 0 byte** | ⚠️ **lacuna declarada**. Medido: `fake()->create('vazio.png', 0)` tem MIME derivado do nome (`image/png`), passa `max`, passa `image`, grava um arquivo vazio no disco público — e um favicon de 0 byte quebra o `<head>` sem erro. Tentado derivar fronteira: o requisito **não define piso**, e `min:1` seria valor chutado (a skill proíbe). Virou pergunta em `## Perguntas para o 00-requisito.md`; nenhum cenário, e o mutante M44 (ausência de piso) fica vivo por decisão |
| Precisão monetária | **não se aplica** |

### O cenário de criação × edição

```gherkin
  Regra: a barreira que vale ao criar vale ao editar

    Esquema do Cenário: [CT-21] a logo da organização na edição: o que passa e o que não passa
      Dado uma organização já criada com a logo "organizacoes/logos/antiga.png"
      E a regra global do upload temporário está alargada para max:1048576
      Quando quem a administra troca a logo por <arquivo>
      Então o resultado é "<resultado>"
      E a coluna logo vale "<gravado>"

      Exemplos:
        | arquivo              | resultado                       | gravado                          | # partição |
        | "nova.png", 10240 KB | aceito                          | um caminho novo                  | válida     |
        | "logotipo.svg", 8 KB | recusado com a regra "mimetypes"| organizacoes/logos/antiga.png    | formato    |
        | "logo.png", 10241 KB | recusado com a regra "max"      | organizacoes/logos/antiga.png    | tamanho    |
```

**A linha válida é o achado A-16**, e é a metade que faltava: com as duas linhas de recusa só, uma
edição que **recusa toda troca de logo** ficaria verde — e a armadilha própria da edição (validação
que só roda no `create`, unicidade contra o próprio registro) viveria intacta. A skill é explícita:
cada coluna da matriz precisa de ao menos uma célula válida exercitada, e é ela que se liga ao gate de
tela de escrita.

**As regras são nomeadas** nas duas linhas de recusa, pela mesma razão de CT-05: sem isso o cenário
prova "algo recusou", não "o teto/o tipo recusou".

**Por que este cenário existe** apesar de `create` e `edit` compartilharem o schema: o defeito de
edição não é a regra estar num schema diferente — é a regra ser escrita na **página** de criação
(`CreateTenant`), ou a validação do `save` divergir da do `create`. É a omissão que a skill descreve
como "a mais cara e a mais fácil de cometer", e as duas partições inválidas vão em linhas separadas
para a primeira validação a disparar não mascarar a outra. O `Então` afirma o **valor gravado**, não
só a recusa.

**Camada**: `tests/Tenancy` (é onde vivem os testes de `EditTenant`).

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | o valor cru do `.env` vira um teto sempre maior que zero | R1 | EP / tabela de decisão | Kit | `tests/Kit/UploadLimiteETiposTest.php` | M1, M2, M3, M4, M5 |
| CT-02 | a instalação de fábrica limita o upload em 10 MB | R2 | valor literal do requisito | Kit | `tests/Kit/UploadLimiteETiposTest.php` | M3, M5, M6, M7 |
| CT-03 | a fronteira de tamanho no campo de favicon | R3 | **BVA 3-valores** | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | M5, M7, M8, M10, M11, M12, M15, M32 |
| CT-04 | o par teto/acima-do-teto nos três campos da tela | R3 | EP por campo, **par obrigatório** | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | M9, M10, M12, M15, M45 |
| CT-05 | o mesmo par fora da tela de configurações | R3 | EP por campo, **par obrigatório** | componente (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | M9, M10, M15, M45 |
| CT-06 | a fronteira acompanha a config **em cada campo** | R4 | rastreio de efeito + BVA + campo como 2ª dimensão | componente (Kit **e** Tenancy) | os dois arquivos | M13, M14, M15, M16 |
| CT-07 | nenhum `maxSize` do kit recebe literal numérico | R4 | varredura + ausência filtrada | Kit | `tests/Kit/UploadLimiteETiposTest.php` | M14 |
| CT-08 | o upload temporário é limitado pela chave do kit | R5 | rastreio no consumidor, **com config injetada** | Kit | `tests/Kit/UploadLimiteETiposTest.php` | M18, M19, M20, M22 |
| CT-09 | com o teto de fábrica, o arquivo acima dele não chega ao formulário | R5 | BVA 2-valores | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | M21 |
| CT-10 | SVG recusado nos três campos de imagem | R6 | tabela de decisão + não-efeito | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | M23, M24, M25, M26, M29 |
| CT-11 | SVG recusado também na logo da organização, com a regra nomeada | R6 | regressão por campo + não-efeito no disco | componente (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | M25, M27 |
| CT-12 | SVG renomeado para `.png` é recusado pela regra `image` | **R10** | premissa de **vendor** | Kit (regra) | `tests/Kit/UploadLimiteETiposTest.php` | — (premissa; **não** mata M28/M35) |
| CT-13 | o detector por conteúdo não se deixa enganar pela extensão | **R10** | premissa de **vendor** | Kit | `tests/Kit/UploadLimiteETiposTest.php` | — (premissa; **não** mata M46 — achado R2-09) |
| CT-14 | os formatos de imagem aceitos, um por partição, em três campos | R7 | **EP exaustiva** + campo como 2ª dimensão | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | M30, M31, M32, M33, M45 |
| CT-15 | `.ico` e `.tiff` ficam de fora, e é decisão registrada (`@premissa`) | R7 | premissa falsificável | componente (Kit) | `tests/Kit/UploadLimiteETiposTest.php` | — (fixa a premissa de ADR-02; impede a allow-list de crescer em silêncio) |
| CT-16 | SVG recusado no anexo, **em qualquer posição do envio** (`@premissa`) | R8 | tabela de decisão + partição por posição | componente (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | M34, M38, M47, M48 |
| CT-17 | documento continua sendo aceito no anexo, um deles no teto | R8 | domínio condicionado | componente (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | M36, M37, M45 |
| CT-18 | cada README documenta a chave, a unidade e a escada | R9 | presença, dois idiomas | Kit | `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` | M39, M40 |
| CT-19 | a chave aparece no `.env.example`, comentada | R9 | ausência filtrada + presença | Kit | `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` | M42, M43 |
| CT-20 | o default do README é o default do kit, ancorado na chave | R9 | asserção cruzada | Kit | `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` | M41 |
| CT-21 | a logo da organização na edição: o que passa e o que não | taxonomia (criação × edição) | par válido/inválido, partições isoladas | componente (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | M9, M27, M45 (no `save`) |
| **CT-22** | SVG **disfarçado** recusado pelas regras do kit, nos três mecanismos | **R10** | detecção por conteúdo, na camada da regra | regra (Tenancy) | `tests/Tenancy/UploadLimiteETiposTenancyTest.php` | **M28, M35, M46, M50** |
| **CT-23** | nenhum teto de infra é mais estreito que a chave | **R11** | asserção cruzada config × infra | Kit | `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` | **M49, M51, M52** |

**Mutantes sem matador** (2, declarados): **M17** — número cravado no `helperText`, por escolha (o
texto não é oráculo; CT-20 cobre em parte); **M44** — ausência de piso, o arquivo de 0 byte, que
virou pergunta antes de virar cenário. Os outros 47 têm cenário nomeado.

**Cobertura por cláusula, para a Matriz de Rastreabilidade do `feature-quality-gate`**:

| `RQ` | Regras | Cenários que a falsificam | Em quantos dos 5 campos |
|---|---|---|---|
| RQ-01 (aceita até 10 MB) | R1, R2, R3, R5 | CT-01, CT-02, CT-03 (`borda−1`, `borda`), CT-04 e CT-05 (linhas `10240`), CT-09 (`borda`), CT-17, CT-21 (linha válida), CT-23 | **5 de 5** |
| RQ-02 (acima é recusado) | R3, R5 | CT-03 (`borda+1`), CT-04 e CT-05 (linhas `10241`), CT-06, CT-09 (`borda+1`), CT-21 | 5 de 5, com a **regra `max` nomeada** |
| RQ-03 (recusa SVG) | R6, R8 | CT-10, CT-11, CT-16, CT-21, **CT-22** (o caso renomeado, em código do kit) · CT-12/CT-13 como premissa de vendor | 5 de 5 |
| RQ-04 (aceita o resto) | R7 | CT-14 (nove formatos × três campos), CT-15 (`@premissa`, o limite declarado da cláusula) | 3 de 3 campos de imagem do kit (a logo da organização é allow-list por decisão anterior) |
| RQ-05 (o teto vive na config) | R1, R2, R4, R5 | CT-01, CT-02, CT-06 (5 campos), CT-07, CT-08 (**config injetada**) | 5 de 5 + o teto global |
| RQ-06 (documentado **e funcionando**) | R9 | CT-18, CT-19, CT-20, **CT-23** | — |

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| BVA **3-valores** em cada um dos cinco campos (a linha `borda−1`, além do par) | CT-03 já mata M8/M11 com as três linhas, e a aritmética da fronteira é a mesma nos cinco campos. CT-04 e CT-05 ficaram com **2 valores por campo** (`10240` / `10241`), que é o mínimo para provar as duas metades da cláusula: `borda−1` não acrescenta mutante que `borda` não pegue quando o teto vem da mesma expressão |
| SVG renomeado por **componente** Livewire (`fillForm`), nos três campos de imagem | inexpressável por esse caminho: o `fillForm` grava o meta JSON com o MIME derivado do nome, e o cenário ficaria **verde** com a produção recusando (`## A armadilha de camada`, item 2). Substituído por CT-22, que constrói o upload temporário **sem** o meta e alcança a detecção por conteúdo |
| CT-22 replicado nos três campos de imagem | mata o mesmo mutante (M28/M35) que a versão no `anexos`, e ali a regra é a mais frágil das duas — um `Closure` escrito à mão, contra a regra `image` mantida pelo framework. Escolhido o campo onde o mutante é plausível |
| "o CSV do `ImportAction` acima de 10 MB é recusado" (consequência negativa de ADR-04) | mata o mesmo mutante que CT-08 (a regra global), por um caminho muito mais caro — o importador do Filament não é superfície desta feature |
| `assertNotified()` na recusa | asserção de apoio; nenhuma cláusula do requisito afirma sobre notificação, e a recusa de validação do Filament não notifica |
| teto por campo diferente por campo | **fora de escopo declarado** no `00-requisito.md` |
| dimensões da imagem (largura/altura) | **fora de escopo declarado** |
| `min:1` para o arquivo de 0 byte | valor chutado — virou pergunta, não cenário |

---

## Sem CT-B

**Nenhum cenário de navegador, e o gate não passa por pouco — não passa por nada.**

O gate do `05` exige que a asserção dependa de **JavaScript executado, console, acessibilidade, cor
ou layout**. Percorrendo as seis cláusulas:

| Cláusula | O que ela afirma | Onde isso é observável |
|---|---|---|
| RQ-01, RQ-02 | um arquivo é aceito ou recusado por tamanho | regra de validação do servidor → componente Livewire |
| RQ-03, RQ-04 | um arquivo é aceito ou recusado por formato | idem |
| RQ-05 | o número vive numa chave de config | leitura de config → sem UI |
| RQ-06 | está escrito em dois arquivos de texto | leitura de arquivo → sem UI |

Nenhuma cláusula afirma sobre comportamento de cliente. O `maxFileSize` que o FilePond recebe é
**conveniência de quem escolhe o arquivo**, não a barreira: um cliente pode ser contornado, e é a
regra do servidor que RQ-02 exige. Um CT-B ali provaria o JavaScript de um pacote de terceiro, não a
regra do kit — e provaria **menos**, porque o navegador não distingue "o cliente filtrou" de "o
servidor recusou".

**A regressão de navegador que a feature precisa já existe e não é dela**: as telas tocadas estão em
`telasDoKit()` e passam pelo smoke de `tests/Browser/TelasDoKitTest.php`
(`/admin/configuracoes-do-kit`, `/admin/organizacoes/create`) — é ele que pega o campo de upload que
parou de renderizar. Rodar `composer test:browser` continua na `## Verificação Final` do PRD por esse
motivo, e não porque exista CT-B desta feature.

**Gate de tela de escrita**: as três rotas de escrita têm cenário de **gravação bem-sucedida por
componente** nesta wiki — CT-03, CT-04 e CT-14 (`/admin/configuracoes-do-kit`), CT-05 e CT-21
(`/admin/organizacoes`, criação **e** edição) e CT-17 (`/app/{tenant}/projetos`). Nenhuma é coberta
só por visita, e nenhuma é coberta só por recusa: a segunda metade foi achado da revisão adversarial
(A-01, A-16) e é a que faltava na primeira versão deste arquivo.

---

## Revisão Adversarial

> Obrigatória: A1 é perfil `completo`. Delegada a sub-agente que não derivou os cenários, com
> entrada restrita ao `00-requisito.md` e a este arquivo — sem o PRD, sem os ADRs, sem o código e
> sem o raciocínio de quem derivou.

### Rodada 1 — 5 implementações defeituosas que passavam pelo conjunto inteiro

A revisão não trouxe sugestões de estilo: trouxe cinco implementações que ficariam **verdes nos 21
cenários** e deixariam o defeito passar. Todas fechadas.

| # | Implementação que passava | Regra | Técnica que faltava | Fechamento |
|---|---|---|---|---|
| IMPL-1 | `TenantForm` com `->maxSize(1024)` cravado — logo de 5 MB recusada, tudo verde | R3, R4 | BVA do lado **aceito** por campo; o campo como 2ª dimensão | CT-06 ganhou a coluna `campo` (5 linhas); CT-04/CT-05 ganharam a linha `10240` por campo; CT-14 ganhou `logo` e `arte_do_login` |
| IMPL-2 | recusa de SVG por **nome** (`str_ends_with(..., '.svg')`) — o renomeado entra | R6, R8 | rastreio de efeito no **código do kit** para o caso renomeado | **CT-22**, com `TemporaryUploadedFile` sem meta (MIME por conteúdo dentro do teste) |
| IMPL-3 | teto global gravado **literal** (`max:10240`) em vez de derivado da chave | R5 | rastreio no consumidor com **config injetada** | CT-08 virou `Esquema` com a linha `1500` |
| IMPL-4 | regra do `anexos` inspecionando só `$arquivos[0]` do campo `->multiple()` | R8 | partição por **posição** no array | CT-16 virou `Esquema` com 3 posições |
| IMPL-5 | `uploads.ini`/`nginx.conf` em 10M — instrução do README correta e inútil | R9 | asserção **cruzada** config × infra | **CT-23** |

### Rodada 1 — oráculos fracos apontados e reescritos

16 achados (A-01…A-16). Os que mudaram o oráculo, não só a prosa:

| Achado | Cenário | O que estava fraco | Reescrito para |
|---|---|---|---|
| A-01 | CT-06 | sustentava R4 inteira num campo só | coluna `campo`, 5 linhas, duas suítes |
| A-02 | CT-04 | `nada é gravado` **sem valor anterior arranjado** — verde em campo vazio, e verde numa implementação que **apaga** a logo ao recusar | `Dado` grava `kit/anterior.png`; `Então` compara caminho e conta arquivos no disco |
| A-03 | CT-05 | `a operação é recusada` sem regra — recusa por motivo alheio passava | erro atribuído à regra `max` |
| A-04 | CT-07 | proibia `10240` e liberava `1024`, `5120`, `10_240` | `->maxSize(` seguido de dígito, qualquer número |
| A-05 | CT-08 | 10240 é default **e** literal plausível — indistinguíveis de fábrica | linha com config injetada em 1500 |
| A-06 | CT-02 | `10240 / 1024 é 10` era tautologia | linha removida |
| A-07 | CT-12, CT-13 | **exercitam vendor**, e o conjunto lhes creditava M28/M35 | crédito removido; M28/M35 passaram a CT-22 |
| A-08 | CT-11 | sem o não-efeito no disco, apesar de R6 prometer "antes de gravar" | linha do disco acrescentada |
| A-09 | CT-16 | um SVG, sempre na primeira posição de campo múltiplo | 3 posições, e "nenhuma mídia" nas três |
| A-10 | CT-17 | 12 KB não tocava fronteira; nada sobre o que ficou gravado | um arquivo **no teto**; nomes gravados asseridos |
| A-11 | CT-14 | RQ-04 com cenário positivo num campo só | `logo` e `arte_do_login` acrescentados |
| A-12 | CT-14 | "aponta para arquivo no disco" passava com valor antigo | comparação com o caminho arranjado |
| A-13 | CT-15 | sem regra nomeada, M32 sobrevivia por porta lateral | regra `image` nomeada |
| A-14 | CT-20 | numeral solto, não ancorado à chave | linha `KIT_UPLOAD_MAXIMO_MB={n}` |
| A-15 | RQ-06 | nada provava que aumentar o valor aumenta o teto | **CT-23** |
| A-16 | CT-21 | duas recusas e **nenhuma** edição válida | linha `nova.png, 10240 KB → aceito` |

Também acatado: `## Fronteira com o Plano` classificava os tetos de infra como "detalhe", e era essa
linha que abria IMPL-5 — corrigida para distinguir o **valor** (não é oráculo) da **relação** com a
chave (é oráculo, por RQ-01).

Não acatados, com motivo: os dois `Quando` com estímulo duplo (CT-13, CT-17). CT-13 foi reescrito
para uma consulta só com duas perguntas; CT-17 mantém os dois arquivos porque o **campo é
`->multiple()`** e enviar dois é a ação única que a regra existe para cobrir — separar em dois
cenários mediria outra coisa. As duas lacunas já declaradas (M17, M44) permanecem declaradas: a
revisão confirmou que nenhuma das cinco implementações depende delas.

### Rodada 2 — a superfície nova, e ela não estava limpa

Executada porque o fechamento **criou cenário novo** (CT-22 e CT-23), e cenário novo introduz
superfície nova. **Encontrou achado estrutural** — 9 achados e 3 implementações que passavam pelos 23
cenários. O mais importante virou a lição desta wiki.

#### O achado que derrubou o próprio argumento do documento

**R2-01**: CT-22 nasceu marcado `componente (Tenancy)` e mandava a fixture sem meta pelo formulário.
Mas `Testable::setProperty()` desvia **todo** `UploadedFile` para o ciclo de upload, e
`TemporaryUploadedFile` **é** um `UploadedFile` — o meta seria reescrito, o MIME voltaria a vir do
nome, o arquivo seria aceito, e o cenário ficaria **vermelho por defeito da fixture**. É palavra por
palavra o argumento com que a `## A armadilha de camada` expulsou CT-12 da camada de componente,
aplicado contra o cenário que o documento acabara de escrever para fechar A-07.

A lição, e é a que vale registrar: **o mecanismo que você mediu para explicar um cenário precisa ser
reaplicado aos cenários que você escreve depois.** Medir uma vez e citar não basta — a rodada 1 me
deu o mecanismo, e eu escrevi o cenário novo como se ele não valesse ali.

#### Os nove achados e o que virou cada um

| Achado | Onde | O que estava errado | Fechamento |
|---|---|---|---|
| R2-01 | CT-22 | o arnês do componente destrói o arranjo que o cenário exige | cenário movido para a **camada da regra** (`getValidationRules()`), sem o ciclo de upload; a linha do não-efeito de mídia saiu, porque media outro caminho |
| R2-02 | CT-22 | o caso disfarçado cobria só o `anexos`; os 4 campos de imagem ficavam sem ele | virou `Esquema` com os **três mecanismos** (Closure, regra `image`, allow-list) |
| R2-03 | CT-23, CT-18 | a escada tem **três** degraus de infra e o cenário lia dois — `post_max_size` trava antes | linha nova em CT-23; âncora nova em CT-18 |
| R2-04 | CT-23 | só a relação, sem a metade de **presença** da diretiva | `Então a diretiva está declarada nele` |
| R2-05 | CT-03 | `o valor anterior` sem `Dado` que o arranje; prosa creditava asserção de disco ausente | `Dado` do valor anterior + linha do arquivo no disco |
| R2-06 | CT-11, CT-21 | `erro de tipo de arquivo` sem regra nomeada, contra o padrão de A-03/A-13 | regra `mimetypes` nomeada nas duas |
| R2-07 | CT-20 | âncora casava por **prefixo**: `=10` casa com `=10240`, o erro de unidade mais plausível | asserção de linha delimitada |
| R2-08 | CT-01 | o piso de 1 MB era oráculo **sem origem no requisito**, e o documento recusara `min:1` pelo mesmo critério | linhas marcadas `@premissa` + pergunta nova ao `00` |
| R2-09 | CT-13 | M46 creditado a um cenário que não o mata (asserta o Flysystem, não a escolha do Livewire) | M46 movido para CT-22 |

Cosméticos acatados: CT-04 contava arquivos no disco e reprovaria uma substituição legítima (virou
"o arquivo apontado existe"); CT-09 prometia no `Então` a **camada** da recusa, que nenhuma asserção
observa (frase removida). Cosmético **não** acatado: CT-08 asserta `required`/`file` além do `max:`,
que é composição do vendor e pode quebrar num upgrade do Livewire sem defeito do kit — mantido de
propósito, porque é o array inteiro que mata M20, e um upgrade que mude essa composição **deve**
aparecer como vermelho para alguém decidir.

#### Resolução estrutural, e por que não houve rodada 3

O teto da skill é 2 rodadas, e a instrução para o caso de a rodada 2 ainda trazer achado estrutural é
explícita: *o problema não é o conjunto — é a regra, que provavelmente deveria ser duas. Registrar e
escalar.* Foi o que se fez, e as duas separações estão em
`### Por que R6 virou duas regras, e R11 nasceu`:

- **R6 → R6 + R10**: recusar SVG **declarado** e recusar SVG **disfarçado** têm mecanismos e camadas
  diferentes. Era a fusão delas que produziu A-07 na rodada 1 e R2-02 na rodada 2 — o mesmo defeito
  aparecendo duas vezes por caminhos diferentes é a assinatura de regra mal separada.
- **R9 → R9 + R11**: documentar a escada e a escada **estar correta** são regras diferentes. Era a
  fusão delas que produziu IMPL-5 na rodada 1 e R2-03 na rodada 2.

**Escalado ao dono do kit** (nenhuma se resolve nesta wiki): o piso de 1 MB para env inválida (R2-08)
mexe em `NumeroDoEnv`, infra compartilhada por cinco chaves; e o sacrifício de `.ico`/`.tiff`
contraria a letra de RQ-04. As duas em `## Perguntas para o 00-requisito.md`.

---

## Comandos

```bash
# os cenários desta feature
php artisan test --testsuite=Kit,Tenancy --compact --filter=UploadLimite

# a regressão que a feature obriga (o PRD fixa a base em 1016)
php artisan test --testsuite=Unit,Feature,Kit,Tenancy

# navegador: não há CT-B, mas as telas tocadas estão no smoke
composer test:browser

# fechamento do ciclo, depois de implementar
vendor/bin/pest tests/Kit/UploadLimiteETiposTest.php --mutate --path=app/Support
vendor/bin/pest tests/Kit/UploadLimiteETiposTest.php --mutate --path=app/Filament/Admin/Pages
vendor/bin/pest tests/Tenancy/UploadLimiteETiposTenancyTest.php --mutate --path=app/Filament/App
```

⚠️ **`--path=` e não `--class=`**, e escopado: `--class='App\Support\...'` pode não casar, e mutar o
projeto inteiro devolve ruído. Se algum arquivo de teste declarar `covers()`, a classe vizinha que
se quer medir precisa entrar em `covers()`/`mutates()` — senão os mutantes dela são reportados
`uncovered` e o score vai a **0%** mesmo com o código sendo executado.

⚠️ A regra do `anexos` vive em `app/Filament/App/**`, e é a única lógica **escrita à mão** desta
feature (as outras barreiras são declarativas). É onde o mutation score tem algo a dizer — e é onde
CT-16 e CT-22 são os únicos matadores.

⚠️ `pest --mutate` é **cego à omissão**: se uma cláusula nunca virou código, não há linha para mutar
e o score não cai. Quem responde por omissão aqui é a cobertura `RQ` → regra do `## Mapa de Regras`
e o gate de mutantes de especificação, que nasceram do requisito.

✅ `pestphp/pest-plugin-mutate` está declarado **direto** no `composer.json` (`^5.0`, linha 93) —
verificado, e não assumido. O comando não depende de acidente da árvore de dependências.
