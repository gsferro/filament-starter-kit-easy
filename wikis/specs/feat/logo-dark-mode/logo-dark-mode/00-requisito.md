# Requisito — feat/logo-dark-mode: Suporte a logo dark / light com unificação de campo

## Fonte

- **Origem**: pedido do solicitante colado no chat (texto estruturado em itens)
- **Data**: 2026-10-03
- **Autor / solicitante**: usuário da sessão
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> Título: Suporte a logo dark / light com unificação de campo
>
> Contexto: Kit possui apenas o campo "Logo da marca" (usado para o modo light). A tela de lock‑screen (tenant) usa a classe .fi-auth-media (width / height 100 %) → imagem distorcida.
>
> Requisitos
> 1. Banco – manter campo logo (light). Criar campo logo_dark nullable em organizations.
> 2. Settings – toggle unifica_logo_marca (default true).
> - true → usa somente o campo existente logo.
> - false → exibem dois campos obrigatórios:
> - logo (light) – helper "fundo claro recomendado".
> - logo_dark (dark) – helper "fundo transparente recomendado".
> 3. Componente de exibição (login e lock‑screen):
> - Remover classe fi-auth-media.
> - Envolver <img> em wrapper flex display:flex;justify-content:center;align-items:center;.
> - Aplicar ao <img>: style="max-width:100%;max-height:100%;object-fit:contain;".
> - Lógica:
>   $useUnified = config('kit.unifica_logo_marca');
>   $darkMode = $this->isDarkMode(); // Filament helper ou request()->prefersDarkMode()
>   if ($useUnified) {
>       $logo = config('kit.logo');
>   } else {
>       $logo = $darkMode ? config('kit.logo_dark') : config('kit.logo');
>   }
>   // tenant overrides:
>   $tenant = tenant(); // helper do pacote
>   if ($tenant) {
>       $logo = $useUnified
>           ? $tenant->logo ?? $logo
>           : ($darkMode ? $tenant->logo_dark ?? $logo : $tenant->logo ?? $logo);
>   }
> - Renderizar <img src="{{ $logo }}" alt="Logo da marca" …> dentro do wrapper.
> 4. Branch – criar branch exclusiva feat/logo-dark-mode.
> 5. Documentação – atualizar docs/pt/recursos/configuracoes-do-kit.md e a versão EN com nova seção "Logo da marca – modo light/dark", incluir recomendações de fundo e screenshots dos dois modos.
> 6. Screenshots – gerar assets:
> - docs/assets/logo-light.png (modo light)
> - docs/assets/logo-dark.png (modo dark)
>   Inserir no markdown: ![Logo modo light](../assets/logo-light.png) ![Logo modo dark](../assets/logo-dark.png)
> 7. Testes – Pest feature LogoDarkModeTest que verifica:
> - toggle true → login exibe apenas logo.
> - toggle false + dark mode → login exibe logo_dark.
> - lock‑screen usa logo do tenant quando disponível.
> 8. Finalização – rodar vendor/bin/filacheck --fix, vendor/bin/pint --format agent, commit e abrir PR.

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Manter o campo `logo` (modo light) e criar `logo_dark` nullable na tabela do tenant (`organizations`/`tenants`). | "manter campo logo (light). Criar campo logo_dark nullable em organizations." | funcional | fechada |
| RQ-02 | Settings ganha o toggle `unifica_logo_marca` com default `true`. | "toggle unifica_logo_marca (default true)" | funcional | fechada |
| RQ-03 | Com o toggle `true`, só o campo `logo` existente é usado/exibido. | "true → usa somente o campo existente logo." | funcional | fechada |
| RQ-04 | Com o toggle `false`, a tela exibe DOIS campos obrigatórios: `logo` (helper "fundo claro recomendado") e `logo_dark` (helper "fundo transparente recomendado"). | "false → exibem dois campos obrigatórios: logo (light) – helper 'fundo claro recomendado'. logo_dark (dark) – helper 'fundo transparente recomendado'." | funcional | fechada |
| RQ-05 | No componente de exibição (login e lock-screen): remover a classe `fi-auth-media`, envolver o `<img>` num wrapper flex centrado e aplicar `max-width:100%;max-height:100%;object-fit:contain` ao `<img>`. | "Remover classe fi-auth-media. Envolver <img> em wrapper flex display:flex;justify-content:center;align-items:center;. Aplicar ao <img>: style='max-width:100%;max-height:100%;object-fit:contain;'." | funcional | fechada |
| RQ-06 | A lógica de escolha: unificado usa `logo`; não unificado usa `logo_dark` no modo escuro e `logo` no claro; o tenant sobrepõe pelo próprio `logo`/`logo_dark` quando preenchido. | "$useUnified = config('kit.unifica_logo_marca'); $darkMode = $this->isDarkMode(); … if ($tenant) { $logo = $useUnified ? $tenant->logo ?? $logo : ($darkMode ? $tenant->logo_dark ?? $logo : $tenant->logo ?? $logo); }" | funcional | fechada |
| RQ-07 | Renderizar `<img src="{{ $logo }}" alt="Logo da marca" …>` dentro do wrapper. | "Renderizar <img src=\"{{ $logo }}\" alt=\"Logo da marca\" …> dentro do wrapper." | funcional | fechada |
| RQ-08 | Criar a branch exclusiva `feat/logo-dark-mode`. | "criar branch exclusiva feat/logo-dark-mode." | não-funcional | fechada |
| RQ-09 | Atualizar `docs/pt/recursos/configuracoes-do-kit.md` e a versão EN com a seção "Logo da marca – modo light/dark", com recomendações de fundo e screenshots dos dois modos. | "atualizar docs/pt/recursos/configuracoes-do-kit.md e a versão EN com nova seção 'Logo da marca – modo light/dark', incluir recomendações de fundo e screenshots dos dois modos." | funcional | fechada |
| RQ-10 | Gerar os assets `docs/assets/logo-light.png` e `docs/assets/logo-dark.png` e embuti-los no markdown com `![…](../assets/…)`. | "gerar assets: docs/assets/logo-light.png (modo light) / docs/assets/logo-dark.png (modo dark). Inserir no markdown: ![Logo modo light](../assets/logo-light.png) ![Logo modo dark](../assets/logo-dark.png)" | funcional | fechada |
| RQ-11 | Pest feature `LogoDarkModeTest` cobrindo: toggle true → login exibe apenas `logo`; toggle false + dark → login exibe `logo_dark`; lock-screen usa a logo do tenant quando disponível. | "Pest feature LogoDarkModeTest que verifica: toggle true → login exibe apenas logo. toggle false + dark mode → login exibe logo_dark. lock-screen usa logo do tenant quando disponível." | funcional | fechada |
| RQ-12 | Finalização: `vendor/bin/filacheck --fix`, `vendor/bin/pint --format agent`, commit e abrir PR. | "rodar vendor/bin/filacheck --fix, vendor/bin/pint --format agent, commit e abrir PR." | não-funcional | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Enquanto aberta, a RQ afetada fica `aberta — Qn`
     e nenhum passo do 01 a implementa. A resposta entra como Adendo com fonte. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Na tela de **login** a mídia atual é a `arte_do_login` (ilustração lateral), NÃO a logo — quem usa a logo como mídia é só a lock-screen do tenant. E o `fi-auth-media` com `object-fit:cover` é o que faz a arte cobrir a lateral sem faixa: remover a classe da mídia do login deforma a arte. A remoção do `fi-auth-media` + wrapper flex + `contain` se aplica **só à lock-screen** (a logo), mantendo a arte do login em `cover`? Ou o login passa a exibir a LOGO no lugar da arte? | RQ-05, RQ-07 | Só lock-screen, arte do login continua `cover` — "a logo distorce" é o defeito descrito no Contexto; trocar a arte pela logo mudaria a cara do login, que o texto não pede. | respondida no Adendo 1 |
