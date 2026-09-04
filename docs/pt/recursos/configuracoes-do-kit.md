---
title: "Configurações do kit em /admin"
parent: Recursos
grand_parent: Português
nav_order: 6
---

# Configurações do kit em `/admin`

O que a instalação perguntou — e mais um punhado de coisas que antes só se mudava editando arquivo — vive em **`/admin/configuracoes-do-kit`** — no menu do painel a tela se chama **Configurações da aplicação**, porque depois de instalado o kit é a procedência, não o produto —, em seis abas. Nada de `.env`, nada de deploy.

| Aba | O que você troca |
|---|---|
| **Identidade** | nome da aplicação, cor primária (a paleta do Filament **ou** um hexadecimal livre), logo da marca, favicon e a arte das telas de autenticação |
| **E-mail** | transporte (`log`, `array`, `smtp`), servidor, porta, criptografia, usuário, senha e remetente |
| **Tabelas** | linhas por página, linhas listradas, persistência do recorte do usuário e colunas arrastáveis — os defaults de **toda** tabela dos três painéis |
| **Registro** | cadastro sem convite no `/app`, aprovação manual e validação de e-mail ([detalhes](../autenticacao/registro-aberto.md)) |
| **Login** | os quatro provedores de login social, cada um com interruptor, *Client ID* e *Client Secret* (cifrado), e o rodapé da tela de login ([detalhes](../autenticacao/login-social.md)) |
| **Kit** | hub de navegação em cartões, e como o seu negócio chama cada organização (singular e plural) |

Tudo é gravado pelo `spatie/laravel-settings` na tabela `settings`, com a tela vindo do `filament/spatie-laravel-settings-plugin` — os dois já estavam instalados no kit e sem uso até esta versão.

## Quem manda: o banco ou o `.env`?

Esta é a pergunta que decide se a tela é útil ou decorativa, e a resposta é uma só:

> **O banco vence em tempo de execução. O `.env` semeia a primeira gravação e é o plano B.**

Como isso funciona sem que nenhum consumidor saiba que o settings existe:

1. A migration `database/settings/*_create_kit_settings.php` semeia cada propriedade com o valor **de `config(...)`**, que vem do `.env`. Numa instalação nova, a cor e o nome que você escolheu no `kit:install` chegam ao banco sozinhos — o `migrate` roda depois de o instalador ter escrito o arquivo.
2. `App\Providers\KitServiceProvider::configureSettingsDoKit()` sobrepõe a configuração do processo com o que está no banco, uma vez por request e por comando artisan.
3. `App\Support\CorPrimaria`, os três `PanelProvider`, a configuração global de tabela e o próprio `MailManager` do Laravel continuam lendo `config()`. Nenhum deles foi alterado.

O que acontece em cada situação:

| Situação | Quem vence |
|---|---|
| a propriedade tem linha no banco | **o banco** |
| a propriedade não tem linha (você acrescentou uma e não migrou) | o `.env`, com um `warning` no log |
| a tabela `settings` não existe (antes do primeiro `migrate`) | o `.env`, em silêncio |
| o banco está inacessível | o `.env`, com um `warning` |
| `kit:install` numa instalação nova | o `.env` → a migration leva os valores para o banco |
| `kit:install --force` | apaga o banco, reescreve o `.env` e re-migra → o banco nasce igual ao `.env` novo |
| `kit:install --custom` num projeto já instalado | reescreve o `.env` **e** grava no settings — as duas fontes ficam iguais |

**Não existe interruptor para "usar ou não o settings"**, e isso é decisão, não esquecimento: uma flag seria uma terceira fonte da verdade, que é justamente o problema que a regra acima resolve. Para desligar, `php artisan migrate:rollback` na migration de settings — sem linha na tabela, o alinhamento é no-op e o `.env` volta a ser a única fonte.

## Cor: lista fechada e cor livre

São dois campos, e a precedência é declarada:

**hexadecimal válido → nome da paleta → padrão do Filament.**

