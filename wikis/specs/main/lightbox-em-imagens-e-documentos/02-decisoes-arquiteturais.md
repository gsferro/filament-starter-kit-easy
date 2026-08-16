# Decisões Arquiteturais — Lightbox em imagens e documentos

## ADR-01: O kit adota um pacote de lightbox em vez de um modal próprio

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

O requisito pede ampliação de mídia em tabela. O Filament não tem isso nativamente: `ImageColumn` renderiza a miniatura e `->action()` abre modal, mas o modal do Filament é um round-trip Livewire e não tem navegação entre imagens, zoom nem fechamento por `Esc`.

### Decisão

Instalar `solution-forest/filament-simplelightbox` (v1.x, linha que suporta Filament 4 e 5) e usar o macro `->simpleLightbox()`.

### Alternativas Consideradas

1. **Modal do Filament com `->action()`** — descartada: cada clique vira request Livewire para exibir uma imagem que o navegador já tem em cache. Custo de servidor por clique, e ainda faltaria zoom e teclado.
2. **`<a target="_blank">` na miniatura** — descartada: tira o usuário da listagem, perde filtro, paginação e posição de rolagem.
3. **Escrever um lightbox próprio em Alpine** — descartada pela escada do Ponytail (rung 5: dependência já existente/disponível resolve). Seriam ~150 linhas de JS + CSS + acessibilidade de foco para reimplementar `fslightbox`, que o pacote já entrega compilado.

### Consequências

- **Positivas**: zero JS de autoria própria; o pacote registra o asset pelo mesmo mecanismo dos outros plugins do kit; funciona sem tema Filament customizado e sem `npm run build`.
- **Negativas**: mais uma dependência de terceiro na superfície de UI; o preview de documento tem a restrição do ADR-03.
- **Riscos**: pacote pequeno, de um único mantenedor. Mitigação: o rollback é trivial (remover 3 linhas de plugin e 3 chamadas de macro), e sem o macro a coluna continua sendo uma `ImageColumn` normal.

### Referências

- `vendor/solution-forest/filament-simplelightbox/src/SimpleLightBoxPlugin.php`
- `01-plano-acao.md` → passos 1 a 4

---

## ADR-02: O plugin é registrado nos TRÊS painéis, inclusive no que não tem mídia hoje

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

`SimpleLightBoxPlugin::boot(Panel $panel)` registra **macros** — `ImageColumn::macro('simpleLightbox', …)` e três irmãs. Macro é resolvido por `Macroable::__call()` no momento da chamada; se o painel corrente não registrou o plugin, `->simpleLightbox()` lança `BadMethodCallException` **na renderização da tabela**, não no boot.

O `/infra` não tem coluna de mídia hoje. Registrar o plugin lá é, à primeira vista, peso sem uso.

### Decisão

Registrar `SimpleLightBoxPlugin::make()` nos três painéis: `/admin`, `/app` e `/infra`.

### Alternativas Consideradas

1. **Só nos painéis com mídia (`/admin`, `/app`)** — descartada: a primeira coluna de imagem criada no `/infra` derruba a tela, com uma exceção que não menciona "painel" nem "plugin". O modo de falha é caro e silencioso até o clique; a economia é um `<script>` por página.
2. **Registrar o macro num service provider do kit, fora do plugin** — descartada: duplicaria o `boot()` do vendor e quebraria no primeiro upgrade que mudasse a assinatura do macro.

### Consequências

- **Positivas**: a convenção "coluna de imagem nasce com lightbox" vale em qualquer painel, sem pegadinha por localização.
- **Negativas**: ~20 KB de JS carregados no `/infra` sem uso imediato.
- **Riscos**: nenhum relevante — o script não executa nada sem um elemento com `x-on:click` correspondente.

### Referências

- `vendor/solution-forest/filament-simplelightbox/src/SimpleLightBoxPlugin.php:24-104` (o `boot()` que registra os macros)
- `.ai/rules/filament.md` — candidato a rule no step 9