| Q2 | Com `unifica_logo_marca` **false**, o texto manda os dois campos serem "obrigatórios". Uma instalação que hoje não enviou logo nenhuma ficaria **impedida de salvar** qualquer campo da aba Identidade ao desligar o toggle, até enviar os dois arquivos. Os campos ficam obrigatórios literalmente para todos, ou obrigatórios **entre si** (enviar um exige o outro; ambos vazios continuam válidos = sem logo)? | RQ-04 | Obrigatórios entre si — `nullable` no banco indica que "sem logo" é estado legítimo; trancar a tela inteira por um campo que a pessoa não veio mexer é o defeito já documentado em outros toggles desta mesma tela. | respondida no Adendo 1 |
| Q3 | O `logo_dark` também vale para a **marca do topo dos três painéis** (o `brandLogo` do Filament)? O Filament tem `->darkModeBrandLogo()` nativo — uma linha por PanelProvider — e sem isso a logo clara pode ficar ilegível no topo escuro, que é o mesmo defeito que a feature combate nas telas de auth. O texto só cita login e lock-screen. | RQ-06 | Sim, aplicar `darkModeBrandLogo` nos três providers — é o mecanismo nativo e completa a promessa "dark/light" onde a marca aparece. | respondida no Adendo 1 |
| Q4 | O link `![Logo modo light](../assets/logo-light.png)` dentro de `docs/pt/recursos/` resolve para `docs/pt/assets/`, não para `docs/assets/`. O site de documentação é bilíngue (pastas `pt/` e `en/`). Os screenshots ficam em `docs/pt/assets/` e `docs/en/assets/` (paridade por idioma, como o link pedido resolve), ou numa pasta única `docs/assets/` fora dos idiomas? | RQ-10 | `docs/pt/assets/` e `docs/en/assets/` — mantém o markdown pedido funcionando e cada idioma pode ter print na própria língua. | respondida no Adendo 1 |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | "organizations" do item 1 é a tabela `tenants` do kit (`App\Models\Tenant`, migration `create_tenants_table`), não existe tabela `organizations`. | step 3 — schema real | 2026-10-03 | RQ-01 | vigente |
| P-02 | O toggle mora na chave `kit.identidade.unifica_logo_marca` (e não `kit.unifica_logo_marca` como o pseudocódigo), porque toda a identidade já vive sob `kit.identidade.*` — uma pergunta, uma dona (`.ai/rules/config.md`). | step 3 — estrutura do `config/kit.php` | 2026-10-03 | RQ-02, RQ-06 | vigente |
| P-03 | O `logo_dark` do tenant é **opcional**: banco `nullable` + pseudocódigo com `?? $logo` indicam fallback para `logo` e depois para o kit. | pseudocódigo do próprio requisito | 2026-10-03 | RQ-06 | vigente |
| P-04 | "tenant() helper do pacote" não existe neste kit; a resolução real é `session('tenant_corrente')` + `Tenant::find()` dentro de `TelaBloqueio` (e `Filament::getTenant()` no painel). | step 3 — `TelaBloqueio@getAuthDesignerConfig` | 2026-10-03 | RQ-06 | vigente |
| P-05 | A mediaAlt da imagem segue o padrão atual: nome da organização na lock-screen (`$organizacao->nome`); "Logo da marca" é o alt literal pedido quando não há organização. | `TelaBloqueio@getAuthDesignerConfig` | 2026-10-03 | RQ-07 | vigente |