O hexadecimal vence porque é o campo mais específico: quem digita `#7c3aed` escolheu aquela cor, enquanto o seletor da lista tem valor padrão e pode nunca ter sido tocado. Valor fora do formato (`#abcd`, `azul`, `#gggggg`) é **ignorado** e a resolução cai para o nome — a mesma tolerância que o kit já tinha para nome de cor inválido, e pelo mesmo motivo: isto roda no boot de todo painel, e uma exceção ali derrubaria **toda** página do projeto, não uma tela.

Dentro de `/app/{organização}`, a cor da **organização** continua vencendo as duas.

## Permissão

Uma só: **`View:ConfiguracoesDoKit`**, gerada pelo `ShieldPermissionsSeeder` e entregue ao papel `admin` pelo `PapeisSeeder` — sem nenhuma lista para editar, porque a matriz do papel é a do painel inteiro. `master_global` entra pelo `Gate::before`; `infra` e `panel_user` não recebem.

É uma permissão para abrir **e** para salvar, de propósito. O `canEdit()` do plugin desabilita o formulário mas **não esconde valor** — o próprio README do pacote diz isso por escrito —, e esta tela guarda a senha do SMTP. Um papel "só leitura" aqui seria um papel que lê credencial.

## Teto de upload: 10 MB, e onde mudar

Todo upload do kit — a logo, o favicon e a arte do login desta tela, a logo da organização em
`/admin/organizacoes` e os anexos de Projeto — aceita arquivo de **até 10 MB**, e **recusa SVG**.

O número é **uma** chave, no `.env`:

```dotenv
# Em MEGABYTES. Vazio, 0 ou ausente = 10.
KIT_UPLOAD_MAXIMO_MB=10
```

Ela alimenta `config('kit.uploads.maximo_em_kb')` — a config guarda **kilobytes**, porque é a
unidade que o `->maxSize()` do Filament e a regra de upload temporário do Livewire recebem. A
multiplicação por 1024 vive num lugar só, no `config/kit.php`, e quem lê a chave é
`App\Support\TetoDeUpload`. Não há campo na tela para isto de propósito: é decisão de
instalação, não de operação diária.

**Um upload atravessa quatro limites, e o menor manda.** Eles não recusam igual, e é isso que
torna o desalinhamento caro:

| Camada | Onde | Valor no kit | Como aparece o erro |
|---|---|---|---|
| nginx | `docker/nginx/nginx.conf` | `client_max_body_size 60M` | falha de rede no console |
| PHP | `docker/php/uploads.ini` | `upload_max_filesize=52M`, `post_max_size=60M` | idem |
| Livewire (upload temporário) | alinhado à chave do kit por `KitServiceProvider`, com 1 MB de folga | 11 MB | 422 no XHR, erro genérico |
| Filament (`->maxSize()`) | a chave do kit | 10 MB | **mensagem em português, no campo** |

Só a última recusa com mensagem clara — por isso o kit alinha o Livewire à chave em vez de deixar
o default dele (12 MB) mais frouxo que a tela.

**Para subir muito o teto**, mude junto:

1. `KIT_UPLOAD_MAXIMO_MB` — cobre a tela e o Livewire de uma vez;
2. acima de 52 MB, `docker/php/uploads.ini` (`upload_max_filesize` e `post_max_size`);
3. acima de 60 MB, `docker/nginx/nginx.conf` (`client_max_body_size`).

⚠️ **Fora do Docker do kit, o PHP costuma vir com `upload_max_filesize=2M` de fábrica.** Ali o
teto real é 2 MB, não o da chave — e o erro aparece como falha de rede, sem mencionar tamanho.
Confira com `php -i | grep upload_max_filesize` antes de culpar o kit.

## Por que SVG é recusado

SVG é XML, e XML aceita `<script>`. A logo, o favicon e a arte do login são servidos pelo
**mesmo origin** da aplicação, com visibilidade pública: abrir a URL de um SVG enviado executaria
o script com acesso ao cookie de sessão — XSS armazenado. Quem envia é o `admin`, que já tem
acesso total, então é escalada de insider e não porta anônima; num starter kit vale fechar.

