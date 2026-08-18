# Relatório de QA — Enriquecimento do kit para a versão 1.0

**Data**: 2026-08-18 · **Ciclo**: 1 de 3 · **Branch**: `feature/v1-enriquecimento-kit`
**Oráculo**: `00-requisito.md` (fidelidade alta, texto verbatim do usuário)

---

## Matriz de Rastreabilidade

O que este quadro procura é a **omissão silenciosa**: cláusula que nunca virou passo, nunca virou
teste, nunca virou código — e por isso não aparece em nenhuma falha.

| RQ | Plano | Código / artefato | Teste | Estado |
|----|-------|-------------------|-------|--------|
| RQ-01 analisar AdminPanel do `mini-pff` | passo 0 | `08-comparativo-mini-pff.md` §1 e §3 | n/a (pesquisa) | ✅ |
| RQ-02 importar a view do dropdown | passos 2, 3 | 2 views + `User::papelDoPainel()` | CT-06..CT-10 | ✅ |
| RQ-03 em **todos** os painéis | passo 4 | 3 `renderHook` | **CT-06** (dataset dos três) | ✅ |
| RQ-04 demais features do `mini-pff` | passo 8 | `08` §2 (7 lacunas), §3 (o que não portar) | n/a | ✅ |
| RQ-05 só plataforma, nada de negócio | passo 8 | corte declarado em `08` §"O corte" | n/a | ✅ |
| RQ-06 revisar e confirmar que funciona | passo 1 | `03-progresso.md` §1 | 4 verificações verdes | ✅ |
| RQ-07 melhorias e features de valor | passos 8, 9 | `08` §5 (6 itens) + §6 (ordem para a 1.0) | n/a | ✅ |
| RQ-08 revisão completa dos pacotes | passo 7 | `varredura/lote-{1..7}` | n/a | ✅ |
| RQ-09 do início ao fim das páginas | passo 7 | 61 páginas; 62 e 63 vazias; 547 = contador do site | n/a | ✅ |
| RQ-10 listar candidatos | passos 7, 9 | `pacotes-candidatos.md` §2 e §3 | n/a | ✅ |
| RQ-11 top 10 com prós e contras | passo 9 | `pacotes-candidatos.md` §2 | n/a | ✅ |
| RQ-12 só v5 e free | passo 7 | filtro `v=5&price=free` em **toda** URL varrida | n/a | ✅ |
| RQ-13 sub-agentes para varredura | passo 7 | 7 sub-agentes | n/a | ✅ |
| RQ-14 sub-agentes para análise | passo 7 | mesmos agentes, com o perfil do kit como critério | n/a | ⚠️ **sob premissa** |
| RQ-15 documentar na wiki | passos 2..10 | 8 arquivos de feature + 1 permanente | n/a | ✅ |
| RQ-16 usados / descartados / candidatos | passos 9, 10 | `pacotes-candidatos.md` §1, §3, §4 | `KitUpdateTest` | ✅ |
| RQ-17 branch nova | passo 0 | `feature/v1-enriquecimento-kit` | n/a | ✅ |
| RQ-18 não fazer merge | — | **não feito** | n/a | ✅ |
| RQ-19 implementar na branch | passos 2..6 | 6 arquivos de código, 3 de teste | 34 casos | ✅ |
| RQ-20 relatório para decisão posterior | passo 9 | `pacotes-candidatos.md` §5 diz o que fazer com ele | n/a | ✅ |
| RQ-21 responder em Caveman `ultra` | — | conduta de conversa | n/a | ✅ |

**Nenhuma cláusula sem plano. Nenhuma cláusula sem artefato.** Uma sob premissa declarada (RQ-14),
registrada no `00-requisito.md` §Ambiguidades com o "Se negado" correspondente.

### A premissa que sustenta RQ-08 a RQ-12 e RQ-20

Nada foi instalado. O `CLAUDE.md` do projeto proíbe alterar dependências sem aprovação, e o próprio
texto do requisito pede *"um relatório a respeito para minha consideração posterior"*. A entrega é o
relatório; a instalação é a decisão seguinte, do mantenedor. Registrado em ADR-07 e no `00`.

**Se essa leitura estiver errada**, o desvio é grande e conhecido: cada pacote aprovado vira um passo
de PRD com CTs próprios, e nada do que foi entregue precisa ser desfeito.

---

## Verificações executadas

| # | Verificação | Resultado |
|---|---|---|
| 1 | `pint --test --parallel` (antes) | verde |
| 2 | `phpstan analyse` (antes) | 0 erros |
| 3 | `test --testsuite=Kit --parallel` (antes) | 278 / 739 asserções — verde |
| 4 | `test --testsuite=Tenancy --parallel` (antes) | 77 / 308 asserções — verde |
| 5 | `pint` (depois) | verde |
| 6 | `phpstan analyse` (depois) | 0 erros |
| 7 | `test --testsuite=Kit,Tenancy --parallel` (depois) | **388 / 1099 asserções — verde** |
| 8 | `npm run build` + suíte `Browser` (em 4 blocos) | **27 casos: 25 ✅, 2 pulados, 0 falhas**, 145 asserções |

Delta: **+33 casos de componente, +1 de browser**, nenhum caso existente alterado.
Os 2 pulados da suíte `Browser` são pré-existentes.

> **Nota de execução.** A suíte `Browser` inteira num comando só foi interrompida duas vezes pelo
> ambiente, sem emitir resultado — o formato de saída do Pest só escreve no fim, então uma execução
> morta não deixa evidência nenhuma. Rodada em 4 blocos de arquivos, passa. A primeira versão deste
> relatório afirmava "verde" com base na execução isolada do CT-B01; o número da suíte completa só
> foi medido depois, e está corrigido acima.

