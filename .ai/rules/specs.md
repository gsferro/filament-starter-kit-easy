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

## Em auditoria, varra o padrao repetido antes de ler mais casos de teste
Auditoria de wiki tem retorno decrescente por gate, e retorno alto por **padrão**. Medido em sete releases (0.18.1 → 0.18.7), seis wikis com gate completo:

- os três primeiros gates renderam defeito de código: permissão de `Exception` num papel de cliente, convite nascendo expirado, e um CT-B que visitava página 404 e passava;
- os **três últimos renderam zero** — cobertura completa, correspondência CT ↔ teste conferida caso a caso;
- um `grep -rn '(int) env(' config/` rendeu **dois** defeitos em minutos, um deles apagando dado.

O motivo é estrutural: defeito de fronteira se espalha por **cópia**, e nenhum caso de teste de feature olha para a fronteira. Nenhuma das seis wikis tinha caso para o próprio `.env`.

**Método, na ordem:**

1. Matriz de rastreabilidade primeiro — ela acha a cláusula órfã, que é o que gate serve para achar.
2. Ao encontrar defeito numa fronteira (config, coerção de tipo, cast de env, data de corte, chave de cache, escopo de query), **varra o padrão no repo inteiro antes de consertar aquele ponto**. Um `grep` custa segundos; consertar de um em um deixa os irmãos vivos por releases.
3. Desconfie do remédio que a própria dívida ou wiki prescreve: **cinco de cinco** prescrições auditadas estavam erradas (DT-10, DT-01, DT-02 ×2, DT-08), duas piorariam o problema e uma era no-op. Abra o `vendor/` antes de aplicar — é a mesma regra de `.ai/rules/specs.md`, vista do lado do conserto.
4. Registre hipótese **rejeitada** com o motivo. Relatório sem rejeições parece que só procurou onde achou, e a rejeição costuma custar o mesmo que o achado.

Sinal de que o gate está no fim do retorno: dois relatórios seguidos com 100% de correspondência CT ↔ teste. Aí pare de ler casos e vá varrer padrão.
