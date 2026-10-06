# Requisito — Issue #148: `kit:update` nao entrega `resources/views/vendor/filament-auth-designer` (override da lock-screen)

## Fonte

- **Origem**: issue #148 do repositório do kit (`gh api repos/gsferro/filament-starter-kit-easy/issues/148`), aberto depois de um `kit:update` de projeto v0.42.0 → v0.44.0
- **Data**: 2026-10-06 (aberto às 15:22 UTC; capturado no mesmo dia)
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito) — com uma ressalva de transcrição: o corpo do issue chegou **sem nenhuma crase** e com caracteres de controle no lugar delas (o shell que criou o issue interpretou `` \` `` como escape: `` `r `` virou CR, `` `f `` virou FF, `` `t `` virou TAB, `` `a `` virou BEL e `` `b `` virou BS). O texto abaixo é o corpo **byte a byte**, com cada caractere de controle trocado por um marcador visível (`⟨CR⟩`, `⟨FF⟩`, `⟨TAB⟩`, `⟨BS⟩`) para o arquivo continuar legível e com fim de linha LF; as barras invertidas sobreviventes ficam como vieram. Assim `\⟨CR⟩esources/views/vendor` é `resources/views/vendor`, `\⟨FF⟩eat/logo-dark-mode` é `feat/logo-dark-mode`, `\⟨TAB⟩ests/Kit/...` é `tests/Kit/...`, `\^Gi-tasks` é `ai-tasks` e `\⟨BS⟩rowser` é `browser`. A leitura reconstruída é a que a Decomposição usa; o original não foi editado.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> ## Resumo
>
> O override de view \⟨CR⟩esources/views/vendor/filament-auth-designer/components/partials/media.blade.php\ — criado na v0.43.0 (\⟨FF⟩eat/logo-dark-mode\, commit c6d900dc) — nunca chega a quem atualiza via \php artisan kit:update\: \⟨CR⟩esources/views/vendor\ nao esta em \CAMINHOS_DO_KIT\.
>
> Resultado medido ao atualizar um projeto v0.42.0 -> v0.44.0: o codigo novo (\TelaBloqueio::urlsDasLogos()\, settings de identidade) e os testes novos (\⟨TAB⟩ests/Kit/LogoDarkModeTest.php\) chegaram, mas a view que emite o par claro/escuro nao. Consequencia: **6 falhas no \LogoDarkModeTest\ CT-16** (\substr_count(\, 'object-fit:contain') >= 1\ retorna 0), porque a lock-screen continua renderizando a media pelo partial do vendor, sem o swap de logos.
>
> ## Por que e o mesmo bug de sempre
>
> E a quarta ocorrencia da divergencia entre as duas rotas de entrega, a mesma classe ja documentada no proprio \KitUpdate.php\:
>
> - \⟨CR⟩esources/views/svg\ (v0.23.0)
> - \⟨TAB⟩ests/Browser\ (v0.39.1)
> - \lang/pt_BR.json\
> - \.ai/\, \.claude/\, \.agents/\, \.junie/\ (\DuasRotasDeEntregaTest\)
>
> A varredura de \⟨TAB⟩ests/Kit/DuasRotasDeEntregaTest.php\ nao pega porque (provavelmente) nao olha \⟨CR⟩esources/views/vendor\ — mesmo motivo das anteriores.
>
> ## Sugestao de correcao
>
> Adicionar a entrada especifica em \CAMINHOS_DO_KIT\:
>
> \\\php
> 'resources/views/vendor/filament-auth-designer',
> \\\
>
> E auditar o resto de \⟨CR⟩esources/views/vendor\: hoje o kit tem overrides em \^Gi-tasks\, \command-center\, \⟨FF⟩ilament-onboarding\, \⟨FF⟩ilament-sentinel\, \⟨FF⟩ilament-captcha\, \⟨FF⟩ilament-jobs-monitor\, \⟨FF⟩ilament-clear-cache\, \⟨FF⟩ilament-composer-release-notifier\, \^Guthentication-log\, \^Gsmit-resized-column\, \pulse\. Se algum deles e autoral do kit (e nao publish cru do vendor), esta no mesmo risco — congelado na versao da instalacao para quem so atualiza.
>
> E estender a varredura de \DuasRotasDeEntregaTest\ (ou criar irmao) para cobrir \⟨CR⟩esources/views/vendor\ sem incluir views que sao publish cru de pacote — senao o kit passaria a sobrescrever customizacao legitima do projeto.
>
> ## Reproducao
>
> \\\⟨BS⟩ash
> # projeto nascido em v0.42.0, atualizado para v0.44.0 via kit:update --all
> Test-Path resources/views/vendor/filament-auth-designer/components/partials/media.blade.php
> # False
>
> vendor/bin/pest tests/Kit/LogoDarkModeTest.php
> # 6 failed — todos os datasets do CT-16
> \\\
>
> ## Contorno aplicado
>
> \git checkout v0.44.0 -- resources/views/vendor/filament-auth-designer/\ — testes voltaram a verde (22 passaram).

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm
     O "trecho literal" cita o texto acima com os marcadores; a coluna Cláusula usa a leitura reconstruída. -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | O override da lock-screen, `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`, passa a chegar a quem atualiza via `php artisan kit:update`: a pasta dele entra em `CAMINHOS_DO_KIT`. | "Adicionar a entrada especifica em \CAMINHOS_DO_KIT\: … 'resources/views/vendor/filament-auth-designer'," | funcional | fechada |