---

## As 11 dimensões que CT e CT-B não cobrem

| # | Dimensão | Achado |
|---|----------|--------|
| 1 | **Fronteiras** | Zero papéis, papel de outro painel, painel inexistente, organização inativa e contexto de tenant trocado — todos cobertos (CT-03, CT-02, CT-05, CT-16, CT-13). Nenhuma fronteira aberta. |
| 2 | **Matriz de permissão** | A feature **não cria** permissão nem policy. O que ela exibe é o próprio papel do próprio usuário — não há registro de terceiro na tela. Sem achado. |
| 3 | **Log real** | Nenhum log, por decisão (ADR-05). Verificado: `grep` por `Log::` no diff devolve vazio. Consistente com o declarado. |
| 4 | **N+1** | Uma consulta por página renderizada, com `first()` (`LIMIT 1`). Não há laço: o cabeçalho é único por página. `master_global` nem consulta — sai no primeiro `if`. |
| 5 | **UX de erro** | Não há caminho de erro: a view tem guarda de usuário nulo e guarda de papel ausente, e o método nunca lança. Uma organização inativa não some com o badge (CT-16). |
| 6 | **Tema / dark mode** | ⚠️ **Achado**. As classes têm par claro/escuro explícito, mas **nenhuma asserção o verifica** — `assertSee` não valida tema. Verificação foi por leitura de código. Documentado como lacuna assumida no `05`. |
| 7 | **Acessibilidade** | O cabeçalho é conteúdo estático dentro do painel do dropdown, que o Filament já governa por teclado. Não foi acrescentado nenhum elemento focável — a decisão de **não** usar `userMenuItems()` (ADR-01, alternativa 3) foi tomada exatamente para não criar um "botão" que não faz nada. Nome e e-mail têm `title` para o valor completo quando truncados. |
| 8 | **Segurança da superfície nova** | Nada novo é exposto: nome, e-mail e papel do **próprio** usuário autenticado. Sob impersonação, o cabeçalho passa a mostrar o alvo — o que é ganho de segurança operacional, não vazamento (quem impersona já vê tudo do alvo). |
| 9 | **Regressão adjacente** | Obrigatória (o PRD marca "toca infra compartilhada"). `User` e os 3 painéis são consumidos por `multi-tenancy`, `perfil-e-acesso-ao-painel` e `admin-da-organizacao`. **Suíte inteira verde, nenhum caso existente alterado.** |
| 10 | **Adequação da suíte** | Ver a tabela de mutantes do `04`. Dois mutantes sobrevivem por lacuna **declarada** (M4 `guard_name`, M7 badge vazio), com o motivo escrito. O mutante que mais importa — M2, trocar `papeisEmQualquerContexto()` por `roles()` — é morto por CT-13, e só por ele. |
| 11 | **Oráculo fraco** | O risco era a suíte afirmar "a página abriu" em vez de "o cabeçalho está lá". Evitado: CT-06 ancora em `data-user-menu-header`, e o CT-B01 usa `assertVisible` com o par presente-e-invisível antes do clique. |

---

## Achados

### A-01 · Tema não verificado por asserção — **baixa** · destino: **teste** (aceito como dívida)

O par claro/escuro do badge existe no código e não é verificado. Provar contraste exige comparação de
pixel, que o kit não faz em lugar nenhum. **Não bloqueia.** Registrado em `05` §"O que não virou
CT-B" e coerente com a nota de `.ai/rules/testes-browser.md` ("`assertSee` não valida tema").

### A-02 · `guard_name` sem mutante que o mate — **baixa** · destino: **teste** (adiado)

O kit tem um único guard. Um caso que matasse M4 teria de fabricar um segundo guard que o kit não
usa. **Reavaliar se o kit ganhar guard de API.**

### A-03 · Nome Composer dos candidatos não confirmado — **média** · destino: **não-defeito**

Os `vendor/pacote` do `pacotes-candidatos.md` vêm do slug da URL do diretório, que **não** expõe o
nome Composer. Está declarado como limite na seção "Método" da própria página, com a instrução de
confirmar no README antes de `composer require`. Não é defeito da entrega; é limite da fonte, e
declarado.

### A-04 · Wiki nova fora do `KitUpdate` — **alta** · destino: **implementação** · ✅ **corrigido**

Detectado pelo **próprio guard test do kit** (`KitUpdateTest`): `wikis/pacotes-candidatos.md` não
estava em `KitUpdate::CAMINHOS_DO_KIT`, então quem já instalou nunca receberia o arquivo. Corrigido no
mesmo ciclo; teste verde.

> Vale registrar: este foi o único defeito real do ciclo, e quem o pegou não fui eu — foi um teste
> que o kit já tinha, escrito para exatamente esta classe de esquecimento.

---

## Veredito

# ✅ APROVADO COM DÉBITO

**Débito registrado** (nenhum bloqueante):

1. A-01 — tema do badge sem asserção. Paga quando o kit tiver estratégia de verificação visual.
2. A-02 — `guard_name` sem mutante. Paga se o kit ganhar um segundo guard.
3. RQ-14 — análise feita pelos mesmos agentes da varredura. Se o usuário quiser ficha técnica por
   finalista, é trabalho adicional, não retrabalho.

**Ciclos usados**: 1 de 3.

---

## O que o usuário precisa decidir

Fora do escopo desta entrega, por design:

1. **Merge para a `main`** — RQ-18 é explícito: a decisão é do usuário.
2. **Instalação de qualquer pacote** do top 10 — ADR-07.
3. **Ordem da 1.0** — a proposta está em `08-comparativo-mini-pff.md` §6.
4. **Os 3 candidatos a rule de projeto** — listados no fim do `03-progresso.md`, nenhum gravado.
