# Decisões Arquiteturais — Cadastro pelo provedor social

## ADR-01: O provedor não ganha lógica de cadastro própria — entrega o contexto às portas que já existem

**Status**: Aceita · **Data**: 2026-08-26

### Contexto
O kit tem DUAS portas de criação de conta com regras próprias: `RegistroAberto::registrar($dados, ?Tenant)` (papel único, pendência de aprovação, exige organização com tenancy, recusa organização fechada) e `Convite::aceitar($dados)` (e-mail do convite vence, organização e papel do convite, uso único, conta existente). O login social já usava a primeira, mas sem organização, e não usava a segunda.

### Decisão
O controller só **transporta** `org` e `token` da tela de registro até a volta do OAuth e chama a porta certa: convite válido → `Convite::aceitar()`; senão → `RegistroAberto::registrar($dados, RegistroAberto::organizacao($org))`. Nenhuma regra de cadastro é reescrita no controller.

### Alternativas
1. Reimplementar "criar na organização" no controller — duplica a regra e a deixa divergir (a matriz P1 da rodada anterior já pagou por duas fontes da verdade).
2. Tela intermediária "escolha a organização" depois do OAuth — mais uma tela, e a organização já é conhecida antes do clique.

### Consequências
- Positivas: toda validação do formulário vale no caminho social de graça (organização fechada, convite expirado, pendência).
- Negativas: o e-mail do provedor precisa ser igual ao do convite — o formulário força o e-mail do convite; o provedor não pode. Recusa explícita (ADR-03).

---

## ADR-02: O contexto viaja pela sessão, consumido em qualquer desfecho

**Status**: Aceita · **Data**: 2026-08-26

### Contexto
O `state` do OAuth é do Socialite (anti-CSRF); sobrescrevê-lo exige driver próprio. A URL de callback é fixa no console do provedor.

### Decisão
`redirecionar()` grava `login_social.contexto = ['org' => …, 'token' => …]` na sessão; `retorno()` faz `pull()` logo após obter o usuário do provedor — antes de qualquer decisão — para que o contexto **morra** mesmo em recusa. Só o ramo "sem conta" usa `org`; `token` vale para conta nova e existente.

### Alternativas
1. `state` customizado — código no driver de cada provedor.
2. Query na URL de callback — o provedor só aceita a URL cadastrada; `?org=` na callback é outra URL.

### Consequências
- Positivas: uma linha para gravar, uma para consumir; sem dependência do provedor.
- Riscos: contexto pendente de um clique abandonado. Mitigado pelo `pull()` (o próximo callback o consome e descarta) e por só o ramo de criação usar `org`. Um usuário que abandonou `?org=acme` e depois se cadastrou por outro botão sem org **na mesma sessão** cairia em `acme` — mas isso exige que ele mesmo tenha aberto o registro da `acme` naquela sessão; é a organização que ele estava se cadastrando.

---

## ADR-03: E-mail do provedor diferente do e-mail convidado recusa — não vincula, não cria

**Status**: Aceita · **Data**: 2026-08-26

### Contexto
O convite é para um e-mail. No formulário o campo vem preenchido e travado. O provedor devolve o e-mail que a pessoa tem lá.

### Decisão
Se `Convite::valido($token)` existe e o e-mail verificado do provedor difere do e-mail do convite, recusa com "Este convite é para outro e-mail" e o convite fica **intacto** (pendente). Nada é criado.

### Alternativas
1. Criar a conta com o e-mail do convite e vincular o provedor — a pessoa entraria pelo provedor com um e-mail que não é o do provedor; o vínculo por `sub` até funcionaria, mas o "e-mail verificado" deixaria de ser a prova.
2. Criar com o e-mail do provedor e consumir o convite — o convite seria aceito por outra pessoa/identidade.

### Consequências
- Positivas: o convite continua sendo para quem foi enviado.
- Negativas: quem tem e-mail diferente no provedor usa o formulário (senha). A mensagem diz isso.

---

## ADR-04: Conta existente aceita convite pelo botão social

**Status**: Aceita (sob premissa — ver `00`) · **Data**: 2026-08-26

### Contexto
`RegistroPorConvite` já aceita convite para quem tem conta ("Entrar e aceitar"). Pelo provedor, a prova é a mesma: token que veio do e-mail + dono do e-mail verificado.

### Decisão
Nos ramos "vínculo existe" e "conta existe", se há convite válido para aquele e-mail, `aceitarComoUsuarioExistente()` roda antes do resto; falha (`RuntimeException`: já usado, outra pessoa) só registra `warning` e o login segue. No modo estrito do vínculo, o convite é aceito e a sessão ainda depende da confirmação por e-mail — coerente: a pessoa já é membro, só falta a entrada.

### Consequências
- Positivas: o link do convite funciona para quem prefere o provedor, com ou sem conta.
- Negativas: a mensagem de recusa do modo estrito não menciona o convite aceito; o e-mail de confirmação basta.