## Fora de Escopo (declarado)

- Upload de `logo_dark` por organização **dentro** do `/app` (gestão pelo próprio tenant) — o requisito só prevê o campo no CRUD de `/admin/organizacoes`.
- `logo_dark` para o `arte_do_login` (a arte de fundo das telas de auth).
- Detecção de dark mode no servidor / troca por `prefersDarkMode()` — o pseudocódigo sugere, mas não há API do Filament para isso; ver a decisão de desenho Q5 no `01`.

## Adendo 1 — 2026-10-03

- **Fonte**: resposta do solicitante no chat, à rodada 1 da entrevista
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q1, Q2, Q3, Q4

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> siga as recomendações

### Decomposição

Nenhuma cláusula nova — o adendo **responde** as quatro perguntas abertas, todas pela recomendação apresentada:

| Q | Resposta consolidada | RQ destravada |
|---|---|---|
| Q1 | Remoção de `fi-auth-media` + wrapper flex + `contain` valem **só para a logo** (lock-screen); a `arte_do_login` do login continua `cover`. | RQ-05, RQ-07 |
| Q2 | Os dois campos são obrigatórios **entre si**: enviar um exige o outro; ambos vazios = sem logo, válido. | RQ-04 |
| Q3 | `logo_dark` também alimenta o `darkModeBrandLogo()` nativo nos três painéis. | RQ-06 |
| Q4 | Screenshots em `docs/pt/assets/` e `docs/en/assets/` (paridade por idioma). | RQ-10 |
