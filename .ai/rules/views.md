---
paths:
  - 'resources/views/**'
---

# Views

## Nunca escreva diretiva do Blade dentro de comentário {{-- --}}
Comentário do Blade NÃO protege o que está dentro dele. O compilador processa as diretivas primeiro e só depois remove o comentário — então mencionar `@include`, `@php` ou qualquer `@diretiva` como texto explicativo, **mesmo entre crases**, vira código no arquivo compilado.

O sintoma é cruel: a página inteira morre com `ParseError: syntax error, unexpected token ","` apontando para `storage/framework/views/<hash>.php`. O arquivo compilado, não a sua view. Custou dois ciclos em `resources/views/filament/perfil-indicator.blade.php` (feature v1-enriquecimento-kit).

No comentário, escreva o nome por extenso: "por inclusão", "no bloco PHP abaixo". Nunca a diretiva.

Vale também: expressão de várias linhas dentro de atributo de componente (`:icon="$a ? X : Y"` quebrado em linhas) gera PHP inválido. Resolva no bloco `@php` do topo e passe a variável.
