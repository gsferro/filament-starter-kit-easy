# Requisito — fix: cadastro por convite cai no painel do papel

## Fonte

- **Origem**: issue #191 do repositório `gsferro/filament-starter-kit-easy`, aberta em 2026-11-13
  — "Cadastro por convite com login unificado desligado termina em 403 quando o papel é de outro
  painel que não o /app"
- **Pedido de execução**: chat, 2026-11-13 — "veja a issue aberta e corrija" (junto com fechar os
  PRs abertos, lançar tag de fix e escrever o roteiro de atualização)
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: **alta** — texto escrito pelo solicitante na issue, transcrito abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> ## Problema
>
> Com o **login unificado desligado**, quem aceita um convite para um papel de **outro painel que não o `/app`** (ex.: `infra`, ou `admin` sem organização) termina o cadastro em **403**.
>
> ### Como reproduzir
>
> 1. Login unificado desligado (`ConfiguracaoDoLogin::unificado()` = `false`).
> 2. Convidar um e-mail novo com o papel `infra` (painel `/infra`, sem organização).
> 3. Abrir o link do e-mail e criar a conta.
> 4. Resultado: redirect para `/app` → **403**, porque a conta só tem acesso ao `/infra`.
>
> Esperado: cair no painel do papel do convite (`/infra`).
>
> ### Causa
>
> - O link do convite é sempre o registro do painel `app`: `Convite::url()` → `Filament::getPanel('app')->route('auth.register', …)` (em `app/Notifications/ConviteDeAcesso.php`). Então o cadastro sempre acontece com o `app` como painel corrente, qualquer que seja o papel.
> - `RespostaDeCadastro::toResponse()`, no ramo **desligado**, só chama `DestinoAposLogin::descartarPretendidaInacessivel($user)` e devolve `parent::toResponse()`, que é `redirect()->intended(Filament::getUrl())`. Sem pretendida, `Filament::getUrl()` é o painel **corrente** (`/app`), que a conta não acessa.
> - O comentário do ramo assume que "a única coisa errada aqui é a pretendida". Não é: o painel corrente também pode ser inacessível, e com convite de papel de outro painel ele **sempre** é.
>
> Com o login unificado **ligado** não acontece, porque `DestinoAposLogin::urlPara()` escolhe pelo(s) painel(is) acessível(is).
>
> ### Proposta
>
> No ramo desligado de `RespostaDeCadastro`, depois de descartar a pretendida inacessível:
>
> - se o painel corrente é acessível pela conta → comportamento atual (`parent::toResponse()`);
> - senão → o painel do papel do convite. Na prática, `DestinoAposLogin::paineisDe($user)`: com 1 painel, `Paineis::url($painel)`. Com mais de 1, o painel default dentre os acessíveis, porque no modo desligado não há tela de escolha. Com 0, o login, como hoje.
>
> Isso mantém a regra do ADR-04 de `wikis/specs/fix/destino-apos-cadastro/` (não há tela de escolha no modo desligado) e só troca o fallback "painel corrente" por "painel acessível".
>
> Casos de teste sugeridos (modo desligado):
>
> - convite `infra` sem organização → `/infra`;
> - convite `admin` sem organização → `/admin`;
> - convite `panel_user` com organização → `/app/{organizacao}` (comportamento atual, regressão);
> - pretendida inacessível + painel corrente inacessível → painel acessível, nunca 403.
>
> ### Observação de deploy (não é do código do kit)
>
> No mesmo caso apareceu uma URL `/public/app`. Isso vem do servidor, não do kit: o `DocumentRoot` da instalação era a **raiz do projeto**, com um `.htaccess` reescrevendo para `public/`. Para o caminho exato `/app`, o Apache encontra o diretório `app/` do Laravel e aplica `DirectorySlash` (`/app` → `/app/`). A regra de barra final do `public/.htaccess` então monta `/public/app`. Talvez valha uma nota no README de deploy: apontar o `DocumentRoot` para `public/` ou, se não der, usar `DirectorySlash Off` no `.htaccess` da raiz.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Com o login unificado desligado, quem conclui o cadastro por convite de papel cujo painel não é o `/app` cai no painel do papel, nunca em 403 | "Esperado: cair no painel do papel do convite (`/infra`)." | funcional | fechada |
| RQ-02 | Com o login unificado desligado, se o painel corrente é acessível pela conta, o destino continua o atual (pretendida acessível, senão o painel corrente) | "se o painel corrente é acessível pela conta → comportamento atual (`parent::toResponse()`)" | funcional | fechada |
| RQ-03 | Painel corrente inacessível e exatamente um painel acessível: o destino é a URL desse painel | "com 1 painel, `Paineis::url($painel)`" | funcional | fechada |
| RQ-04 | Painel corrente inacessível e mais de um painel acessível: o destino é o painel default dentre os acessíveis; não há tela de escolha | "Com mais de 1, o painel default dentre os acessíveis, porque no modo desligado não há tela de escolha" | funcional | fechada |
| RQ-05 | Painel corrente inacessível e nenhum painel acessível: o destino é o login | "Com 0, o login, como hoje." | funcional | fechada |
| RQ-06 | Pretendida inacessível com painel corrente inacessível termina num painel acessível, nunca em 403 | "pretendida inacessível + painel corrente inacessível → painel acessível, nunca 403" | funcional | fechada |
| RQ-07 | O convite `panel_user` com organização continua caindo em `/app/{organizacao}` | "convite `panel_user` com organização → `/app/{organizacao}` (comportamento atual, regressão)" | funcional | fechada |
| RQ-08 | A documentação de deploy ganha a nota sobre `DocumentRoot` na raiz do projeto e `/public/app` | "Talvez valha uma nota no README de deploy: apontar o `DocumentRoot` para `public/` ou, se não der, usar `DirectorySlash Off` no `.htaccess` da raiz." | não-funcional | fechada |

## Perguntas ao Solicitante

| Qn | Pergunta | Recomendação | Estado |
|----|----------|--------------|--------|
| — | nenhuma | — | — |

## Premissas

| ID | Premissa | Origem | Estado |
|----|----------|--------|--------|
| P-01 | "O login" do RQ-05 é a tela de login do painel corrente **com a sessão encerrada**: entregar o login a uma sessão autenticada faz a tela de login do Filament redirecionar de volta ao painel corrente, que é o 403 que a issue quer evitar. "Como hoje" lido como "o destino de quem não entra em painel nenhum", não como o comportamento medido hoje (que é o 403) | decisão de desenho do step 4, falha fechado | vigente |
| P-02 | "Painel default dentre os acessíveis" (RQ-04): o painel default do Filament quando ele é acessível; senão o primeiro acessível na ordem de `Filament::getPanels()` | leitura do RQ-04, step 4 | vigente |
| P-03 | Com o painel corrente inacessível e uma pretendida **acessível** (de outro painel), a pretendida vence: a conta vai à URL completa dela. Responde a Q1 do `04` | o CT-75 de `wikis/specs/fix/destino-apos-cadastro/` já trava esse comportamento (`desligada admin` → `/admin/users`); a issue pede descartar só a pretendida inacessível | vigente |

## Fora de Escopo (declarado)

- Mudar o link do convite para o registro do painel do papel — a issue localiza a causa ali, mas a
  proposta corrige o destino e mantém o link
- O modo ligado (`kit.login.unificado = true`): a issue declara que ele não tem o defeito
- Configuração do servidor do dono (Apache/Nginx): o kit só documenta
