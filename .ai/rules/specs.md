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

## Citação de vendor se confere por símbolo, nunca por número de linha
A regra acima manda citar `file:line`. Esta diz como CONFERIR depois — e nasceu de três remediações falhas seguidas na feature `estudo-de-pacotes-rodada-2`.

O número envelhece por dois caminhos, nenhum deles avisa: a sua própria edição desloca (Pint, um import novo — `AuditsFillables.php:17` virou `:21-23` dentro da mesma feature que a escreveu), e o `composer update` desloca em bloco (subir o Filament 5.7.6 → 5.8.2 moveu NOVE âncoras de uma vez: `USER_MENU_BEFORE` 38→43, os quatro `USER_MENU_PROFILE_BEFORE` 92/105/128/143→97/110/133/148, `FOOTER` 58→61 e 122→126).

Conferir por lista de números escolhida à mão não é conferência, é amostragem que você mesmo selecionou. Medido: a 1ª varredura conferiu 18 pares, todos passaram, e o commit dizia "18/18 ok" — mas o símbolo que estava errado não estava na lista. A 2ª corrigiu 57 ocorrências e CORROMPEU duas, produzindo `UiAvatarsProvider.php:27-23`, intervalo invertido — pior que a citação velha, que ao menos apontava para algum lugar. A 3ª deixou seis intactas.

Cite no formato `{path}:{símbolo}:{linha}` e confira mecanicamente, sem lista: extraia as citações com grep, e para cada uma verifique se `sed -n "{linha}p" {path}` contém o símbolo. Toda linha ERRO é citação a corrigir na fonte, nunca a apagar. O resultado (`14/14 ok`) vai para a Verificação Final do `03-progresso.md`.

Vale para citação em QUALQUER arquivo, não só na wiki: o mesmo defeito apareceu em comentário de `app/Providers/Filament/*.php`, `app/Support/*.php` e `resources/views/filament/*.blade.php`.

O contraste que fecha o argumento: na mesma feature, o CT-35 afirma sobre a posição dos dois render hooks do menu do usuário e SOBREVIVEU ao bump sem uma edição, porque lê a blade do vendor e compara posições relativas (`strpos` de um hook contra o do `<x-filament::dropdown>`), não números. O teste se protegeu; a prosa ao lado dele, escrita no mesmo commit, não. Quando a afirmação vale uma citação, ela costuma valer um teste — e o teste não envelhece.
