# Progresso — Limite e tipos de upload

**Concluída em** 2026-08-25. Base: `origin/main` em `21cbb80` (v0.19.4), depois de rebase.

## 1. A chave de configuração

- [x] Bloco `uploads` em `config/kit.php`, depois de `identidade`
- [x] `NumeroDoEnv::positivo(env('KIT_UPLOAD_MAXIMO_MB'), 10) * 1024`
- [x] Comentário de bloco com a unidade de cada ponta, `file:line` do `maxSize()` e a escada de tetos
- [x] `App\Support\TetoDeUpload` (desvio do plano — ver Desvios)

## 2. O teto global do Livewire e o `.env.example`

- [x] `KitServiceProvider::configureTetoDeUpload()`, chamado por `configureDefaults()`
- [x] Bloco comentado `# KIT_UPLOAD_MAXIMO_MB=10` no `.env.example`
- [x] Folga de 1 MB sobre o teto do campo (correção obrigada pela medição — ver Desvios)

## 3. Os três campos da tela de configurações

- [x] `arquivo()` recebe o texto de ajuda por argumento
- [x] `->rule('mimes:'.FORMATOS_DE_IMAGEM)` (recusa SVG e só SVG), `->maxSize(TetoDeUpload::emKb())`
- [x] `validationMessages()` com o número em MB
- [x] `helperText` citando teto e recusa de SVG nos três campos

## 4. O anexo do Projeto

- [x] `->maxSize(TetoDeUpload::emKb())` no lugar de `10 * 1024`
- [x] Regra de recusa de `image/svg+xml`, dentro de um `Closure` externo
- [x] `helperText` lendo a config

## 5. A logo da organização

- [x] `->maxSize(TetoDeUpload::emKb())` no lugar de `1024`
- [x] `validationMessages(['max' => …])` em MB
- [x] `acceptedFileTypes()` intocado (a allow-list de lá já recusava SVG)

## 6. Documentação nos dois READMEs

- [x] `README.md` — seções "Teto de upload: 10 MB, e onde mudar" e "Por que SVG é recusado"
- [x] `README.en.md` — as duas equivalentes
- [x] Chave, unidade, escada de quatro tetos, os dois arquivos de infra, o aviso do `upload_max_filesize=2M`

## Testes

- [x] `tests/Kit/UploadLimiteETiposTest.php` — CT-01..CT-09, CT-23, CT-24, CT-25 (45 casos com datasets)
- [x] `tests/Kit/UploadLimiteETiposDocumentacaoTest.php` — CT-18, CT-19, CT-20
- [x] `tests/Tenancy/UploadLimiteETiposTenancyTest.php` — CT-10..CT-13, CT-16, CT-21 (11 casos)
- [x] Mapa cenário derivado × caso implementado, no fim do `04` (as numerações divergiram)

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent` → passed
- [x] `vendor/bin/phpstan analyse --no-progress` → 0 erros
- [x] `php artisan test --testsuite=Kit --filter=UploadLimiteETipos` → 45/45
- [x] `php artisan test --testsuite=Tenancy --filter=UploadLimiteETipos` → 11/11
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` → base não caiu
- [x] `composer test:browser`
- [x] `git rebase origin/main` — limpo, sem conflito
- [x] `git push -u origin feat/upload-limite-e-tipos` (sem PR, sem merge)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `TenantForm` "não tem teto, falta só `maxSize()`" | tinha `->maxSize(1024)` — 1 MB — na linha 144, **fora** do bloco de comentários que explicava os outros encadeamentos, e por isso invisível no `grep` que abriu a varredura | tabela do `00` corrigida, seção do `01` reescrita, ambiguidade "RQ-01 no campo `logo` da organização" acrescentada |
| `->maxSize()` recebe kilobytes | confirmado: `BaseFileUpload.php:413-421` monta `max:{$size}` e `ValidatesAttributes.php:2822` divide por 1024 | premissa mantida, com `file:line` no PRD e na ADR |
| `validationMessages(['max' => …])` funciona no formato do Filament | confirmado: `BaseFileUpload.php:761-763` entrega `["{$name}.*" => [...]]` e `FormatsMessages.php:102-127` resolve a chave com `*` devolvendo `$message[$lowerRule]` | premissa mantida |
| a recusa de SVG resiste a arquivo renomeado | confirmado por medição, não por leitura: `FinfoMimeTypeDetector::detectMimeTypeFromFile()` devolve `image/svg+xml` para bytes de SVG num arquivo `.png`, e `detectMimeTypeFromPath()` devolve `image/png` | ADR-03 escrita a partir da medição, com o contraste teste × produção |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar allow-list de nove MIME à mão — a regra `image` do Laravel já é isso, mantida pelo framework | sim | `arquivo()`, ADR-02 |
| 2 | não publicar `config/livewire.php` (~130 linhas de config alheia) para mudar um número | sim | `configureTetoDeUpload()`, ADR-04 |
| 3 | não adicionar dependência de inspeção de imagem (`spatie/image`, `intervention`) — o MIME que a validação lê já vem do conteúdo | sim | ADR-03 |
| 4 | não criar channel de log nem `Log::` para uma feature declarativa | sim | ADR-05 |
| 5 | não criar segunda chave de config em MB — a conversão é código, não config | sim | ADR-01 |
| 6 | recusada: "deixar a conversão inline nos cinco campos em vez de criar `TetoDeUpload`" | recusada — `intdiv((int) config(…), 1024)` apareceria em cinco encadeamentos, em três arquivos, e conversão de unidade por cópia é como um teto diverge do texto que o anuncia | ADR-06 |

