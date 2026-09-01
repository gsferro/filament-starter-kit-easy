# Decisões Arquiteturais — A arte do login com o nome da aplicação

## ADR-01: Data URI, e não rota nem arquivo estático

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

O SVG precisa passar a conter um valor de runtime (`config('app.name')`), e hoje é um arquivo em `public/` servido por `asset()`. Arquivo estático não tem como conter valor de runtime, então alguma coisa precisa mudar de lugar. O consumidor final é um `<img src="…">` (`vendor/caresome/filament-auth-designer/…/partials/media.blade.php`), que aceita tanto URL quanto `data:` URI.

### Decisão

`IdentidadeDoKit::arteDoLogin()` renderiza a view `svg.arte-do-login` e devolve
`data:image/svg+xml;base64,…`. Nenhuma rota nova, e o arquivo `public/images/auth/login.svg` é removido.

Decisão do solicitante, entre as três opções apresentadas.

### Alternativas Consideradas

1. **Rota servindo o SVG** (`GET /arte/login.svg`, `Content-Type: image/svg+xml`) — descartada: HTML mais limpo e cache por URL, mas custa uma rota pública nova e **não resolve** o problema das capturas (ver Consequências), já que o navegador da suíte teria de servir a rota também.
2. **Manter o arquivo e sobrepor o nome por HTML** (um `<div>` posicionado sobre a imagem, via render hook) — descartada por ser a mais frágil: o nome deixa de fazer parte da imagem, desalinha em telas estreitas e some se a arte for usada fora da tela de login.
3. **Gerar o arquivo na instalação** (o `kit:install` reescreveria o SVG com o nome) — descartada: congela o nome no momento da instalação, e quem trocar o `APP_NAME` depois volta a ter a tela mentindo. O requisito pede "dinâmico".

### Consequências

- **Positivas**: uma peça a menos (sem rota, sem arquivo público); o nome é lido a cada render, então trocar `APP_NAME` reflete na hora.
- ~~**Positiva colateral**: as capturas do `composer art` passariam a mostrar a arte, hoje quebrada.~~ **Retirada — a premissa era falsa.** As capturas mostram a arte pintada hoje; o `asset()` **é** servido pelo navegador da suíte. Ver a correção no `00-requisito.md`. Esta ADR não tem benefício colateral nas capturas: elas mudam porque o **texto** da arte muda, e isso é a RQ-05, não um conserto.
- **Risco que a retirada acima expõe, e que passa a ser o ponto de atenção da decisão**: hoje a arte **funciona** nas capturas. O data URI é construído por nós, e um mime errado, um `;base64` esquecido ou um payload truncado quebram uma imagem que hoje pinta — sem mover nenhum status HTTP e sem mudar nenhuma string do documento. É por isso que o CT-B01 do `04` foi aceito: ele deixou de ser "conserta um defeito" e passou a ser **guarda de regressão** sobre comportamento que já é bom.
- **Negativas**: ~1,8 KB a mais no HTML de cada tela de autenticação, e a imagem deixa de ser cacheável em separado pelo browser. São seis telas públicas, cada uma visitada uma vez por sessão — o custo é irrelevante diante de uma peça a menos.
- **Riscos**: `base64_encode` a cada render. É um SVG de 1,3 KB; medir só se aparecer em profiling, não antes.

### Viabilidade verificada: o data URI cai no ramo `<img>`

O pacote escolhe entre `<img>` e `<video>` por **extensão de arquivo**
(`MediaDetector::isVideo()` → `pathinfo($path, PATHINFO_EXTENSION)`), e um data URI base64 **não tem
extensão**. Se caísse no ramo de vídeo, a arte sumiria da tela e a decisão inteira seria inviável.

Confirmado por execução, não por leitura:

```
$ php -r '$d="data:image/svg+xml;base64,PHN2Zy…";
          var_dump(pathinfo(strtok(strtok($d,"?"),"#"), PATHINFO_EXTENSION));'
string(0) ""
```

Extensão vazia → `in_array('', VIDEO_EXTENSIONS, true)` é `false` → ramo `<img>`. E é seguro por
construção: o alfabeto base64 (`A–Z a–z 0–9 + / =`) não contém ponto, então nenhum conteúdo de arte
pode produzir uma extensão acidental.

### Referências

- `app/Support/IdentidadeDoKit.php:74` — o ponto que muda
- `vendor/caresome/filament-auth-designer/resources/views/components/partials/media.blade.php` — o `<img>` que consome
- `vendor/caresome/filament-auth-designer/src/Support/MediaDetector.php` — a escolha do ramo por extensão
- `art/login.png`, `art/login-social.png`, `art/app-bloqueio-social.png` — as três capturas com a arte

---

## ADR-02: Nome longo transborda, e isso fica declarado em vez de resolvido

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

`<text>` em SVG não quebra linha sozinho. Um `APP_NAME` longo — "Secretaria Municipal de Obras e Urbanismo" — passa da largura da arte e é cortado pelo `viewBox`.

### Decisão

Não resolver agora. O nome é renderizado numa linha só, e o comportamento com nome longo fica **declarado** aqui e no README.

### Alternativas Consideradas

1. **`<foreignObject>` com HTML dentro do SVG** — quebra de linha nativa, mas `foreignObject` não renderiza quando o SVG está em `<img>` (é justamente o nosso caso). Descartada por não funcionar aqui.
2. **Quebrar o nome em PHP e emitir vários `<tspan>`** — funciona, e é a saída se o caso aparecer. Descartada agora por YAGNI: exige escolher largura de fonte, contar caracteres e decidir número máximo de linhas, tudo para um caso que ninguém relatou.
3. **Diminuir a fonte proporcionalmente ao comprimento** — descartada: nome longo viraria ilegível, trocando um defeito visível por um pior.

### Consequências

- **Positivas**: nenhuma linha de código para um caso hipotético; o kit continua com a arte que ele tem.
- **Negativas**: quem tiver nome longo vê o nome cortado. Mitigação real e já existente: enviar a própria arte pelas Settings (RQ-06), que é o caminho de quem tem marca.
- **Riscos**: se o caso aparecer, a alternativa 2 é o caminho, e este ADR já a deixa escolhida.

### Referências

- `00-requisito.md` → Riscos
- O campo `arte_do_login` em `/admin/configuracoes-do-kit`, que é a saída para marca própria