A barreira é a regra `mimes` do **Laravel** (não o `->image()` do Filament, que é outra coisa e
aceita `image/*`, SVG incluído), com a lista de formatos em
`ConfiguracoesDoKit::FORMATOS_DE_IMAGEM`: jpg, jpeg, png, gif, bmp, webp, avif, heic, heif, **ico**,
**tif** e **tiff**. SVG é o único formato de imagem fora, e é o único que carrega script.

E ela **não** olha a extensão: o MIME vem do conteúdo do arquivo no disco temporário, então
renomear `logo.svg` para `logo.png` não passa. Nos anexos de Projeto, onde uma allow-list fecharia
o campo para PDF e planilha, a regra recusa apenas `image/svg+xml`.

## Trilha de alterações

Toda alteração aparece em **`/infra/audits`**, com quem mudou, quando, o nome da propriedade e os valores antigo e novo. Uma linha por propriedade alterada; salvar sem mudar nada não gera registro.

A senha de e-mail é **cifrada** na tabela `settings` e entra na trilha **mascarada** (`••••••`): o registro diz que o segredo mudou, nunca qual é.

Dois detalhes que valem para quem for mexer nisso:

- A trilha **não** vem da trait `App\Traits\AuditsFillables`. Um settings do spatie não é um model Eloquent, e apontar o repositório dele para um model com a trait auditaria só a **criação** — a alteração de propriedade existente passa por `upsert()`, que não dispara evento de Eloquent. A trilha sai de um listener de `SavingSettings`, que é o único ponto do pacote com valor antigo e novo juntos.
- O evento gravado é `settings-updated`, e não `updated`, para o botão "restaurar" da trilha **não** aparecer: ele faria `fill(['nome_da_aplicacao' => …])` numa linha cujas colunas são `group`/`name`/`payload`.

## Isto não é o settings de uma organização

A identidade visual de um tenant (cor e logo por organização) continua sendo CRUD comum em **`/admin/organizacoes`**, nas colunas `cor_primaria` (hexadecimal livre), `cor_primaria_nome` (a mesma paleta do Filament deste settings — o hexadecimal vence quando preenchido) e `logo` do model `Tenant`, e ela vence a do kit dentro de `/app/{slug}`. Nada foi movido para cá.

## O que ficou fora, e por quê

| Item | Por quê |
|---|---|
| driver, host e nome do **banco** | trocar depois do `migrate` não é reescrita de configuração, é outra instalação |
| ligar/desligar a **multi-organização** | as tabelas de permissão só nascem com a coluna de contexto se `permission.teams` estiver ativo **antes** do migrate; o caminho é `php artisan kit:tenancy` |
| **e-mail e senha do administrador** | o `UsuarioAdminSeeder` não sincroniza, de propósito (ele roda em todo `db:seed`, e atualizar senha ali reverteria em silêncio a troca feita no perfil). Um campo que não troca a credencial é pior que campo nenhum — o caminho é a tela de perfil |
| **slug** do CRUD de organizações | é lido no registro de rota, não no render, e a URL é identificador permanente |
| **idiomas** do painel | a internacionalização do kit não está feita: ligar um segundo idioma hoje troca metade da tela. Ver o bloco `idiomas` de `config/kit.php` |
| **retenção** das trilhas | não é pergunta da instalação; fica no `.env`, onde o zero tem semântica documentada |

## Desempenho

O alinhamento custa **uma** query por boot (o grupo inteiro vem de uma só leitura). Se isso incomodar, `SETTINGS_CACHE_ENABLED=true` no `.env` — lembrando que, com o cache ligado, gravar pela tela exige `php artisan settings:clear-cache`.

## Acrescentando uma propriedade

Três lugares, sempre, e o teste `tests/Kit/ConfiguracoesDoKitTest.php` reprova se você esquecer um:

1. a propriedade tipada em `app/Settings/ConfiguracoesDoKit.php`;
2. a linha em `ConfiguracoesDoKit::mapaDeConfiguracao()` (propriedade → chave de `config()`);
3. o par `add()` / `deleteIfExists()` numa migration nova em `database/settings/`.

E o campo na aba certa de `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`.

