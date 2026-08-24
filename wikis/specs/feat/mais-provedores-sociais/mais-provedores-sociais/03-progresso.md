# Progresso — W8: mais provedores de login social

Branch: `feat/mais-provedores-sociais` · base `origin/main` = `cc3afd8` (v0.19.3)

## 1. O enum `ProvedorSocial`

- [x] `app/Support/ProvedorSocial.php` com os quatro casos
- [x] `rotulo()`, `icone()`, `propriedadeDeSettings()`
- [x] `emailVerificado()` com o `match` de quatro ramos, falha fechada em todos
- [x] `emailVerificadoNoGithub()` — a consulta a `/user/emails` com prova positiva
- [x] Log de alerta nos dois motivos de recusa do GitHub

## 2. `ConfiguracaoDoLogin` generalizado

- [x] `disponivel(ProvedorSocial)` no lugar de `googleDisponivel()`
- [x] `disponiveis()` para o blade
- [x] `rodapeDoLogin()` e `registroAberto()` intactos
- [x] Docblock atualizado, apontando para os ADRs desta wiki

## 3. Config

- [x] `config/services.php` — blocos `github`, `linkedin-openid`, `x`
- [x] `config/kit.php` — três `habilitado` novos com `filter_var` (falha fechada)
- [x] Comentário do bloco `login` reescrito para quatro provedores

## 4. O controller

- [x] `LoginComGoogleController` → `LoginSocialController`
- [x] `redirecionar(ProvedorSocial)` e `retorno(ProvedorSocial)`
- [x] As seis barreiras preservadas, agora por provedor
- [x] `emailVerificadoNoProvedor()` saiu do controller para o enum
- [x] Mensagens e logs nomeiam o provedor; e-mail mascarado; nenhum segredo

## 5. Rotas

- [x] `auth/{provedor}` com `auth.social.redirect` / `auth.social.callback`
- [x] Implicit enum binding → 404 automático fora do enum
- [x] Conferido: `php artisan route:list --path=auth` mostra as duas, e as URIs literais não mudaram

## 6. Blades

- [x] `resources/views/filament/auth/botoes-sociais.blade.php`
- [x] `resources/views/filament/auth/icones/{google,github,linkedin,x}.blade.php`
- [x] `botao-google.blade.php` removido
- [x] Render hook do `KitServiceProvider` apontando para a view nova
- [x] Nenhuma diretiva escrita dentro de comentário de blade (`.ai/rules/views.md`)
- [x] `data-provedor` em cada partial de ícone — o único oráculo de RQ-03 nas marcas monocromáticas

## 7. Settings

- [x] 9 propriedades novas em `App\Settings\ConfiguracoesDoKit`
- [x] 9 linhas novas em `mapaDeConfiguracao()`
- [x] **`encrypted()` corrigido de 1 para 4 segredos** (ADR-06)
- [x] `database/settings/2026_08_25_000000_add_provedores_sociais_to_kit_settings.php`
- [x] A normalização do `login_google_client_secret` no `up()`
- [x] `abaLogin()` com uma `Section` colapsável por provedor, gerada por laço
- [x] `mutateFormDataBeforeFill()` zera os quatro segredos, por laço
- [x] `segredoDoGoogleGuardado()` → `segredoGuardadoDe(ProvedorSocial)`
- [x] `helperText` por provedor dizendo onde criar o app e qual URI cadastrar
- [x] Migration rodada localmente sem erro

## 8. Testes

- [x] Helpers compartilhados em `tests/Pest.php`: `ligarProvedor()`, `usuarioSocialFalso()`
- [x] `configuracaoGravada()` **movida** de `tests/Kit/ConfiguracoesDoKitTelaTest.php` para
      `tests/Pest.php` (ganhou segundo consumidor — `.ai/rules/testes.md`)
- [x] `tests/Kit/LoginSocialGoogleTest.php` atualizado (rename de controller e de mensagem) —
      **61/61 verdes**, nenhum caso removido
