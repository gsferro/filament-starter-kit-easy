# Decisões Arquiteturais — Vínculo de provedor social

## ADR-01: Entrar pelo e-mail verificado continua sendo o padrão — é a mesma prova do "Esqueceu a senha?"

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

A pergunta do solicitante: "eu poderia criar uma conta do e-mail de outra pessoa no Google e
tentar subverter a autenticação assim?". O kit casa a conta local pelo e-mail que o provedor
devolve, desde que o provedor o declare **verificado** (`ProvedorSocial::emailVerificado()`:
Google `email_verified`, GitHub `primary && verified` via `/user/emails`, LinkedIn OpenID
`email_verified`, X `confirmed_email`).

### Decisão

Manter. Um provedor só marca um e-mail como verificado depois de mandar um código ou link **para
aquela caixa postal** — Google, GitHub, LinkedIn e X, os quatro. Então quem consegue uma
identidade "verificada" com o e-mail de outra pessoa **já controla a caixa postal dela**, e quem
controla a caixa postal já entra pelo "Esqueceu a senha?" do próprio kit. O login social não abre
uma porta que não existisse; ele aceita a mesma prova. É o modelo que o Auth0 chama de *trusted
providers* (vínculo automático só com e-mail verificado de provedor confiável).

### Alternativas Consideradas

1. **Tela "este e-mail já tem conta local, entre com a senha"** — não fecha nada: quem controla a
   caixa reseta a senha. Só acrescenta atrito para o caso legítimo, que é a maioria.
2. **Nunca vincular automaticamente; exigir convite por provedor** — muda o produto (o login
   social deixaria de ser "um segundo jeito de entrar") e não resolve o risco residual abaixo.

### Consequências

- **Positivas**: zero atrito no caso legítimo; a regra é uma só e está escrita.
- **Negativas / riscos residuais**: (a) **endereço reciclado** pelo provedor de correio (Yahoo
  recicla inativos): o novo dono verifica o endereço no Google e entra na conta do antigo — mas
  também resetaria a senha; (b) **bug ou comprometimento do provedor OAuth**. Os dois são
  mitigados por ADR-02 e ADR-03, não por esta.

### Referências

- `app/Support/ProvedorSocial.php:167` (`emailVerificado()`), README "O que o login social faz".
- `wikis/specs/feat/mais-provedores-sociais/…/02-decisoes-arquiteturais.md` ADR-03 (a barreira de
  verificação por provedor).

---

## ADR-02: O vínculo (`provedor`, `sub`) vence o e-mail nas entradas seguintes

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

Sem memória, toda entrada social é "a primeira": o kit refaz a pergunta "qual conta tem este
e-mail?" a cada volta do OAuth. É isso que deixa a reciclagem de endereço (ADR-01, risco a) mudar
de conta em silêncio, e é o que impede avisar a pessoa quando um provedor **novo** aparece.

### Decisão

Tabela `vinculos_sociais` (`user_id`, `provedor`, `sub`, `confirmado_em`, `ultimo_acesso_em`),
única por (`provedor`, `sub`). O `sub` é o identificador da conta **no provedor**
(`$doProvedor->getId()`), estável mesmo quando o e-mail muda. O `retorno()` consulta o vínculo
**antes** do e-mail: se há vínculo, a conta é a dele; o e-mail que o provedor devolveu nem é olhado
para escolher a conta. Se não há, aplica-se ADR-01 (ou ADR-03) e o vínculo é gravado.

### Alternativas Consideradas

1. **Coluna `google_id`, `github_id`… em `users`** — quatro colunas hoje, mais uma por provedor
   futuro, e nenhuma data. A tabela é o formato que cresce sem migration.
