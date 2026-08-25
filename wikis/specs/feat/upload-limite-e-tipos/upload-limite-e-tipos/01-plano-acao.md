# Plano de Ação — Limite e tipos de upload

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/settings-do-kit/settings-do-kit/`
- **Motivo**: o quality gate daquela wiki (v0.19.0) registrou como Minor que os três campos de
  arquivo de `/admin/configuracoes-do-kit` não têm `maxSize()` e aceitam SVG. Esta wiki paga a
  dívida e, na varredura, achou o mesmo padrão em dois campos fora daquela tela.
- **Toca infra compartilhada?**: **sim** — `config/kit.php` (chave nova), `.env.example` e o
  default global de upload temporário do Livewire, em `KitServiceProvider::configureDefaults()`.
  A regressão é obrigatória: os CT/CT-B de `settings-do-kit` e os testes de `anexos-privados` e
  de organizações consomem os campos tocados.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | teto de 10 MB em todo upload | 1, 3, 4, 5 | default da chave nova |
| RQ-02 | acima do teto é recusado | 3, 4, 5 | `->maxSize()`, e o teto global do Livewire no passo 2 |
| RQ-03 | upload recusa SVG | 3, 4 | `mimes:FORMATOS_DE_IMAGEM` nos campos de imagem; regra de recusa no `anexos` |
| RQ-04 | qualquer outro tipo de imagem é aceito | 3 | `FORMATOS_DE_IMAGEM` cobre jpg/jpeg/png/gif/bmp/webp/avif/heic/heif/**ico**/tif/tiff |
| RQ-05 | o teto vive na config do kit | 1, 2 | e os dois números cravados (`1024` no `TenantForm`, `10 * 1024` no `ProjetoResource`) deixam de existir (passos 4 e 5) |
| RQ-06 | documentado para aumentar/diminuir com facilidade | 2, 6 | `.env.example`, comentário do `config/kit.php` e os dois READMEs |

## Objetivo

Fechar duas lacunas de fronteira nos cinco campos de upload do kit: **nenhum teto de tamanho** em
três deles (e teto cravado no código, sem justificativa, nos outros dois), e **SVG aceito** em
quatro deles. Fazer o teto nascer de **uma** chave de configuração documentada, para quem instala
o kit mudar o número num lugar só — inclusive o teto global do Livewire, que hoje é um 12 MB de
fábrica invisível e desalinhado.

## Contexto

Os três campos de `/admin/configuracoes-do-kit` (`logo`, `favicon`, `arte_do_login`) gravam no
disco `public` com `visibility('public')`, porque aparecem na tela de login, antes de haver
sessão. Arquivo servido pelo **mesmo origin** da aplicação e formato que carrega `<script>` é XSS
armazenado: abrir a URL do SVG executa o script com acesso ao cookie de sessão. Quem sobe é o
`admin`, que já tem acesso total — é escalada de insider, não porta anônima, e é por isso que o
gate classificou como Minor e não Blocker. Num starter kit, superfície nova não nasce com isso.

O `logo` da organização (`TenantForm`) já tinha a allow-list e o comentário explicando esta mesma
armadilha — o problema dele era o teto de **1 MB cravado no código**, sem nada justificando o
número. O `anexos` do Projeto tinha o teto cravado também (`10 * 1024`). Os dois são o número que
RQ-05 manda tirar do código.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

O helper privado `arquivo(string $nome, string $rotulo): FileUpload` (linha 501) monta os três
campos com `->image()->disk('public')->directory('kit')->visibility('public')`. Cada uma das três
chamadas (linhas 235, 238, 241) encadeia o seu próprio `->helperText()` **depois** — então texto
de ajuda definido dentro do helper seria sobrescrito. É o motivo de o passo 3 mudar a assinatura
em vez de acrescentar um `->helperText()` no helper.

`->image()` gera `acceptedFileTypes(['image/*'])` (`vendor/filament/forms/src/Components/FileUpload.php:130-134`),
que vira a regra `mimetypes:image/*` (`BaseFileUpload.php:258-268`) — e `image/svg+xml` casa com
ela. É a razão de o SVG passar hoje.

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`

`FileUpload::make('logo')` (linha 119) já tem `acceptedFileTypes(['image/png','image/jpeg','image/webp'])`
e um comentário longo explicando por que não usou `->image()` — SVG já era recusado ali. O que ele
tem de errado é o teto: `->maxSize(1024)` na linha 144, **1 MB**, sem nenhum comentário
justificando o número, entre encadeamentos que explicam tipo, disco e visibilidade.

> **Correção da revisão pós-escrita (step 5).** A primeira versão desta seção dizia "falta só
> `maxSize()`". Errado — ele existia, com 1 MB. Ver a nota no `00-requisito.md` e a ambiguidade
> "RQ-01 no campo `logo` da organização".

### `app/Filament/App/Resources/Projetos/ProjetoResource.php`

`SpatieMediaLibraryFileUpload::make('anexos')` (linha 126) tem `->maxSize(10 * 1024)` cravado e
`->helperText('Até 10 MB por arquivo. …')` — número repetido em dois lugares no mesmo bloco. Sem
`acceptedFileTypes()`, de propósito: é campo de documento.

### `config/kit.php`

O bloco `identidade` (linha 108) guarda os **caminhos** dos três arquivos enviados, sem `env()`.
A chave nova é vizinha natural dele. `NumeroDoEnv` já está importado (linha 4).

### `app/Providers/KitServiceProvider.php`

`configureDefaults()` (linha 74) é onde o kit ajusta default de framework (`Date::use`,
`DB::prohibitDestructiveCommands`, `Password::defaults`). É o lugar do alinhamento do Livewire.

## A escada de tetos, medida (não suposta)

Um upload atravessa quatro limites, e **o menor manda**. O que o kit tem hoje:

| Camada | Onde | Valor hoje | Unidade |
|---|---|---|---|
| nginx | `docker/nginx/nginx.conf:11` | `client_max_body_size 60M` | MB |
| PHP | `docker/php/uploads.ini:2-3` | `upload_max_filesize=52M`, `post_max_size=60M` | MB |
| Livewire (upload temporário) | default do pacote, `vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:116` | `max:12288` | **KB** (12 MB) |
| Filament (`->maxSize()`) | os cinco campos | ausente em 3, `1024` em 1, `10*1024` em 1 | **KB** |

**Quando discordam, o erro muda de qualidade.** O limite do Filament é validação de formulário:
recusa com mensagem no campo, em português, na tela. Os outros três recusam **antes**: o Livewire
devolve 422 no XHR do upload temporário e o FilePond mostra um erro genérico; o PHP e o nginx
cortam o corpo do POST e o navegador registra falha de rede. Teto de 10 MB na tela com
`upload_max_filesize=2M` no servidor (o **default do PHP**, fora do Docker do kit) produz erro
obscuro no console, não mensagem clara no campo — e a pessoa culpa o arquivo.

Por isso o passo 2 alinha o Livewire à chave do kit: a camada logo acima da tela passa a nunca
ser mais estreita que ela, e sobram só PHP e nginx, que são documentados.

## Autorização

Nada muda. A tela de configurações exige `View:ConfiguracoesDoKit`, o `TenantForm` exige o
resource de organizações e o `anexos` herda o escopo do Projeto. **Nenhuma policy, gate,
middleware ou guard é criado ou alterado.**

## Rotas

Nenhuma rota criada ou alterada.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ConfiguracoesDoKit` (aba Identidade) | Filament (SettingsPage) | `/admin/configuracoes-do-kit` | envia logo, favicon e arte do login | Sim (FilePond) |
| `TenantForm` | Filament (Resource) | `/admin/organizacoes/create` e `/{id}/edit` | envia a logo da organização | Sim (FilePond) |
| `ProjetoResource` (form) | Filament (Resource) | `/app/{tenant}/projetos/create` e `/{id}/edit` | anexa arquivos | Sim (FilePond) |

**Gate de CT-B**: nenhuma cláusula desta feature afirma sobre algo que só o navegador prova. O
que muda é **regra de validação** — recusa, mensagem de erro e gravação — e isso é teste de
componente Livewire, que roda em milissegundos. O limite do FilePond no cliente (`maxFileSize` do
JS) é conveniência, não a barreira: a barreira é a regra do servidor, e é ela que precisa de
prova. Ver `## Sem CT-B` no `04-casos-de-teste.md`.

**Gate de tela de escrita**: as três rotas de escrita já têm cenário de gravação por componente
(`tests/Kit/ConfiguracoesDoKitTelaTest.php` CT-08/CT-10, `tests/Kit/PaginasInfraTest.php`,
`tests/Tenancy/…`), e os CTs desta feature acrescentam gravação com arquivo no teto.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_UPLOAD_MAXIMO_MB` | `10` | Teto de tamanho, **em megabytes**, de todo upload do kit. Vazio, `0`, ausente → 10. Negativo ou texto → 1 MB (piso do `NumeroDoEnv::positivo()`). |

A chave é em **MB** porque é o que uma pessoa escreve; a config expõe **KB** porque é o que o
Filament e o Livewire recebem. A multiplicação vive num lugar só, no `config/kit.php`.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`settings-do-kit`** — os três campos ganham teto e recusa de SVG. CT-10 daquela wiki
  (`favicon.png` no disco público) precisa continuar verde: PNG está em `FORMATOS_DE_IMAGEM`.
- **`anexos-privados`** — o `anexos` perde o `10 * 1024` cravado e ganha o valor da config, que é
  **o mesmo número**. Nenhuma mudança de comportamento esperada, exceto SVG passar a ser recusado.
- **Organizações (tenancy)** — a logo troca o teto de 1 MB pelo da config (10 MB de fábrica): é
  **afrouxamento**, e um arquivo entre 1 e 10 MB que era recusado passa a ser aceito. A
  allow-list de lá já recusava SVG; nada muda em tipo.
- **Todo upload temporário do Livewire** — o default global cai de 12 MB para o valor da chave
  (10 MB de fábrica). Nenhum outro campo de upload existe no kit além dos cinco varridos, então o
  efeito é só fechar a folga entre as duas camadas. O importador do Filament (`ImportAction`) usa
  o upload temporário do Livewire: um CSV acima de 10 MB passaria a ser recusado — registrado
  como consequência negativa em ADR-04.

## Rollback

Sem migration e sem dado migrado. Reverter é `git revert` do commit. Para desligar só o teto sem
reverter código, `KIT_UPLOAD_MAXIMO_MB` com um número alto (mas ver ADR-04: acima de 52 MB o PHP
do Docker do kit passa a ser o limite real).

## Dependências

Nenhuma nova, composer ou npm. `NumeroDoEnv` já existe; a regra `image` é do Laravel 13.

## Riscos

- **Toda allow-list de formato envelhece, e a do framework decide por conta própria.** Risco
  **materializado**: a regra `image` do Laravel não tem `ico`, e o kit serve `public/favicon.ico` —
  a tela de favicon recusava o formato de favicon do próprio kit. Achado QA-01, corrigido com lista
  escrita. O risco residual é o inverso: formato novo que o Laravel adotar não entra aqui sozinho, e
  é o caso de partição por formato que expõe a defasagem.
- **Recusa por MIME não é inspeção de conteúdo — mas neste caminho o MIME VEM do conteúdo.**
  Medido, não suposto: `TemporaryUploadedFile::getMimeType()` lê o disco temporário
  (`vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:63-90`) e
  o detector do Flysystem devolve `image/svg+xml` para conteúdo SVG **mesmo com o arquivo
  renomeado para `.png`** (verificado com `League\MimeTypeDetection\FinfoMimeTypeDetector`:
  `detectMimeTypeFromFile()` → `image/svg+xml`, `detectMimeTypeFromPath()` → `image/png`). Ver
  ADR-03 para o que isso implica no teste.
- **Baixar o teto global do Livewire** pode recusar um upload que hoje passa (12 → 10 MB).
  Aceito: é exatamente o alinhamento pedido, e a chave sobe os dois de uma vez.

## Channel de Log da Feature

**Nenhum channel novo, e nenhum log novo.** Decisão registrada em ADR-05.

Varredura feita: `grep -n "'channels'" -A ... config/logging.php` e `grep -rn "Log::channel(" app/`.
Esta feature não executa lógica — ela acrescenta regra de validação declarativa a campos de
formulário. Recusa de upload é resposta ao usuário, na tela, não anomalia de sistema; um channel
`upload-limite` que nunca escreve é ruído no `config/logging.php`, e um `warning` por SVG recusado
gritaria no caminho normal de quem errou o arquivo. A tela de configurações já é auditada por
`filament-auditing` (o que **entrou** fica registrado); a trilha do que foi recusado não é pedida
pelo requisito.

## Estrutura de Implementação

### 1. A chave de configuração

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`
- Também: `app/Support/TetoDeUpload.php` (classe nova — ver a nota de desvio abaixo)
- Novo bloco, **imediatamente depois de `identidade`** (é o vizinho temático: aquele guarda os
  caminhos dos arquivos enviados, este o teto de quem os envia):

```php
'uploads' => [
    'maximo_em_kb' => NumeroDoEnv::positivo(env('KIT_UPLOAD_MAXIMO_MB'), 10) * 1024,
],
```

- **`NumeroDoEnv::positivo()`, e não `(int) env()`**: `.ai/rules/config.md`. Teto zero não é
  configuração, é a feature desligada por acidente — `->maxSize(0)` recusaria **todo** arquivo, e
  o `.env` com `KIT_UPLOAD_MAXIMO_MB=` (chave presente, valor vazio) produziria exatamente isso.
  `positivo()` manda vazio/`0`/ausente para o default e negativo/texto para 1.
- **Uma chave, uma unidade.** `maximo_em_kb` é o nome porque KB é o que os consumidores recebem;
  a env é em MB porque é o que a pessoa escreve. Não criar uma segunda chave em MB: seriam duas
  donas da mesma pergunta (`.ai/rules/config.md`).
- Comentário de bloco obrigatório, no estilo do arquivo, contendo: a unidade de cada ponta, o
  `file:line` do `maxSize()` do Filament, a escada de tetos da seção acima e o que mais mudar
  para passar de 52 MB.
- **Desvio do plano original, aplicado na implementação**: a leitura da chave e a conversão para
  MB são consultadas em **três** arquivos e em duas unidades. `intdiv((int) config('kit.uploads.maximo_em_kb'), 1024)`
  copiado cinco vezes é como um teto acaba divergindo do texto que o anuncia, então a pergunta
  ganhou uma dona: `App\Support\TetoDeUpload`, com `emKb()` e `emMb()`. É o mesmo padrão de
  classe pequena de `NumeroDoEnv`, `BooleanoDoEnv`, `CorPrimaria` e `RegistroAberto`, e o mesmo
  remédio que `.ai/rules/config.md` prescreve para o login social ("uma pergunta, uma dona").
- **Logs**: nenhum (arquivo de config).

### 2. O teto global do Livewire e o `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/KitServiceProvider.php`, dentro de `configureDefaults()`
- Uma linha, com docblock explicando o desalinhamento que ela fecha:

```php
config()->set('livewire.temporary_file_upload.rules', [
    'required', 'file', 'max:'.TetoDeUpload::emKbComFolgaDoLivewire(),
]);
```

> **Correção da implementação**: a primeira versão usava `emKb()` — o **mesmo** número do campo.
> Medido, isso torna a mensagem de erro do campo inalcançável, porque o Livewire recusa antes de o
> formulário validar. A folga de 1 MB é o que preserva a mensagem para o caso comum. Ver a correção
> em ADR-04.

- **Por que `config()->set()` e não publicar `config/livewire.php`**: o projeto não tem esse
  arquivo, e publicá-lo traria ~130 linhas de configuração alheia para o repositório só para
  mudar um número. Uma linha num lugar que já existe, e a chave do kit continua a única dona.
- **Path**: `.env.example` — bloco novo, comentado (o kit comenta as chaves de default:
  `KIT_TABELA_*`), perto do bloco de identidade/tabelas:

```dotenv
# Teto de tamanho de TODO upload do kit, em megabytes. Vale para a logo, o
# favicon e a arte do login (/admin/configuracoes-do-kit), a logo da organização
# e os anexos de Projeto. Vazio ou ausente = 10.
# Acima de 52 MB, mude também docker/php/uploads.ini e docker/nginx/nginx.conf.
# KIT_UPLOAD_MAXIMO_MB=10
```

- **Logs**: nenhum.

### 3. Os três campos da tela de configurações

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- O helper `arquivo()` passa a receber o texto de ajuda e a compor a frase do limite, porque as
  três chamadas encadeiam `->helperText()` depois e sobrescreveriam qualquer texto do helper:

```php
private function arquivo(string $nome, string $rotulo, string $ajuda): FileUpload
{
    $maximoEmKb = (int) config('kit.uploads.maximo_em_kb');
    $maximoEmMb = intdiv($maximoEmKb, 1024);

    return FileUpload::make($nome)
        ->label($rotulo)
        ->image()
        ->rule('mimes:'.self::FORMATOS_DE_IMAGEM)
        ->maxSize($maximoEmKb)
        ->validationMessages([
            'max'   => "O arquivo passa de {$maximoEmMb} MB.",
            'image' => 'SVG não é aceito. Envie PNG, JPEG, WEBP, GIF, BMP, AVIF ou HEIC.',
        ])
        ->helperText("{$ajuda} Até {$maximoEmMb} MB, e SVG não é aceito.")
        ->disk('public')
        ->directory('kit')
        ->visibility('public');
}
```

- As três chamadas (linhas 235, 238, 241) passam o texto de ajuda como **terceiro argumento** e
  perdem o `->helperText()` encadeado.
- **A barreira e o `->image()` são coisas diferentes, e as duas são necessárias**: o `->image()` do
  Filament é o `accept="image/*"` do seletor de arquivo do sistema (e a regra `mimetypes:image/*`),
  conveniência para quem escolhe o arquivo — e `image/svg+xml` **casa** com aquele curinga, que é
  por isso que SVG passava. A barreira é uma regra de validação por cima.
- **A barreira final é `mimes:` com lista escrita, não `->rule('image')`.** A primeira versão usava
  `image` (nove extensões, mantida pelo framework); o quality gate a derrubou — ela recusa `.ico`, e
  o kit serve `public/favicon.ico`. Ver ADR-02 e o achado QA-01. A lista é
  `ConfiguracoesDoKit::FORMATOS_DE_IMAGEM` = os nove + `ico`, `tif`, `tiff`, e `tif` está lá porque
  `guessExtension()` devolve a primeira extensão do MIME, que para `image/tiff` é `tif`.
- **O mecanismo não muda com a troca**: `validateMimes()` compara `guessExtension()`
  (`ValidatesAttributes.php:1746-1761`), derivado do MIME do **conteúdo** — então a resistência a
  arquivo renomeado, que é o argumento de ADR-03, continua valendo (medido).
- **`->maxSize()` recebe KILOBYTES** — `BaseFileUpload.php:413-421` monta `max:{$size}`, e
  `ValidatesAttributes.php:2822` divide `getSize()` por 1024. Confirmado também na doc do
  Filament 5 via `search-docs`: *"restrict the size of uploaded files in kilobytes"*.
- **`validationMessages()` funciona neste formato**: o Filament o entrega como
  `["{$name}.*" => [...]]` (`BaseFileUpload.php:761-763`) e o `getFromLocalArray()` do Laravel
  resolve a chave com `*` e devolve `$message[$lowerRule]`
  (`vendor/laravel/framework/src/Illuminate/Validation/Concerns/FormatsMessages.php:102-127`).
  Sem isso, a mensagem de fábrica fala em "10240 kilobytes".
- **Logs**: nenhum.

### 4. O anexo do Projeto

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/App/Resources/Projetos/ProjetoResource.php`
- Trocar `->maxSize(10 * 1024)` por `->maxSize((int) config('kit.uploads.maximo_em_kb'))` e o
  `helperText` fixo por um que leia o mesmo valor — o `10` sai do código, que é RQ-05.
- Acrescentar a recusa de SVG. Aqui **não** cabe a lista de imagem (recusaria PDF e planilha, que é
  a razão do campo existir) nem `acceptedFileTypes()` (allow-list fecharia o campo). Regra de
  recusa de **um formato**:

```php
->rule(static function (string $atributo, mixed $arquivo, Closure $falhar): void {
    if ($arquivo instanceof TemporaryUploadedFile && $arquivo->getMimeType() === 'image/svg+xml') {
        $falhar('SVG não é aceito: o formato carrega script e o anexo é servido pela aplicação.');
    }
})
```

- **A regra roda por arquivo, não pelo array**: `BaseFileUpload::getValidationRules()` separa as
  regras em `$arrayRules` e `$fileRules` por `isArrayValidationRule()`, que só classifica como
  array as **strings** de uma lista fechada (`BaseFileUpload.php:101-114, 776-785`) — um `Closure`
  cai em `$fileRules` e é validado contra cada `TemporaryUploadedFile` em
  `["{$name}.*" => ['file', ...$fileRules]]` (`BaseFileUpload.php:752-763`). É por isso que o
  campo é `->multiple()` e a regra ainda vê um arquivo por vez.
- Docblock curto explicando por que a forma aqui é diferente da dos campos de imagem.
- **Logs**: nenhum.

### 5. A logo da organização

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- **Trocar** `->maxSize(1024)` por `->maxSize(TetoDeUpload::emKb())`, acrescentar
  `validationMessages(['max' => …])` em MB e citar o limite no `helperText` que já existe. O
  campo passa de 1 MB para o teto da config (10 MB de fábrica) — é afrouxamento, e está declarado
  como premissa em `## Ambiguidades` do `00-requisito.md`.
- **Não** mexer no `acceptedFileTypes(['image/png','image/jpeg','image/webp'])`: o comentário
  daquele bloco é a decisão anterior, com justificativa de segurança, e RQ-04 não pede para
  afrouxar. Registrado em `## Ambiguidades` do `00-requisito.md`.
- **Logs**: nenhum.

### 6. Documentação nos dois READMEs

> Skills: nenhuma específica

- **Paths**: `README.md` e `README.en.md`
- Onde: na seção da tela de configurações (`README.md:1584`, `README.en.md:1550`) e na de anexos
  (`README.md:787`, `README.en.md:749`), com o conteúdo completo num lugar e referência no outro.
- O que **precisa** estar escrito, porque é RQ-06:
  1. a chave `KIT_UPLOAD_MAXIMO_MB` e o default `10`;
  2. a **unidade da env (MB)** e a da config (`kit.uploads.maximo_em_kb`, em KB);
  3. que ela vale para os cinco campos e para o upload temporário do Livewire;
  4. **o que mais mudar para subir muito**: `docker/php/uploads.ini`
     (`upload_max_filesize`/`post_max_size`) e `docker/nginx/nginx.conf`
     (`client_max_body_size`) acima de 52 MB — e o aviso de que **fora do Docker do kit o PHP
     costuma vir com `upload_max_filesize=2M`**, que passa a ser o teto real;
  5. que SVG é recusado em todos os campos, e por quê.
- **Logs**: nenhum.

### 7. Testes

> Skills: `pest-testing`

- **Path**: `tests/Kit/UploadLimiteETiposTest.php` (novo) e os arquivos existentes que os CTs
  indicarem. Cenários em `04-casos-de-teste.md`.
- Regra do par, sem exceção: **dentro do teto passa / acima é recusado**, e **PNG passa / SVG é
  recusado**. Caso que só prova o caminho verde não prova barreira nenhuma.

### 8. Verificação e commit

- Ver `## Verificação Final`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> 1. Reutilizar código existente antes de criar novo — `NumeroDoEnv`, a regra `image` do Laravel,
>    o helper `arquivo()` que já existe, `configureDefaults()` que já existe.
> 2. Usar stdlib do PHP/Laravel antes de código custom — a regra `image` em vez de allow-list de
>    MIME escrita à mão; `intdiv()` em vez de aritmética à mão.
> 3. Usar features nativas antes de dependências — **nenhuma dependência nova**. `spatie/image` e
>    `intervention` não entram: o MIME que a validação lê já vem do conteúdo.
> 4. Uma linha quando possível — o alinhamento do Livewire é uma linha.
> 5. Mínimo código que funciona.
>
> Atalhos deliberados marcados com `ponytail:`. Após implementar, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário. Arquivos wiki (00-06),
> código, commits e PRs são boundary — prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. Não há `05-casos-de-teste-browser.md`: ver o gate de CT-B acima e a
> seção `## Sem CT-B` do `04`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` → 0 erros
- [ ] `php artisan test --testsuite=Kit --compact --filter=UploadLimite`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` → base **1016** não cai
- [ ] `composer test:browser`
- [ ] `git push -u origin feat/upload-limite-e-tipos` (sem PR, sem merge)

## Commits

- `:sparkles: feat(upload): teto de 10 MB configurável e recusa de SVG`
- `:memo: docs(readme): a chave do teto de upload, nos dois idiomas`
- `:memo: docs(specs): wiki da feature upload-limite-e-tipos`
