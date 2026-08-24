# Requisito — W7: validação de e-mail editável na tela

## Fonte

- **Origem**: `.claude/requisitos/w7-verificacao-de-email-editavel.txt` (raiz do repositório), entregue verbatim pelo orquestrador da rodada
- **Data**: 2026-08-24
- **Autor / solicitante**: dono do kit (dívida declarada na v0.19.1, achado Blocker QA-01 da wiki `registro-e-aprovacao`)
- **Fidelidade**: alta (texto escrito, arquivo versionado)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> [Contexto: divida declarada na v0.19.1, achado Blocker QA-01 da wiki registro-e-aprovacao]
>
> Ao liberar o register, abra opções se deve usar a validação de email.
>
> A opção existe e funciona, mas SÓ pelo .env (`KIT_REGISTRO_VERIFICAR_EMAIL`). Ela nao pode
> ser editada na tela de Configuracoes do Kit porque e lida no BOOT do painel, e o middleware
> de e-mail verificado e fixado no array da rota no momento do registro
> (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`) — nao por request. Nem
> Closure em `isRequired` resolve.
>
> O caminho registrado no docblock de `App\Support\RegistroAberto`: um middleware proprio do
> kit que decida por request, tirando a decisao do array da rota.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A exigência de e-mail validado passa a ser editável na tela de Configurações do Kit, na aba Registro | "Ao liberar o register, abra opções se deve usar a validação de email" | funcional |
| RQ-02 | A decisão passa a ser tomada **por request**, e não no boot do painel / no registro da rota | "um middleware proprio do kit que decida por request, tirando a decisao do array da rota" | restrição |
| RQ-03 | O mecanismo é um middleware **próprio do kit** — não uma Closure em `isRequired`, que o requisito descarta explicitamente | "Nem Closure em `isRequired` resolve" | restrição |
| RQ-04 | Com a opção ligada, o usuário do `/app` sem `email_verified_at` é barrado e levado à tela de confirmação | "se deve usar a validação de email" (+ comportamento já existente pelo `.env`, que a edição na tela precisa preservar) | funcional |
| RQ-05 | Com a opção desligada, ninguém é barrado e nenhum e-mail de verificação é enviado | "opções se deve usar" — a opção tem os dois lados; o lado desligado é o default do kit | funcional |
| RQ-06 | O valor gravado na tela vence o `.env` e vale **no request seguinte**, sem deploy | "Ela nao pode ser editada na tela … porque e lida no BOOT" — o defeito a corrigir é justamente o efeito diferido | funcional |
| RQ-07 | Quem entra por convite continua entrando sem ser barrado e sem receber e-mail de verificação | contexto do achado QA-01 e `Convite::aceitar()` (`app/Models/Convite.php:591`), citado na dívida | não-funcional (não-regressão) |
| RQ-08 | Os painéis `/admin` e `/infra` não passam a exigir e-mail validado | escopo do requisito é "ao liberar o register", que é o `/app` | restrição |
| RQ-09 | A rota de destino do redirecionamento tem de existir sempre que o middleware puder redirecionar | consequência direta de RQ-02: middleware que decide por request precisa de destino que não dependa da decisão | restrição |

## Ambiguidades e Perguntas Abertas

<!-- Sem usuário disponível na execução: premissa registrada com o custo de negá-la. -->

- **RQ-01** — a chave continua existindo no `.env` depois de virar editável?
  - **Assumido**: sim. `KIT_REGISTRO_VERIFICAR_EMAIL` continua sendo o **semeador** e o plano B,
    exatamente como as demais propriedades do Settings (regra dura do docblock de
    `App\Settings\ConfiguracoesDoKit`: *"o banco vence em tempo de execução; o .env semeia e é o
    plano B"*).
  - **Se negado**: a chave sairia de `config/kit.php` e da migration de settings, e
    `RegistroAberto::exigirVerificacaoDeEmail()` passaria a ler o Settings direto — o que quebra o
    "ponto único lê `config()`" que o CT-01 enforça. Custo: passo 3 e passo 4 refeitos.

- **RQ-05** — "nenhum e-mail sai" cobre também o usuário que **navega de propósito** até
  `/app/email-verification/prompt` com a opção desligada e clica em *reenviar*?
  - **Assumido**: não. A cláusula é sobre o fluxo — com a opção desligada ninguém é levado à tela
    e o registro não dispara envio. A tela permanece alcançável por URL digitada, e para quem já
    tem `email_verified_at` ela redireciona sozinha no `mount()`
    (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:29-33`).
  - **Se negado**: seria preciso uma subclasse da página de prompt que redireciona quando a opção
    está desligada — um arquivo novo, e um passo novo no plano.

## Fora de Escopo (declarado)

- Verificação de e-mail nos painéis `/admin` e `/infra` (RQ-08 a proíbe explicitamente).
- Verificação de **troca** de e-mail (`emailChangeVerification()` do Filament) — outra feature.
- Reparo automático de base legada: o comando de reparo do README continua sendo manual e
  deliberado. Automatizá-lo marcaria e-mails como validados sem ninguém pedir.
- Mudar o default do kit: a opção continua nascendo **desligada**.
