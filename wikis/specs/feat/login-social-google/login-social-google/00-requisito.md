# Requisito — Login social com Google (recorte `w3c` do pedido de Settings)

## Fonte

- **Origem**: `.claude/requisitos/w3c-login-social-google.txt` (recorte literal do pedido maior,
  cujo texto integral está em `.claude/requisitos/w3-settings-INTEGRAL.txt`)
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, copiado verbatim do arquivo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - analise cuidadosamente a documentação do laravel socialite: "https://laravel.com/framework/docs/13.x/socialite"
> - crie também a config se vai utilizar o login, com laravel/socialite, do google: "https://laraveldaily.com/post/filament-sign-in-with-google-using-laravel-socialite", "https://medium.com/@a.dhakal/filament-login-with-google-using-laravel-socialite-83c8bd476ace", "https://dev.to/tadeubdev/login-com-rede-social-usando-laravel-socialite-1i61" sendo este ultimo com uma tela de login usando icon e abaixo do form
> - se tiver essa opção, deve abrir os campos para adicionar os dados de config e das outras opções também:
> 'google' => [
>     'client_id'     => env('GOOGLE_CLIENT_ID'),
>     'client_secret' => env('GOOGLE_CLIENT_SECRET'),
>     'redirect'      => '/auth/google/callback',
> ],
> - so deve exibir o botão de login caso todos os dados estejam preenchidos
> - se a pessoa se registar por um login social, talvez ele ainda precise preenchar mais alguns dados, então redirecione ele para a tela do perfil dele
> - vamos disponibilizar o login com o google primeiro, depois, podemos disponibilizar mais opções como github, facebook, linkedin, x (antigo twitter), discord e etc
> - no botão de login do google, use a icon correspondente.
> - adicione também na tela de login, vindo da tela de settings, um rodapé. olhe esse exemplo: "https://auth.fiotec.org.br/?client_id=epf2&redirect_uri=https%3A%2F%2Fepf2.fiotec.org.br%2Fauth%2Fcallback&state=zyko9a59blat2o6cuzvx0l"
> - deixe toda essa parte muito bem documentado nos @README.md
> - o default é false para register e do socialite, mas se tiver true, precisa refletir em tudo que vem

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A implementação segue a documentação oficial do Laravel Socialite 13.x, não os artigos de terceiros | "analise cuidadosamente a documentação do laravel socialite" | restrição |
| RQ-02 | Existe uma configuração que decide **se** o login com Google é usado | "crie também a config se vai utilizar o login, com laravel/socialite, do google" | funcional |
| RQ-03 | O botão de login social fica **abaixo do formulário** de login | "sendo este ultimo com uma tela de login usando icon e abaixo do form" | funcional |
| RQ-04 | A configuração expõe `client_id`, `client_secret` e `redirect` do provedor | "deve abrir os campos para adicionar os dados de config […] 'client_id' […] 'client_secret' […] 'redirect'" | funcional |
| RQ-05 | O `redirect` do Google é `/auth/google/callback` | "'redirect' => '/auth/google/callback'" | restrição |
| RQ-06 | O botão só aparece quando **todos** os dados da config estão preenchidos | "so deve exibir o botão de login caso todos os dados estejam preenchidos" | funcional |
| RQ-07 | Quem se **registra** por login social é redirecionado para a tela do próprio perfil | "se a pessoa se registar por um login social […] redirecione ele para a tela do perfil dele" | funcional |
| RQ-08 | Google é o **primeiro e único** provedor desta entrega; os demais (github, facebook, linkedin, x, discord) vêm depois | "vamos disponibilizar o login com o google primeiro, depois, podemos disponibilizar mais opções" | restrição |
| RQ-09 | O botão usa o **ícone do Google** | "no botão de login do google, use a icon correspondente" | funcional |
| RQ-10 | A tela de login ganha um **rodapé** | "adicione também na tela de login […] um rodapé" | funcional |
| RQ-11 | O conteúdo do rodapé **vem da tela de Settings** | "vindo da tela de settings" | funcional |
| RQ-12 | Toda esta parte fica **muito bem documentada** no README | "deixe toda essa parte muito bem documentado nos @README.md" | não-funcional |
| RQ-13 | O default do socialite é **false** | "o default é false para register e do socialite" | restrição |
| RQ-14 | O default do **register** é false | "o default é false para register e do socialite" | restrição |
| RQ-15 | Com o socialite em `true`, o efeito precisa se refletir em **tudo que vem depois** (rota, botão, callback, criação de conta) | "mas se tiver true, precisa refletir em tudo que vem" | funcional |

