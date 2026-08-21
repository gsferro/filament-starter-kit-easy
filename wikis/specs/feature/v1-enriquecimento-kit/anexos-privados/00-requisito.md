# Requisito — Anexos privados

## Origem

**Descrição verbal do usuário na conversa** — fonte de baixa fidelidade, transcrita literalmente:

> abre a wiki pro vazamento e corrige

O pedido é curto porque o achado já estava medido na mesma conversa. O texto autoriza a correção;
o conteúdo do defeito vem da medição abaixo.

> **Este arquivo foi reescrito.** A primeira versão atribuía a causa ao componente do Filament, e
> essa atribuição estava **errada**. O registro da correção está em `## Errata`, ao fim — a
> conclusão sobreviveu, o mecanismo não, e a diferença muda qual linha de código se toca.

## O achado, como foi medido

Servidor `php artisan serve` na porta 8000. Requisições **sem cookie e sem sessão**:

```
/storage/1/anexo-secreto.png                       HTTP 200  1087 bytes  image/png
/storage/1/conversions/anexo-secreto-miniatura.jpg HTTP 200  1577 bytes  image/jpeg
/storage/3/sem-disco-explicito.png                 HTTP 200   659 bytes
/app/acme                                          HTTP 302  → /app/login   (controle)
```

A tela exige sessão. O arquivo que ela exibe, não.

## A causa: dois caminhos de escrita, dois discos

O kit tem **dois defaults de disco que discordam entre si**:

| Config | Valor | Onde |
|---|---|---|
| `filament.default_filesystem_disk` | **`local`** | `.env` → `FILESYSTEM_DISK=local` |
| `media-library.disk_name` | **`public`** | `config/media-library.php:36`, `.env.example:141` |

E a coleção não declara disco: `Projeto::registerMediaCollections()` (`app/Models/Projeto.php:83`)
chama `addMediaCollection('anexos')` **sem `useDisk()`**, deixando `MediaCollection::$diskName = ''`.

Com a coleção omissa, cada caminho cai no **seu próprio** default:

| Caminho de escrita | Resolve o disco em | Disco | Resultado |
|---|---|---|---|
| Upload **pela tela** (`SpatieMediaLibraryFileUpload`) | `getDiskName()`, linhas 272-311 | **`local`** | URL assinada que expira |
| `$model->addMedia(...)->toMediaCollection('anexos')` | `FileAdder::determineDiskName()`, linhas 428-443 | **`public`** | **URL pública permanente** |

Medido, com a chamada idiomática do spatie, sem disco explícito:

```
addMedia(...)->toMediaCollection('anexos')   [SEM disco]
  disco gravado: public
  url: http://localhost:8000/storage/3/sem-disco-explicito.png
  temporaryUrl: LANCOU: This driver does not support creating temporary URLs.
```

E essa URL responde **200 sem sessão**.

**O defeito é a discordância, não o componente.** A chamada que vaza é a documentada pelo spatie
e a que qualquer usuário do kit escreveria — inclusive a que o próprio kit já usa no
`tests/BrowserTenancy/AnexosDoProjetoTest.php:66-68`.

## Segundo achado: a rota assinada não autoriza ninguém

O disco `local` tem `'serve' => true` (`config/filesystems.php:36`), o que registra a rota
`storage.local`. Medido com `route:list --json`:

```json
{"uri":"storage/{path}","name":"storage.local","action":"Closure","middleware":[]}
```

**Middleware vazio.** Sem `web`, sem sessão, sem `auth`. E
`vendor/laravel/framework/src/Illuminate/Filesystem/ServeFile.php:55-61` valida **só a assinatura**:

```php
return ! $request->boolean('upload') && (
    ($this->config['visibility'] ?? 'private') === 'public' ||
    $request->hasValidRelativeSignature()
);
```

Consequência: mesmo o caminho "certo" produz um link que **qualquer anônimo abre** até expirar
(5 a 30 minutos, conforme o gerador). É redução grande de superfície comparada à URL permanente,
mas **não é isolamento por organização**. E o kit não tem `MediaPolicy` nem gate de mídia.

## Terceiro achado: colisão de URI

A rota `storage.local` nasce em `/storage/{path}` — **o mesmo caminho** do symlink `public/storage`
criado por `KitInstall.php:299`. Arquivo físico ganha da rota, no `artisan serve` e no Nginx.

Hoje não colide porque os roots diferem. Mas as mídias legadas em `storage/app/public/{id}/` têm
o padrão `{id}/{arquivo}` — o mesmo da rota. Um arquivo público antigo **sombreia em silêncio** a
rota assinada de um arquivo privado de mesmo id e nome.

