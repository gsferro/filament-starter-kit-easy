# Relatório de QA — Settings do kit em `/admin`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Perfil de esforço: **completo** (UI com JS **e** domínio sensível: autorização + segredo de SMTP)
> Natureza da wiki: nova · Regressão: sim (infra compartilhada — três painéis, boot, toda tabela)
> Executado por agente de QA que **não** implementou a feature.

## Veredito — Ciclo 1

**REPROVADO → implementação** (QA-02, Blocker) **e teste** (QA-01, Major — o CT vem primeiro)

- Blocker: 1 · Major: 1 · Minor: 5 · Cosmético: 1
- Ambiente: `.claude/worktrees/settings-do-kit`, SQLite migrado e semeado · Pest 5.0.5 · pcov + xdebug presentes
- Playwright MCP: **proibido nesta execução** (instância compartilhada) — ver "Não Verificado"
- Sonda: arquivo de teste efêmero criado e **removido**; nenhuma linha de código de aplicação ou de teste alterada. Dados de sonda no banco de desenvolvimento foram revertidos (`paginacao_padrao` = 10, `mail_password` = null, trilhas de sonda apagadas).

---

## Achados

### QA-01 — Valor legítimo do `.env` fora do domínio do campo trava TODA a gravação da tela · **Major** · destino 3 → 2

- **Dimensão**: A (omissão), B (fronteira), F (UX de erro)
- **Relacionado a**: RQ-03, RQ-06, RQ-08, ADR-08, R5/R8 do `04`, `config/kit.php:49-50`
- **Esperado**: `config/kit.php:49-50` promete por escrito que "nome fora da lista é ignorado, e o painel volta ao padrão em vez de morrer", e ADR-08 repete a promessa. A tela é o caminho normal de correção desse estado.
- **Observado**: a propriedade semeada do `.env` chega ao formulário e, quando está **fora do domínio do campo**, o `save()` inteiro é recusado — inclusive para quem só queria trocar o nome da aplicação. Nada é gravado. Três instâncias reproduzidas e duas latentes:

  | Propriedade | Semeada de | Domínio do campo | Estado real que trava | Reproduzido |
  |---|---|---|---|---|
  | `mail_mailer` | `MAIL_MAILER` | `log`, `array`, `smtp` | `ses`, `postmark`, `resend`, `sendmail`, `failover`, `roundrobin` — **seis dos nove mailers que o próprio `config/mail.php:40-95` traz** | sim |
  | `cor_primaria` | `KIT_COR_PRIMARIA` | as 16 de `CustomizadorDaInstalacao::CORES` | `Green`, `Yellow`, `Gray`, `Zinc`, `Neutral`, `Stone`, `Mauve`, `Olive`, `Mist`, `Taupe` (existem em `Color` e o boot as aceita), **e qualquer nome inválido** (`Roxo`) | sim |
  | `paginacao_padrao` | `KIT_TABELA_PAGINACAO` | `1..100` | `500` — `NumeroDoEnv::positivo()` não tem teto e aceita | sim |
  | `mail_scheme` | `MAIL_SCHEME` | `tls`, `ssl` | `starttls` etc. | latente (só com `mail_mailer=smtp`; campo oculto não é validado) |
  | `mail_port` | `MAIL_PORT` | `1..65535` | `70000` | latente (idem) |

- **Repro** (componente, sem navegador):
  1. `gravarConfiguracao('mail_mailer', 'ses')` — ou `('cor_primaria', 'Green')`, ou `('paginacao_padrao', 500)`
  2. `Livewire::test(ConfiguracoesDoKit::class)->fillForm(['nome_da_aplicacao' => 'Nome Novo'])->call('save')`
  3. Erro: *"O campo transporte não contém um valor válido."* / *"O campo cor primária não contém um valor válido."* / *"…não deve conter um valor superior a 100."*; `nome_da_aplicacao` permanece `Starter Kit`
