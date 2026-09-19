# Decisões Arquiteturais — `/public` nunca aparece na URL

## Superfície Livewire

Nenhuma página, widget ou componente Livewire é criado ou alterado. Registrado explicitamente
porque a skill exige a tabela sempre que há superfície — e "não há" é resposta válida, silêncio
não é.

| Origem | O que foi inventariado | Resultado |
|---|---|---|
| código do projeto | `public function` novo em Page/Widget/componente | **nenhum**. O `handle()` do middleware é público por contrato do framework, e não é alcançável por `$wire.`; o `ouNulo()` é estático e puro *(alterado em 2026-09-18: a versão anterior dizia "método `protected` num provider", desenho que a ADR-05 descartou)* |
| framework | arrays de estado que o cliente escreve (`$filters`, `$pageFilters`, …) | **nenhum consumido** |
| pacote de terceiro | ação que recebe id do cliente, model persistido | **nenhum** — a feature não persiste nada |

O único dado de fora que a correção lê é `Request::getBaseUrl()`, derivado pelo Symfony de
`SCRIPT_NAME`/`REQUEST_URI`. Ele **não** é escrito pelo cliente diretamente, e o uso dele é
comparação de sufixo — não vira índice de array, nome de coluna nem argumento de `parse`.

---

## ADR-01: A correção age na RAIZ da URL, não numa lista de painéis

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

RQ-02 nomeia `/admin`, `/infra` e `/app`. RQ-03 acrescenta *"ou outro que possa vir a ser criada
pelo usuario do kit"* — e é essa cláusula que decide o desenho.

### Decisão

Corrigir a **raiz** com que o `UrlGenerator` monta qualquer URL, uma vez por request. Nenhum
painel é nomeado em lugar nenhum da correção.

### Alternativas Consideradas

1. *Middleware por painel, registrado em cada `PanelProvider`* — descartada: exige que o usuário
   do kit lembre de registrá-lo no painel novo. Isso **viola RQ-03**, que pede garantia para
   painel que ainda não existe.
2. *Reescrever a URL na saída (response) removendo `/public`* — descartada: agiria sobre HTML
   pronto, alcançaria falso-positivo (um link legítimo para `/public/algo.pdf`) e não tocaria
   `Location:` de redirect nem payload de Livewire.
3. *Documentar o `DocumentRoot` certo e não mexer no código* — descartada sozinha, mantida
   **junto**: é a correção de raiz, mas RQ-01 pede que o `/public` nunca apareça, e o kit não
   controla a hospedagem de quem o instala.

### Consequências

- **Positivas**: painel novo criado pelo usuário do kit nasce coberto, sem nenhuma ação dele.
- **Negativas**: a correção é global; não há como um painel optar por sair.
- **Riscos**: aplicação que viva legitimamente sob caminho terminado em `public` tem a raiz
  encurtada — ambiguidade declarada no `00`, assumida deliberadamente.

---

## ADR-02: O gatilho é o SUFIXO da base, não o `APP_URL`

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

O jeito mais curto de eliminar o problema seria `URL::forceRootUrl(config('app.url'))` sempre.

### Decisão

Forçar a raiz **somente** quando `Request::getBaseUrl()` termina em `/public`, e derivar a raiz
nova do **próprio request**, removendo o sufixo. `APP_URL` não entra na conta.

### Alternativas Consideradas

1. *`forceRootUrl(config('app.url'))` incondicional* — descartada por dois motivos concretos:
   - quebra instalação multi-domínio: todo request passaria a gerar URL do domínio do `APP_URL`,
     e o usuário acessando pelo segundo domínio seria jogado para o primeiro;
   - depende de o `APP_URL` estar certo, que é justamente o tipo de configuração que já estava
     errada no ambiente onde o defeito apareceu.
2. *Comparar com `public_path()`* — descartada: resolve caminho de disco, não de URL, e não
   responde o que o Apache entregou.

### Consequências

- **Positivas**: em instalação correta o método **não faz nada** — a base é vazia e ele sai na
  primeira condição. É por isso que CT-02 existe: ele prova o no-op.
- **Negativas**: a correção só age depois de o request chegar; não há garantia em CLI. Aceito —
  em CLI a URL sai de `APP_URL`, que nunca teve o prefixo.

---

## ADR-03: `getSchemeAndHttpHost()` e a dependência de `TrustProxies`

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

A raiz nova é montada com `$request->getSchemeAndHttpHost()`. Atrás de proxy que termina TLS, o
esquema correto depende de `TrustProxies`.

