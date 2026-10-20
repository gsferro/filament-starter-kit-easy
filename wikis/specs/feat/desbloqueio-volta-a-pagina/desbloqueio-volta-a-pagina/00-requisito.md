# Requisito — Desbloqueio da sessão volta para a página em que a pessoa estava

## Fonte

- **Origem**: pedido do solicitante no chat, via `/feature-wiki` (argumentos do comando)
- **Data**: 2026-10-20
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos melhorar o desbloqueio apos o "/lock/screen"
> - quando a tela é travada apos o bloqueio por inatividade, ao desbloquear é redirecionado para a tela raiz
> - o ideal é que o usuário seja redirecionando para a pagina que estava, como é feito no efeito de entented no laravel apos acessar uma url que precisa de autenticação, voce é jogado para a tela de login, e apos o sucesso, volta para a url que voce entrou inicialmente
> - analise o pacote base e veja se tem essa funcionalidade já nativa
> - use "codex --yolo" como auxiliar
> - use blueprint no processo
> - crie uma branch especifica para implementar essa melhoria
> - ao fianlizar, abra o PR e depois rode o fluxo completo de Tag + release + testes dos quarto cenarios

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Quando a sessão é travada pelo bloqueio por inatividade, o desbloqueio bem-sucedido deixa de levar a pessoa à tela raiz | "quando a tela é travada apos o bloqueio por inatividade, ao desbloquear é redirecionado para a tela raiz" | funcional | fechada |
| RQ-02 | Depois do desbloqueio bem-sucedido, a pessoa é levada à página em que estava | "o ideal é que o usuário seja redirecionando para a pagina que estava" | funcional | fechada |
| RQ-03 | "A página em que estava" segue o efeito *intended* do Laravel: é a URL que a pessoa acessou e que a barreira interceptou — a mesma que ela pediu, e não outra da navegação anterior | "como é feito no efeito de entented no laravel apos acessar uma url que precisa de autenticação, voce é jogado para a tela de login, e apos o sucesso, volta para a url que voce entrou inicialmente" | restrição | fechada |

Os itens de processo do pedido — analisar se o pacote base já tem a funcionalidade, usar o
`codex --yolo` como auxiliar, usar o Blueprint no processo, criar uma branch específica, abrir o PR
e, depois, rodar tag + release + os quatro cenários do checklist — não são cláusulas do sistema: são
instruções à sessão, cumpridas e registradas em `03-progresso.md` (`## Processo do pedido`).

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Execução autônoma: a sessão seguiu a recomendação
     como premissa; a resposta entra como Adendo e, se divergir, a premissa é revogada. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | A tela de bloqueio oferece dois jeitos de destravar: a senha e os botões de login social. O retorno à página vale para os dois, ou só para a senha? | RQ-02 | Os dois: para quem só tem conta social, o botão do provedor é o único desbloqueio possível, e voltar à raiz por ele reabriria o defeito para essas pessoas | aberta |
| Q3 | Se quem volta do provedor, numa sessão travada, é a conta de **outra** pessoa (o botão "Trocar de conta" ou outro provedor), isso é desbloqueio e leva à página da primeira? (devolvida pela derivação do `04`, era Q?1) | RQ-02 | Não — falha fechado: só a mesma conta que travou volta à página; a outra pessoa segue o destino de um login comum | aberta |
| Q2 | Quando não há página a que voltar (a tela de bloqueio aberta direto, por favorito ou histórico), para onde vai o desbloqueio? | RQ-01 | Para a raiz do painel da tela de bloqueio — nunca para a página pública de boas-vindas da aplicação, que é a "tela raiz" de hoje | aberta |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | O retorno à página vale para os dois desbloqueios da tela: a senha e a volta do provedor social | step 4 — leitura do pedido (Q1) | 2026-10-20 | RQ-02 | vigente |
| P-02 | Sem página a que voltar, o desbloqueio leva à raiz do painel da tela de bloqueio, nunca à página pública da aplicação | step 4 — premissa de comportamento (Q2) | 2026-10-20 | RQ-01 | vigente |
| P-03 | Com a sessão já travada, a página de retorno é a última que a pessoa tentou abrir no painel — não a própria tela de bloqueio | step 4 — leitura de RQ-03 ("volta para a url que voce entrou") | 2026-10-20 | RQ-03 | vigente |
| P-04 | A régua de "página em que estava" é a do *intended* do Laravel: um GET interceptado que não espera JSON vira destino; chamada de componente da tela (Livewire), requisição que espera JSON e envio que não seja GET não viram *(alterado em 2026-10-20: ADV-08 da revisão adversarial — o texto antigo tinha duas réguas, "abertura de página" e "a do Laravel")* | step 4 — leitura de RQ-03 | 2026-10-20 | RQ-03 | vigente |
| P-06 | Só a mesma conta que estava na sessão travada volta à página pelo provedor social; outra conta segue o destino de um login comum | step 7 — Q3 devolvida pela derivação do `04`, premissa de comportamento, falha fechado | 2026-10-20 | RQ-02, P-01 | vigente |
| P-07 | Na confirmação de vínculo social pelo link do e-mail aberta numa sessão travada, a página guardada é descartada: o link não é um desbloqueio da tela | step 9 — CR-03 (`/code-review`) e RD-02 (`fw-revisor-diff`) | 2026-10-20 | P-06 | vigente |
| P-08 | Sem página guardada, no painel com organização o desbloqueio volta à organização em que a pessoa estava (a da sessão), não à primeira da conta | step 9 — CR-02 (`/code-review`), premissa de comportamento, falha fechado | 2026-10-20 | P-02 | vigente |
| P-09 | O logout forçado por excesso de tentativas na tela de bloqueio não deixa página guardada para o próximo login | step 9 — CR-05 (`/code-review`), premissa de comportamento, falha fechado | 2026-10-20 | P-02 | vigente |
| P-05 | Com a sessão já destravada, abrir a tela de bloqueio leva de volta ao painel — ela só aparece para a sessão travada | step 3 — análise do vendor pelo Codex (a marca de trava fica na sessão com valor falso depois de destravar) | 2026-10-20 | RQ-01 | vigente |

## Fora de Escopo (declarado)

- O tempo de inatividade e o kill-switch do bloqueio (`LOCKSCREEN_IDLE_TIMEOUT`, `LOCKSCREEN_ENABLED`): não mudam
- O login comum (por senha ou provedor, sem sessão travada): continua com o destino de hoje
- A confirmação de vínculo social pelo link do e-mail: é um fluxo de outra aba, não um desbloqueio da tela — que, numa sessão travada, só descarta a página guardada (P-07) *(alterado em 2026-10-20: CR-03/RD-02)*
- Contar inatividade no navegador (timer em JavaScript): o bloqueio continua disparando na próxima navegação, como hoje
