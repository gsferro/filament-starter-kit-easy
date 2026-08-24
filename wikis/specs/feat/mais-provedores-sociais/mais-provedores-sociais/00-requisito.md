# Requisito — W8: mais provedores de login social

## Fonte

- **Origem**: `.claude/requisitos/w8-mais-provedores-sociais.txt` (raiz do repositório), copiado verbatim
- **Data**: 2026-08-24
- **Autor / solicitante**: dono do kit
- **Fidelidade**: alta (texto escrito)

> É o **mesmo** texto que originou a wiki `feat/login-social-google/login-social-google`. Aquela
> entrega atendeu a primeira metade ("o login com o google primeiro"); esta atende a segunda
> ("depois, podemos disponibilizar mais opções como github, facebook, linkedin, x, discord e etc").
> As cláusulas `RQ` abaixo são renumeradas para esta entrega e não correspondem às daquela wiki.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

```text
vamos disponibilizar o login com o google primeiro, depois, podemos disponibilizar mais opções como github, facebook, linkedin, x (antigo twitter), discord e etc
- no botão de login do google, use a icon correspondente.
- so deve exibir o botão de login caso todos os dados estejam preenchidos
- se tiver essa opção, deve abrir os campos para adicionar os dados de config e das outras opções também
- o default é false para register e do socialite, mas se tiver true, precisa refletir em tudo que vem
- deixe toda essa parte muito bem documentado nos @README.md
```

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Além do Google, o kit oferece login social por GitHub, Facebook, LinkedIn, X (antigo Twitter) e Discord | "depois, podemos disponibilizar mais opções como github, facebook, linkedin, x (antigo twitter), discord e etc" | funcional |
| RQ-02 | A lista é aberta ("e etc"): acrescentar o próximo provedor não deve exigir reescrever os anteriores | "discord e etc" | não-funcional |
| RQ-03 | Cada botão de login usa o ícone da marca do provedor correspondente | "no botão de login do google, use a icon correspondente" | funcional |
| RQ-04 | O botão de um provedor só aparece quando **todos** os dados dele estão preenchidos | "so deve exibir o botão de login caso todos os dados estejam preenchidos" | funcional |
| RQ-05 | Ligar a opção de um provedor **abre** os campos de configuração dele na tela | "se tiver essa opção, deve abrir os campos para adicionar os dados de config" | funcional |
| RQ-06 | Os campos de configuração existem para **todos** os provedores, não só para o Google | "e das outras opções também" | funcional |
| RQ-07 | O default do registro aberto é `false` | "o default é false para register" | restrição |
| RQ-08 | O default de **cada** provedor do socialite é `false` | "e do socialite" | restrição |
| RQ-09 | Ligado (`true`), o efeito precisa alcançar **tudo** que decorre da opção — não só o botão | "mas se tiver true, precisa refletir em tudo que vem" | funcional |
| RQ-10 | Toda essa parte fica muito bem documentada nos READMEs | "deixe toda essa parte muito bem documentado nos @README.md" | não-funcional |

### Como RQ-09 foi lido, e por que a leitura é estreita

"refletir em tudo que vem" é a cláusula mais aberta do requisito, e a wiki do Google já a
interpretou uma vez: o interruptor governa a **rota**, não só o botão — desligado,
`/auth/{provedor}/*` responde **404**, porque a URL é pública e "escondido no HTML" não é
barreira. Esta entrega herda essa leitura e a estende ao caso simétrico: ligar um provedor
liga **só aquele**, e o inverso também — desligar o Google não pode derrubar o GitHub.

## Ambiguidades e Perguntas Abertas

- **RQ-01 / Discord** — o requisito pede Discord, e **o Socialite não tem driver de Discord**.
  A documentação oficial lista "Facebook, X, LinkedIn, Google, GitHub, GitLab, Bitbucket, and
  Slack" e remete o resto a `socialiteproviders.com`, um catálogo comunitário
  (`vendor/laravel/socialite/src/Two/` não tem `DiscordProvider.php`, e
  `vendor/socialiteproviders/` não existe nesta instalação).
  - **Assumido**: Discord fica **fora desta entrega**. A instrução de escopo é explícita —
    "Não adicione dependência" — e Discord exigiria `composer require
    socialiteproviders/discord` mais um listener de evento, ou seja, uma dependência **e** um
    segundo mecanismo de extensão.
  - **Se negado**: RQ-01 volta a ter cinco provedores; o passo 2 do PRD ganha a dependência e o
    listener, e o ADR-04 é revisto.

- **RQ-01 / Facebook** — o Facebook **não expõe nenhum sinal de e-mail verificado**. O campo
  `verified` que o `FacebookProvider` pede (`vendor/laravel/socialite/src/Two/FacebookProvider.php:34`)
  é do nível da *conta*, legado na Graph v23.0, e não afirma nada sobre o endereço; o caminho
  OIDC/Limited Login (`:134-167`) devolve claims sem `email_verified`.
  - **Assumido**: Facebook fica **fora desta entrega**. A premissa mais estreita, como o
    requisito desta rodada pede em caso de provedor sem confirmação de verificação. Casar conta
    por e-mail não verificado é a tomada de conta clássica do login social, e aceitá-la para um
    provedor rebaixaria a barreira que o kit já cobra do Google.
  - **Se negado**: RQ-01 recupera o Facebook; o ADR-05 muda de "recusar" para "aceitar com
    risco", e um par de casos de teste sobre criação de conta por e-mail não verificado precisa
    existir antes.

- **RQ-01 / LinkedIn** — há **dois** drivers de LinkedIn no Socialite: `linkedin` (API v2
  legada, escopos `r_liteprofile`/`r_emailaddress`, **sem** nenhum campo de verificação) e
  `linkedin-openid` (userinfo OpenID, **com** `email_verified`).
  - **Assumido**: `linkedin-openid`. É o único dos dois que atende a barreira de verificação, e
    os escopos do legado foram descontinuados pela própria LinkedIn.

- **RQ-04** — "todos os dados" foi lido como as **três** chaves que o Socialite exige
  (`client_id`, `client_secret`, `redirect`), a mesma régua do Google. O `redirect` é literal do
  `config/services.php` e nunca fica vazio na prática, mas é conferido de propósito: conferir
  duas e esquecer uma é o mutante mais provável aqui.

## Fora de Escopo (declarado)

- **Discord** — sem driver no Socialite; exigiria dependência nova (ver Ambiguidades).
- **Facebook** — sem sinal de e-mail verificado (ver Ambiguidades e ADR-05).
- **Twitter OAuth 1.0** (`services.twitter` sem `'oauth' => 2`) — o `One\TwitterProvider` não
  põe o e-mail nem no payload bruto (`vendor/laravel/socialite/src/One/TwitterProvider.php:23`),
  e é caminho morto na API v2 do X. O kit usa o driver `x`.
- **Vincular várias identidades sociais a uma conta** (coluna `github_id` e irmãs) — o kit casa
  por e-mail verificado, decisão do ADR-07 da wiki do Google, mantida aqui.
- **Guardar o token de acesso do provedor** — o kit não chama API de provedor em nome de
  ninguém, exceto a conferência de e-mail do GitHub, que usa o token e o descarta.
