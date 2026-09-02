---
title: "Anexos e mídia"
parent: Recursos
grand_parent: Português
nav_order: 2
---

# Anexos e mídia

O `filament/spatie-laravel-media-library-plugin` entrega a camada de mídia — upload, coleções e
conversões — nos componentes de formulário, tabela e infolist do Filament. A model de demonstração
`App\Models\Projeto` mostra o desenho completo:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Projeto extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('anexos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->nonQueued()   // sem garantia de worker no ar: enfileirada, a
            ->width(200)    // conversão só existiria com um worker de pé, e a coluna
            ->height(200);  // ficaria vazia sem erro nenhum
    }
}
```

E o `ProjetoResource` consome as duas pontas:

```php
SpatieMediaLibraryFileUpload::make('anexos'),   // no formulário

SpatieMediaLibraryImageColumn::make('anexos')   // na tabela
    ->simpleLightbox(),
```

O `->simpleLightbox()` funciona sem cola porque `SpatieMediaLibraryImageColumn` **estende
`ImageColumn`**, que é exatamente onde o macro do lightbox é registrado.

[![Listagem de Projetos no /app com a coluna de anexos: miniaturas circulares empilhadas na linha de cada registro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-projetos-anexos.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-projetos-anexos.png)

Repare nas miniaturas empilhadas na linha do registro: cada uma é servida por **URL assinada**,
porque o disco é privado — o mesmo arquivo pedido sem a assinatura responde 403.

**O escopo por organização vem de graça** — e é o ponto. A tabela `media` do Spatie é polimórfica:
o arquivo pertence ao registro, e o registro já é escopado por `BelongsToTenant`. Quem não alcança
o projeto não alcança o anexo, sem coluna de tenant em `media` e sem configuração para lembrar de
ligar.

> ⚠️ **O disco default da mídia é `local`, e é privado de propósito.** Com `MEDIA_DISK=public` o
> arquivo cai em `storage/app/public`, servido pelo symlink `public/storage`: caminho
> `/storage/{id}/{arquivo}`, ID sequencial, alcançável **sem sessão** — a multi-organização do
> Filament não chega ao sistema de arquivos. Use `public` só para avatar e logo, que aparecem na
> tela de login.
>
> Duas consequências práticas do disco privado:
>
> 1. **`Media::getUrl()` responde 403.** É falha fechada, e é o que se espera. Quem publica link de
>    mídia privada usa **`getTemporaryUrl()`**, que assina a URL.
> 2. **Quem tem o link entra, durante a validade da assinatura, sem sessão.** A rota
>    `storage.local` do Laravel valida a assinatura, não o usuário: compartilhar o link é
>    compartilhar o arquivo até ele expirar. Para anexo que precise de autorização por
>    organização, sirva por rota própria que consulte a policy antes de entregar.
>
> Já tem instalação rodando com `MEDIA_DISK=public`? A config nova protege só o arquivo NOVO.
> Rode **`php artisan kit:midia-privada`** (aceita `--dry-run`) para mover o que já foi gravado —
> sem ele, a mídia antiga continua servida pelo symlink.

