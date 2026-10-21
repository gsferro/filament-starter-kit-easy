> Referência da feature-wiki 4.2.1. Lida em: etapa Roteiro de Teste Manual (entre os steps 10 e 11) e ao pedido tardio do usuário. Fonte única de: template, conferência, execução, segurança, tickets e atualização do 08-roteiro-teste-manual.md.

# Template do `08-roteiro-teste-manual.md`

Documento da implementação, escrito em linha pela sessão após o step 10. Os exemplos abaixo são
fictícios: substituir comandos, dados, IDs e resultados pelos fatos da feature e pelos seus CT/RQ.
Um `RM-nn` por fluxo de valor; numeração única também nos negativos e nas permissões.
Cobrir caminho feliz, negativos e permissões das RQ com superfície manual. Concorrência,
mutantes, precisão numérica e tempo ficam em `## Fora deste roteiro`, com motivo.
Com `07-tickets/`, adicionar `**Ticket**: 02` (ou os tickets do fluxo) em cada RM; sem tickets, omitir.
Gerar um `08` por feature, depois do último ticket `concluído`; não gerar por ticket.
Nas tabelas, escrever `|` dentro de código como `\|`, preservando as colunas Markdown.

A decisão fica só no `03`, em formato de **linha**, nunca como tabela-modelo nem no `00`/`01`/`02`:

```text
Roteiro manual: {sim | não} — {YYYY-MM-DD} — {fonte: resposta a Qn | instrução explícita do desenvolvedor | convenção {arquivo} | sem resposta a Qn} — {sinal ou motivo}
```

Mudança sempre acrescenta linha abaixo; riscar anteriores com `~~…~~`. Pedido antes do step 10
só atualiza a decisão no `03`; gerar na etapa normal. Depois do step 10, gerar na hora.

```markdown
# Roteiro de Teste Manual — {Card}: {Título}

> **Para quem**: {revisor do PR, QA humano, solicitante na homologação, operador do deploy}
> **Requisito/Casos**: [00-requisito.md](00-requisito.md) · [04-casos-de-teste.md](04-casos-de-teste.md) · [05-casos-de-teste-browser.md](05-casos-de-teste-browser.md) <!-- omitir 05 se ausente -->
> **Base**: {commit curto} · {YYYY-MM-DD}
> **Vias**: {terminal | aplicação | ambas}
> **Conferência**: {n} de {m} roteiros executados pela sessão
> **Auditoria**: {pendente — próximo ciclo do quality gate | auditado — ciclo N, data e link do 06}
> O resultado esperado vem do CT/CT-B/RQ/P-nn citado; o código só decide como chegar lá.
<!-- Se gerado depois do step 11: Auditoria: não auditado pelo quality gate (gerado depois do ciclo {N}).
     Não herdar veredito de ciclo anterior à geração; atualizar somente após auditoria efetiva. -->

## Preparação do ambiente

| # | Ação | Comando ou caminho | Como confirmar |
|---|---|---|---|
| 1 | Preparar ambiente local com configuração da feature | `.env` local, conforme `.env.example` e `01` | Banco e integrações apontam para ambiente de teste |
| 2 | Criar dados reproduzíveis | {factory/seed conferido no projeto} | {estado exigido pelo CT citado} |
| 3 | Servir aplicação, se houver via aplicação | {comando do projeto}; `http://localhost` | {URL inicial responde no ambiente local} |

## Dados e usuários de teste

| Papel | Como obter | Estado necessário |
|---|---|---|
| Aprovador | {factory/state/seed; segredo somente no `.env` local} | {permissões e vínculo exigidos pelo CT-01/CT-B01} |
| Sem permissão | {factory/state/seed} | {vínculo e ausência de permissão do CT-B02} |
| Pedido | {factory/state/seed} | {pendente e elegível segundo RQ-01} |

## Roteiros

### RM-01 — Simular aprovação em lote · Via: terminal

**Origem**: RQ-01 · CT-01
**Conferência**: {conferido pela sessão YYYY-MM-DD | não executado — motivo}

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Simular lote com os pedidos preparados | `php artisan pedidos:aprovar-lote --dry-run` | Exibe os pedidos elegíveis e mantém os estados gravados (Então do CT-01) | CT-01 · RQ-01 | {observação de quem executou; vazio até executar} |

### RM-02 — Aprovar pedido · Via: aplicação

**Origem**: RQ-01 · CT-B01
**Conferência**: {conferido pela sessão YYYY-MM-DD | não executado — motivo}

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Entrar como aprovador e abrir pedido preparado | `http://localhost/pedidos/{id}`; botão `Aprovar` | Após clicar, mostra situação aprovado (Então do CT-B01) | CT-B01 · RQ-01 | {observação de quem executou} |

## Casos negativos e permissões

### RM-03 — Recusar aprovação sem permissão · Via: aplicação

**Origem**: RQ-02 · CT-B02
**Conferência**: {conferido pela sessão YYYY-MM-DD | não executado — motivo}

| # | Ação | Comando / tela | Resultado esperado | Origem | Resultado obtido |
|---|---|---|---|---|---|
| 1 | Entrar sem permissão e acessar o pedido preparado | `http://localhost/pedidos/{id}` | Acesso recusado; pedido permanece pendente (Então do CT-B02) | CT-B02 · RQ-02 | {observação de quem executou} |