## Ambiguidades e Perguntas Abertas

> Nenhuma destas pôde ser perguntada ao solicitante: as cinco features da rodada rodam em
> paralelo e sem interlocutor disponível. Todas seguem a premissa **mais estreita**, no par
> obrigatório "Assumido / Se negado".

- **RQ-02, RQ-07 — login social AUTENTICA ou também CRIA conta?**
  O requisito não diz. Hoje o kit só admite conta nova por **convite obrigatório**
  (`app/Filament/Pages/Auth/RegistroPorConvite.php:48-66`), e um login social que cria conta
  contorna o convite: é furo de autorização, não escolha de UX.
  - **Assumido**: o login com Google **autentica quem já tem conta**. Criar conta só acontece
    se o **registro aberto** estiver ligado — e o registro aberto nasce desligado (RQ-14).
  - **Se negado** (o solicitante quer criação sempre): `LoginComGoogle::autenticar()` deixa de
    consultar `LoginSocial::registroAberto()`, os CT-08 e CT-09 mudam de veredito e o furo do
    convite passa a ser decisão consciente, registrada no ADR-05.

- **RQ-07 — qual papel recebe quem se registra por login social?**
  Sem papel ninguém abre painel algum (`App\Models\User::canAccessPanel()`, `:76-105`), e
  `/app/meu-perfil` é página do painel `/app`. Atribuir papel é decisão da feature de
  **registro e aprovação**, que roda em paralelo nesta mesma rodada.
  - **Assumido**: esta feature **não atribui papel**. Redireciona para a URL do perfil; se a
    conta nova não tem papel, o painel responde 403 — e isso é o comportamento **correto** do
    kit, não defeito a contornar.
  - **Se negado**: o controller passa a chamar `assignRole('panel_user')`, e a decisão migra
    para a wiki de registro aberto.

- **RQ-11 — de onde vem o rodapé enquanto a tela de Settings não existe?**
  A classe de Settings do kit está sendo criada **em paralelo**, na branch
  `feat/settings-do-kit`, que mergeia **antes** desta.
  - **Assumido**: a leitura de configuração fica isolada atrás de **um ponto único**
    (`App\Support\LoginSocial`), lendo de `config()` por enquanto. Quando o Settings mergear,
    só o corpo daquela classe muda. Ver ADR-02.
  - **Se negado**: nada muda no resto da feature — é exatamente o que o ponto único protege.

- **RQ-06 — "todos os dados preenchidos" inclui o interruptor de ligar/desligar?**
  O requisito pede as duas coisas em linhas diferentes: um interruptor com default `false`
  (RQ-13) e a exigência de config completa (RQ-06).
  - **Assumido**: as duas condições valem, e em conjunção — o botão aparece só com o
    interruptor `true` **e** as três chaves preenchidas. Interruptor ligado com credencial
    vazia mantém o botão fora do ar.
  - **Se negado**: uma das duas condições cai, e o CT-02 ou o CT-03 deixa de ser devido.

- **RQ-10 — o rodapé vale para as outras telas de autenticação (recuperação de senha, aceite
  de convite)?**
  O requisito fala só em "tela de login".
  - **Assumido**: só a tela de login, nos três painéis.
  - **Se negado**: o mesmo render hook é registrado nas chaves
    `AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER` e `AUTH_REGISTER_FORM_AFTER`, sem nenhuma outra
    mudança.

## Fora de Escopo (declarado)

<!-- Para o quality gate não acusar omissão indevida. -->

- **A tela de Settings em si** (campos, permissões, model, migration) — é a branch
  `feat/settings-do-kit`. Desta wiki sai apenas o **ponto único de leitura** que ela vai
  alimentar. Cobre a metade de RQ-04 que diz "deve abrir os campos": abrir campo é ato de
  formulário, e o formulário não é desta entrega.
- **RQ-14 (default false do register)** — o interruptor de registro aberto e o fluxo de
  aprovação pertencem à branch `feat/registro-e-aprovacao`. Esta wiki apenas **consulta** o
  interruptor pelo ponto único, com default `false`.
- **RQ-08, segunda metade** — github, facebook, linkedin, x e discord são explicitamente
  "depois". Nenhum deles é implementado aqui; o desenho apenas não impede.
- **Verificação de e-mail do kit** — o kit não exige (`emailVerification(null, isRequired: false)`);
  não entra por causa desta feature.
