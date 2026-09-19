# Requisito — `/public` nunca aparece na URL antes do painel

## Fonte

- **Origem**: pedido do mantenedor do kit no chat, depois de observar o sintoma num ambiente de homologação
- **Data**: 2026-09-18
- **Autor / solicitante**: mantenedor do starter-kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> tem momentos que a url adiciona um "/public/app/..." e outras vezes vai direto "/app/...
> - faça uma investigação do motivo e entenda se é um erro do projeto ou do starter-kit, se for do kit, me passe sua analise do erro para que eu possa corrigir lá
> - o certo é NUNCA exibir o "/public" na url antes de qualquer painel, seja ele "/admin", "/infra" ou "/app" ou outro que possa vir a ser criada pelo usuario do kit

E, depois da análise entregue:

> - implemente no kit com a wiki: "D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\starter-kit-easy" em uma branch propria, quando terminar abra um PR para a main já que tem outra sessão tratando de outra melhoria

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | `/public` **nunca** aparece na URL antes do segmento do painel | "o certo é NUNCA exibir o \"/public\" na url antes de qualquer painel" | funcional |
| RQ-02 | A garantia vale para `/admin`, `/infra` e `/app` | "seja ele \"/admin\", \"/infra\" ou \"/app\"" | funcional |
| RQ-03 | A garantia vale para painel que o **usuário do kit** vier a criar — não pode ser lista fechada de painéis | "ou outro que possa vir a ser criada pelo usuario do kit" | restrição |
| RQ-04 | A causa foi investigada e atribuída a projeto ou kit | "faça uma investigação do motivo e entenda se é um erro do projeto ou do starter-kit" | funcional |
| RQ-05 | A correção vive no **kit**, em branch própria, entregue por PR para a `main` | "implemente no kit … em uma branch propria … abra um PR para a main" | restrição |

## Achado da investigação (RQ-04) — resposta antes do plano

**Não é defeito de código, nem do projeto nem do kit.** É a hospedagem.

Medido no ambiente onde o sintoma aparece:

- `DocumentRoot` do vhost aponta para a **raiz do projeto Laravel**, e não para `public/`
- o front controller real está em `<raiz>/public/index.php`
- um `.htaccess` na raiz compensa com reescrita **interna** `^(.*)$ → public/$1`, mais um `R=301`
  que tira `/public` de requisições externas

Depois da reescrita interna o Apache entrega `SCRIPT_NAME=/public/index.php` enquanto o
`REQUEST_URI` continua o que o usuário pediu. O Symfony deriva a base comparando os dois:

| `REQUEST_URI` que chega | `baseUrl` calculado | URLs geradas na página |
|---|---|---|
| `/app`, `/app/acme`, `/admin`, `/infra`, `/` | vazio | limpas |
| `/public/app` | **`/public`** | **todas** com o prefixo |

Daí a intermitência, que **não é aleatória**: basta entrar **uma vez** por um endereço com
`/public` — bookmark, link compartilhado, cache de navegador — para que toda a navegação daquela
página saia prefixada. O `R=301` do `.htaccess` devolve cada clique para a forma limpa, e é esse
vaivém que se vê na barra de endereços.

O Laravel está correto: ele honra a base do request que recebeu. Quem inventa o `/public` é a
configuração do servidor.

**A correção definitiva é de infraestrutura** — apontar o `DocumentRoot` para `public/`. Mas
RQ-01 pede que o `/public` **nunca** apareça, e o kit não controla a hospedagem de quem o instala.
Daí esta wiki: tornar o kit **imune**, de modo que a instalação errada deixe de produzir o sintoma.

## Ambiguidades e Perguntas Abertas

- **RQ-01** — "nunca exibir" cobre também o caso em que a aplicação vive legitimamente sob um
  subdiretório chamado `public` (`https://host/public` como caminho de verdade)?
  - **Assumido**: sim, o `/public` é removido também nesse caso. Uma aplicação Laravel servida
    num caminho cujo último segmento é literalmente `public` **é** o caso quebrado: é o
    `DocumentRoot` um nível acima, visto de outro ângulo.
  - **Se negado**: a regra passa a exigir um sinal adicional (por exemplo, confirmar que
    `<base>/index.php` é o front controller), e CT-04 muda de oráculo.

## Fora de Escopo (declarado)

- Corrigir o `DocumentRoot` ou o `.htaccess` de qualquer ambiente — é infraestrutura, e o kit não
  a controla. A documentação do kit passa a dizer qual é a configuração certa.
- Reescrever URL de **asset** (`/css`, `/js`, `/build`): servidos pelo mesmo mecanismo, e a
  correção de raiz de URL já os alcança por `asset()`.


## Adendo 1 — a cláusula RQ-01 não é aplicável sem ressalva (2026-09-18)

- **Fonte**: achado **1 (high)** do `/code-review` sobre o diff, confirmado por inspeção do kit
- **Fidelidade**: alta (medido, não inferido)

### O que o achado mostrou

Dois arranjos de hospedagem entregam ao PHP **exatamente a mesma assinatura** —
`SCRIPT_NAME=/public/index.php`, `REQUEST_URI=/public/app`, base derivada `/public`:

| Arranjo | `/app` funciona? | Encurtar a raiz… |
|---|---|---|
| **A** — `DocumentRoot` na raiz **com** `.htaccess` reescrevendo para `public/` | sim | …é a correção |
| **B** — `DocumentRoot` na raiz **sem** reescrita, acesso por `https://host/public/app` | **não** | …transforma todo link e todo asset em **404** |

**O kit não distribui `.htaccess` na raiz** — confirmado: só existe `public/.htaccess`. Logo o
arranjo B é o de quem nunca acrescentou a gambiarra, e não é hipótese.

A seção `## Ambiguidades` acima afirmava que os dois eram "o mesmo caso visto de outro ângulo".
**Estava errado**: um tem a reescrita e o outro não, e a base não os distingue.

### Cláusulas revisadas

| ID | Cláusula | Origem | Tipo | Substitui |
|----|----------|--------|------|-----------|
| RQ-06 | O `/public` só é removido quando houver **evidência positiva** de que `/` roteia para dentro de `public/` | achado 1 do `/code-review` | restrição | **RQ-01 (parcial)** |
| RQ-07 | Sem evidência, o kit **não age** — o que funciona continua funcionando | falha segura | restrição | — |
| RQ-08 | O operador pode **declarar** o comportamento por configuração, nos dois sentidos | nginx não tem `.htaccess` para inspecionar | funcional | — |

**RQ-01 passa a valer sob RQ-06**: *"nunca exibir `/public`"* torna-se *"nunca exibir `/public`
quando for seguro removê-lo"*. A alternativa seria quebrar instalação que funciona, o que é pior
do que o sintoma que a cláusula original queria eliminar.

### Limitação declarada (achado 3 do `/code-review`)

A correção conserta o que a aplicação **gera**. O que vem do próprio request continua prefixado:
a URL pretendida que `redirect()->guest()` guarda (`fullUrl()`) e o `url()->previous()`, que sai
do `Referer`. Quem cai no login vindo de `/public/app` volta para `/public/app` depois de
autenticar. Está escrito na documentação e no docblock da classe — **não** foi corrigido nesta
entrega, e é dívida declarada, não omissão.
