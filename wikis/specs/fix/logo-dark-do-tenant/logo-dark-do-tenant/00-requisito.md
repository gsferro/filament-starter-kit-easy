# Requisito — Logo escura da organização: trocar com o tema também quando o tenant está ativo

## Fonte

- **Origem**: pedido do solicitante no chat (invocação de `/feature-wiki`), em 2026-10-07, com uma segunda invocação idêntica acrescentando "crie branchs separadas e rode em paralelo usando subagentes ou worktree"; e a resposta a uma pergunta de requisito feita pela sessão no mesmo dia (Adendo 1)
- **Data**: 2026-10-07
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito no chat, copiado sem correção)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> foi criado a parte da logo dark no settings para ser usado no logo marca da aplicação, porem, no caso do tenant, tem a opção de criar a logo dark, mas ela não esta sendo alterada conforme troca o modo.
>
> * faça uma revisão do uso da feature
> * revise completamente a implementação e veja se ficou algo que passou ou foi esquecido quando o tenant estiver ativo
> * a logo marca dark e light (default) esta funcionando corretamente, so precisamos replicar a mesma funcionalidade para quando uso no tenant

Da segunda invocação, o trecho que vale para as duas features do pedido:

> crie branchs separadas e rode em paralelo usando subagentes ou worktree

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Revisar o uso da feature "logo escura" (a da instalação e a da organização): onde cada variante aparece e quando troca com o tema. | "faça uma revisão do uso da feature" | funcional (análise) | fechada |
| RQ-02 | Revisar a implementação inteira e identificar o que ficou de fora quando uma organização (tenant) está ativa. | "revise completamente a implementação e veja se ficou algo que passou ou foi esquecido quando o tenant estiver ativo" | funcional (análise) | fechada |
| RQ-03 | A logo escura da organização passa a trocar com o modo claro/escuro onde a organização está em uso, replicando o comportamento que a logo da marca da instalação já tem. | "no caso do tenant, tem a opção de criar a logo dark, mas ela não esta sendo alterada conforme troca o modo" · "so precisamos replicar a mesma funcionalidade para quando uso no tenant" | funcional | substituída por RQ-05 (Adendo 1) |
| RQ-04 | A logo da marca da instalação (clara e escura) não muda: já funciona e é a referência. | "a logo marca dark e light (default) esta funcionando corretamente" | restrição | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Onde a logo escura da organização deveria trocar com o tema? Hoje ela já troca na tela de bloqueio do `/app` (com a marca separada), e o topo dos painéis mostra sempre a logo da instalação, por decisão da feature do cabeçalho (P-12 dela). Opções: (a) topo do `/app` com organização aberta; (b) só onde a logo da organização já aparece (bloqueio e cadastro no `/admin`); (c) os dois. | RQ-03 | ➡️ (a): é o lugar onde "a logo marca" da instalação troca e onde o usuário da organização passa o dia; cai para a da instalação quando a organização não tem logo. | respondida no Adendo 1 |
| Q7 | Com a marca separada e uma organização que tem só a logo clara, o tema escuro do topo do `/app` mostra a escura **da instalação** (P-03: a regra por variante da tela de bloqueio). Confirma, ou prefere que, sem escura própria, a organização use a própria clara nos dois temas? | RQ-05 | ➡️ manter P-03: é a regra que a tela de bloqueio já aplica e que o pedido manda replicar; o formulário da organização não exige o par | retirada — coberta por P-03 (confirmar no PR) |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | A tela de bloqueio do `/app` já troca a logo da organização com o tema (clara/escura) quando a marca está separada; isso não é defeito e não muda. | step 3 — mapeamento do código e dos testes existentes | 2026-10-07 | RQ-01, RQ-02 | vigente |
| P-02 | A composição do cabeçalho (nome do projeto, nome do painel, logo) continua regida pela feature do cabeçalho; esta entrega só troca **qual** logo a composição e a marca simples usam no `/app` com organização aberta. | step 3 | 2026-10-07 | RQ-05 | vigente |
| P-03 | A resolução é por variante, independente, a mesma que `TelaBloqueio::urlsDasLogos()` já aplica: clara = clara da organização, senão clara da instalação; escura = `null` com a marca unificada, senão escura da organização, senão escura da instalação. Organização sem logo nenhuma usa o par da instalação. Organização só com a escura (estado que o formulário não produz, só dado) mostra a clara da instalação com a escura da organização, como a tela de bloqueio mostra hoje. | step 4 — confronto com o código (`TelaBloqueio::urlsDasLogos()`), revisada em 2026-10-07 | 2026-10-07 | RQ-05 | vigente — revisada |
| P-04 | Com a marca unificada (interruptor da aba Identidade ligado), a escura da organização é ignorada, como a da instalação. | step 3 — regra existente | 2026-10-07 | RQ-05 | vigente |
| P-05 | A página de erro do Sentinel dentro de `/app/{slug}` herda a marca do painel; o que ela mostra decorre de quando o middleware fixa a organização (404 de rota inexistente vem antes dele: logo da instalação; 403 depois dele: logo da organização). Comportamento decorrente, não exigido e não testado nesta entrega — registrado para o leitor. | step 5 — RD-06; revisada pela revisão adversarial (ADV-24) | 2026-10-07 | RQ-05 | vigente — revisada (ADV-24) |
| P-06 | O `alt` das imagens da marca não muda nesta entrega: o do Filament vem do nome da marca do painel e o da composição é o nome da aplicação; o `alt` com o nome da organização é da tela de bloqueio (CT-19 de `logo-dark-mode`) e fica fora de escopo. | step 5 — RD-12 | 2026-10-07 | RQ-05 | vigente |

## Fora de Escopo (declarado)

- Logo da organização nos painéis `/admin` e `/infra` (não há organização aberta neles).
- Logo da organização no seletor de organizações e no cadastro (`/admin/organizacoes`: cabeçalho, ficha, listagem) — opção (b)/(c) da Q1, não escolhida.
- Upload de logo pelo próprio painel `/app`.
- `alt` das imagens da marca com o nome da organização (P-06): só a tela de bloqueio o tem.
- Tag/release: não pedida nesta feature.
- A outra feature do mesmo pedido (rodapé): wiki e branch próprias.

## Adendo 1 — 2026-10-07

- **Fonte**: resposta do solicitante à Q1, no chat (pergunta de múltipla escolha feita pela sessão)
- **Fidelidade**: alta (opção escolhida numa lista; o rótulo da opção é o texto)
- **Responde a**: Q1

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Topo do /app com organização aberta (Recomendado)
>
> (descrição da opção escolhida, escrita pela sessão e aceita pelo solicitante: "Com organização aberta, a marca do topo do painel /app passa a ser a logo da organização (clara e escura, trocando com o tema), caindo para a da instalação quando ela não tem logo. Reverte a P-12 só para o /app.")

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-05 | No painel `/app`, com uma organização aberta, a marca do topo (a marca simples e a composição do cabeçalho) usa a logo da organização, clara e escura, trocando com o tema; sem logo da organização, usa a da instalação. | "Topo do /app com organização aberta" · "a marca do topo do painel /app passa a ser a logo da organização (clara e escura, trocando com o tema), caindo para a da instalação quando ela não tem logo" | funcional | RQ-03 |
