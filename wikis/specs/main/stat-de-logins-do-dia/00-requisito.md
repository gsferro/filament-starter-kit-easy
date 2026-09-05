# Requisito — Stat de logins do dia em "Usuários e acesso"

## Fonte

- **Origem**: pedido do usuário no chat, via invocação da skill `feature-wiki`
- **Data**: 2026-09-04
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, colado verbatim abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> no painel admin, na parte dos widgets "Usuários e acesso" temos 5 stats, adicione +1 (para ficar 6 e harmonico com a tela) de login no dia usando o grafico dentro do stat com historico dos ultimos 7 dias
> - os dados vem da tela de logs de acesso

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O widget "Usuários e acesso" do painel `admin` ganha um sexto stat | "na parte dos widgets \"Usuários e acesso\" temos 5 stats, adicione +1" | funcional |
| RQ-02 | O valor do stat novo é a quantidade de logins **do dia** | "de login no dia" | funcional |
| RQ-03 | O stat exibe um gráfico **dentro dele**, e não um widget de gráfico ao lado | "usando o grafico dentro do stat" | restrição |
| RQ-04 | O gráfico mostra o histórico dos **últimos 7 dias** | "com historico dos ultimos 7 dias" | funcional |
| RQ-05 | Os dados saem da mesma fonte da tela de logs de acesso | "os dados vem da tela de logs de acesso" | restrição |
| RQ-06 | Seis stats deixam a linha do widget harmônica na tela | "(para ficar 6 e harmonico com a tela)" | não-funcional |

## Ambiguidades e Perguntas Abertas

Nenhuma foi levada ao usuário: as três têm default óbvio e o custo de errar é baixo e reversível.
Todas seguem como **premissa explícita**, com o que muda se forem negadas.

- **RQ-02 — "login no dia" conta tentativa que falhou?**
  - **Assumido**: **não**. Só login bem-sucedido. Uma tentativa recusada não é um login, e o kit
    já separa as duas grandezas em `App\Filament\Infra\Widgets\AutenticacaoStats`, onde falha tem
    stat próprio e cor de alerta.
  - **Se negado**: some o filtro `login_successful` do valor e da série; o número sobe e passa a
    misturar ataque de força bruta com uso normal.

- **RQ-04 — "últimos 7 dias" inclui hoje?**
  - **Assumido**: **sim**. Sete posições, sendo hoje a última. É o desenho que faz o valor grande
    (RQ-02, o dia de hoje) ser a ponta direita da série — sem isso, número e gráfico contariam
    coisas diferentes na mesma caixa.
  - **Se negado**: a série passa a ser dos 7 dias anteriores a hoje, e o valor do stat deixa de
    ter correspondência visual com o gráfico.

- **RQ-06 — o que acontece com a harmonia numa instalação sem a tabela de log de acesso?**
  - **Assumido**: o stat novo **não é exibido**, e o widget volta às 5 stats de hoje.
  - **Se negado**: seria preciso exibir o stat com valor zero, o que afirma "ninguém entrou hoje"
    numa instalação onde o dado simplesmente não é coletado. Ver ADR-03.

- **Levantadas na derivação dos casos de teste** — o requisito não determina o texto visível do
  stat novo. Nenhuma bloqueia implementação; todas mudam só o que se lê na tela.
  - Rótulo do stat: assumido `Logins hoje`.
  - Ícone e cor: assumidos `heroicon-o-arrow-right-on-rectangle` e `success`.
  - Formato do rótulo de cada dia no gráfico: assumido `d/m`.
  - **Se negados**: troca de string, sem efeito em nenhum caso de teste — nenhum `Então` afirma
    esses valores, de propósito.

## Fora de Escopo (declarado)

- Alterar os cinco stats existentes. Nenhum deles muda de valor, cor, ícone, descrição ou ordem.
- Gráfico de acesso como widget próprio no dashboard. O requisito é explícito em querer o gráfico
  **dentro** do stat (RQ-03).
- Distinguir por painel, por organização ou por dispositivo. O stat conta logins, sem recorte.
- Contar **usuários distintos** em vez de logins. O requisito diz "login no dia".
- Widget equivalente nos painéis `/app` e `/infra`. O requisito diz "no painel admin".