<!-- Acrescentar negativos de entrada/estado e permissões de cada RQ com superfície manual.
     Usar o mesmo formato RM, sem reaproveitar IDs dos caminhos felizes. -->

## Limpeza

- {Reverter dados de teste pelo procedimento local conferido; identificar o que preservar.}
- {Comando destrutivo, somente se necessário: aviso explícito "apenas ambiente descartável confirmado; o quality gate não executa".}
<!-- migrate:fresh, db:wipe, truncates e filas de produção são destrutivos. Nunca operar produção.
     Não gravar credenciais reais no roteiro: usar factory/seed/.env. -->

## Fora deste roteiro

| CT | Motivo de ser só automatizado |
|---|---|
| {CT de concorrência} | Interleaving exige execução controlada e asserção automatizada |
| {CT de precisão/tempo ou prova de mutante} | Medida exige precisão/repetição ou mutação do código; inspeção manual não prova |

## Registro de execução

| Data | Quem | Ambiente | Commit | RM executados | Resultado | Observações |
|---|---|---|---|---|---|---|
| {YYYY-MM-DD} | sessão {identificação} | {local e vias efetivamente disponíveis} | {commit curto} | {RM efetivamente executados} | {resultado observado} | {limites da conferência} |

<!-- Preenchido por quem executa à mão. A sessão só preenche a linha da própria conferência;
     sem execução, não criar linha de execução nem preencher Resultado obtido como se tivesse rodado. -->
```

## Esperado completo, preparação separada

- **Passo que verifica** reproduz **todas** as pós-condições observáveis do `Então` citado: valor
  exato, estado, contagem, unicidade e ausência de efeito colateral (o que "não muda", "não cria",
  "não consome"). Resumir o `Então` perde verificação: quem executa à mão confere menos do que o CT
  exige. Se o RM só exercita parte dos Exemplos do CT, dizer qual linha e manter as pós-condições dela.
- **Passo de preparação** leva `prep ·` na Origem (`prep · CT-03`) e descreve só o estado de partida
  que o `Dado` do CT pede. Não apresentar preparação como verificação do CT.
- **Passo negativo** diz o motivo esperado da recusa (estado, permissão, validação de qual campo) e,
  conferido, registra a classe ou mensagem observada. Recusa pelo motivo errado não confere o RM.
- **Preparação documentada é a executada.** Corrigida durante a conferência, reexecutar o bloco
  corrigido desde o início antes de marcar qualquer RM que dependa dela.
- Rodada 2026-10-20: o juiz cego classificou 43 de 72 esperados como parciais — preparação citando o
  CT como oráculo e pós-condições omitidas — sem nenhum divergente; a auditoria do log achou recusas
  sem motivo registrado e uma preparação diferente da que rodou.

## Conferência e limites

- **Script** — `bash {skills}/feature-wiki/scripts/roteiro-manual.sh {wiki}`: decisão × presença do
  `08` (inclusive arquivo sem decisão), esperado não vazio e Origem com ID nas tabelas de passos,
  CT/CT-B vigentes, existência e vigência de RQ/P por Estado e Substitui integral de Adendo,
  RM duplicado. `aberta` não é achado do script: fica para leitura. Ignorar decisões riscadas,
  comentários e exemplos cercados; usar a última decisão não riscada. Colar saída e exit em
  `## Verificação Final` do `03`; silêncio + exit 0 = OK.
- **Leitura (L8)** — fidelidade ao `Então` do CT/CT-B ou à RQ/P citada, cobertura dos fluxos e
  permissões, rota/comando existente, segurança dos dados e honestidade da conferência.
  ID existente não prova resultado fiel. Comportamento só encontrado no código volta ao roteamento
  do step 9 (`P-nn` → CT → correção, quando não há RQ) e exige **repetir o step 10** antes de
  retomar o `08`; nunca vira resultado esperado por cópia da implementação.
- **Execução** — conferir terminal não destrutivo e aplicação pelo Playwright MCP quando disponíveis;
  usar app local servido, dados de teste e ambiente descartável confirmado para qualquer gravação
  de estado. Comandos destrutivos (`migrate:fresh`, `db:wipe`, truncates e filas de produção)
  exigem aviso e ambiente descartável confirmado; nunca operar produção nem gravar credenciais
  reais no roteiro (usar factory/seed/`.env`). O **quality gate nunca executa RM com comando
  destrutivo nem RM que grava estado fora de ambiente descartável confirmado**: registrar
  `Não Verificado — destrutivo/ambiente não confirmado`. Só executa RM não destrutivos contra
  app local servido. Falta de ambiente/ferramenta vira `não executado — {motivo}` em cada RM
  afetado. Só marcar conferido quando todos os passos do RM rodaram. O cabeçalho soma apenas
  os RM executados; `## Registro de execução` registra somente a própria conferência.
- **Atualização** — mudança de código após gate repete o step 10 e reconcilia o `08`, inclusive base,
  caminhos e conferências invalidadas antes do próximo ciclo; não conservar execução como prova
  de código novo. Pedido pós-gate recebe `Auditoria: não auditado pelo quality gate (gerado depois
  do ciclo {N})`; nunca herdar veredito de ciclo que não leu o arquivo.
- **Limite** — roteiro não é oráculo novo nem substitui Pest, CT-B ou quality gate. **Hipótese não
  medida**: custo de tokens, manutenção e deriva justificam a opção no step 4; conferir não é
  medir eficácia comportamental.
