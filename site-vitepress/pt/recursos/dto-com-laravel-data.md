---
title: "DTO com Laravel Data"
description: "Quando um dado estruturado atravessa a fronteira de uma classe no kit, ele viaja como DTO — um objeto tipado do spatie/laravel-data, não como array com o…"
---

# DTO com Laravel Data

Quando um dado estruturado atravessa a fronteira de uma classe no kit, ele viaja como **DTO** —
um objeto tipado do [`spatie/laravel-data`](https://spatie.be/docs/laravel-data), não como array
com o formato escrito no comentário.

A diferença não é estética. Antes desta adoção, o resultado do convite em massa tinha o mesmo
formato documentado **palavra por palavra** em duas classes: quem produzia e quem consumia
concordavam por comentário, e nada os obrigava a continuar concordando.

## Onde eles moram

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

Uma subpasta por contexto, uma classe por arquivo. **O sufixo `Data` é reservado**: nenhum model,
serviço ou enum do kit termina nele, então `app/Data/**` responde sozinho "quais DTOs existem".

## Como se escreve um

Três regras, e um teste que reprova quem não as segue:

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
        // mapeia campo a campo e chama self::from()
    }
}
```

1. **`final`, com propriedades `readonly` promovidas.** Sem herança e sem estado mutável.
2. **A criação passa por uma fábrica nomeada** (`de()`, `doSocialite()`, …) que chama `self::from()`
   por dentro. Isto não é preferência de estilo: `new MeuData(...)` **não roda os casts** do pacote,
   e o defeito é silencioso — o valor entra sem conversão e ninguém percebe.
3. **O mapeamento do payload externo é explícito, campo a campo.** O kit não usa
   `#[MapInputName]`: os provedores sociais usam três nomes diferentes para "e-mail verificado", e
   o casing automático erraria justamente aí, deixando a propriedade nula sem erro nenhum.

## Duas proibições que valem segurança

**Credencial não entra em DTO.** `senha`, `password`, `token`, `secret`, `api_key`: um Data é
serializável — aparece inteiro em `toArray()`, em log, em `dd()` e no payload de uma fila. O que o
kit faz com a senha é mantê-la como parâmetro à parte, marcado `#[SensitiveParameter]`.

**DTO não é propriedade pública de componente Livewire ou de página Filament.** O pacote registra
um *synth* que **reconstrói a propriedade a partir do payload do navegador** — quem escreve na
propriedade, nesse caso, é o cliente.

## O que os DTOs do kit carregam hoje

| DTO | De onde vem | O que resolve |
|---|---|---|
| `VeredictoDoGuardrailData` | resposta do classificador de prompt (IA) | o veredito chegava como array e era lido com `?? false`; agora `seguro` tem cast, e `"false"` em texto (que é *truthy* em PHP) não libera mais um prompt bloqueado |
| `PerfilSocialData` | usuário devolvido pelo provedor social | normaliza o e-mail, distingue "sem e-mail" de string vazia, lê a marca de verificação dos três nomes possíveis e **deixa as credenciais do provedor de fora** |
| `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` | `Convite::convidarEmMassa()` | acaba com o formato duplicado entre o model e a tela |

## Consumo e resposta de API

**Todo dado que entra ou sai por uma API tem DTO.** O kit não expõe API própria hoje — não há
`routes/api.php` nem `app/Http/Resources/` —, e é por isso que a regra vive num guarda automático
em vez de um aviso no README: `App\Support\GuardaDoPadraoDeDto` fica vermelho no dia em que
aparecer a primeira rota de API, Resource ou controller devolvendo JSON sem um Data no caminho.

O guarda roda em `tests/Kit/DtoComLaravelDataTest.php` e também confere estrutura: herança, `final`,
`readonly`, sufixo reservado, credencial em propriedade e `new` fora da fábrica. As raízes que ele
varre são dado público (`GuardaDoPadraoDeDto::RAIZES_PADRAO`), e existe um caso afirmando que o kit
publicado passa por ele sem nenhuma reprovação — um guarda que reclama de tudo é tão inútil quanto
um que não reclama de nada.

## No seu projeto

O `app/Data/` viaja no `kit:update`, e a convenção está registrada em `.ai/rules/app.md` — os
agentes de IA que trabalham no seu projeto leem a regra automaticamente. Para criar um DTO novo,
copie o formato de um dos três acima; se você escapar de alguma regra, o teste diz qual e por quê.