| RQ-02 | O resto de `resources/views/vendor` é auditado: toda pasta de override que é autoral do kit (editada pelo kit, e não publish cru do pacote) está no mesmo risco e passa a ser entregue também. | "E auditar o resto de \⟨CR⟩esources/views/vendor\ … Se algum deles e autoral do kit (e nao publish cru do vendor), esta no mesmo risco — congelado na versao da instalacao para quem so atualiza." | funcional | fechada |
| RQ-03 | A varredura automática das duas rotas de entrega passa a cobrir `resources/views/vendor`: override autoral fora de `CAMINHOS_DO_KIT` reprova antes da tag (hoje a varredura não olha essa pasta, pelo mesmo motivo das ocorrências anteriores). | "E estender a varredura de \DuasRotasDeEntregaTest\ (ou criar irmao) para cobrir \⟨CR⟩esources/views/vendor\" · "A varredura de \⟨TAB⟩ests/Kit/DuasRotasDeEntregaTest.php\ nao pega porque (provavelmente) nao olha \⟨CR⟩esources/views/vendor\" | funcional | fechada |
| RQ-04 | A varredura e a lista **não** incluem view que é publish cru de pacote: o `kit:update` não pode sobrescrever customização legítima do projeto. | "sem incluir views que sao publish cru de pacote — senao o kit passaria a sobrescrever customizacao legitima do projeto." | restrição | fechada |
| RQ-05 | Depois da correção, o sintoma reportado desaparece: um projeto atualizado tem a view do par claro/escuro e `LogoDarkModeTest` CT-16 passa (hoje são 6 falhas, porque a lock-screen renderiza o partial do vendor). | "Consequencia: **6 falhas no \LogoDarkModeTest\ CT-16** … porque a lock-screen continua renderizando a media pelo partial do vendor, sem o swap de logos." · "Contorno aplicado … testes voltaram a verde (22 passaram)." | não-funcional | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Enquanto aberta, a RQ afetada fica `aberta — Qn`
     e nenhum passo do 01 a implementa. A resposta entra como Adendo com fonte.
     Qn é a sequência única da feature: os números que faltam aqui são perguntas de desenho (01/02). -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | A auditoria achou 7 pastas de `resources/views/vendor` cujo conteúdo é **idêntico** ao que o pacote instalado traz (publish cru: `ai-tasks`, `authentication-log`, `filament-composer-release-notifier`, `filament-jobs-monitor`, `filament-onboarding`, `filament-sentinel`, `pulse`). Elas ficam fora do `kit:update` (RQ-04). Devem também **sair do repositório do kit** — uma cópia publicada esconde atualização futura da view do pacote — ou ficam como estão? | nenhuma RQ desta entrega (remover é escopo novo; manter fora da lista é a RQ-04 literal, P-01) | ➡️ ficam como estão nesta correção (fora de escopo declarado) e viram issue próprio: remover publish é mudança de comportamento das telas, não de entrega. *(alterado em 2026-10-06: QA-01 — a coluna Afeta dizia RQ-02/RQ-04; nenhum passo depende da resposta)* | aberta |