## Quality Gate (step 8)

**Veredito: `APROVADO COM DÉBITO`**, ciclo 1 de 3. Relatório em `06-relatorio-qa.md`.

| Achado | Severidade | Destino | Situação |
|---|---|---|---|
| QA-01 — `.ico` recusado no favicon, com o kit embarcando um `.ico` | Major | implementação + especificação | corrigido no ciclo |
| QA-02 — mensagem do campo inalcançável com os tetos iguais | Major | implementação | corrigido no ciclo |
| QA-03 — regra crua avaliada como closure do Filament (quebrava no envio) | Blocker | implementação | corrigido no ciclo |
| QA-04 — `public/favicon.ico` do repo tem 0 byte | Minor | não-defeito desta feature | pré-existente, fora do diff |
| QA-05 — arquivo de 0 byte é aceito | Minor | especificação | **débito declarado** |

O débito é QA-05: o requisito define teto e não define piso, e inventar `min:1` seria chutar valor.
Registrado em `## Ambiguidades` do `00-requisito.md`, com o mutante declarado sem matador no `04`.

## Blockers

Nenhum aberto.

## Desvios do Plano

- **Passo 1 e 2 — `App\Support\TetoDeUpload` não estava no plano.** O PRD prescrevia
  `(int) config('kit.uploads.maximo_em_kb')` direto nos cinco campos. Ao implementar, a conta em MB
  (`intdiv(…, 1024)`) apareceu em cinco encadeamentos e três arquivos. Registrado como **ADR-06**, a
  pedido da `feature-test-design`, que achou a divergência wiki × código antes do quality gate.

- **Passo 2 — a folga de 1 MB no teto do Livewire.** O plano e a primeira versão de ADR-04 alinhavam
  o upload temporário ao **mesmo** número do campo. A medição mostrou que isso quebra a consequência
  que a própria ADR prometia: com os tetos iguais, o Livewire recusa antes de o formulário existir e
  a mensagem do campo fica inalcançável. ADR-04 corrigida com a medição, e
  `TetoDeUpload::emKbComFolgaDoLivewire()` criado.

- **Passo 5 — a logo da organização foi AFROUXADA, não apertada.** Ela tinha 1 MB cravado. Passou a
  10 MB (o teto da config). É mudança de comportamento em campo existente, declarada como premissa em
  `## Ambiguidades` do `00`.

- **Passo 4 — a regra do `anexos` precisou de um `Closure` externo.** Não estava no plano e só
  aparece no envio: `CanBeValidated::getValidationRules()` faz `$rule = $this->evaluate($rule)`
  (`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:872`), então regra passada crua é
  **avaliada** com injeção de utilitários em vez de entregue ao validador. A tela abria normalmente e
  quebrava no envio, com `"[$atributo] was unresolvable"`.

- **A barreira de tipo mudou de `rule('image')` para `mimes:` com lista escrita.** Achado QA-01 do
  quality gate: a regra `image` do Laravel recusa `.ico`, e o kit serve `public/favicon.ico`. Ver
  ADR-02, que teve a justificativa corrigida junto — ela afirmava que o kit usava PNG.