- [x] `tests/Tenancy/LoginSocialGoogleTenancyTest.php` — 4/4 verdes sem edição
- [x] `tests/Kit/LoginSocialProvedoresTest.php` — CT-01..CT-19, CT-36..CT-42
- [x] `tests/Kit/SegredosDoSettingsTest.php` — CT-20..CT-35
- [x] `tests/Tenancy/LoginSocialProvedoresTenancyTest.php` — CT-43, CT-44
- [x] CT-B em `tests/Browser/`
- [x] Nenhum caso sai para a rede (`Socialite::fake` + `Http::fake`)

## 9. Documentação

- [x] `README.md` — seção "Login social: quatro provedores", com a tabela por provedor
- [x] `README.en.md` — a mesma seção, traduzida
- [x] Por provedor: onde criar o app OAuth, qual URI cadastrar, como a verificação é conferida
- [x] Por que Facebook e Discord ficaram fora, e o que faltaria para incluí-los
- [x] Roteiro do próximo provedor (quatro passos, nenhum arquivo de lógica)
- [x] `.env.example` — as 4 chaves de interruptor e as 6 de credencial, vazias
- [x] `CHANGELOG.md`

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse` — **0 erros**
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [x] `composer test:browser`
- [x] `php artisan route:list --path=auth`
- [x] `/ponytail:ponytail-review` no diff (step 6 / quality gate)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa | O código real diz | Correção aplicada |
|---|---|---|
| "o Socialite já suporta os cinco provedores" (instrução de escopo) | **não suporta Discord** — `vendor/laravel/socialite/src/Two/` não tem `DiscordProvider.php`, e a doc oficial remete a `socialiteproviders.com` | ADR-04 criado; Discord declarado fora de escopo no `00-requisito` e no PRD |
| "o Facebook tem um campo de verificação" (leitura otimista da lista de `$fields`) | `FacebookProvider.php:34` pede `verified`, que é de **conta**, legado, ausente na Graph v23.0 (`:27`); o caminho OIDC (`:134-167`) não traz `email_verified` | ADR-05 criado; Facebook declarado fora de escopo |
| "LinkedIn é um driver" | são **dois** — `linkedin` (legado, **sem** verificação) e `linkedin-openid` (**com** `email_verified`), com chaves de config diferentes (`SocialiteManager.php:94,108`) | o enum usa `linkedin-openid`; ADR-01 e ADR-03 registram |
| "o GitHub devolve `verified` no bruto" | `GithubProvider.php:73` avalia `primary && verified` e **descarta**: `:48` guarda só a string; e o `catch → return` de `:68-70` deixa o e-mail do perfil público quando a chamada falha | ADR-03: o kit refaz a consulta a `/user/emails` |
| "o `client_secret` do Google está cifrado no Settings, imite" | **está cifrado só na ida** — `addEncrypted` na migration, ausente em `encrypted()`; leitura devolve ciphertext e um save regrava em claro | ADR-06 criado; conserto entrou no escopo desta entrega |
| "a rota genérica custa uma lista branca para validar" (ADR-10 da wiki ancestral) | **implicit enum binding** do Laravel 13 devolve 404 sozinho; a lista branca é o enum | ADR-02 substitui aquela alternativa recusada |
| "os testes usam `route('auth.google.*')`" | **nenhum** usa — todos usam a URL literal; o único consumidor do nome era um blade | trocar o nome da rota ficou barato; o par de rotas virou um só |
| "`$user->email_verified` funciona" | funciona **só** no LinkedIn OpenID: `AbstractUser::map()` só atribui se `property_exists` (`:143`), e `$user['x']` lê o **bruto**, não os atributos (`:170-173`) | `getRaw()` explícito no enum, com o porquê no docblock |

### Auditoria Ponytail (step 6)

| # | Sugestão | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar `driver()` separado do `value` do enum — uma string, quatro usos | sim | `ProvedorSocial` |
| 2 | não criar interface nem classe por provedor | sim (recusadas no ADR-01) | — |
| 3 | não criar `whereIn()` além do enum binding: duas guardas para a mesma pergunta | sim | `routes/web.php` |
| 4 | não criar um blade por provedor: um laço e quatro partials de SVG | sim | ADR-08 |
| 5 | não criar channel de log novo: `autenticacao` já é o dono do assunto | sim | PRD |
| 6 | não criar serviço só para hospedar a consulta do GitHub | sim, com `ponytail:` e teto declarado | `ProvedorSocial` |
| 7 | não criar migration de dados separada da que acrescenta as propriedades | sim | ADR-06, alternativa 3 |
| 8 | usar `filter_var` da stdlib, não coerção própria | sim | enum e `config/kit.php` |

## Blockers

Nenhum.

## Desvios do Plano

- **Passo 8 — quatro arquivos de teste, não três como o `04` previa.** O `04` argumentou que
  R10–R12 tinham de ficar juntos de R8/R9 por causa do helper `configuracaoGravada()`. O
  argumento estava certo quanto ao helper e errado quanto à conclusão: mover a função para
  `tests/Pest.php` — que é o que `.ai/rules/testes.md` manda de qualquer forma quando ela ganha o
  segundo consumidor — libera o agrupamento. Feito assim.
- **A premissa de escopo "o socialite já suporta os cinco" era falsa**, e o efeito não é de
  implementação: RQ-01 é atendida em 3 de 5 provedores pedidos. Está declarado no
  `00-requisito.md` → Ambiguidades, no `## Cobertura do Requisito` do PRD e nos ADR-04/ADR-05.
  **Não** foi marcado como atendido.

