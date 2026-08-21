---
paths:
  - 'wikis/specs/**'
---

# Specs

## Justificativa de comportamento de pacote se escreve depois de ler o vendor
Antes de escrever numa wiki, ADR ou caso de teste POR QUE um pacote se comporta de um jeito, abra o arquivo do `vendor/` e cite `file:line`. Escrever a explicação a partir do que você espera encontrar, e conferir depois (ou nunca), é o padrão que já produziu três erros numa única feature.

Na feature `anexos-privados`, três afirmações estavam factualmente erradas e as três SUSTENTAVAM decisões de desenho:

- "o `visibility('private')` do componente é decorativo" — `SpatieMediaLibraryFileUpload::getDiskName()` usa a visibilidade para forçar `local` quando o default seria público
- "o `Storage::fake()` substitui a rota `storage.{disk}`" — não substitui; a rota fica de pé e o `ServeFile` lê do root falso consultando o config capturado no boot
- "o `phpunit.xml` não define `MEDIA_DISK`, então o teste lê o `config/`" — o veredito dependia do `.env`, que é gitignorado

Nas três, a CONCLUSÃO estava certa por outro motivo. É isso que torna o erro invisível: a wiki fica verde, o teste passa, e o defeito só aparece quando alguém tenta consertar o cenário pelo motivo escrito — e conserta a coisa errada.

Sinal de alerta na sua própria escrita: frase sobre vendor sem `file:line` ao lado.