| Q2 | `command-center` tem 4 views: 3 editadas pelo kit (tradução, commit 5511a0a) e 1 idêntica ao pacote (`pages/history.blade.php`). A entrada em `CAMINHOS_DO_KIT` é a **pasta**: o arquivo idêntico viaja junto. Aceita que o `kit:update` ofereça esse arquivo (hoje idêntico ao pacote) ou prefere entradas por arquivo? | RQ-02, RQ-04 | ➡️ pasta inteira (P-02): o arquivo idêntico sobrescrito por si mesmo é inócuo, e entrada por arquivo quebraria no próximo arquivo editado da mesma pasta — a mesma classe de esquecimento que gerou o issue. | retirada — reclassificada como **desenho** em 2026-10-06 (QA-01): granularidade da entrada é decisão de entrega, não de requisito; é a D2 do `01`, e P-02 registra o que a feature passa a assumir |
| Q3 | A correção só chega a quem atualiza quando houver tag. Sai como patch `v0.45.1` logo depois do merge? | release (nenhuma RQ desta entrega: a tag é a publicação, não o comportamento) | ➡️ sim, patch — é correção de entrega sem API nova; a sessão não cria a tag sem a sua palavra. *(alterado em 2026-10-06: QA-01 — Afeta dizia RQ-01/RQ-05)* | aberta |
| Q9 | Para a classe **antiga** do `kit:update` (a que roda na primeira rodada de quem atualiza) entregar o override, cada uma das dez views autorais precisa mudar nesta release: entra uma linha de comentário Blade no cabeçalho de cada uma ("Override autoral do kit, entregue pelo kit:update — issue #148"). Aceita a linha? A alternativa é documentar que quem já está em ≥ v0.43.0 rode `kit:update` duas vezes, a segunda com `--from=<versão anterior>`. | RQ-01, RQ-02, RQ-05 | ➡️ sim, a linha (P-07): entrega sem passo manual, e o comentário não chega ao HTML. Implementado assim; se recusar, remove-se a linha e a doc ganha o passo duplo. | retirada — reclassificada como **desenho** em 2026-10-06 (QA-01): como o arquivo chega é mecanismo de entrega; é a D7 do `01`, e P-07 registra a premissa. Numerada Q9 porque Q4 já era a pergunta de desenho da varredura |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. Não é requisito.
     Premissa que contradiz ou estende o pedido gera também uma pergunta em ## Perguntas ao Solicitante. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Pasta de `resources/views/vendor` cujas views são todas idênticas às do pacote instalado (publish cru) **não** entra em `CAMINHOS_DO_KIT`, e a varredura reprova se entrar: é a direção que não sobrescreve customização do projeto. | step 4 — entrevista (Q1) | 2026-10-06 | RQ-02, RQ-04 | vigente |