---

## ADR-03: Lightbox em documento é permitido apenas para arquivo público e não sensível

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

O requisito diz "ou for colocado um documento na table, devemos usar esse pacote". Mas o JS do pacote não renderiza documento localmente. Em `resources/js/index.js`:

```js
getViewerURL(url) {
    let extension = url.split('.').pop();
    switch (extension) {
        case 'pdf':
            return `https://docs.google.com/viewer?url=${url}&embedded=true`;
        case 'doc': case 'docx': case 'xls': case 'xlsx': case 'ppt': case 'pptx':
            return `https://view.officeapps.live.com/op/embed.aspx?src=${url}`;
        default:
            return url;
    }
}
```

Duas consequências, nenhuma delas mencionada na página do plugin:

1. **A URL do documento é enviada a um terceiro** (Google ou Microsoft), que a registra e a busca a partir da infraestrutura dele.
2. **O arquivo precisa ser publicamente acessível pela internet.** Documento atrás de autenticação, em rede interna ou em disk privado devolve preview em branco — falha silenciosa, sem erro na tela.

Imagem não tem esse problema: o ramo `default` devolve a própria URL e o `fslightbox` a exibe direto do navegador do usuário, que já está autenticado.

### Decisão

A convenção do kit fica assimétrica, e escrita assim na documentação:

- **Imagem, foto, avatar, logo**: lightbox **sempre**. Renderização 100% local.
- **Documento (PDF/Office)**: lightbox **apenas** quando o arquivo já é público e não é sensível (manual, catálogo, folheto). Documento com dado pessoal, contrato, holerite ou anexo de cliente segue com **download autenticado**, sem lightbox.

### Alternativas Consideradas

1. **Aplicar lightbox a todo documento, como o requisito sugere ao pé da letra** — recusada: transformaria a convenção do kit num vazamento de URL de documento privado por padrão, e o preview apareceria em branco justamente nos casos privados. É o tipo de default que ninguém percebe estar errado.
2. **Viewer local de PDF (PDF.js) embutido no kit** — descartada nesta entrega: é uma feature própria, com asset novo, e o requisito não a pediu. Fica registrada como caminho se a necessidade aparecer.
3. **Proxy interno que serve o documento com URL assinada de curta duração** — descartada: resolve a acessibilidade, **não** resolve o vazamento (a URL assinada continua indo para o Google) e custa uma rota, um controller e uma política de expiração.

### Consequências

- **Positivas**: a convenção não vira, por descuido, um canal de exfiltração de documento privado.
- **Negativas**: RQ-03 é atendido parcialmente e de forma condicionada, não literal. Está declarado no `00-requisito.md`.
- **Riscos**: alguém lê a receita pela metade e aplica em documento sensível. Mitigação: o aviso fica no **mesmo bloco de código** da receita, não em nota de rodapé.

### Referências

- `vendor/solution-forest/filament-simplelightbox/resources/js/index.js` → `getViewerURL()`
- `00-requisito.md` → ambiguidade de RQ-03

---

## ADR-04: A mídia continua em disk público, e a coluna nova não muda isso

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

Avatar (`users.avatar_url`) e logo (`tenants.logo`) já vivem no disk `public`, servidos por URL direta sem passar pelo `auth`. Quem souber a URL vê o arquivo sem estar logado. A coluna nova torna essa URL visível na tela para quem já tinha acesso à listagem.

### Decisão

Não mudar o modelo de armazenamento nesta entrega. A exposição é pré-existente e deliberada: a logo precisa ser servível sem sessão para aparecer na tela de bloqueio (`TelaBloqueio`), que é justamente a tela de quem **não** tem sessão ativa.

### Alternativas Consideradas

1. **Mover para disk privado com rota assinada** — descartada aqui: quebra a tela de bloqueio, quebra o avatar do menu, e é mudança de arquitetura de mídia que o requisito não pediu. Se um dia for feita, é wiki própria.

### Consequências

- **Positivas**: nenhuma superfície nova de exposição; a entrega não mistura apresentação com modelo de acesso a arquivo.
- **Negativas**: avatar e logo continuam enumeráveis por quem descobrir o padrão de path.
- **Riscos**: aceito e registrado. O `TenantForm` já barra SVG por causa de XSS armazenado (`acceptedFileTypes` explícito), que é o risco realmente perigoso desse disk.

### Referências

- `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:100-126`
- `app/Models/Tenant.php:119-146`

---

## ADR-05: A listagem usa a `ImageColumn` crua — que já verifica existência sozinha

**Status**: Aceita — **premissa corrigida durante a implementação**
**Data**: 2026-08-15

### Contexto

`Tenant::logoUrl()` verifica `Storage::disk('public')->exists($this->logo)` antes de devolver a URL, para não entregar logo quebrada. A pergunta era se a coluna deveria ser alimentada por `->state(fn (Tenant $record) => $record->logoUrl())` para herdar essa verificação.

> ⚠️ **A premissa original deste ADR estava errada, e foi o teste que a derrubou.**
>
> Escrito antes da implementação, ele afirmava que *"`ImageColumn` não faz essa verificação: monta a URL a partir da coluna e deixa o navegador descobrir o 404"*, e recusava o `->state()` para evitar um `Storage::exists()` por linha.
>
> O código do Filament diz o contrário (`vendor/filament/tables/src/Columns/ImageColumn.php:208-220`):
>
> ```php
> if ($this->shouldCheckFileExistence()) {
>     try {
>         if (! $storage->exists($state)) {
>             return null;
> ```
>
> `shouldCheckFileExistence()` é **verdadeiro por padrão**. Ou seja: a ida ao disco por linha **já acontece**, e registro cuja mídia sumiu renderiza **célula vazia**, não imagem quebrada.
>
> Como o defeito apareceu: o primeiro CT-01 falhou com `<img src="">` para um usuário cujo `avatar_url` apontava para um caminho que a fixture não havia criado no disco. A correção foi na **fixture** (`Storage::fake('public')` + `put()`), não no código — mas ela revelou que o argumento do ADR não distinguia as duas alternativas.

### Decisão

Usar `ImageColumn::make('logo')->disk('public')` cru. A conclusão não muda; o **motivo** muda.

### Alternativas Consideradas

1. **`->state()` chamando `logoUrl()`** — descartada, agora por redundância e não por custo: a verificação de existência que ela traria **já é feita** pela coluna. Sobraria um acessor a mais no caminho, com o mesmo I/O.
2. **`->checkFileExistence(false)`** para economizar a ida ao disco — descartada: é o padrão do Filament, o kit não tem medida de que ele doa aqui, e desligá-lo troca célula vazia por imagem quebrada. Se um dia o disco for remoto e a listagem for grande, esta é a alavanca — com medição antes.
3. **Cache do resultado do `exists()`** — descartada: over-engineering para um caso de borda (arquivo apagado por fora da aplicação).

### Consequências

- **Positivas**: comportamento padrão do Filament, que qualquer pessoa reconhece; mídia ausente degrada para célula vazia, que é o mesmo destino do RQ-10.
- **Negativas**: uma chamada de `Storage::exists()` por linha renderizada — inerente ao componente, não à nossa escolha.
- **Riscos**: com disk remoto (S3) e listagem grande, essa verificação é latência por linha. Fica registrado como a alavanca a puxar, com medição.

### Referências

- `vendor/filament/tables/src/Columns/ImageColumn.php:208-220`
- `app/Models/Tenant.php:119-146`
- `tests/Kit/LightboxEmTabelaTest.php` — o `beforeEach` explica por que a fixture cria o arquivo de verdade
- `03-progresso.md` → Notas de Implementação