## Notas de Implementação

- **O defeito do `encrypted()` é o achado mais caro desta rodada**, e ele não estava no requisito.
  Apareceu por seguir a instrução "imite o campo do Google": imitar literalmente teria replicado
  o defeito em três segredos novos. Confirma a lição de `.ai/rules/specs.md` — ao encontrar
  defeito numa fronteira, varra o padrão antes de consertar o ponto.
  - Também explica por que ele sobreviveu duas releases: `Crypto::encrypt(null)` devolve `null`,
    e o segredo é `null` em toda instalação de desenvolvimento e em toda a suíte. **O defeito só
    existe quando há valor** — a classe de defeito que nenhum teste de caminho felizocupa.
- **`checkIfPropertyExists()` do `SettingsMigrator` é `protected`** (`:139`), então "a propriedade
  já existe?" numa migration se pergunta pelo `catch (SettingDoesNotExist)` que o `update()`
  lança (`:83`). Custou uma iteração.
- **O PHPStan pegou uma decisão de segurança disfarçada de erro de tipo**: `$token` é declarado em
  `Two/User.php:14`, não em `AbstractUser`. Estreitar o tipo para `Two\User` é a decisão certa —
  usuário de OAuth 1.0 não tem token utilizável aqui, e sem token não há como refazer a consulta
  do GitHub, logo a resposta é NÃO. Mesmo padrão do `instanceof AbstractUser` que a wiki ancestral
  já tinha registrado.
- **O `throttle:10,1` passou a ser compartilhado** pelo grupo de rotas: dez por minuto por IP
  somando os quatro provedores, não dez por provedor. Registrado no ADR-02 como consequência
  aceita.

## Retrospectiva

- **Funcionou bem**: ler o `vendor/` provedor por provedor **antes** de desenhar. Foi isso que
  revelou que o eixo a abstrair era a verificação de e-mail, e não o redirect — e a wiki ancestral
  tinha previsto exatamente isso ao escrever que a extração feita com um caso "adivinha a forma".
  Com um provedor, a interface teria abstraído as três coisas idênticas e deixado de fora a única
  diferente.
- **Funcionou bem**: derivar os casos de teste do `00-requisito.md` em paralelo com a
  implementação, por outro agente. A independência deixou de ser uma promessa de processo e passou
  a ser um fato de execução.
- **Faltou no plano**: o PRD não previu que a instrução de escopo poderia estar factualmente
  errada em dois pontos (Discord e Facebook). A lição para a próxima wiki é tratar a instrução de
  escopo como fonte de **baixa fidelidade** sobre capacidade de biblioteca — o mesmo regime que a
  skill já aplica a requisito verbal — e conferir no `vendor/` antes de escrever a Cobertura do
  Requisito.
