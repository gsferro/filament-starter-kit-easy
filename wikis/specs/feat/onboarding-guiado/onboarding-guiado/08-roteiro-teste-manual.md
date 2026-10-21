# Roteiro de Teste Manual — Onboarding guiado: tutorial guiado nos três painéis

> **Para quem**: revisor do PR e quem homologa o tutorial antes da release
> **Requisito/Casos**: [00-requisito.md](00-requisito.md) · [04-casos-de-teste.md](04-casos-de-teste.md) · [05-casos-de-teste-browser.md](05-casos-de-teste-browser.md)
> **Base**: 259732f8 (correções do step 9 commitadas) + ciclo 2 do gate · 2026-10-21
> **Vias**: ambas
> **Conferência**: 0 de 9 roteiros executados pela sessão
> **Auditoria**: pendente — próximo ciclo do quality gate
> O resultado esperado vem do CT/CT-B/RQ/P-nn citado; o código só decide como chegar lá.

## Preparação do ambiente

| # | Ação | Comando ou caminho | Como confirmar |
|---|---|---|---|
| 1 | Banco local descartável, sem multi-organização | `.env` local conforme `.env.example` (`KIT_ONBOARDING=false`, `KIT_TENANCY=false`); `php artisan migrate:fresh --seed` — **destrutivo: apenas em banco local descartável confirmado; o quality gate não executa** | `php artisan config:show kit.onboarding.habilitado` responde `false` |
| 2 | Assets do painel | `npm run build` | o `/admin/login` abre sem `Unable to locate file in Vite manifest` |
| 3 | Servir a aplicação | `composer run dev`; `http://localhost:8000` (ou a URL do Herd/`APP_URL` local) | `/admin/login` responde 200 |

## Dados e usuários de teste

| Papel | Como obter | Estado necessário |
|---|---|---|
| Administrador geral (`master_global`) | criado pelo `UsuarioAdminSeeder` com `KIT_ADMIN_EMAIL`/`KIT_ADMIN_PASSWORD` do `.env` local | papel `master_global` |
| Administradora (`admin`) | `/admin` → Usuários → Novo, papel `admin`, como o administrador geral | sem passos concluídos |
| Íris (`infra`) | `/admin` → Usuários → Novo, papel `infra` | sem passos concluídos |
| Ana (`panel_user`) | `/admin` → Usuários → Novo, papel `panel_user` | sem passos concluídos |

## Roteiros

### RM-01 — Semear as jornadas sem ligar o tutorial · Via: terminal

**Origem**: RQ-05, RQ-06 · CT-22 · CT-29
**Conferência**: não executado — sem banco local descartável confirmado nesta sessão

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Semear com o tutorial gravado como desligado | `php artisan db:seed --class=OnboardingSeeder` | Termina com sucesso; o valor gravado do interruptor continua `false` e `php artisan config:show kit.onboarding.habilitado` continua `false` (linha `false` do CT-22) | CT-22 · RQ-06 | |
| 2 | Semear de novo | `php artisan db:seed --class=OnboardingSeeder` | Jornadas, passos e condições iguais aos da primeira vez: 3 jornadas e 32 passos, nenhuma chave de passo repetida dentro de uma jornada (CT-29) | CT-29 · RQ-25 | |

### RM-02 — Ligar e desligar o tutorial pela tela · Via: aplicação

**Origem**: RQ-10 · CT-05
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Entrar como administradora | `/admin/configuracoes-da-aplicacao`, aba Aplicação, seção "Tutorial guiado" | prep — interruptor "Usar o tutorial guiado" desmarcado | prep · CT-05 | |
| 2 | Marcar o interruptor e salvar | botão Salvar | Valor gravado `true` e, no request seguinte, `config:show kit.onboarding.habilitado` = `true` (linha `false → true` do CT-05) | CT-05 · RQ-10 | |
| 3 | Desmarcar e salvar | botão Salvar | Valor gravado `false` e config efetiva `false` (linha `true → false`); deixar ligado de novo para os RM seguintes | CT-05 · RQ-10 | |

### RM-03 — O dashboard de cada painel traz o checklist da jornada daquele painel · Via: aplicação

**Origem**: RQ-28, RQ-22 · CT-15
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Com o tutorial ligado, entrar como administradora e abrir o dashboard | `/admin` | Checklist flutuante presente com a jornada do `/admin`, título começando por "Primeiros passos"; nenhuma jornada de outro painel oferecida; item "Primeiros passos" na navegação; sem tela de boas-vindas do pacote | CT-15 (linha administradora) | |
| 2 | Entrar como Ana | `/app` | Mesmas quatro pós-condições, com a jornada do `/app` | CT-15 (linha Ana) | |
| 3 | Entrar como Íris | `/infra` | Mesmas quatro pós-condições, com a jornada do `/infra` | CT-15 (linha Íris) | |

### RM-04 — O tour de um passo abre pelo checklist · Via: aplicação

**Origem**: RQ-28 · CT-B01
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Como administradora, com o passo de Usuários pendente, abrir o checklist e iniciar o tour de Usuários | `/admin`; checklist flutuante; passo "Usuários"; iniciar tour | Popover do tour visível com o título pt_BR da primeira parada do passo; console do navegador sem erro de JavaScript | CT-B01 · RQ-28 | |