- **Os casos de documentação foram para arquivo próprio.** O plano os punha no arquivo principal; a
  `feature-test-design` os separou, seguindo o padrão de `ConfiguracoesDoKitDocumentacaoTest` e
  `AnexosPrivadosDocumentacaoTest`.

## Notas de Implementação

- **Há DUAS barreiras de tamanho, não uma, e a de fora dispara primeiro.**
  `FileUploadController::validateAndStore()` aplica `FileUploadConfiguration::rules()` antes de o
  arquivo virar propriedade do componente. Consequência para todo teste de teto de campo: ou o
  cenário alarga a regra global no arranjo, ou mede a barreira errada — e o `->maxSize()` do campo
  poderia estar **ausente** com tudo verde, que é literalmente o defeito que esta feature paga.

- **O MIME que a validação lê, em teste, vem do NOME — por dois saltos.**
  `Illuminate\Http\Testing\File::getMimeType()` devolve `MimeType::from($this->name)`
  (`Testing/File.php:132-135`); `FileUploadConfiguration::storeTemporaryFile()` persiste esse MIME num
  `.json` de meta; e `TemporaryUploadedFile::getMimeType()`, **sob `runningUnitTests()`**, lê o meta em
  vez do disco (`TemporaryUploadedFile.php:63-77`). Fora de teste é o Flysystem, por conteúdo. É o que
  torna o cenário do SVG **renomeado** inexpressável por componente — e o que torna os nove formatos
  de imagem expressáveis, porque `avif`, `heic` e `bmp` o GD não gera.

- **Em teste de componente o arquivo É um `TemporaryUploadedFile`.**
  `Testable::setProperty()` desvia para `upload()`, que roda o fluxo real de upload temporário. Sem
  essa medição, a regra `instanceof TemporaryUploadedFile` do `anexos` pareceria inexercitável por
  componente e o cenário desceria para o navegador sem necessidade.

- **A asserção de tipo é sobre a MENSAGEM, não sobre o nome da regra.** As regras de arquivo do
  Filament rodam num validador aninhado e a falha volta como `$fail($validator->errors()->first())`
  (`BaseFileUpload.php:752-772`), o que descarta o nome da regra. `assertHasFormErrors(['logo' => 'image'])`
  reprova **mesmo com a recusa funcionando**.

- **`configuracaoDeUploadGravada()` como oráculo, e não `assertHasNoFormErrors()`.** Arquivo recusado
  pela camada do Livewire chega ao teste como "sem erro e sem gravação" — indistinguível de aceito e
  ignorado. Só o valor gravado distingue.

- **Arranjo do form de Projeto: três coisas, nenhuma dispensável.** `kit.demo` ligado (o `phpunit.xml`
  o fixa em `false`), `noPainelDa()` antes do `getUrl()` (senão a rota resolve no painel default do
  processo, `infra` nesta suíte) e um GET real antes do teste de componente (quem boota o painel é o
  middleware `SetUpPanel`). Cada uma falha com um erro que não menciona a causa.

## Retrospectiva

- **Funcionou bem**: a varredura antes de aceitar o escopo do card. O requisito diz "no upload", sem
  nomear campo; o `grep` achou **cinco** campos em três arquivos, dois deles com o teto já cravado no
  código — que é exatamente o que a cláusula da config proíbe. Corrigir só os três campos que a dívida
  nomeava teria deixado três números em três arquivos, com um só documentado.

- **Funcionou bem**: delegar a derivação dos casos. O agente independente achou duas coisas que quem
  escreveu o plano não veria — a armadilha de camada (que mudou o desenho de produção, não só o teste)
  e a divergência `TetoDeUpload` × PRD, que virou ADR-06.

- **Faltou no plano**: medir o comportamento do arnês **antes** de escrever a primeira asserção. Os
  três retrabalhos desta feature (chave de regra, oráculo de gravação, camada do Livewire) são todos
  do mesmo tipo: asserção escrita a partir do que o Filament e o Livewire *deveriam* fazer. Meia hora
  de `probe` no começo teria poupado três voltas — e é a mesma lição que `.ai/rules/specs.md` já
  registra para wiki e ADR, aqui do lado do teste.

- **Faltou no plano**: nenhuma seção do PRD perguntava "o que acontece quando o limite da tela e o
  limite da camada de transporte discordam **em UX**". A seção existia sobre *qual* limite vence; a
  pergunta que importava era *qual mensagem o usuário lê*, e ela só apareceu na medição.
