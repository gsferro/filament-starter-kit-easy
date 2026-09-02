---
title: "Busca e idioma"
parent: "Referência"
grand_parent: "Português"
nav_order: 2
---

# Busca e idioma

## A busca ⌘K

[![Busca ⌘K](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/spotlight.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png)

O campo na topbar é o **nativo do Filament** — mesma marcação, mesma aparência, mesmo `Ctrl/⌘+K`. O que muda é o que acontece ao clicar: em vez de digitar ali, abre o overlay do Spotlight, que busca em quatro frentes:

| Categoria | O que encontra |
|---|---|
| **Registros** | a busca global nativa do Filament (respeita `getGloballySearchableAttributes()` dos seus resources) |
| **Telas** | os resources do painel, **filtrados por `canAccess()`** |
| **Páginas** | as páginas do painel, também por `canAccess()` |
| **Ações** | "Criar X" para cada resource, com `canAccess()` + `canCreate()` + `shouldRegisterNavigation()` |

O filtro por permissão é a razão de existirem `App\Filament\Spotlight\*` no kit: as categorias do pacote **não** chamam `canAccess()`, e sem isso a busca oferece telas que resultariam em 403 — vazamento de affordance. As sugestões "Criar X" também são do kit (`AcoesDeCriacao`), pelo mesmo motivo e mais um: o discovery do pacote resolve URLs sem checar contexto e derruba a tela de login com 500.

## O seletor de idioma

O botão de idioma (`bezhansalleh/filament-language-switch`) está registrado nos **três painéis e também nas telas de login** — que é justamente onde alguém que não lê português precisa trocar, antes de existir sessão.

**Ele é dirigido por dado, não por flag.** A lista de locales fica em `config/kit.php`:

```php
'idiomas' => ['pt_BR'],           // como o kit nasce: um idioma, sem botão
'idiomas' => ['pt_BR', 'en'],     // dois idiomas: o seletor aparece sozinho
```

Com **um único idioma** — que é o padrão — o seletor não aparece: não há para onde trocar. É a razão de isto ser uma lista e não um booleano; ninguém esquece uma flag ligada com um idioma só.

> ⚠️ **O seletor traduz a camada do Filament e dos pacotes, não os rótulos do kit.** A cobertura vem do Filament e do `laravel-lang/common`. "Administrador Geral", "Acesso ao painel /app", os títulos dos hubs e os labels dos resources são strings pt-BR escritas no código — há onze `__()` em todo o app. Com `en` ligado hoje, **metade da tela troca de idioma e a outra metade não**. Internacionalizar o kit é trabalho declarado e ainda não feito.