### Decisão

Usar `getSchemeAndHttpHost()` assim mesmo, sem tratamento próprio de proxy.

### Alternativas Consideradas

1. *Ler o esquema de `APP_URL`* — descartada: mistura duas fontes de verdade e reintroduz a
   dependência que a ADR-02 recusou.

### Consequências

- O kit **já** depende de `TrustProxies` para qualquer URL absoluta correta atrás de proxy. Esta
  correção não piora nem melhora esse ponto — ela herda o que já valia.
- Registrado para que ninguém leia um `http://` gerado atrás de proxy como defeito desta correção.

---

## ADR-04: Sem log

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

O padrão do kit é log com channel próprio em toda etapa de execução.

### Decisão

**Nenhum log.**

### Alternativas Consideradas

1. *`warning` uma vez por request quando a base vem com `/public`* — descartada: numa instalação
   mal configurada o ramo dispara em **todos** os requests, para sempre. Seria uma linha por
   request repetindo a mesma informação, e o sinal se perderia no volume que ele próprio cria.
2. *Log só na primeira vez, com marca em cache* — descartada: custo de cache e complexidade de
   invalidação para entregar uma informação que o operador já tem de outro jeito (a URL na barra
   de endereços).

### Consequências

- **Positivas**: método de custo zero, sem I/O.
- **Negativas**: a instalação errada não deixa rastro em log. Mitigado pela documentação, que
  descreve a configuração certa de `DocumentRoot`.


---

## ADR-05: Evidência positiva com falha segura, e a correção vira middleware

**Status**: Aceita
**Data**: 2026-09-18
**Revisa**: **ADR-01** (o local), **ADR-02** (o gatilho — a base sozinha não identifica o arranjo
quebrado) e **ADR-03** (o momento de ler o host)

### Contexto

O `/code-review` do diff derrubou a premissa da ADR-02. A base `/public` **não** identifica o
arranjo quebrado: ela é idêntica no arranjo B, onde `/public/...` é o único endereço que existe.
Encurtar ali quebra uma instalação que funciona — ver Adendo 1 do `00`.

E o achado 2 mostrou um segundo problema no **local**: `boot()` de provider roda **antes** do
`TrustProxies`, então `getSchemeAndHttpHost()` congelaria host e porta sem os cabeçalhos
`X-Forwarded-*`. Atrás de proxy entregando em 8080, a raiz forçada viraria `https://host:8080`.

### Decisão

1. **Middleware global** (`App\Http\Middleware\RaizDeUrlSemPublic`), anexado em
   `bootstrap/app.php` por `Middleware::append()`
   (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:append():185`).
   Roda **depois** do `TrustProxies`, e o achado 2 desaparece por construção.

   O registro chega ao kernel por um `afterResolving` pelo **contrato**
   (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:289`),
   e é por isso que CT-13 resolve `Contracts\Http\Kernel` — não porque a classe concreta
   devolvesse o stack de fábrica, como um comentário anterior afirmava sem medir.
2. **Evidência positiva**: só encurta quando o `.htaccess` da raiz tem uma `RewriteRule` apontando
   para `public/`. É o único sinal que o PHP observa sem sair pela rede, e é exatamente o que
   separa o arranjo A do B.
3. **Falha segura**: sem sinal, não age.
4. **Declaração explícita vence a detecção**, nos dois sentidos, por
   `kit.url.remover_sufixo_public` — saída para nginx, onde a reescrita está no vhost.

### Alternativas Consideradas

1. *Manter a heurística só pela base* — descartada: quebra o arranjo B por completo.
2. *Tentar um HEAD em `/` para ver se responde* — descartada: I/O de rede por request, e uma
   aplicação não deve chamar a si mesma para descobrir onde está.
3. *Exigir config sempre, sem detecção* — descartada: o caso comum ficaria sem proteção, e RQ-01
   pede que o prefixo não apareça. A detecção cobre o arranjo A sem pedir nada ao operador.

### Consequências

- **Positivas**: nenhuma instalação que hoje funciona passa a quebrar. Middleware elimina o
  problema de ordem com o `TrustProxies`.
- **Negativas**: em nginx a detecção não acha sinal e o operador precisa declarar. Documentado.
- **Custo**: uma leitura de arquivo, **só** quando a base já veio com o sufixo — ou seja, nunca
  em instalação correta. *(alterado em 2026-09-18: a versão anterior memoizava por processo; a
  auditoria Ponytail cortou o memo, porque ele forçava uma API pública existente só para o teste
  conseguir resetá-lo.)*
