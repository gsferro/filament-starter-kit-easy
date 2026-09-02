---
title: "Attachments and media"
parent: Features
grand_parent: English
nav_order: 2
---

# Attachments and media

`filament/spatie-laravel-media-library-plugin` delivers the media layer — uploads, collections and
conversions — inside Filament's form, table and infolist components. The demo model
`App\Models\Projeto` shows the whole design:

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
            ->nonQueued()   // with no guaranteed worker running: queued, the
            ->width(200)    // conversion would only exist with a worker up, and the
            ->height(200);  // column would stay empty with no error at all
    }
}
```

And `ProjetoResource` consumes both ends:

```php
SpatieMediaLibraryFileUpload::make('anexos'),   // in the form

SpatieMediaLibraryImageColumn::make('anexos')   // in the table
    ->simpleLightbox(),
```

`->simpleLightbox()` works with no glue because `SpatieMediaLibraryImageColumn` **extends
`ImageColumn`**, which is exactly where the lightbox macro is registered.

[![The Projeto listing on /app with the attachment column: circular thumbnails stacked on each record's row](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-projetos-anexos.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-projetos-anexos.png)

Look at the thumbnails stacked on the record's row: each one is served through a **signed URL**,
because the disk is private — the same file requested without the signature answers 403.

**Organization scoping comes for free** — and that's the point. Spatie's `media` table is
polymorphic: the file belongs to the record, and the record is already scoped by
`BelongsToTenant`. Whoever can't reach the project can't reach the attachment, with no tenant
column in `media` and no configuration to remember to turn on.

> ⚠️ **The default media disk is `local`, and it is private on purpose.** With
> `MEDIA_DISK=public` the file lands in `storage/app/public`, served by the `public/storage`
> symlink: path `/storage/{id}/{file}`, sequential ID, reachable **without a session** — Filament's
> multi-tenancy does not reach the file system. Use `public` only for avatars and logos, which show
> up on the login screen.
>
> Two practical consequences of the private disk:
>
> 1. **`Media::getUrl()` answers 403.** That is fail-closed, and it is what you want. To publish a
>    link to private media use **`getTemporaryUrl()`**, which signs the URL.
> 2. **Whoever holds the link gets in, for as long as the signature is valid, with no session.**
>    Laravel's `storage.local` route validates the signature, not the user: sharing the link shares
>    the file until it expires. For attachments that need per-organization authorization, serve them
>    through your own route that checks the policy first.
>
> Already running an install with `MEDIA_DISK=public`? The new config only protects NEW files. Run
> **`php artisan kit:midia-privada`** (it takes `--dry-run`) to move what was already written —
> without it, the old media stays served by the symlink.

