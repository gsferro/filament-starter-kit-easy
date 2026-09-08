# Decisões Arquiteturais — fix: destino depois do cadastro

## ADR-01: A correção é uma resposta de cadastro vinculada no container, não um `register()` sobrescrito

**Status**: Aceita
**Data**: 2026-09-08

### Contexto

O destino errado nasce em `app(RegistrationResponse::class)`, resolvido na última linha de
`Register::register()` (`vendor/filament/filament/src/Auth/Pages/Register.php:register():70-112`).
Há três lugares onde a correção poderia morar:

1. dentro de `RegistroPorConvite::register()`, que o kit já sobrescreve
2. numa classe de resposta vinculada no container
3. num middleware que limpasse `url.intended` antes do cadastro

O kit tem **dois** pontos de entrada de cadastro (`/app/register` e `/cadastro`), e o segundo é
uma subclasse do primeiro. Tem também **dois** modos (convite e registro aberto) na mesma classe.

### Decisão

Uma classe `App\Http\Responses\RespostaDeCadastro`, estendendo
`Filament\Auth\Http\Responses\RegistrationResponse`, vinculada ao contrato no `KitServiceProvider`
ao lado do bind de `LoginResponse` que já existe.

### Alternativas Consideradas

1. **Sobrescrever `RegistroPorConvite::register()`** — descartada por dois motivos concretos. O
   método já é sobrescrito para o cadastro pendente e devolve `null` naquele caminho; enfiar a
   decisão de destino ali mistura duas regras que falham por motivos diferentes e obriga a ler
   `$resposta === null` para saber qual delas está em jogo. E o segundo motivo é decisivo: o
   cadastro social e qualquer página de registro que a aplicação venha a acrescentar **não**
   passam por essa classe, mas passam pelo contrato.
2. **Middleware que apaga `url.intended` na rota de cadastro** — descartada porque apagaria a
   pretendida **antes** de saber quem se cadastrou, e a pretendida legítima (RQ-04, painel que a
   conta acessa) morreria junto. A decisão só é possível depois do `login($user)`.
3. **Não corrigir com a chave desligada** — descartada porque contradiz RQ-03, que é uma medição:
   o 403 acontece igual nas duas configurações.

### Consequências

- **Positivas**: a correção vale para os dois pontos de entrada, os dois modos e os três painéis,
  sem uma linha nova nas páginas. É simétrica ao login: mesmo diretório, mesmo molde, mesmo bind,
  mesmo decisor. E o rollback é a remoção de uma linha.
- **Negativas**: o bind é global, então qualquer página de registro futura herda a decisão mesmo
  se não quisesse. Aceito — é a mesma propriedade que o bind de `LoginResponse` já tem, e o
  destino sem 403 não é o tipo de coisa de que se quer opt-out.
- **Riscos**: um CT existente que dependesse de o cadastro honrar pretendida inacessível ficaria
  vermelho. Mitigação: a regressão completa é obrigatória (ver `## Impacto` do PRD), e um CT assim
  estaria assertando o defeito — ele se corrige na wiki de origem, marcado.

### Referências

- `app/Http/Responses/RespostaDeLogin.php` — o irmão exato
- `app/Providers/KitServiceProvider.php:configureLoginUnificado():535`
- Refine: ADR-06 de `wikis/specs/feat/login-unificado/login-unificado/`

---

## ADR-02: Com a chave ligada, o cadastro usa `urlPara()` inteiro — inclusive quando ele descarta uma pretendida legítima fora de painel

**Status**: Aceita
**Data**: 2026-09-08

### Contexto

`DestinoAposLogin::urlPara()` só honra a pretendida quando ela é de um **painel** acessível
(`painelDe()`, `app/Support/DestinoAposLogin.php:painelDe():234`). Uma pretendida que aponta para
uma rota fora de painel — `/boas-vindas`, uma página pública da aplicação, um link de um site — é
descartada, e a pessoa vai para o painel único ou para a escolha.

Isso não é acidente do login: é a ADR-06 da wiki ancestral, e a razão é que a tela de escolha
existe justamente para não adivinhar. Mas ao trazer o cadastro para o mesmo decisor, o kit importa
essa característica para um fluxo onde ela é mais visível: quem se cadastra costuma vir de um
link, e o link costuma não ser de painel.

### Decisão

Usar `urlPara()` **como está**, sem exceção para pretendida fora de painel. Cadastro e login
decidem destino pela mesma função, sem ramo condicional pelo fluxo de origem.

### Alternativas Consideradas

1. **Honrar qualquer pretendida do próprio host, mesmo fora de painel, só no cadastro** —
   descartada. Criaria exatamente a assimetria que esta wiki existe para fechar, agora invertida:
   a mesma URL pretendida levaria a lugares diferentes dependendo de a pessoa ter entrado ou se
   cadastrado. E a próxima pessoa a ler `DestinoAposLogin` teria de descobrir por quê.
2. **Ampliar `urlPara()` para aceitar pretendida fora de painel nos dois fluxos** — descartada
   nesta wiki. É mudança de comportamento do **login**, com CTs próprios na wiki ancestral, e o
   requisito aqui é uma correção. Se for desejável, é uma wiki de evolução da ancestral.

