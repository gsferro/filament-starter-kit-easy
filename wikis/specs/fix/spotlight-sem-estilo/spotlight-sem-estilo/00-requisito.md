# Requisito — A busca ⌘K (Spotlight) não funciona nas instalações

## Fonte

- **Origem**: mensagem do mantenedor no chat, via `/feature-wiki`, complementada por três respostas a
  perguntas de esclarecimento na mesma sessão
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **baixa** — descrição verbal, e a frase original chegou **truncada**. As três
  respostas abaixo fecham o que faltava; foram confirmadas antes de qualquer escrita

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Mensagem original (truncada no envio):**

> o sportlight não esta funcionando em nenhuma das instalações de testes. Necessário correção e

**Respostas às perguntas de esclarecimento (2026-09-02):**

> **Qual é o sintoma exato que você viu nas instalações de teste?**
> Nada acontece ao clicar / Ctrl+K

> **A frase do pedido cortou em "Necessário correção e…". O que vinha depois?**
> …e teste que pegue isso (o CT-B F-45 está verde com o bug)
> …e garantir nas instalações já existentes via kit:update

> **Nome da branch/wiki para o fix?**
> fix/spotlight-sem-estilo

## O que foi medido antes de escrever (não é requisito — é o estado)

| Fato | Valor |
|---|---|
| Pacote | `wezlo/filament-search-spotlight` **1.0.4**, em todas as instalações de `TESTES KIT/` (v0.20.0 a v0.22.3) e no kit |
| Como o overlay chega à tela | `@livewire('filament-search-spotlight')` no render hook `BODY_END` (`FilamentSearchSpotlightPlugin.php:274`) |
| Classes que a blade do overlay emite | **66** utilitárias Tailwind cruas (`fixed inset-0 z-50 backdrop-blur-sm rounded-xl max-h-[60vh] dark:bg-gray-900`…) |
| Quantas existem na CSS que o Filament publica (`public/css/filament/filament/app.css`) | **0 de 66** |
| O que o README do pacote exige | `@source '…/vendor/wezlo/filament-search-spotlight/resources/views/**/*'` num **tema Tailwind compilado** do painel |
| Tema compilado no kit | **não existe** — nenhum painel chama `viteTheme()`, de propósito (os painéis funcionam sem `npm run build`) |
| Medido no navegador após o clique (kit, `/admin`) | `position: fixed`, `display: flex`, **`z-index: auto`, fundo `rgba(0,0,0,0)`, `top: 1833px` numa viewport de 1117px** — o overlay abre **fora da tela**, sem backdrop |
| O CT-B F-45 (`tests/Browser/RoteiroDoKitTest.php:88`) | verde: `assertVisible` sobre o input do overlay — o input **está** visível para o Playwright, só que a 700 px abaixo da dobra |
| Precedente no kit | `resources/css/filament/cards.css`: o mesmo defeito, no `harvirsidhu/filament-cards` (51 de 53 classes ausentes), resolvido com CSS à mão registrado por `FilamentAsset` — e já é Project Rule (`.ai/rules/css-filament.md`) |
| `kit:update` e o CSS do kit | `resources/css/filament` **não está** em `KitUpdate::CAMINHOS_DO_KIT`: nem `kit.css` nem `cards.css` chegam a quem atualiza. A varredura de `KitUpdateTest` não olha `resources/css` |

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Clicar no campo de busca da topbar, ou pressionar `Ctrl/⌘+K`, abre o overlay do Spotlight **visível e utilizável** — no kit e em projeto nascido do `create-project` | "o sportlight não esta funcionando em nenhuma das instalações de testes" / "Nada acontece ao clicar / Ctrl+K" | funcional |
| RQ-02 | O defeito é corrigido no kit | "Necessário correção" | funcional |
| RQ-03 | Existe teste automatizado que **fica vermelho com o defeito** e verde com a correção — o CT-B F-45 atual não distingue os dois | "…e teste que pegue isso (o CT-B F-45 está verde com o bug)" | não-funcional (qualidade) |
| RQ-04 | A correção chega a projeto **já instalado** por `php artisan kit:update`, não só a instalação nova | "…e garantir nas instalações já existentes via kit:update" | restrição |

## Ambiguidades e Perguntas Abertas

- **"Não está funcionando"** — resolvido: o sintoma é "nada acontece". Medido: o overlay **abre**,
  mas fora da viewport e sem backdrop; para quem olha, é indistinguível de "não abriu". Fica
  registrado porque a hipótese inicial da sessão era "HTML cru visível no rodapé", e a medição
  mostrou que nem isso aparece — o elemento fica **abaixo** do fim da página.
- **RQ-01, "utilizável"** — o requisito diz "funcionando". Assumido: overlay ancorado à viewport,
  com fundo escurecido, caixa centralizada e o campo de busca focado. **Se negado**: só "aparece"
  bastaria, e o CT-B de RQ-03 perde as asserções de geometria.
- **RQ-04, "garantir"** — assumido: `kit:update` passa a **entregar** o arquivo de CSS novo e os
  dois que já existiam (`kit.css`, `cards.css`), **fonte e publicado** — `public/css/kit/*.css` é
  versionado no kit, então entregar o publicado junto elimina o `filament:assets` para estes três
  arquivos (pergunta devolvida pela `feature-test-design`; premissa adotada em CT-04).
  **Se negado** (entregar só a fonte): CT-04 perde a linha do publicado e o CHANGELOG mantém o
  passo manual `php artisan filament:assets`, que o aviso pós-update já lista.

## Fora de Escopo (declarado)

- **Tema Filament compilado (`viteTheme()`)** — é a solução que o pacote recomenda, e o kit a
  recusa por decisão registrada (`.ai/rules/css-filament.md`, `cards.css`): os painéis funcionam
  sem Node. Reabrir isso é outra feature.
- **Upgrade do `wezlo/filament-search-spotlight`** — a versão 1.0.4 é a mesma em todas as
  instalações; não há evidência de que outra versão resolva.
- **Mudança de comportamento ou aparência do Spotlight** além de ele aparecer como o pacote desenha.
- **`kit:update` rodar `filament:assets` sozinho** — ver a premissa de RQ-04.