| P-02 | Pasta com ao menos uma view diferente da do pacote é autoral do kit **inteira**: a entrada em `CAMINHOS_DO_KIT` é a pasta, não o arquivo. | step 4 — entrevista (Q2, depois D2) | 2026-10-06 | RQ-02 | vigente |
| P-03 | "Autoral" é decidido por **conteúdo**: a view do kit difere, byte a byte (fim de linha normalizado), da view de mesmo caminho relativo dentro de `vendor/*/*/resources/views`. Não é decidido por histórico do git (o override de `asmit-resized-column` nasceu já editado, num commit só) nem por lista escrita à mão. | step 3 — auditoria por script | 2026-10-06 | RQ-02, RQ-03, RQ-04 | vigente |
| P-04 | A varredura nova só roda na árvore do kit: num projeto instalado, `resources/views/vendor` contém publishes do próprio projeto, que apareceriam como "autorais fora da lista". Fora da árvore o caso pula, declarado — a mesma guarda dos casos vizinhos. | step 3 — leitura dos casos vizinhos | 2026-10-06 | RQ-03 | vigente |
| P-05 | Se um pacote atualizar a própria view, o publish cru do kit passa a diferir dela e a varredura o acusa como "autoral fora da lista". É achado legítimo (a cópia do kit passou a esconder a view nova do pacote), e a mensagem do teste diz as duas saídas: republicar a view ou listá-la. | step 4 — entrevista (Q6, desenho) | 2026-10-06 | RQ-03 | vigente |
| P-06 | Caminho que está na lista de `CAMINHOS_DO_KIT` da versão de destino e **não** estava na lista da versão de origem é comparado **tag de destino × árvore do projeto** (como o `kit:update` já faz quando não há origem), só para esses caminhos: arquivo que o projeto não tem aparece como "novo no kit", arquivo diferente como "modificado", arquivo que só o projeto tem é ignorado. Sem isso, a entrada nova só entregaria o que mudou **depois** da origem, e quem já está na v0.43.0 ou posterior sem o override nunca o receberia — o caso do próprio issue. Vale só quando a lista da origem pôde ser lida; se não pôde, o comportamento de hoje fica, e o aviso existente manda repetir com `--from`. | step 9 — CR-01 (passe genérico) = RD-01 (`fw-revisor-diff`) | 2026-10-06 | RQ-01, RQ-05 | vigente |
| P-07 | Nesta release, **cada view autoral** das cinco pastas muda (uma linha de comentário Blade, que não chega ao HTML, no cabeçalho de cada arquivo). Motivo: a primeira rodada do `kit:update` roda a classe **instalada** (antiga), que compara tag→tag e não tem a P-06; arquivo que não mudou entre a versão do projeto e esta não entra, e o aviso "rode de novo" não dispara quando a lista do destino é lida. Com a mudança, `git diff {origem} {destino}` dentro das pastas deixa de ser vazio e a própria classe antiga entrega os dez arquivos na primeira rodada, para qualquer origem. A P-06 continua sendo o conserto estrutural para a próxima pasta que entrar na lista. | step 9 — leitura do aviso de segunda rodada em `KitUpdate::encerrar()` (CR-01/RD-01) | 2026-10-06 | RQ-01, RQ-02, RQ-05 | vigente |
| P-08 | Arquivo que o **projeto não tem** sai rotulado "novo no kit" ainda que o diff tag→tag o veja como modificado: o rótulo olha a árvore do projeto. É o rótulo que `--only-new` aplica. Vale a partir da versão que traz a classe nova; na primeira rodada a partir de uma versão anterior (classe antiga) a view ausente ainda sai "modificado", e as docs mandam aplicar pelo modo interativo ou `--all`. | step 11 — QA-03 (fw-qa-gate, ciclo 1) | 2026-10-06 | RQ-01, RQ-05 | vigente |

## Fora de Escopo (declarado)

- Remover do repositório do kit as 7 pastas de publish cru (Q1): outra entrega.
- Mudar o que as views autorais fazem (lock-screen, captcha, limpar cache, tradução do command-center): a correção é só de **entrega**.
- O cenário 3 do `checklist-de-release` (projeto antigo + `kit:update` real) sobre a tag publicada: continua sendo o roteiro de release, não um teste desta wiki.
- Tag/release: depende de Q3.