2. **Guardar o token do provedor** — o README promete que não ("Guarda token de acesso ou
   `refresh_token`: nada é gravado"), e o kit não chama API nenhuma do provedor depois do login.
   `sub` é identidade, não credencial.

### Consequências

- **Positivas**: reconhecimento estável; a "primeira vez" passa a ser observável (ADR-03/04);
  apagar o usuário apaga os vínculos (`cascadeOnDelete`).
- **Negativas**: uma tabela a mais; provedor que não devolva `sub` (nenhum dos quatro) cai no
  e-mail e fica sem vínculo — registrado em log, não em erro.
- **Riscos**: um `sub` já vinculado a outra conta na hora de confirmar (dois links em corrida) —
  a confirmação recusa em vez de re-vincular (CT-V08).

### Referências

- `app/Models/VinculoSocial.php`, migration `create_vinculos_sociais_table`.

---

## ADR-03: Primeira entrada em conta existente — avisar por padrão, confirmar se configurado

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

ADR-01 aceita a prova do e-mail verificado; ADR-02 dá memória. O que fazer **na primeira vez**
que um provedor aparece para uma conta que já existia é escolha de quem instala: há quem prefira
zero atrito e há quem exija uma confirmação a mais para a conta de administrador.

### Decisão

Dois modos, um interruptor (`KIT_SOCIALITE_VINCULO_CONFIRMAR`, também na tela de Settings):

| Modo | Primeira entrada de um provedor numa conta existente |
|---|---|
| **padrão** (`false`) | vincula, **entra**, e envia `PrimeiroAcessoSocial` ("sua conta foi acessada pelo Google pela primeira vez; não foi você? troque a senha e avise quem administra") |
| **confirmar** (`true`) | **não entra**; envia `ConfirmarVinculoSocial` com URL assinada de 30 minutos (`auth.social.confirmar`); a pessoa abre o link, o vínculo nasce e a sessão começa |

Conta **nova** (registro aberto) nasce já vinculada, em qualquer modo: não há conta anterior a
proteger.

### Alternativas Consideradas

1. **Confirmar pela senha local** — descartada como único caminho: a conta criada por outro
   provedor não tem senha conhecida (a rodada de validação mediu esse tropeço e criou o bloco
   "Definir senha por e-mail"). Pode ser acrescentada depois como segundo caminho.
2. **Sempre confirmar** — atrito em todo primeiro acesso, inclusive onde o risco residual não
   importa. Interruptor é mais honesto.
3. **Só avisar, sem modo estrito** — deixaria a conta de administrador sem opção mais dura.

### Consequências

- **Positivas**: o modo padrão preserva o comportamento medido na rodada; o aviso torna o risco
  residual **detectável** pela própria pessoa; o modo estrito fecha o caso (b) de ADR-01 no
  momento em que ele importa.
- **Negativas**: dois e-mails novos na fila — sem worker nada sai (README avisa).
- **Riscos**: link assinado é portador — quem lê a caixa postal confirma. É, de novo, a mesma
  prova do "Esqueceu a senha?"; a janela de 30 minutos e o `throttle` do grupo limitam o resto.

### Referências

- `LoginSocialController::pedirConfirmacaoDoVinculo()`, `::confirmarVinculo()`;
  `routes/web.php` (rota `confirmar`, middleware `signed`).

---

## ADR-04: Booleano, não enum, para o interruptor — e ele vive nos três lugares do Settings

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

A proposta escreveu `KIT_SOCIALITE_VINCULO=confirmar`. O kit já tem seis interruptores de login
booleanos com `FILTER_VALIDATE_BOOLEAN` e `Toggle` na tela.

### Decisão

`KIT_SOCIALITE_VINCULO_CONFIRMAR=false` (booleano), lido por `config('kit.login.vinculo_confirmar')`
via `ConfiguracaoDoLogin::vinculoExigeConfirmacao()`, e propriedade `login_vinculo_confirmar` no
Settings — classe, `mapaDeConfiguracao()` e migration (`.ai/rules/settings.md`). A chave é lida
**por request** (no `retorno()`), então pode ser governada pela tela.

### Alternativas Consideradas

1. **Enum de dois valores** — é um booleano com nome mais longo e sem `Toggle`.
2. **Só `.env`** — contradiz a regra do kit: tudo que é lido por request e é do login está na tela.

### Consequências

- **Positivas**: coerência com os outros interruptores; falha fechado (qualquer valor estranho é
  o modo padrão).
- **Negativas**: um terceiro modo futuro exigiria trocar o tipo — não há terceiro modo previsto.

### Referências

- `config/kit.php` (`login.vinculo_confirmar`), `database/settings/*_add_login_vinculo_confirmar_*`.