### Consequências

- **Positivas**: uma regra, um lugar, um conjunto de testes. Nenhum ramo por fluxo de origem.
- **Negativas**: uma pretendida fora de painel continua sendo perdida depois do cadastro, como já
  é depois do login. É perda de conveniência, nunca um erro visível — o destino é sempre uma tela
  que abre.
- **Riscos**: se a aplicação passar a ter páginas autenticadas fora de painel, a limitação fica
  mais sensível. É o gatilho para a wiki de evolução mencionada acima.

### Referências

- `app/Support/DestinoAposLogin.php:painelDe():234`
- Refine: ADR-06 de `wikis/specs/feat/login-unificado/login-unificado/`

---

## ADR-03: O carimbo de painel no log de acesso passa a valer para o cadastro — e isso é ganho, não efeito colateral

**Status**: Aceita
**Data**: 2026-09-08

### Contexto

`urlPara()` chama `carimbarAcesso()` quando o painel de entrada é decidido ali, para que "acessos
por painel" (insights e o stat de logins) não contem todo login por senha como `app`
(`app/Support/DestinoAposLogin.php:carimbarAcesso():204`). O cadastro faz login pelo vendor
(`Filament::auth()->login($user)`), o que grava uma linha no `authentication_log` — e hoje essa
linha fica **sem painel**, porque nada a carimba.

Ao passar o cadastro por `urlPara()`, o carimbo acontece.

### Decisão

Aceitar e documentar o carimbo como parte da correção, sem código adicional.

### Alternativas Consideradas

1. **Extrair de `urlPara()` uma variante sem carimbo, para o cadastro** — descartada. Duplicaria a
   função e produziria acesso sem painel de propósito, que é exatamente a lacuna que o carimbo
   fecha.
2. **Carimbar explicitamente na resposta de cadastro** — descartada por redundância: já acontece
   dentro de `urlPara()`.

### Consequências

- **Positivas**: o primeiro acesso de uma conta nova deixa de ser um acesso sem painel nos
  insights. Corrige, de graça, um segundo buraco de dado.
- **Negativas**: com a chave **desligada** o carimbo não acontece, porque `urlPara()` não roda.
  A assimetria fica, e é a mesma que o login já tem — o carimbo é uma feature da página única.
- **Riscos**: um CT de `tests/Kit/CarimboDePainelNoAcessoTest.php` pode ter medido o acesso do
  cadastro **sem** painel e passado a contar com isso. Mitigação: rodar aquele arquivo na
  regressão e, se houver, corrigir o CT na wiki de origem com a marca.

### Referências

- `app/Support/DestinoAposLogin.php:carimbarAcesso():204`
- `tests/Kit/CarimboDePainelNoAcessoTest.php`

---

## ADR-04: O descarte da pretendida mora em `DestinoAposLogin`, não na resposta

**Status**: Aceita
**Data**: 2026-09-08

### Contexto

Com a chave desligada, a correção precisa de uma operação que `urlPara()` não oferece: **descartar
a pretendida inacessível e não decidir mais nada** — porque quem decide dali em diante é o
Filament, e não existe tela de escolha para onde apontar.

A pergunta "esta URL é de um painel que esta conta acessa?" é respondida por `painelDe()`, que é
`private` e onde vivem as duas defesas achadas pela auditoria Blueprint: URL absoluta tem de
começar exatamente por `url('/')`, e path relativo não pode começar por `//` nem `/\`.

### Decisão

Um método público novo em `DestinoAposLogin`,
`descartarPretendidaInacessivel(User $user): bool`, que reusa `paineisDe()` e `painelDe()`, apaga
`url.intended` e loga. A resposta de cadastro chama esse método e nada mais sabe sobre URLs.

### Alternativas Consideradas

1. **Tornar `painelDe()` pública e checar na resposta** — descartada. Espalha a decisão de
   segurança de URL por dois arquivos e convida o próximo chamador a esquecer uma das duas
   defesas.
2. **Reimplementar a checagem na resposta** — descartada pelo mesmo motivo, agravado por
   duplicação literal.
3. **Fazer `urlPara()` aceitar um parâmetro de modo** — descartada: bandeira booleana em função de
   decisão é o começo de duas funções mal separadas, e `urlPara()` é chamada por login por senha,
   login social e `CadastroUnificado::mount()`.

### Consequências

- **Positivas**: uma única implementação da pergunta "é painel acessível?". A resposta de cadastro
  fica com três linhas de decisão e nenhuma regra de URL.
- **Negativas**: `DestinoAposLogin` ganha superfície pública — quatro métodos em vez de três. É o
  preço de não duplicar `painelDe()`.
- **Riscos**: o nome do método é longo. Preferido a um nome curto e ambíguo, porque ele é chamado
  de um lugar só e o que importa é o leitor entender que **apaga estado de sessão**.

### Referências

- `app/Support/DestinoAposLogin.php:painelDe():234` — as duas defesas de URL
- `app/Support/DestinoAposLogin.php:paineisDe():52`
