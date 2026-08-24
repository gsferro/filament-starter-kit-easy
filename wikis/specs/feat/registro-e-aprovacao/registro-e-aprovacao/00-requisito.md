# Requisito — w3b: registro aberto no /app e aprovação de cadastro

## Fonte

- **Origem**: `.claude/requisitos/w3b-registro-e-aprovacao.txt` — recorte do pedido maior,
  cujo contexto integral está em `.claude/requisitos/w3-settings-INTEGRAL.txt` (a wiki do
  Settings do kit). Este recorte é a parte do pedido que o próprio autor autorizou a virar
  wiki separada ("caso essa parte fuga do contexto dessa wiki, pode criar uma nova").
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - adicione a opção de Register, dentro do painel "/app" como um settings, sendo que se tiver um tenancy, e o register estiver liberado, também pode optar por habilitar ou não o uso de register no seu tenant. Ao se regitrar assim, a pessoa recebe somente o perfil de acesso ao "/app" e nenhuma outro perfil ou acesso além disso. caso o administrado queria, ele mesmo mexe no cadastro e altera a permissão futuramente
> - podemos pensar em um outro settings para autorização de registro automatico ou se ele fica pendente ate alguem aprovar (pesquise na documentação do filament se já tem algo nativo ou se existe algum pacote pronto para isso, caso não tenha, implementamos). Ao liberar o register, abra opções se deve usar a validação de email.
> - deixe toda essa parte muito bem documentado nos @README.md
> - o default é false para register e do socialite, mas se tiver true, precisa refletir em tudo que vem
> - caso essa parte fuga do contexto dessa wiki, pode criar uma nova

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Existe uma opção que liga/desliga o registro aberto no painel `/app` | "adicione a opção de Register, dentro do painel \"/app\"" | funcional |
| RQ-02 | Essa opção é lida de um Settings — não de uma constante no código | "como um settings" | restrição |
| RQ-03 | Com tenancy ligada **e** registro liberado, cada organização decide se aceita registro | "se tiver um tenancy, e o register estiver liberado, também pode optar por habilitar ou não o uso de register no seu tenant" | funcional |
| RQ-04 | Quem se registra por essa via recebe **somente** o perfil de acesso ao `/app` | "a pessoa recebe somente o perfil de acesso ao \"/app\"" | autorização |
| RQ-05 | Quem se registra por essa via **não** recebe nenhum outro perfil nem acesso | "e nenhuma outro perfil ou acesso além disso" | autorização |
| RQ-06 | Quem administra pode, depois, editar o cadastro e alterar a permissão pela tela | "caso o administrado queria, ele mesmo mexe no cadastro e altera a permissão futuramente" | funcional |
| RQ-07 | Existe uma segunda opção: registro autorizado automaticamente **ou** pendente até alguém aprovar | "um outro settings para autorização de registro automatico ou se ele fica pendente ate alguem aprovar" | funcional |
| RQ-08 | Antes de implementar aprovação, pesquisar se há algo nativo do Filament ou pacote pronto | "pesquise na documentação do filament se já tem algo nativo ou se existe algum pacote pronto para isso, caso não tenha, implementamos" | restrição |
| RQ-09 | Ao liberar o registro, abrir a opção de exigir validação de e-mail | "Ao liberar o register, abra opções se deve usar a validação de email" | funcional |
| RQ-10 | Toda essa parte fica muito bem documentada nos README | "deixe toda essa parte muito bem documentado nos @README.md" | não-funcional |
| RQ-11 | O default do registro é `false` | "o default é false para register" | restrição |
| RQ-12 | Com o valor `true`, a consequência precisa refletir em **tudo** o que vem depois | "mas se tiver true, precisa refletir em tudo que vem" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-02 — a classe de Settings não existe nesta branch.** Ela está sendo criada em
  paralelo em `feat/settings-do-kit`, que mergeia antes desta.
  - **Assumido**: a leitura da configuração fica isolada atrás de **um** ponto único
    (`App\Support\RegistroAberto`), que hoje lê `config('kit.registro.*')` e, quando o
    Settings mergear, passa a ler o Settings **sem tocar em mais nenhum arquivo**.
  - **Se negado**: se o Settings do kit expuser uma API diferente da esperada, muda só
    `App\Support\RegistroAberto` — os passos 4 a 10 do PRD e todos os CT continuam válidos.

- **RQ-03 — com tenancy ligada, em qual organização a pessoa se registra?** A rota de
  registro do Filament é do PAINEL, não do tenant (`/app/register`, fora do grupo de rotas
  de tenant), então não existe organização no caminho da URL.
  - **Assumido**: a organização vem por query string (`/app/register?org={slug}`), no mesmo
    formato que o convite já usa para o token. Sem `org` válido e com registro habilitado
    naquela organização, a tela **recusa** — o mesmo caminho genérico do convite inválido.
  - **Se negado**: se o desejado for uma rota por tenant, o passo 5 do PRD muda (rota nova
    dentro do grupo de tenant) e CT-12..CT-15 são refeitos.

- **RQ-04/RQ-05 — "somente o perfil de acesso ao /app" é qual papel?** O kit tem dois papéis
  no painel `app`: `panel_user` (usuário comum) e `admin_app` (administra a organização).
  - **Assumido**: `panel_user`. `admin_app` administra a organização, o que contradiz
    frontalmente "nenhum outro perfil ou acesso além disso".
  - **Se negado**: muda uma linha em `App\Support\RegistroAberto::papel()`.

- **RQ-09 — ligar validação de e-mail barra quem já existe?** Com
  `->emailVerification(…, isRequired: true)` o middleware do Filament barra **todo** usuário
  do `/app` sem `email_verified_at`, não apenas os recém-registrados.
  - **Assumido**: sim, barra, e isso é documentado no README como consequência de ligar a
    opção. Os caminhos do kit que criam usuário já gravam `email_verified_at`
    (`UsuarioAdminSeeder.php:45`, `UserFactory.php:30`, `DemoTenancySeeder.php:103`,
    `Convite::aceitar()` em `Convite.php:591`, `KitAdmin.php:204`), então numa instalação
    limpa ninguém é barrado.
  - **Se negado**: usar `isRequired: false` (tela no ar, sem middleware) e a opção passa a
    ser só informativa — o que não atende "precisa refletir em tudo que vem" (RQ-12).

- **RQ-12 — o que é "tudo que vem"?** Interpretado como as consequências observáveis de
  ligar a chave, enumeradas em `## Cobertura do Requisito` do PRD: rota pública, link
  "Cadastre-se" no login, papel atribuído, verificação de e-mail, pendência de aprovação,
  tela de aprovação, log e README. Cada item tem CT próprio.

## Fora de Escopo (declarado)

- **Login social / Socialite** (Google e demais provedores). O texto original cita "e do
  socialite" apenas para dizer que o default dele também é `false`; a implementação do login
  social é outra wiki (`feat/login-social-google`), em paralelo.
- **A página de Settings em si** (`/admin`), o pacote `filament-spatie-settings`, a model de
  settings com auditoria e as permissões de operar o settings — tudo isso é da wiki
  `feat/settings-do-kit`.
- **`->tenantRegistration()`** (criar organização pela tela). Registrar-se NUMA organização
  não é criar organização; quem cria organização continua sendo quem administra a
  instalação, pelo `/admin`. O `AppPanelProvider.php:360` já declara essa ausência como
  deliberada.
- **Rodapé na tela de login** e **imagens/logo/brand vindos do settings** — wiki do Settings.