## Quarto achado: o lightbox entrega URL a terceiro — mas hoje não dispara

`vendor/solution-forest/filament-simplelightbox/resources/js/index.js:11-23` manda PDF para
`docs.google.com/viewer?url=` e Office para `view.officeapps.live.com`.

**Não acontece no kit hoje**: `ProjetoResource.php:159` chama `->simpleLightbox()` **sem argumento**,
então a URL interpolada é vazia e o ramo do visualizador é pulado — o lightbox lê o `src` já
renderizado. É um risco armado para quem passar a URL, não um vazamento em curso.

## Cláusulas

| RQ | Cláusula | Origem |
|----|----------|--------|
| **RQ-01** | Abrir wiki para o vazamento | literal do usuário |
| **RQ-02** | Anexo não pode ser recuperável sem sessão, **por qualquer caminho de escrita** | medição |
| **RQ-03** | A miniatura está sujeita à mesma regra | medição |
| **RQ-04** | Os dois defaults de disco precisam parar de discordar | leitura das duas resoluções |
| **RQ-05** | As mídias já gravadas em disco público precisam de tratamento | medição: 3 linhas com `disk=public` |
| **RQ-06** | Documentação e comentários passam a descrever o que o código faz | ver `## Errata` |
| **RQ-07** | A ausência de autorização na rota assinada precisa de decisão explícita | `ServeFile.php:55-61` |
| **RQ-08** | Corrigir | literal do usuário |

### Sobre a RQ-07

Pode ser **"aceitar e documentar"**. Link assinado de vida curta é um patamar de proteção
legítimo, e muito acima do atual. Autorização real exige `UrlGenerator` próprio, controller
autenticado e policy de mídia — decisão de arquitetura, não de correção de bug. Vai para ADR.

### Sobre a RQ-05

**Nenhuma correção de default migra o que já existe.** Os arquivos em `storage/app/public/{1,2,3}`
continuam servidos pelo symlink, e o vazamento medido continua reproduzível exatamente como está.
Sem tratar isso, a wiki fecha a porta e deixa a janela aberta.

## Ambiguidades

- **RQ-02 — corrigir só `Projeto` ou o default do kit?**
  - **Assumido**: o **default**. `Projeto` é a única superfície de mídia hoje, mas o kit existe
    para o usuário aplicar a media library nos models dele, e o caminho que vaza é justamente o
    idiomático. Corrigir só a coleção da demo deixa a armadilha armada para o primeiro model real.
  - **Se negado**: vira uma linha em `Projeto::registerMediaCollections()` e o kit continua
    entregando um default que vaza.

- **RQ-05 — migrar as mídias antigas é escopo desta wiki?**
  - **Assumido**: **sim**, com comando idempotente. São 3 linhas aqui, mas num projeto instalado
    podem ser milhares, e o kit precisa entregar o caminho.
  - **Se negado**: a RQ-05 sai, e a wiki precisa dizer em voz alta que instalações existentes
    seguem vazando.

## Fora de escopo

- Trocar o pacote de lightbox
- Visibilidade de avatar e logo, que são públicos de propósito (aparecem na tela de login, antes
  de haver sessão) e usam `->disk('public')` explícito, fora da media library
- `filament-maillog`, que grava corpo de e-mail em tabela — outra superfície, outra wiki

## Errata

A primeira versão deste arquivo afirmava:

> "A gravação, na linha 154, é `toMediaCollection($collection, $diskName)` — sem visibilidade. E o
> `catch` vazio faz o driver local cair silenciosamente na URL pública. **O comentário descreve
> uma proteção que não existe.**"

**Errado.** `SpatieMediaLibraryFileUpload::getDiskName()` (linhas 272-311) usa a visibilidade
para escolher o disco: quando o default seria `public` e a visibilidade é `private`, ele força
`local` (linhas 291-296 e 303-309). A linha 154 recebe **`'local'`**, não `'public'`. O
`->visibility('private')` do `ProjetoResource` faz sim parte da resolução, e o comentário ao lado
dele descreve uma intenção correta.

**Como o erro entrou.** A medição que gerou o achado foi feita com

```php
->toMediaCollection('anexos', config('media-library.disk_name'))
```

isto é, **passando o disco explicitamente**. O valor coincidiu com o default do caminho
programático, então o resultado observado estava certo — mas a atribuição de causa não podia ser
derivada dele, porque o experimento não exercitava o caminho da tela. A conclusão sobreviveu por
coincidência, não por rigor.

**O que muda na prática.** Consertar o componente seria consertar o que não está quebrado. A
correção mora no default da media library e na coleção — RQ-04.