- **Evidência de vendor** (`.ai/rules/specs.md`): o `Select` acrescenta `Rule::in(<chaves das options>)` sozinho — `vendor/filament/forms/src/Components/Select.php:1742-1748` (fallback para as options) e `vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:808-857` (`getInValidationRule()` entrando em `getValidationRules()`).
- **Agravante em `mail_mailer`**: o campo é `->required()`, então a única saída é escolher `log`/`array`/`smtp` — o que **rebaixa em silêncio o transporte de produção** de quem usava `ses`/`postmark`/`resend`. O README (`README.md:1271`) lista as três opções, mas nem ele nem a seção "O que ficou fora" registram a consequência.
- **Varredura do padrão** (a rule manda varrer antes de consertar o ponto): o defeito é **de classe** — *todo campo cujo domínio é mais estreito do que a fonte que o semeia*. As cinco linhas da tabela são as cinco ocorrências desta tela; não há outra `SettingsPage` no kit.
- **Ação exigida**: decidir a regra (destino 1 se o recorte de mailers for deliberado — então documentar e tratar o valor de fora; caso contrário, ampliar o domínio) e **escrever primeiro o CT que falha** — um cenário por família de campo, com o valor semeado fora do domínio e o oráculo "outro campo grava". Não aplicar o remédio óbvio sem medir: `->options()` dinâmico que injeta o valor gravado resolve o travamento mas passa a oferecer valor inválido na lista; `->rules([])` derrubaria a validação legítima.

### QA-02 — A senha de SMTP chega em claro ao HTML da página · **Blocker** · destino 2 (+3)

