# Plano de Ação — A arte do login exibe o nome da aplicação

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feature/identidade-visual-da-organizacao/` — é ela que criou `IdentidadeDoKit` e o campo `arte_do_login`
- **Motivo**: a arte padrão é um arquivo estático com o nome do kit escrito dentro. Toda instalação nasce com "starter-kit-easy" na tela de login, mesmo depois de o `kit:install` trocar o `APP_NAME`.
- **Toca infra compartilhada?**: **sim** — `IdentidadeDoKit::arteDoLogin()` é consumida pelos **três** PanelProviders em **10** pontos (`/admin` 3, `/app` 4, `/infra` 3), e mais **duas telas herdam** a chave de outra sem `->media()` próprio: o bloqueio de sessão (herda `login`) e o desafio de 2FA (herda `password-reset`). Regressão obrigatória.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Arte exibe o nome da aplicação | 1, 2 | |
| RQ-02 | Nome lido em tempo de execução | 2 | `config('app.name')` a cada render |
| RQ-03 | Somente o nome — a segunda linha sai | 1 | |
| RQ-04 | README atualizado | 4 | |
| RQ-05 | Capturas regeradas | 5 | |
| RQ-06 | Arte própria continua funcionando | 2 | o `doDisco()` continua tendo precedência |

## Objetivo

Fazer a arte padrão das telas de autenticação mostrar o **nome da aplicação** em vez de "starter-kit-easy", lido em tempo de execução, para que toda instalação nasça com uma tela de login que já é dela. Quem enviar a própria arte pelas Settings continua com ela.

## Contexto

`public/images/auth/login.svg` traz duas linhas de texto fixas: `starter-kit-easy` e `Laravel 13 · Filament 5 · pronto para uso`. `IdentidadeDoKit::arteDoLogin()` (`:74`) devolve `asset()` desse arquivo quando não há arte customizada, e os três painéis a usam nas telas de autenticação — **10 chamadas**, mais duas telas que herdam a chave de outra.

Resultado: quem instala o kit, roda `kit:install` e customiza o `APP_NAME` continua com o nome do kit na tela de login até substituir a imagem à mão.

## Análise dos Arquivos Existentes

### `app/Support/IdentidadeDoKit.php`

`arteDoLogin(): string` — `doDisco('kit.identidade.arte_do_login') ?? asset(self::ARTE_PADRAO)`. A precedência da arte customizada (`doDisco`) **não muda**; o que muda é o fallback.

### `public/images/auth/login.svg`

O SVG de 1,3 KB. Vira a base da view Blade do passo 1 e **é removido** — dois lugares com o mesmo desenho divergem.

### Os três PanelProviders

10 chamadas de `->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))`. **Nenhuma muda** — a assinatura continua devolvendo uma string usável em `<img src>`.

**O `alt` é o que arma o falso ✅ desta feature**: ele já é `config('app.name')` hoje, então
`assertSee(config('app.name'))` numa tela de login **passa antes de uma linha ser escrita**. Todo
oráculo tem de decodificar o `src` e afirmar sobre o documento. Ver `04-casos-de-teste.md`.

## Autorização

Nenhuma. A arte é pública por natureza: aparece antes do login.

## Rotas

Nenhuma rota nova — é a decisão da ADR-01.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Telas de autenticação dos três painéis | Blade (Auth Designer) | `/app/login`, `/admin/login`, `/infra/login` e os pares de recuperação de senha e verificação de e-mail | nenhuma — a pessoa **vê** a arte | Não |

**Gate de CT-B** — *revisado em 2026-09-01; a primeira versão dizia **Sem CT-B** e estava errada*:

As três afirmações de conteúdo — o nome está lá, o texto antigo não está, o XML é válido — de fato se
provam em HTTP, e é onde vivem CT-01…CT-11. O que a primeira versão descartou como "comportamento do
`<img>`, não do kit" **deixa de ser genérico** quando a fonte da imagem passa a ser um data URI que
**nós construímos**: mime errado, `;base64` esquecido, payload truncado e escape do atributo produzem
todos um `<img>` com `naturalWidth === 0` — e **todo cenário HTTP continua verde**, porque a string
está no documento. Para o HTTP, "a imagem pintou" e "a imagem quebrou" são a mesma resposta.

**1 CT-B**, com oráculo que o projeto já tem (`assertNoBrokenImages()`), implementado como
**reancoragem** de `tests/Browser/IdentidadeVisualPadraoTest.php:33` — que quebra com esta feature de
qualquer jeito — mais uma linha. Não é arquivo novo, e por isso não há `05`.

> **Correção de premissa**: o argumento original a favor do CT-B, no `04`, era consertar a "arte
> quebrada nas capturas". Esse defeito **não existe** — ver a correção no `00`. O CT-B fica de pé
> pelo motivo inverso e mais forte: a arte **funciona hoje**, e é isso que a mudança pode quebrar.
> Ele é guarda de regressão.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é tocada.

## Variáveis de Ambiente

Nenhuma nova. `APP_NAME` já existe e já é customizado pelo `kit:install`.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`identidade-visual-da-organizacao`** — a precedência da arte customizada é a razão de ser daquela feature e **não muda**. A suíte dela é a regressão principal.
- **As capturas do `composer art`** — **três** mostram a arte: `art/login.png`, `art/login-social.png` e `art/app-bloqueio-social.png`. Todas exibem hoje `starter-kit-easy` + a segunda linha, e passam a exibir o nome da aplicação. `login-social` e `app-bloqueio-social` saem do comando; **`login.png` não está em `KitArte::IMAGENS`** e precisa de decisão na implementação (regerar à mão, adotar no comando — exige o par `->screenshot()` **e** a linha em `IMAGENS` — ou aposentar).
- **As quatro asserções ancoradas no arquivo removido** — `tests/Kit/IdentidadeDoKitTest.php` (duas), `tests/Kit/ConviteTest.php:370` e `tests/Browser/IdentidadeVisualPadraoTest.php:33` referenciam `images/auth/login.svg` ou `ARTE_PADRAO`. Todas quebram **por mudança deliberada** e são reancoradas pelo `04`.
- **Dois comentários passam a mentir** — `config/kit.php:99` e `app/Models/Tenant.php:149` citam o arquivo removido. Sem oráculo; item de checklist antes do commit.

## Rollback

`git revert`. Sem migration, sem dado, sem config. O arquivo `public/images/auth/login.svg` volta junto.

## Dependências

Nenhuma.

## Riscos

- **Nome com caractere especial quebra o SVG.** `&`, `<` e `>` são sintaxe de XML; um nome como "Silva & Cia" invalidaria o documento inteiro e a tela perderia a arte. Mitigação: escape XML no passo 2, com caso de teste próprio.
- **Nome muito longo transborda.** O `<text>` do SVG não quebra linha sozinho. Mitigação e decisão declarada na ADR-02.
- **HTML maior** — o data URI acrescenta ~1,8 KB por tela de autenticação. Aceito na ADR-01.

## Channel de Log da Feature

**Nenhum log e nenhum channel novo.** A geração da arte não tem decisão de fluxo: é uma string montada a cada render de tela pública. `IdentidadeDoKit::doDisco()` já loga o caso que **tem** decisão — arquivo declarado e ausente no disco, no channel `configuracoes` — e esse caminho não muda.

## Estrutura de Implementação

### 1. O SVG vira uma view Blade, com o nome e sem a segunda linha (RQ-01, RQ-03)

> Skills: `laravel-best-practices`, `tailwindcss-development`, `ponytail`

- **Path novo**: `resources/views/svg/arte-do-login.blade.php`
- Conteúdo: o SVG atual, com duas mudanças —
  1. o `<text>` de 44px passa a imprimir o nome recebido;
  2. o `<text>` de 20px (`Laravel 13 · Filament 5 · pronto para uso`) **é removido** (RQ-03).
- O gradiente, o brilho e os círculos ficam: são forma, não texto (premissa declarada no `00`).
- **Path removido**: `public/images/auth/login.svg` — a view passa a ser a única fonte.
- **Logs**: nenhum.

### 2. `arteDoLogin()` devolve o data URI da view (RQ-01, RQ-02, RQ-06)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/IdentidadeDoKit.php`
- `doDisco('kit.identidade.arte_do_login')` continua **primeiro** — arte customizada vence (RQ-06).
- O fallback passa a ser: renderizar `svg.arte-do-login` com o nome e devolver
  `'data:image/svg+xml;base64,'.base64_encode($svg)`.