### RM-05 — A página "Primeiros passos" mostra só a jornada do painel · Via: aplicação

**Origem**: RQ-22 · CT-16
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Como administradora, com todas as features da tabela ligadas | `/admin/onboarding` | Responde 200 com a página; jornada do `/admin` com 11 passos; o título da jornada do `/infra` não aparece | CT-16 (linha administradora) | |
| 2 | Como Íris | `/infra/onboarding` | 200; jornada do `/infra` com 15 passos; título da jornada do `/app` ausente | CT-16 (linha Íris) | |

### RM-06 — Feature desligada esconde só o passo dela · Via: aplicação

**Origem**: P-03 · CT-27
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Tirar o `/admin` dos painéis do dock (mantendo `/app` e `/infra`) | `/admin/configuracoes-da-aplicacao`, seção do dock | prep — dock ligado só no `/app` e `/infra` | prep · CT-27 | |
| 2 | Como administradora, abrir o checklist | `/admin` | Jornada com 10 passos visíveis; passo "Dock de atalhos" ausente (linha "o dock fora do /admin" do CT-27) | CT-27 · P-03 | |
| 3 | Devolver o `/admin` ao dock e reabrir | `/admin` | 11 passos visíveis; "Dock de atalhos" presente (linha "nenhuma") | CT-27 · P-03 | |

## Casos negativos e permissões

### RM-07 — Tutorial desligado: telas sem checklist e URLs em 404 · Via: aplicação

**Origem**: RQ-16 · RQ-17 · P-04 · CT-11 · CT-45 · CT-08
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Desligar o tutorial (RM-02 passo 3) e abrir o dashboard como administradora | `/admin` | Checklist flutuante ausente; item "Primeiros passos" ausente da navegação; marcação de tour ausente; sem tela de boas-vindas (linha administradora/desligado do CT-11) | CT-11 · RQ-17 | |
| 2 | Abrir a página como administradora | `/admin/onboarding` | 404, sem a página no corpo — recusa pelo interruptor, mesmo com a permissão da página (CT-45) | CT-45 · RQ-17 · P-04 | |
| 3 | Como administrador geral, abrir a autoria | `/admin/onboarding-flows` e `/admin/onboarding-conditions` | 404 nas duas, sem a página de autoria no corpo (CT-08, linhas do `master_global`) | CT-08 · RQ-16 | |

### RM-08 — Tutorial ligado, sem a permissão da página: 403 · Via: aplicação

**Origem**: P-18 · CT-62
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Como administrador geral, retirar `View:PrimeirosPassos` do papel `infra` | `/admin` → Papéis → infra | prep — papel sem a permissão da página | prep · CT-62 | |
| 2 | Com o tutorial ligado, abrir a página como Íris | `/infra/onboarding` | 403, sem a página no corpo — recusa pela permissão, não pelo interruptor (linha Íris do CT-62) | CT-62 · P-18 | |

### RM-09 — Dashboard dinâmico: o passo é de quem pode montar · Via: aplicação

**Origem**: P-22 · CT-68
**Conferência**: não executado — sem app servido em ambiente descartável confirmado

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Ligar o dashboard dinâmico no `/admin`; o papel `admin` com `Manage:Dashboard` | configurações da aplicação; Papéis → admin | prep — dinâmico ligado no painel | prep · CT-68 | |
| 2 | Como administradora, abrir o checklist | `/admin` | Passo "Dashboard dinâmico" visível (linha 1 do CT-68, controle `N₄`) | CT-68 · P-22 | |
| 3 | Retirar `Manage:Dashboard` do papel `admin` e reabrir | `/admin` | Passo "Dashboard dinâmico" ausente e a jornada com `N₄ − 1` visíveis (linha 2) | CT-68 · P-22 | |

## Limpeza

- Desligar o tutorial na tela (RM-02 passo 3) e devolver as permissões retiradas (RM-08, RM-09) e o dock ao `/admin` (RM-06).
- Para zerar o banco: `php artisan migrate:fresh --seed` — **apenas em banco local descartável confirmado; o quality gate não executa**.

## Fora deste roteiro

| CT | Motivo de ser só automatizado |
|---|---|
| CT-13, CT-43, CT-63 (ação do checklist já montado depois do desligar) | Exige desligar entre o render e o clique em outra sessão; a ordem é controlada no teste |
| CT-18, CT-66 (chave fora do domínio escrita pelo cliente) | Exige forjar o payload Livewire; inspeção manual não reproduz com fidelidade |
| CT-38, CT-55, CT-67 (`kit:install` de verdade) | Cria diretório de instalação novo e força falha do seeder; o teste isola o ambiente |
| CT-70, CT-71 (canal de log em projeto atualizado) | Sobe aplicação nova com config alterada antes do boot |
| CT-34..CT-37, CT-41, CT-49..CT-52, CT-63, CT-64 (multi-organização) | Pede instalação com `kit:tenancy`; fora do ambiente da preparação |
| CT-29 contagem por conteúdo, CT-59 (limites de coluna), CT-69 (união de personas) | Comparação de fotografia e varredura de todos os passos; manual só confere amostra |

## Registro de execução

| Data | Quem | Ambiente | Commit | RM executados | Resultado | Observações |
|---|---|---|---|---|---|---|
