# Requisito — A arte do login exibe o nome da aplicação

## Fonte

- **Origem**: mensagem do mantenedor no chat, olhando a tela de login do kit
- **Data**: 2026-09-01
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **alta** — texto escrito, com a pergunta e a decisão na mesma mensagem

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> no login da tela, é um SVG, é possivel exibir aqui o nome da aplicação ao inves de um texto fixo?!
> - se sim, deixe dinamico para já ter uma tela de login basica que use o nome da apllcação, que é customizavel na instalação e deixe somente o nome, sem outro texto
> - será necessário atualizar o @README.md e as arts que exibam a tela que a logo é exibida

## Resposta à pergunta do requisito, antes do plano

**Sim, é possível.** A arte é renderizada como `<img src="{{ $config->media }}">`
(`vendor/caresome/filament-auth-designer/resources/views/components/partials/media.blade.php`), e a
URL vem de `IdentidadeDoKit::arteDoLogin()` (`app/Support/IdentidadeDoKit.php:74`). Uma `<img>`
aceita tanto uma URL de rota quanto um `data:` URI, então há mais de um caminho — a escolha vai
para a ADR-01.

**O que está fixo hoje**, em `public/images/auth/login.svg`:

```xml
<text x="80" y="500" … font-size="44" font-weight="700">starter-kit-easy</text>
<text x="80" y="548" … font-size="20" fill-opacity="0.75">Laravel 13 · Filament 5 · pronto para uso</text>
```

Duas linhas de texto. O requisito manda trocar a primeira pelo nome da aplicação e **remover a
segunda**.

**O nome da aplicação é `config('app.name')`**, e é customizável na instalação: o `kit:install`
reescreve o `APP_NAME` do `.env` (`KitInstall.php:136`). É o mesmo valor que já vai no `alt` da
imagem hoje (`AdminPanelProvider.php:139`, e os pares nos outros dois painéis).

**Correção de uma afirmação errada desta seção** (feita na revisão do `04`, 2026-09-01): a primeira
versão deste requisito afirmava que a arte aparecia **quebrada** nas capturas do `composer art`, com
o navegador mostrando o `alt`, e citava `art/login-anti-robo.png` como evidência. **As duas coisas
eram falsas**: esse arquivo não existe (o da tela anti-robô é `art/admin-anti-robo.png`, e é uma
captura de Settings, não de autenticação), e as capturas que **de fato** mostram a tela de
autenticação — `art/login.png`, `art/login-social.png` e `art/app-bloqueio-social.png` — exibem a
arte **pintada corretamente**, com as duas linhas de texto legíveis. O `asset()` **é** servido pelo
navegador da suíte.

A afirmação foi inspecionada abrindo as três imagens. Ela não vinha do requisito nem do código: foi
introduzida por quem escreveu este arquivo, e contaminou a ADR-01 (como "positiva colateral") e o
`04` (como justificativa do CT-B01). As duas foram corrigidas.

**O que sobra de verdadeiro, e importa para a RQ-05**: as capturas mostram hoje `starter-kit-easy` e
`Laravel 13 · Filament 5 · pronto para uso` dentro da arte — exatamente o que esta feature muda.
Elas precisam ser regeradas porque o **conteúdo** muda, não porque estejam quebradas.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A arte padrão do login exibe o **nome da aplicação**, não um texto fixo | "é possivel exibir aqui o nome da aplicação ao inves de um texto fixo?!" | funcional |
| RQ-02 | O nome é lido em tempo de execução, refletindo o que a instalação customizou | "deixe dinamico… que é customizavel na instalação" | funcional |
| RQ-03 | A arte exibe **somente o nome** — a segunda linha de texto sai | "deixe somente o nome, sem outro texto" | restrição |
| RQ-04 | O README é atualizado | "será necessário atualizar o @README.md" | funcional |
| RQ-05 | As artes (capturas) que mostram a tela com a arte são regeradas | "e as arts que exibam a tela que a logo é exibida" | funcional |
| RQ-06 | Continua sendo possível substituir a arte por uma imagem própria | derivado: `arte_do_login` nas Settings existe e o requisito não pede para removê-lo — "tela de login **basica**" descreve o default, não o único caminho | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-01 — "o nome da aplicação" é `config('app.name')` ou o nome da organização (com tenancy)?**
  - **Assumido**: `config('app.name')`. É o que o requisito chama de "customizavel na instalação"
    (o `kit:install` reescreve `APP_NAME`), é o que já vai no `alt` da imagem, e a tela de login do
    `/app` com tenancy é anterior à escolha da organização — não há organização para nomear ali.
  - **Se negado**: com tenancy, a arte passaria a depender do tenant da rota, e as três telas de
    autenticação mudam de natureza.

- **RQ-03 — "sem outro texto" inclui a marca d'água visual (círculos, gradiente)?**
  - **Assumido**: **não**. "Texto" é texto: as duas `<text>` do SVG. O gradiente e os círculos são
    forma, não texto, e removê-los deixaria um retângulo liso — que não é "uma tela de login
    básica", é uma tela sem arte.
  - **Se negado**: o SVG vira só fundo e nome.

- **RQ-02 — o nome entra no SVG como texto; e se ele tiver caractere especial (`&`, `<`)?**
  - **Assumido**: precisa de escape XML, e é obrigação da implementação — nome com `&` é comum
    ("Silva & Cia"). Sem escape, o SVG inteiro quebra e a tela de login perde a arte.
  - Isto não é ambiguidade do requisito; é requisito implícito de correção, e vira caso de teste.

- **RQ-05 — quais capturas exatamente?**
  - **Respondido por inspeção** (não é mais premissa): abrindo cada PNG de `art/`, as que mostram a
    arte lateral são **três** — `art/login.png`, `art/login-social.png` e
    `art/app-bloqueio-social.png`. As duas últimas estão em `KitArte::IMAGENS` e saem do
    `composer art`; **`login.png` não está**, e é anterior ao comando.
  - `art/admin-anti-robo.png` e `art/admin-configuracoes-login.png` são telas do `/admin`
    autenticado (Settings), **não** têm a arte, e ficam fora.
  - **Decisão pendente para a implementação**: o que fazer com `login.png` — regerar à mão, adotar
    no `kit:arte` (exige o par `->screenshot(filename: 'login')` **e** a linha `'login'` em
    `KitArte::IMAGENS`, senão o comando reporta como ignorada) ou aposentar.

## Fora de Escopo (declarado)

- Trocar o layout das telas de autenticação, a posição da arte ou o pacote que a renderiza.
- Remover o campo `arte_do_login` das Settings — quem envia a própria imagem continua com ela
  (RQ-06).
- Fazer o nome aparecer também no formulário (ele já aparece: o `brandName` do painel).
- Internacionalizar o SVG.
