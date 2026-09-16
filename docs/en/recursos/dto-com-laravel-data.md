---
title: "DTOs with Laravel Data"
parent: Features
grand_parent: English
nav_order: 8
---

# DTOs with Laravel Data

When structured data crosses a class boundary in the kit, it travels as a **DTO** — a typed object
from [`spatie/laravel-data`](https://spatie.be/docs/laravel-data), not an array whose shape lives in
a comment.

The difference is not cosmetic. Before this was adopted, the bulk invite result had the same shape
documented **word for word** in two classes: producer and consumer agreed by comment, and nothing
forced them to keep agreeing.

## Where they live

```text
app/Data/
├── Convite/
│   ├── FalhaDoConviteData.php
│   └── ResultadoDoConviteEmMassaData.php
├── Ia/
│   └── VeredictoDoGuardrailData.php
└── Social/
    └── PerfilSocialData.php
```

One subfolder per context, one class per file. **The `Data` suffix is reserved**: no model, service
or enum in the kit ends with it, so `app/Data/**` alone answers "which DTOs exist".

## How to write one

Three rules, and a test that rejects whoever breaks them:

```php
final class VeredictoDoGuardrailData extends Data
{
    public function __construct(
        #[WithCast(BooleanoFlexivelCast::class)]
        public readonly bool $seguro = false,
        public readonly string $categoria = 'fora_de_escopo',
        public readonly string $motivo = '',
    ) {}

    public static function de(array|object|string $fonte): self
    {
        // maps field by field, then calls self::from()
    }
}
```

1. **`final`, with promoted `readonly` properties.** No inheritance, no mutable state.
2. **Creation goes through a named factory** (`de()`, `doSocialite()`, …) that calls `self::from()`
   internally. This is not a style preference: `new MyData(...)` **does not run the package's
   casts**, and the defect is silent — the value goes in unconverted and nobody notices.
3. **External payload mapping is explicit, field by field.** The kit does not use
   `#[MapInputName]`: social providers use three different names for "email verified", and
   automatic casing would fail exactly there, leaving the property null without any error.

## Two prohibitions that are about security

**Credentials never go into a DTO.** `senha`, `password`, `token`, `secret`, `api_key`: a Data
object is serializable — it shows up whole in `toArray()`, in logs, in `dd()` and in a queue
payload. The kit keeps the password as a separate `#[SensitiveParameter]` argument instead.

**A DTO is never a public property of a Livewire component or Filament page.** The package registers
a *synth* that **rebuilds the property from the browser payload** — in that case, the one writing to
the property is the client.

## What the kit's DTOs carry today

| DTO | Comes from | What it fixes |
|---|---|---|
| `VeredictoDoGuardrailData` | prompt classifier response (AI) | the verdict arrived as an array read with `?? false`; now `seguro` has a cast, and a `"false"` string (truthy in PHP) no longer releases a blocked prompt |
| `PerfilSocialData` | user returned by the social provider | normalizes the email, tells "no email" apart from an empty string, reads the verification flag from all three possible names, and **leaves provider credentials out** |
| `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` | `Convite::convidarEmMassa()` | ends the duplicated shape between the model and the screen |

## API consumption and responses

**Every piece of data entering or leaving through an API has a DTO.** The kit exposes no API of its
own today — there is no `routes/api.php` and no `app/Http/Resources/` — which is why the rule lives
in an automated guard instead of a note in the README: `App\Support\GuardaDoPadraoDeDto` turns red
the day the first API route, Resource or JSON-returning controller shows up without a Data in the
path.

The guard runs in `tests/Kit/DtoComLaravelDataTest.php` and also checks structure: inheritance,
`final`, `readonly`, the reserved suffix, credential properties and `new` outside the factory. The
roots it scans are public data (`GuardaDoPadraoDeDto::RAIZES_PADRAO`), and one test asserts that the
published kit passes it with zero findings — a guard that complains about everything is as useless
as one that complains about nothing.

## In your project

`app/Data/` travels with `kit:update`, and the convention is recorded in `.ai/rules/app.md`, which
AI agents working on your project read automatically. To create a new DTO, copy the shape of one of
the three above; if you break a rule, the test tells you which one and why.