- **O nome vai escapado**: `e(config('app.name'))` no Blade já faz escape de `&`, `<` e `>` — é o comportamento default do `{{ }}`, e é o que o teste do risco 1 verifica.
- `ARTE_PADRAO` deixa de apontar para o arquivo. Manter a constante só se algum outro ponto a usar — conferir com `grep` antes de remover.
- **Logs**: nenhum.

### 3. Verificação da suíte

- `vendor/bin/pint --dirty --format agent`
- `composer types:check`
- `php artisan test --testsuite=Kit,Tenancy --parallel --compact`

### 4. README (RQ-04)

> Skills: nenhuma

- **Path**: `README.md` e `README.en.md`, na seção de identidade visual.
- Dizer: a arte padrão das telas de autenticação **mostra o nome da aplicação**, lido de `APP_NAME` em tempo de execução; para trocar a imagem, o campo continua em `/admin/configuracoes-do-kit`.

### 5. Regerar as capturas (RQ-05)

> Skills: nenhuma

- `composer art`, e conferir **as três** que mostram a arte, abrindo cada PNG:
  `art/login.png`, `art/login-social.png`, `art/app-bloqueio-social.png`.
- `login-social` e `app-bloqueio-social` estão em `KitArte::IMAGENS` e saem do comando.
- `art/login.png` **não** está: decidir entre regerar à mão, adotar no comando (exige o par
  `->screenshot(filename: 'login')` **e** a linha `'login'` em `KitArte::IMAGENS`, senão o comando a
  reporta como ignorada) ou aposentar. Registrar a decisão nos Desvios.
- `art/admin-anti-robo.png` e `art/admin-configuracoes-login.png` são telas de Settings autenticadas,
  **não** têm a arte, e ficam fora.
- Conferir a imagem antes de commitar — captura errada é pior que captura velha.

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada decidiu:
> 1. **Sem rota nova** — data URI resolve com menos peças, e de quebra conserta a arte quebrada nas capturas (ADR-01).
> 2. **Uma fonte só** — a view Blade substitui o arquivo estático; manter os dois é garantir divergência.
> 3. **Sem log, sem channel** — não há decisão de fluxo a registrar.
> 4. **Escape pelo `{{ }}`** do Blade, não por `htmlspecialchars` à mão: é o default e já está certo.
> 5. ~~**Sem CT-B**~~ — **1 CT-B**, por reancoragem de um cenário que já existe. Ver o gate revisado.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-*-browser.md`: o único CT-B é uma linha acrescentada a um
> cenário de navegador **existente**, e um runbook para isso seria burocracia. Ver o gate revisado.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] `composer art` e conferência visual de cada captura de tela de autenticação

## Commits

- `✨ feat(identidade): a arte padrão do login exibe o nome da aplicação`
- `🎨 chore(art): regera as capturas com a arte nova`
- `📝 docs(readme): a arte do login usa o nome da aplicação`
