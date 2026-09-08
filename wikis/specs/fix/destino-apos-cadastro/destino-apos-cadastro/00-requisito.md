# Requisito — fix: destino depois do cadastro

## Fonte

- **Origem**: pedido do solicitante no chat, aceitando a correção oferecida ao fim da validação
  por navegador da v0.32.2 na instalação de teste `login-unificado-sem-tenancy`
- **Data**: 2026-09-08
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: **alta** para o pedido (texto escrito pelo solicitante); **o defeito em si é
  medição do agente**, não texto do solicitante — está registrado abaixo em
  `## Antecedente medido` e vale como evidência, não como requisito

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - abra uma /feature-wiki e implemente a correção. deixe tudo bem documentado e implemente +
> merge main + nova tag.
> - faça o kit:update para validar em um dos testes na pasta de testes do kit

## Antecedente medido

O pedido diz "a correção" no singular e sem descrever o defeito, porque ele foi apresentado ao
solicitante na mensagem imediatamente anterior, escrita pelo agente depois de reproduzir o
problema no navegador. Aquele texto **não é requisito** — é o laudo que o solicitante aprovou ao
responder "implemente a correção". Transcrito aqui verbatim para que o oráculo desta wiki não
dependa da memória da conversa:

> Depois de criar a conta pelo convite, a pessoa caiu em **403 no `/admin`**. Reproduzi limpo, sem
> nenhuma sessão de administrador envolvida: um visitante abre `/admin`, é mandado ao login (isso
> grava a URL pretendida na sessão), depois abre o link do convite e se cadastra — e o cadastro o
> joga na URL pretendida, um painel que a conta nova não acessa.
>
> Testei com a chave **desligada** e o 403 acontece igual. Então é comportamento anterior à
> feature, não regressão dela.
>
> A causa é uma assimetria com nome: o kit endureceu isso para o **login**.
> `DestinoAposLogin::urlPara()` valida a URL pretendida contra os painéis que a pessoa acessa e
> descarta a que não serve — é a ADR-06 da wiki do login unificado. O **cadastro** ficou com o
> `redirect()->intended(Filament::getUrl())` cru do Filament, sem validação nenhuma.
>
> A correção é pequena e usa o que já existe: a resposta de registro passar pelo mesmo decisor do
> login. Quer que eu faça?

### Evidência de código conferida ao escrever esta wiki

| Afirmação do laudo | Onde se confere |
|---|---|
| a resposta de registro do Filament não valida nada | `vendor/filament/filament/src/Auth/Http/Responses/RegistrationResponse.php:toResponse():12-15` — `return redirect()->intended(Filament::getUrl());` |
| ela é chamada no fim do cadastro, depois do login automático | `vendor/filament/filament/src/Auth/Pages/Register.php:register():70-112` — `return app(RegistrationResponse::class);` na linha 112, após `Filament::auth()->login($user)` na 108 |
| a URL pretendida sobrevive ao `session()->regenerate()` do cadastro | `vendor/filament/filament/src/Auth/Pages/Register.php:register():70-110` — `session()->regenerate()` na linha 110 troca o id da sessão e preserva os dados |
| o login do kit já valida a pretendida | `app/Support/DestinoAposLogin.php:urlPara():85-99` — `$painelDaPretendida = ...` e `painelDe()` na 234 |
| o kit já substitui a resposta de login, e só ela | `app/Providers/KitServiceProvider.php:configureLoginUnificado():535-538` — `$this->app->bind(LoginResponse::class, RespostaDeLogin::class);` |

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Depois de um cadastro que deu certo, a conta nova nunca é entregue na URL de um painel que ela não acessa | "o cadastro o joga na URL pretendida, um painel que a conta nova não acessa" | funcional |
| RQ-02 | A decisão do destino depois do cadastro é a mesma do login — passa por `DestinoAposLogin`, não por uma regra nova | "a resposta de registro passar pelo mesmo decisor do login" | restrição |
| RQ-03 | A correção vale também com a chave `kit.login.unificado` desligada, porque o defeito acontece nas duas configurações | "Testei com a chave desligada e o 403 acontece igual" | funcional |
| RQ-04 | Uma URL pretendida que É de painel acessível continua sendo honrada depois do cadastro | "valida a URL pretendida contra os painéis que a pessoa acessa e **descarta a que não serve**" | funcional |
| RQ-05 | A entrega fica documentada: wiki, docs de usuário e CHANGELOG | "deixe tudo bem documentado" | não-funcional |
| RQ-06 | A correção entra na `main` e ganha tag nova publicada | "implemente + merge main + nova tag" | restrição |
| RQ-07 | A correção é validada por `kit:update` numa das instalações da pasta de testes do kit | "faça o kit:update para validar em um dos testes na pasta de testes do kit" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-03** — com a chave desligada não existe tela de escolha de painel (`EscolhaDePainel::mount()`
  devolve ao painel default quando `ConfiguracaoDoLogin::unificado()` é falso,
  `app/Filament/Pages/Auth/EscolhaDePainel.php:mount():62-69`). Então "não entregar num painel
  inacessível" com a chave desligada só pode significar **descartar a pretendida** e cair no
  destino do próprio Filament.
  - **Assumido**: com a chave desligada, a correção **descarta a pretendida inacessível** e delega
    ao `parent::toResponse()`; não inventa tela de escolha, não muda o fallback do Filament
  - **Se negado**: RQ-03 vira "a escolha de painel passa a existir com a chave desligada", o que é
    mudança de feature e não correção — outra wiki, e o passo 2 do plano é refeito

- **RQ-01 no limite: conta nova sem painel nenhum.** O caso existe (convite com papel que não dá
  `/app`) e é anterior a este defeito: aí nem a pretendida nem `Filament::getUrl()` servem.
  - **Assumido**: com a chave **ligada**, `DestinoAposLogin::urlPara()` já resolve — manda para a
    escolha, que encerra a sessão com aviso. Com a chave **desligada** o kit não tem para onde
    mandar, e o comportamento continua sendo o do Filament
  - **Se negado**: entra um passo novo para o caso zero-painel com a chave desligada

## Fora de Escopo (declarado)

- O cadastro **pendente de aprovação**, que já tem tratamento próprio e encerra a sessão
  (`app/Filament/Pages/Auth/RegistroPorConvite.php:register():216`) — este requisito é sobre o
  cadastro que entra
- O destino depois do **login**, que já é correto (ADR-06 de `wikis/specs/feat/login-unificado/`)
- A resposta de **verificação de e-mail** e a de **redefinição de senha**, que têm contratos
  próprios no Filament e não foram medidas
- Criar tela de escolha de painel com a chave desligada (ver Ambiguidades)