- **Dimensão**: I (segurança da superfície nova), A (a linha de validação que pegaria isso ficou vazia)
- **Relacionado a**: RQ-08, ADR-05, **`05-casos-de-teste-browser.md:152`**
- **Esperado**: a própria wiki declara, na linha 7 do "Roteiro de Validação: Desenhado × Implementado", *"senha com revelação, **nunca em claro no HTML inicial**"*. Essa linha **nunca foi preenchida** — e é ela que teria pegado o defeito. É a omissão silenciosa clássica: promessa escrita, coluna de evidência vazia, tudo verde. ADR-05 promete "cifrada no banco e mascarada na trilha", e as duas metades **se confirmam**; o terceiro caminho — o navegador — ficou sem guarda.
- **Observado**: o valor decifrado entra no estado público `$data` da Page (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:33` e `:35-51`, `fillForm(app(getSettings())->toArray())`) e o Livewire serializa esse estado no `wire:snapshot` do HTML. `GET /admin/configuracoes-do-kit` (200) devolve, literalmente: `…&quot;mail_password&quot;:&quot;SENHA-SUPER-SECRETA-42&quot;…` — **sem nenhum clique em "revelar"**, e em cada ida e volta do Livewire.
- **Repro**: gravar `mail_password` pela classe de settings, autenticar como `admin`, `$this->get('/admin/configuracoes-do-kit')->getContent()` e procurar o segredo.
- **Por que Blocker**: é um segredo saindo por um caminho que a wiki declarou fechado. Ressalva honesta, e ela importa para dimensionar o conserto: **não há escalada de privilégio** — ADR-04 decidiu por escrito que quem abre a tela pode ler a credencial (`->revealable()` é intencional, a permissão é única). O que o achado acrescenta é exposição **passiva**: cache de disco do navegador, HAR, gravação de sessão, ombro alheio e histórico de proxy passam a conter a senha sem que ninguém tenha pedido para vê-la.
- **Ação exigida**: não hidratar o segredo no formulário — o padrão do Filament é `->dehydrated(fn (?string $estado) => filled($estado))` com o estado inicial vazio, gravando só quando o campo é preenchido. E um CT com oráculo no HTML (`assertDontSee` do segredo no conteúdo da rota) — hoje **nenhum** dos 36 CT olha para o HTML dessa tela.

### QA-03 — O remetente global pode ser esvaziado pela tela, sem aviso · **Minor** · destino 3 → 2

- **Dimensão**: B, F
- **Relacionado a**: RQ-08, R1 do `04`
- **Observado**: `mail_from_address` não é `->required()`. Esvaziando o campo, o alinhamento leva `mail.from.address` para `null` (comportamento correto e coberto por CT: "zera a chave de configuração quando a propriedade é limpada") e, aí, `MailManager::setGlobalAddress()` não configura remetente algum — `vendor/laravel/framework/src/Illuminate/Mail/MailManager.php:512-517` exige `isset($address['address'])`. Medido por reflexão: `Mailer::$from` sai de `{"address":"hello@example.com",…}` para `null`.
- **Consequência**: convite e lembrete (que não declaram `from()` próprio) passam a produzir mensagem sem cabeçalho `From`. **Não reproduzi a exceção de envio neste ambiente** — o pacote de log de e-mail estoura antes (`NOT NULL constraint failed: mail_logs.body`), com e sem remetente.
- **Ação exigida**: CT com o remetente vazio e oráculo no `Mailer`/envio; depois decidir entre `->required()` e um aviso explícito no campo.

### QA-04 — O caminho de fallback loga stack trace completo em TODO request · **Minor** · destino 2

- **Dimensão**: D (observabilidade real)
- **Observado**: no estado "tabela `settings` existe, grupo `kit` sem linhas" — exatamente o que acontece entre `kit:update` e o `php artisan migrate` seguinte —, `KitServiceProvider::configureSettingsDoKit()` grava um `warning` com `['exception' => $e]` a cada request e a cada comando artisan. Medido no log real deste worktree: **11 warnings produziram ~346 das 415 linhas e ~120 KB dos 132 KB do arquivo do dia**. A mensagem do `MissingSettings` já lista as 21 propriedades; o trace é puro volume.
- **Ação exigida**: manter o `warning` (é anomalia e deve aparecer) e cortar o `['exception' => $e]`, ou trocar por `$e->getMessage()` mais um `debug` com o trace.

### QA-05 — Um `debug` por request e por comando artisan, com o driver ligado por default · **Minor** · destino 2

- **Dimensão**: D
- **Observado**: `ConfiguracoesDoKit::aplicarNaConfig()` loga sempre, com as 21 chaves de config (58 linhas num único dia de desenvolvimento). O `.env.example:22` traz `LOG_LEVEL=debug`, então o canal `configuracoes` escreve isso em qualquer instalação que não mexa na chave. É o mesmo custo/benefício que o próprio PRD usou para **não** logar a abertura da tela ("um `info` por request … 1,1 MB/dia").
- **Ação exigida**: logar só quando o alinhamento mudar algo, ou remover a linha. Não é bloqueante.

### QA-06 — Upload sem `maxSize()` e aceitando SVG · **Minor** · destino 2 (+3)

- **Dimensão**: I
- **Observado**: os três `FileUpload` usam `->image()`, que traduz para `mimetypes:image/*` (`vendor/filament/forms/src/Components/BaseFileUpload.php:377-388`). Reproduzido: um `.svg` contendo `<script>alert(1)</script>` é aceito e gravado em `kit/…svg` no disco público, servido na mesma origem do cookie de sessão e renderizado nas telas de login (pré-autenticação). Não há `maxSize()` — e o próprio vendor manda usar: *"Always use `acceptedFileTypes()` and `maxSize()` for server-side validation regardless of visibility setting"* (`BaseFileUpload.php:493-495`).
- **Atenuante**: só quem tem `View:ConfiguracoesDoKit` envia, e nomes de arquivo são aleatórios (`preserveFilenames` não é usado), então não há execução de PHP.
- **Ação exigida**: `->acceptedFileTypes(['image/png','image/jpeg','image/webp','image/x-icon'])` e `->maxSize(...)`, com CT de rejeição.

### QA-07 — O ramo "propriedade sem linha na tabela" do listener não tem nenhum teste · **Minor** · destino 3

- **Dimensão**: K (adequação da suíte)
- **Evidência**: `vendor/bin/pest tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/ConfiguracoesDoKitTelaTest.php --mutate --path=app/Listeners/AuditarConfiguracoesDoKit.php` → **score 84,75%**, 50 mutantes mortos, **9 `uncovered`**, todos nas linhas 116-124 (`linhaDaPropriedade() === null` → `warning` → `continue`). `grep` nos testes confirma: nenhum caso exercita o ramo.
- **Ação exigida**: um CT que salve uma propriedade sem linha em `settings` e afirme o `warning` e a ausência de linha em `audits`. Acima do piso de 70%, portanto não reprova por si só.

### QA-08 — Rastro da wiki desatualizado e um docblock que descreve código inexistente · **Cosmético** · destino 1/2

- `03-progresso.md` estava com **todos** os checkboxes vazios, com a feature inteira entregue (o agente caiu por limite de sessão antes do fechamento). Atualizado por este gate.
- **`05-casos-de-teste-browser.md:148-155` — o "Roteiro de Validação: Desenhado × Implementado" está com as oito linhas em branco.** Não é papelada: a linha 7 prometia "senha nunca em claro no HTML inicial", e é exatamente ela que o QA-02 reprova. Roteiro em branco foi o que deixou um Blocker passar por uma suíte de 894 casos verdes.
- `CustomizadorDaInstalacao::propagarParaOSettings()` tem o parágrafo *"`refresh()` depois do `save()`"* — e o método **não chama** `refresh()`. É o padrão que `.ai/rules/specs.md` proíbe: explicação escrita a partir do que se esperava escrever. Apagar o parágrafo ou acrescentar a chamada.

---

## Matriz de Rastreabilidade

Nenhuma cláusula ficou **órfã**. As linhas abaixo são as que têm ressalva; as 13 restantes (RQ-01, RQ-02, RQ-04, RQ-05, RQ-07, RQ-09, RQ-10, RQ-12, RQ-13, RQ-16, RQ-18, RQ-19 e RQ-17 na parte da trilha) foram conferidas contra código + CT e estão OK.

| RQ | Cláusula | Passo PRD | CT | Código | Resultado |
|---|---|---|---|---|---|
| RQ-03 | Customizações da instalação viram settings | 2, 3, 5, 7 | CT-01, CT-27 | `ConfiguracoesDoKit`, `propagarParaOSettings()` | ⚠️ atendido, mas a tela pode ficar **ingravável** com valor legítimo do `.env` — QA-01 |
| RQ-06 | Seleção pelo Enum `Color` | 2, 4, 5 | CT-05, CT-36 | `Select` com `CustomizadorDaInstalacao::CORES` | ⚠️ 16 das 26 cores reais de `Color`; as outras 10 são aceitas no boot e travam a tela — QA-01 |
| RQ-08 | Dados de e-mail | 2, 4, 5 | CT-01, CT-21, CT-25 | aba E-mail + `encrypted()` | ⚠️ 3 dos 9 mailers (QA-01); segredo no HTML (QA-02); remetente esvaziável (QA-03) |
| RQ-11 | TODOs de settings implementados | 3, 7, 9 | CT-17…CT-20, CT-32 | `kit.tabelas.*`, `configuraTable()` | ⚠️ 3/4 por ADR-09 (densidade não existe no Filament 5) — **declarado**, não omitido |
| RQ-14/15 | Permissão, semeada desde o início | 4, 8 | CT-13…CT-16, CT-33 | `ExigePermissaoDaTela` | ✅ `admin` tem; `master_global` pelo `Gate::before`; `infra`, `admin_app` e `panel_user` **não** — conferido no banco de desenvolvimento **e** por CT |

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | nenhuma RQ órfã; 3 RQ com ressalva (QA-01) |
| B | Fronteiras e dados | ❌ | QA-01, QA-03; espaço em branco, `maxLength`, bordas de paginação e porta conferidos |
| C | Matriz de permissão | ✅ | 5 papéis × (abrir, salvar) — CT-13/15/16/33 cobrem as duas colunas |
| D | Observabilidade real | ⚠️ | log **lido de verdade** (`storage/logs/configuracoes-2026-08-24.log`); sem PII e sem segredo; QA-04, QA-05 |
| E | Performance | ✅ | 2 queries por boot, medidas (`hasTable` + leitura do grupo); sem N+1 |
| F | UX de erro | ❌ | QA-01 é beco sem saída; mensagens em pt-BR, estado preservado, sem vazar interno |
| G | Tema e cor | ⏭️ | nenhum Blade/CSS novo no diff (só componentes do Filament); inspeção visual impossível sem MCP |
| H | Acessibilidade | ⏭️ | os CT-B não chamam `assertNoAccessibilityIssues()`; não verificado |
| I | Segurança da superfície nova | ❌ | QA-02, QA-06; sem IDOR (a tela não tem registro), sem `DB::raw`, sem mass assignment novo |
| J | Regressão adjacente | ✅ | suíte completa verde antes deste gate (894 casos, browser 42 verdes / 5 pulados pré-existentes) |
| K | Adequação da suíte | ⚠️ | oráculos fortes (banco, tipo, visibilidade, renderizado); mutação: `IdentidadeDoKit` **100%** (20/20), listener **84,75%**, `BooleanoDoEnv` 85,71% (o único sobrevivente é artefato do recorte de arquivos); QA-07 |

## Débitos Aceitos

- **QA-05**, **QA-07**, **QA-08** — registrados em `03-progresso.md`.
- `mail_password` fica em claro em `config('mail.mailers.smtp.password')` depois de `aplicarNaConfig()`. É **inevitável e correto**: é assim que o `MailManager` autentica. Exposição só por quem já tem shell (`php artisan config:show`).
- Sem `darkModeBrandLogo` (declarado em `00-requisito.md` → Fora desta entrega). Vale registrar que a lacuna **muda de natureza** agora: antes não havia logo; com `brandLogo` configurável, uma logo escura fica ilegível em um dos dois temas.
- `IdentidadeDoKit` garante que o arquivo existe no disco, **não** que ele é servível: sem `public/storage`, a URL responde 404 e a guarda não detecta. O `kit:install` roda `storage:link` com `callSilently` dentro de uma task que sempre devolve `true` (`app/Console/Commands/KitInstall.php:351-356`), então a falha (Windows sem privilégio de symlink) é invisível. **Pré-existente** (avatar e logo de organização já dependiam do link) → destino 4.

## Hipóteses Rejeitadas

Custaram o mesmo que os achados e ficam registradas para não voltarem no próximo ciclo.

1. **`->media(IdentidadeDoKit::arteDoLogin())` é escalar, então a arte congelaria no boot** (o próprio PRD registra a dúvida). **Rejeitada**: o configurador é uma `Closure` guardada (`vendor/caresome/filament-auth-designer/src/Concerns/HasPages.php:36-41`) e só é executada em `AuthDesignerPlugin::boot()` (`src/AuthDesignerPlugin.php:53-56` → `configureRepository()` em `:89-91`), chamado por `Panel::boot()` (`vendor/filament/filament/src/Panel.php:111`) — que roda no request, **depois** do `KitServiceProvider::boot()`. CT "veste as telas de login com a arte gravada" confirma pelo comportamento.
2. **XSS por `nome_da_aplicacao` no brand dos painéis** (valor de banco renderizado na tela de login, pré-autenticação). **Rejeitada**: `vendor/filament/filament/resources/views/components/logo.blade.php:46` usa `{{ $brandName }}` e as três Closures declaram `: string` — não `Htmlable` —, então o Blade escapa.
3. **A trilha vaza a senha**. **Rejeitada**: verificado na tabela `audits` real — `old_values` e `new_values` gravam `••••••` nos dois lados; `user_id`/`user_type` preenchidos; `auditable` resolve para a linha real de `settings` (`SettingsProperty#33`).
4. **Alinhamento parcial da config quando falta propriedade** (metade do banco, metade do `.env`). **Rejeitada**: `aplicarNaConfig()` monta o array inteiro e chama `config()` **depois** do laço, então `MissingSettings` impede qualquer aplicação parcial.
5. **Existe estado em que a aplicação sobe sem nome, sem cor e sem remetente** (pergunta explícita da auditoria). **Rejeitada nos quatro cenários**: tabela ausente (no-op silencioso), grupo vazio (`MissingSettings` → `.env`, medido: `app.name` = valor do `.env`, `GET /admin` = 200, paleta resolvida), banco quebrado (`catch (Throwable)` → `.env`), `--force` (apaga o SQLite, reescreve o `.env`, re-migra, re-semeia). `nome_da_aplicacao`, `rotulo_da_organizacao` e `rotulo_das_organizacoes` são `required()` e rejeitam até espaço em branco. O único vazio alcançável é o **remetente** — QA-03.
6. **`->visibility('public')` seria decorativo no disco `public`**. **Parcialmente**: o vendor diz que o default já é público *nesse* disco (`BaseFileUpload.php:493-495`), então a chamada é redundante — mas é correta, barata e sobrevive a uma troca de disco. Não é defeito; e o oráculo de CT-10 é a visibilidade no disco, que é o oráculo certo.
7. **A permissão não chega a papel nenhum** (`PapeisSeeder` não foi editado). **Rejeitada**: `View:ConfiguracoesDoKit` existe (id 263 no banco de desenvolvimento) e está no papel `admin`; nenhum outro papel a tem.
8. **N+1 ou custo relevante no boot**. **Rejeitada**: 2 queries, medidas com `DB::getQueryLog()`.
9. **`itensManuais()` deveria ter perdido a linha da arte do login** (o PRD diz "sai da lista"). **Rejeitada como defeito**: a linha foi **reescrita** apontando para a tela, o que informa mais, e há CT afirmando os sete itens.

## Suspeitas Não Confirmadas

- Envio de e-mail com remetente nulo lançando na cara do usuário: neste ambiente o pacote de log de e-mail estoura antes (`mail_logs.body` NOT NULL) **com e sem** remetente, então a exceção final não foi observada. A cadeia até `Mailer::$from === null` está confirmada (QA-03).
- Upload de 30 MB: não foi rejeitado **e** não foi gravado (`favicon` ficou `null`, sem erro de formulário). Pode ser artefato do `UploadedFile::fake()->size()` em teste de componente. Não reproduzido de forma confiável; o achado de `maxSize()` (QA-06) se sustenta pela leitura do código e pela instrução do vendor, não por esta sonda.

## Não Verificado

- **Playwright MCP** (proibido nesta execução): inventário de elementos × cobertura dos CT-B, screenshot nos dois temas (dimensão G) e console/rede. Dimensão G ficou só na varredura estática — que não achou nada porque o diff não traz Blade nem CSS.
- **Acessibilidade** (dimensão H): tab order, foco após submit e `assertNoAccessibilityIssues()` na tela nova.
- **`kit:install --force` de ponta a ponta**: não executado (apaga o banco do worktree). Analisado por leitura, mais o CT "desfaz e refaz a migration de settings sem quebrar".
- **Mutação em `app/Settings/ConfiguracoesDoKit.php`, `app/Support/CorPrimaria.php` e na Page**: o Pest honrou apenas **um** `--path` por execução (a armadilha que a própria skill registra), e eu medi três classes em três execuções. As demais ficaram sem medição.
- **`persistTabInQueryString()`**: nenhum CT-B exercita a aba vinda da URL.
