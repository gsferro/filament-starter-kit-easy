# Decisões Arquiteturais — Limite e tipos de upload

## ADR-01: Uma chave, em MB no `.env` e em KB na config

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-05 manda o teto viver na config do kit e RQ-06 manda que seja fácil mudar. As duas pontas
querem unidades diferentes: uma pessoa escreve "10" pensando em megabytes; o `->maxSize()` do
Filament e o `max:` do Livewire recebem **kilobytes**
(`vendor/filament/forms/src/Components/BaseFileUpload.php:413-421` monta `max:{$size}`, e
`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:2822` divide
`getSize()` por 1024 — a doc do Filament 5 diz o mesmo com palavras: *"restrict the size of
uploaded files in kilobytes"*).

### Decisão

Uma chave só: `kit.uploads.maximo_em_kb`, alimentada por `KIT_UPLOAD_MAXIMO_MB` (em MB) e
multiplicada por 1024 dentro do `config/kit.php`. O nome da chave carrega a unidade, e a
conversão vive num lugar.

```php
'uploads' => [
    'maximo_em_kb' => NumeroDoEnv::positivo(env('KIT_UPLOAD_MAXIMO_MB'), 10) * 1024,
],
```

### Alternativas Consideradas

1. **Duas chaves, `maximo_em_mb` e `maximo_em_kb`** — descartada por `.ai/rules/config.md`: "uma
   pergunta, uma dona". Duas chaves para o mesmo número convidam ao caso em que uma é editada e a
   outra não, e o consumidor errado lê a que ninguém mudou — sem erro nenhum.
2. **Env em KB (`KIT_UPLOAD_MAXIMO_KB=10240`)** — consistente e sem multiplicação, mas RQ-06 pede
   "de forma facil", e `10240` é o tipo de número que a pessoa erra por um fator de 1024.
3. **`(int) env('KIT_UPLOAD_MAXIMO_MB', 10)`** — descartada por `.ai/rules/config.md`: o segundo
   argumento do `env()` só cobre chave **ausente**. Com `KIT_UPLOAD_MAXIMO_MB=` (o que sobra
   quando alguém apaga o número), `env()` devolve string vazia, `(int) ''` é 0, e
   `->maxSize(0)` recusa **todo** arquivo — a feature desligada por acidente, que é exatamente o
   caso de `NumeroDoEnv::positivo()`.
4. **Campo na tela `/admin/configuracoes-do-kit`** — descartada: o requisito diz "deve ficar na
   config no kit ... ao instalar o nosso kit". É decisão de instalação, não de operação diária, e
   um campo na tela permitiria a um admin baixar o teto para 1 KB sem passar por deploy.

### Consequências

- **Positivas**: um número, um lugar, uma unidade explícita no nome. O piso do `positivo()`
  garante que nenhum valor de `.env` desligue os uploads.
- **Negativas**: o nome da env (MB) e o da config (KB) não coincidem — quem lê só um dos dois
  pode se confundir. Mitigado pelo comentário do bloco e pelos READMEs.
- **Riscos**: `positivo()` manda texto e negativo para **1 MB**, não para o default. É o
  comportamento documentado da classe (o pior caso é um teto curto e visível, que faz alguém
  corrigir o `.env`), e não uma surpresa desta feature.

### Adendo da implementação: `App\Support\TetoDeUpload`

A chave é uma, mas é lida em **duas unidades** (KB para `->maxSize()` e para a regra do Livewire,
MB para o texto que a pessoa lê) e em **três arquivos**. Escrever
`intdiv((int) config('kit.uploads.maximo_em_kb'), 1024)` cinco vezes é como um teto acaba
divergindo do texto que o anuncia — e é o mesmo defeito de fronteira por cópia que
`.ai/rules/specs.md` mede.

Então a pergunta ganhou uma dona, com `emKb()` e `emMb()`. É o padrão de classe pequena que o kit
já usa (`NumeroDoEnv`, `BooleanoDoEnv`, `CorPrimaria`, `RegistroAberto`, `IdentidadeDoKit`) e o
remédio que a própria rule de config prescreve: *"a correção foi delegar para a classe dona da
pergunta"*. Ela também fica testável em unidade, sem tela.

Recusado no caminho: uma terceira chave `kit.uploads.maximo_em_mb`. Seriam duas donas do mesmo
número na config, que é o que ADR-01 acabou de rejeitar — a conversão pertence a código, não a
uma segunda linha de config.

### Também aceito aqui: o teto de 1 MB da logo de organização foi AFROUXADO

`TenantForm` tinha `->maxSize(1024)`, sem nenhum comentário justificando o número, entre
encadeamentos que explicavam tipo, disco e visibilidade. Ele passa ao teto da config (10 MB de
fábrica).

Não é neutro: um arquivo entre 1 e 10 MB que era recusado passa a ser aceito. A decisão vem de
RQ-01 ("pode subir arquivos de ate 10mb" — um campo travado em 1 MB não permite) e de RQ-05
("o tamanho maximo de upload", singular, na config). Quem quiser a logo mais apertada volta um
número naquele campo — e reintroduz, deliberadamente, uma segunda dona da pergunta.

### Referências

- `.ai/rules/config.md`, `app/Support/NumeroDoEnv.php`, `app/Support/TetoDeUpload.php`
- `vendor/filament/forms/src/Components/BaseFileUpload.php:413-421`
- `vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:2822`

---

## ADR-02: SVG é o único formato de imagem recusado, por lista de extensões validada no conteúdo

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Nos três campos de imagem da tela de configurações, `->image()` do Filament gera
`acceptedFileTypes(['image/*'])` (`vendor/filament/forms/src/Components/FileUpload.php:130-134`),
que vira `mimetypes:image/*` (`BaseFileUpload.php:258-268`) — e `image/svg+xml` casa com esse
curinga (`ValidatesAttributes.php:1781-1783`). É por isso que SVG passa hoje.

RQ-03 quer SVG fora; RQ-04 quer "qualquer outro tipo de image" dentro. As duas cláusulas
puxam para lados opostos de qualquer allow-list.

### Decisão

Manter `->image()` (que é o `accept="image/*"` do seletor do sistema, conveniência de quem
escolhe o arquivo) e acrescentar `->rule('image')` — a regra do **Laravel**, que é outra coisa e
recusa SVG por padrão:

```php
$mimes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'heic', 'heif'];

if (is_array($parameters) && in_array('allow_svg', $parameters)) {
    $mimes[] = 'svg';
}
```
(`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:1531-1540`)

A doc do Laravel 13 é explícita sobre o motivo: *"By default, the image rule does not allow SVG
files due to the possibility of XSS vulnerabilities."*

### Alternativas Consideradas

1. **Allow-list explícita de MIME, como no `TenantForm`** —
   `acceptedFileTypes(['image/png','image/jpeg','image/webp'])`. É o padrão que já existe na casa.
   Descartada por RQ-04: três tipos não são "qualquer outro tipo de image".
2. **`->rule('image')` puro** — foi a primeira decisão, e o quality gate a derrubou: ela recusa
   `.ico`, que o kit embarca. Ver a correção acima.
3. **`acceptedFileTypes()` com a lista completa** — funciona, mas `acceptedFileTypes()` também
   reescreve o `accept` do seletor de arquivo, e aí o seletor deixaria de oferecer imagem que a
   lista não cita. Como `mimes:` só valida, o `->image()` continua abrindo o seletor para
   `image/*` e a recusa é explicada por mensagem — o que é preferível a um seletor que esconde
   arquivos sem dizer por quê.
3. **Regra de recusa de `image/svg+xml` (um `Closure`), mantendo `image/*`** — é literalmente o
   que a cláusula diz, e cobre formato de imagem que a regra `image` não conhece. Descartada nos
   campos de imagem por ser mais código para menos garantia: qualquer coisa que o finfo não
   classifique como `image/svg+xml` (um SVG servido como `text/xml`, por exemplo) passaria. **É a
   forma escolhida no `anexos`** — ver ADR-04 — porque ali a allow-list não é opção.
4. **Sanitizar o SVG no upload** (`spatie/image`, DOMPurify, reescrever o XML) — fora de escopo
   declarado no `00-requisito.md` e dependência nova, que o requisito não pede.

### ⚠️ Correção do quality gate: a justificativa do `.ico` era FALSA, e a regra mudou

A primeira versão desta ADR aceitava perder `.ico` e `.tiff` com esta frase:

> *"Trade-off aceito e escrito no `helperText` do campo: favicon moderno é PNG, e é o que o kit já
> usa."*

**O kit serve `public/favicon.ico`.** A frase foi escrita a partir do que se esperava encontrar, e
não de um `ls` — exatamente o padrão que `.ai/rules/specs.md` manda desconfiar, e desta vez a
conclusão também estava errada, não só o motivo.

Medido, com um ICO real (cabeçalho de ícone + PNG embutido, que é a forma que os geradores de
favicon entregam hoje):

```
mime por conteúdo   → image/vnd.microsoft.icon
guessExtension()    → ico
regra `image`       → RECUSA
regra `mimes:…,ico` → aceita
```

Ou seja: a tela de favicon do kit recusava o formato de favicon que o próprio kit embarca, e
RQ-04 ("o restante pode ser qualquer tipo de image") ficava violada num caso concreto e provável —
`.ico` é o que a maioria dos kits de marca entrega.

**Decisão revisada**: a barreira passa a ser `mimes:` com uma lista escrita —
`ConfiguracoesDoKit::FORMATOS_DE_IMAGEM` = os nove da regra `image` **mais `ico` e `tiff`**.

O mecanismo é o mesmo: `validateMimes()` compara `guessExtension()`
(`ValidatesAttributes.php:1746-1761`), que vem do MIME derivado do **conteúdo** — então a
resistência a arquivo renomeado, que é o argumento de ADR-03, **não muda** (medido: o SVG chamado
`logo.png` continua recusado pela lista).

O que se perde é a herança automática: um formato novo que o Laravel adicionar à regra `image` não
aparece aqui sozinho. É um preço menor que recusar o formato que o kit distribui, e o caso de
formatos aceitos é onde a defasagem aparece.

### Consequências

- **Positivas**: RQ-03 e RQ-04 atendidas ao mesmo tempo — SVG é o único formato de imagem fora, e
  ele é o único que carrega script. `.ico` e `.tiff` entram, incluindo o `.ico` que o kit embarca.
- **Negativas**: onze extensões escritas à mão, que envelhecem. Mitigado por serem uma constante
  única, com o motivo de cada adição no docblock, e por haver caso de teste por partição.
- **Negativas**: o kit passa a ter duas listas de formato de imagem — esta e a do `TenantForm`
  (`png`, `jpeg`, `webp`). Não é duplicação de decisão (lá a lista é estreita de propósito, com
  justificativa própria no arquivo), mas exige o comentário nos dois lugares para não parecer
  descuido.
- **Negativas**: o kit passa a ter **duas** formas de recusar SVG — a allow-list do `TenantForm` e
  a regra `image` aqui. Não é duplicação de decisão (são requisitos diferentes: lá a lista é
  fechada de propósito, aqui é aberta por cláusula), mas é uma inconsistência visível que
  precisa do comentário nos dois lugares para não parecer descuido.
- **Riscos**: `->image()` deixa o seletor de arquivo do sistema oferecer `.svg`, e a recusa só
  aparece depois da escolha. É pior que filtrar no seletor, e é o preço de RQ-04. Mitigado pela
  `validationMessages(['image' => 'SVG não é aceito. …'])`, que diz o que fazer.

### Referências

- `vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:1531-1540`
- `vendor/filament/forms/src/Components/FileUpload.php:130-134`
- `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:119-133` (a decisão anterior)

---

## ADR-03: A barreira é MIME, e neste caminho o MIME vem do CONTEÚDO — mas o teste não prova isso

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

A pergunta que decide se esta feature entrega segurança ou teatro: **um `.svg` renomeado para
`.png` passa?**

`mimetypes` e `mimes` (que é o que a regra `image` usa por baixo) não leem o MIME declarado pelo
cliente — leem `$value->getMimeType()`. O que esse método devolve depende da **classe** do
arquivo, e é aí que produção e teste divergem.

### Decisão

Aceitar a barreira de MIME como suficiente, **e declarar exatamente até onde ela vai**, medido:

| Caminho | Origem do MIME | `.svg` renomeado para `.png` |
|---|---|---|
| **Produção** — `Livewire\...\TemporaryUploadedFile::getMimeType()` (`vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:63-90`) | `$this->storage->mimeType($this->path)` → detector do Flysystem, que lê o **conteúdo** | **recusado** |
| **Teste** — `Illuminate\Http\Testing\File::getMimeType()` (`vendor/laravel/framework/src/Illuminate/Http/Testing/File.php:132-135`): `return $this->mimeTypeToReport ?: MimeType::from($this->name)` | o **nome** do arquivo | **aceito** |

Verificado, não suposto — `League\MimeTypeDetection\FinfoMimeTypeDetector` sobre um arquivo com
bytes de SVG e nome `.png`:

```
detectMimeTypeFromFile() → 'image/svg+xml'
detectMimeTypeFromPath() → 'image/png'
```

e `finfo(FILEINFO_MIME_TYPE)` devolve `image/svg+xml` para os dois formatos de SVG testados (com
e sem declaração XML), enquanto `getimagesize()` devolve `false`.

Consequência para os testes: o caso "SVG renomeado" **não pode** ser provado por componente
Livewire, porque o arquivo falso do Laravel deriva o MIME do nome. Ele é provado uma camada
abaixo, com um `Illuminate\Http\UploadedFile` real apontando para um arquivo temporário com bytes
de SVG e nome `.png`, validado contra a mesma regra `image` — que é o caminho de detecção por
conteúdo que a produção usa. Ver CT-05 no `04-casos-de-teste.md`.

### Alternativas Consideradas

1. **Prometer que renomear não passa, sem medir** — é o defeito que `.ai/rules/specs.md`
   documenta: escrever a explicação a partir do que se espera encontrar. Três afirmações erradas
   numa única feature vieram daí.
2. **Validar conteúdo com `getimagesize()`** — funciona (`false` para SVG) e não custa
   dependência, mas é redundante: o MIME já vem do conteúdo neste caminho. Acrescentaria uma
   segunda barreira que recusa também formatos que o GD não conhece (avif em PHP sem suporte),
   violando RQ-04 em silêncio.
3. **`spatie/image` ou `intervention/image` para abrir e reescrever a imagem** — já instalados
   (`spatie/image`), então não seria dependência nova. Descartado: reescrever imagem é a
   sanitização declarada fora de escopo, e abrir arquivo do usuário para descobrir se é imagem é
   trocar uma superfície de ataque por outra.
4. **Escrever o teste de renomeado com `UploadedFile::fake()->createWithContent('logo.png', $svg)`
   e afirmar que ele é recusado** — passaria a impressão de cobrir e **falharia**: o `getMimeType()`
   do arquivo falso devolveria `image/png`. Um teste que reprova por defeito da própria fixture
   viraria pressão para relaxar a asserção.

### Consequências

- **Positivas**: a barreira resiste a renomear em produção, e isso está medido com `file:line`.
  Nenhuma dependência nova, nenhum byte do arquivo do usuário aberto pela aplicação.
- **Negativas**: a suíte não prova o caminho de produção do caso renomeado com o componente real
  — prova a regra, na camada onde a detecção por conteúdo acontece. É uma prova mais fraca que um
  E2E com arquivo de verdade, e está declarada como tal.
- **Riscos**: se o Livewire trocar o detector de MIME do disco temporário por um baseado em
  extensão, a barreira cai e **nenhum teste desta feature reprova**. Mitigação: CT-06 asserta
  diretamente que o detector do Flysystem classifica bytes de SVG como `image/svg+xml`
  independentemente da extensão — é o teste que morre se a premissa morrer.

### Referências

- `vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:63-90`
- `vendor/laravel/framework/src/Illuminate/Http/Testing/File.php:132-135`
- `vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:1531-1540`
- `.ai/rules/specs.md`

---

## ADR-04: O teto do Livewire passa a nascer da chave do kit; PHP e nginx ficam documentados

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Um upload atravessa quatro limites e **o menor manda**: nginx (`client_max_body_size 60M`,
`docker/nginx/nginx.conf:11`), PHP (`upload_max_filesize=52M`, `post_max_size=60M`,
`docker/php/uploads.ini:2-3`), Livewire no upload temporário (default `max:12288` — 12 MB em KB,
`vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:116`) e o
`->maxSize()` do campo.

Quando discordam, o erro muda de **qualidade**, não só de valor. O limite do campo recusa com
mensagem em português no formulário. O do Livewire recusa antes: 422 no XHR do upload temporário,
erro genérico no FilePond. O do PHP e do nginx cortam o corpo do POST — falha de rede no console.

O caso concreto que isso evita: alguém sobe `KIT_UPLOAD_MAXIMO_MB=50` seguindo o README, e todo
arquivo entre 12 e 50 MB falha com um erro que não menciona tamanho nenhum.

### Decisão

Alinhar a camada imediatamente acima da tela à chave do kit, com uma linha em
`KitServiceProvider::configureDefaults()`:

```php
config()->set('livewire.temporary_file_upload.rules', [
    'required', 'file', 'max:'.config('kit.uploads.maximo_em_kb'),
]);
```

PHP e nginx **não** são tocados por código — são infraestrutura, ficam nos READMEs com os dois
paths e o aviso de que fora do Docker do kit o PHP costuma vir com `upload_max_filesize=2M`, que
passaria a ser o teto real.

### Alternativas Consideradas

1. **Publicar `config/livewire.php` (`php artisan livewire:publish --config`)** — é o que a doc do
   Filament sugere. Descartada: trariam ~130 linhas de configuração alheia ao repositório para
   mudar um número, e o kit passaria a ter de acompanhar mudanças do arquivo publicado a cada
   upgrade do Livewire.
2. **Ler `config('kit.uploads.maximo_em_kb')` de dentro de um `config/livewire.php`** — funciona
   por acidente de ordem alfabética no carregamento dos arquivos de config (`kit` antes de
   `livewire`), e é exatamente o tipo de esperteza que quebra em silêncio quando alguém renomeia
   um arquivo.
3. **Só documentar, sem alinhar** — a alternativa mais barata, e é o que o requisito exige no
   mínimo. Descartada porque uma linha remove a classe inteira de erro obscuro, e RQ-06 pede
   "aumentar ou diminuir de forma facil": fácil que só funciona até 12 MB não é fácil.
4. **Subir o teto do Livewire para um número alto fixo (ex.: 60 MB) e deixar só o campo validar** —
   descartada: o upload temporário do Livewire vale para **toda** a aplicação, inclusive
   superfícies sem `maxSize()`. Afrouxar o global para apertar no específico é a direção errada.

### Correção da implementação: a folga de 1 MB, e por que igualar os tetos era ERRADO

A primeira versão desta ADR alinhava o teto do Livewire **ao mesmo número** do campo. Medido no
worktree, isso quebra a própria consequência que a ADR promete:

```
teto do campo = 10240 KB, teto do Livewire = 10240 KB
favicon de 20 MB  →  erros=[]   gravado=NULL

teto do campo = 10240 KB, teto do Livewire = 102400 KB
favicon de 20 MB  →  erros={"data.favicon":["O arquivo passa de 10 MB."]}   gravado=NULL
```

Com os tetos iguais, o upload temporário recusa **antes** de o formulário existir
(`FileUploadController::validateAndStore()` aplica `FileUploadConfiguration::rules()` antes de o
arquivo virar propriedade do componente), e o `validationMessages(['max' => …])` do campo fica
**inalcançável**: no navegador é 422 no XHR e erro genérico do FilePond; num teste de componente é
"nenhum erro e nada gravado", que é indistinguível de aceito e ignorado.

Ou seja: o alinhamento ingênuo trocava um erro obscuro por outro, e ainda apagava a única camada
que sabia falar em português.

A correção é `TetoDeUpload::emKbComFolgaDoLivewire()` — o teto do campo **mais 1 MB**:

- arquivo pouco acima do teto (o caso comum, alguém que mandou 11 MB) → recusado pelo **campo**,
  com mensagem no formulário;
- arquivo muito acima → cortado pelo Livewire, e é o que se quer: ninguém deve transferir meio
  gigabyte para o servidor dizer que era grande.

A folga não afrouxa nada que o requisito prometa: o teto que vale para o que **entra** continua
sendo o do campo, e nenhum arquivo acima dele é gravado. O que a folga compra é a mensagem.

### Consequências

- **Positivas**: um número controla as duas camadas, com a mensagem legível preservada para o caso
  comum. Nenhum arquivo de config alheio no repositório.
- **Negativas**: o teto global do upload temporário **cai** de 12 MB para 11 MB de fábrica (10 + 1
  de folga). Isso atinge qualquer upload que passe pelo Livewire e não tenha `maxSize()` próprio —
  hoje, o CSV do `ImportAction` do Filament. Um CSV entre 11 e 12 MB que passava, passa a ser
  recusado. Aceito: 10 MB é o teto que o requisito define para "todo upload", e o CSV de importação
  é upload.
- **Negativas**: a folga é um número escolhido (1 MB), não derivado. Grande demais e o Livewire
  para de proteger; pequena demais e a mensagem do campo volta a ser inalcançável para arquivos
  pouco acima do teto. 1 MB cobre o caso comum e mantém o corte de fora em ordem de grandeza.
- **Riscos**: `config()->set()` em `boot()` é sobrescrito por qualquer coisa que rode depois e
  mexa na mesma chave. Nada no kit mexe (`grep -rn "temporary_file_upload" app/ config/` só acha
  esta linha), e um teste asserta o valor efetivo no processo.

### Referências

- `vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:116`
- `docker/php/uploads.ini`, `docker/nginx/nginx.conf:11`
- doc do Filament 5 (via `search-docs`), *Uploading large files*

---

## ADR-05: Nenhum channel de log e nenhum log novo

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

A `feature-wiki` exige um channel por feature e log em toda etapa de execução. Esta feature não
tem etapa de execução: ela acrescenta regra de validação declarativa a cinco campos de formulário
e uma chave de config.

### Decisão

Nenhum channel novo em `config/logging.php`, nenhuma chamada a `Log::` no diff.

### Alternativas Consideradas

1. **Channel `upload-limite` com `warning` a cada recusa** — descartada. Recusa de upload é
   resposta ao usuário, não anomalia: quem escolheu o arquivo errado vê a mensagem no campo e
   troca o arquivo. Um canal que grita no caminho normal é canal que ninguém lê — a mesma razão
   pela qual o `configureSettingsDoKit()` sai em silêncio quando a tabela não existe.
2. **Channel só para o SVG recusado**, como trilha de segurança — mais defensável, porque é
   tentativa de subir formato executável. Descartada por escopo: o requisito não pede trilha, a
   tela já é auditada por `filament-auditing` para o que **entra**, e um channel que escreve uma
   linha por ano é um arquivo que ninguém rotaciona.

### Consequências

- **Positivas**: `config/logging.php` não cresce; nada a limpar na pós-implementação.
- **Negativas**: não há registro de tentativa de upload de SVG. Se isso virar necessidade, o
  lugar é o `filament-auditing` ou um listener de falha de validação, não um channel por feature.

### Referências

- `app/Providers/KitServiceProvider.php` (`configureSettingsDoKit`, o precedente do silêncio
  deliberado)

---

## ADR-06: A conversão de unidade vive numa classe, não repetida em cada consumidor

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Registrada a pedido da `feature-test-design`, que apontou a divergência: o PRD prescreve
`(int) config('kit.uploads.maximo_em_kb')` nos cinco campos, a implementação usa
`App\Support\TetoDeUpload::emKb()`, e os dois READMEs já documentavam a classe — a decisão tinha
sido escrita para quem usa o kit e não para a wiki. Discordância entre wiki e código em texto é
exatamente o achado de especificação que o `feature-quality-gate` procura, então ela vira ADR em
vez de ficar só como adendo.

### Decisão

`App\Support\TetoDeUpload`, com três métodos: `emKb()` (o que Filament e Livewire recebem),
`emMb()` (o que a pessoa lê) e `emKbComFolgaDoLivewire()` (ver ADR-04).

### Alternativas Consideradas

1. **`config()` direto nos cinco campos, como o PRD escreveu** — funciona, e RQ-05 fica atendida.
   Descartada quando a conta apareceu: `intdiv((int) config('kit.uploads.maximo_em_kb'), 1024)`
   repetido em cinco encadeamentos, em três arquivos, é conversão de unidade por cópia — a forma
   como um teto acaba divergindo do texto que o anuncia.
2. **Uma segunda chave de config em MB** — recusada em ADR-01: duas donas do mesmo número.
3. **Helper global (`function tetoDeUpload()`)** — o kit não tem helpers globais de aplicação, e
   uma classe é testável em unidade sem bootar tela.

### Consequências

- **Positivas**: um lugar para a conversão, um lugar para a folga do Livewire, e cada decisão com o
  docblock ao lado do código que a implementa. Segue o padrão de `NumeroDoEnv`, `BooleanoDoEnv`,
  `CorPrimaria`, `RegistroAberto` e `IdentidadeDoKit`.
- **Negativas**: uma classe a mais para quem lê o kit pela primeira vez. Mitigado por ela ter três
  métodos e nenhum estado.
- **Riscos**: nenhum de comportamento — se a classe sair e os campos voltarem a ler `config()`
  direto, nenhum caso de teste muda de sinal. É a definição de refactor neutro.

### Referências

- `app/Support/TetoDeUpload.php`
- `.ai/rules/config.md` ("uma pergunta, uma dona")
- `04-casos-de-teste.md` → `## Fronteira com o Plano`
