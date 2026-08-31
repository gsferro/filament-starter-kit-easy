# Progresso — Anexos privados

**Branch**: `feature/v1-enriquecimento-kit`
**Situação**: **implementado**. Wikis, código, testes e documentação fechados.

## 1. Wikis

- [x] `00-requisito.md` — 8 cláusulas, 2 ambiguidades com premissa e "se negado"
- [x] `01-plano-acao.md` — 5 passos, cobertura por RQ, impacto
- [x] `02-decisoes-arquiteturais.md` — 4 ADRs
- [x] `04-casos-de-teste.md` — 6 regras, 21 cenários, 24 mutantes, 3 lacunas declaradas
- [x] Revisão adversarial por sub-agente independente — 12 achados, todos fechados
- [x] Validação do usuário — liberada com a instrução de finalizar o que faltava

## 2. Implementação

| Passo | Estado | Onde |
|---|---|---|
| 1. defaults concordam | ✅ | `config/media-library.php:36` (`env('MEDIA_DISK', 'local')`), `.env.example:141` |
| 2. `useDisk()` na coleção | ✅ | `app/Models/Projeto.php:89` |
| 3. comando de migração | ✅ | `app/Console/Commands/KitMidiaPrivada.php` (`kit:midia-privada`, com `--dry-run`) |
| 4. testes | ✅ | 3 arquivos, 28 casos — tabela abaixo |
| 5. documentação e rule | ✅ | 6 documentos + `.ai/rules/models.md` |

### Comentários corrigidos

`ProjetoResource.php:118-123` parava de dizer que o `->visibility('private')` é o que protege.
Agora diz o que o vendor faz: o componente usa a visibilidade só para **escolher** o disco quando
o default seria público (`SpatieMediaLibraryFileUpload::getDiskName()`); quem decide é o disco.
`Projeto.php` idem, e passou a ensinar o padrão em vez de descrevê-lo.

## 3. Testes

| Arquivo | CT | Resultado |
|---|---|---|
| `tests/Tenancy/AnexosPrivadosTest.php` | CT-01…CT-11 | 11 ✅ |
| `tests/Tenancy/MigracaoDeMidiaPrivadaTest.php` | CT-12…CT-19 | 8 ✅ |
| `tests/Kit/AnexosPrivadosDocumentacaoTest.php` | CT-20, CT-20b, CT-21, CT-21b | 9 ✅ |
| `tests/BrowserTenancy/AnexosDoProjetoTest.php` | asserção nova sobre a `src` | ajustado |
| `tests/Tenancy/PacotesTierSTenancyTest.php` | `Storage::fake('public')` → `'local'` | ajustado |

### Verificação final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [x] `vendor/bin/filacheck` — 17 regras, 0 falha
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`
- [x] **par 403/200 sem sessão** — CT-06 e CT-11 fazem o experimento que produziu o achado, dentro
      da suíte: a mesma URL, sem assinatura e com assinatura, na mesma requisição de teste

## Desvios do Plano

| Desvio | Motivo |
|---|---|
| **CT-12…CT-19 em `tests/Tenancy`, não `tests/Kit`** | `projetos.tenant_id` é NOT NULL com FK, e `Projeto` é a única superfície de mídia real do kit. Sem organização não há como anexar arquivo pelo caminho de verdade, e mídia fabricada por `Media::create()` mediria o comando contra dado que o kit nunca produz |
| **CT-04 e CT-05 pelo modal da listagem, não por página de criação** | `ProjetoResource::getPages()` só registra `index` — é resource simples, a criação é modal. `Livewire::test(ListProjetos::class)->callAction('create', …)` |
| **CT-04/CT-05 precisam de request HTTP antes do teste de componente** | `noPainelBootado('app')` sozinho morre no `BreezyCore:112`, que lê `request()->route()->parameter('tenant')` no boot do plugin. Um `$this->get()` na listagem antes resolve, e é o que um request real faz |
| **CT-15 e CT-16 usam fixture `OrganizacaoComMidia`** | só o `Projeto` implementa `HasMedia` no kit. A fixture herda de `Tenant` pela tabela e declara as duas naturezas de coleção: uma com `useDisk('public')` e uma sem declaração |
| **CT-20 não cobre `wikis/pacotes-ranking.md`** | aquele documento cita o default DO PACOTE numa análise de adoção, e proibir o literal ali apagaria a comparação. Virou **CT-20b**, que exige a errata |
| **CT-19 virou marco da decisão, não falsificação dela** | o prefixo `/storage` continua compartilhado, como o ADR-01 aceitou. O caso afirma a igualdade dos prefixos: fica vermelho no dia em que alguém der `url` própria ao disco privado, obrigando a reler a decisão em vez de descobri-la por acidente |
| **CT-21b acrescentado** | o comando de migração precisa estar nos dois READMEs, ou instalação existente nunca o roda e a mídia antiga segue pública. Não estava no `04` |

## Notas de Implementação

- **A correção é uma palavra**, e todo o resto é migração, teste e documentação. O primeiro
  diagnóstico apontava para o componente do Filament e teria produzido um diff grande no lugar
  errado.
- **`getUrl()` de mídia privada sempre 403.** Falha fechada, correta — mas quebra código que chame
  `getUrl()` esperando link utilizável. Está nos dois READMEs, na `arquitetura.md`, na
  `receitas.md` e na rule: **quem publica link de mídia privada usa `getTemporaryUrl()`**.
- **O comando decide por visibilidade declarada, não por nome de disco.** `ehPublico()` lê
  `filesystems.disks.{disco}.visibility`, que é a mesma chave que o `ServeFile` consulta.
  Perguntar pelo nome daria falso negativo no primeiro projeto que renomeasse o `public`.
- **A ordem é arquivo, depois linha.** Falha no meio deixa metade migrada — nunca uma linha
  apontando para arquivo inexistente, que faria a mídia sumir da tela.
- **O comando entra em `CAMINHOS_DO_KIT` de graça**: `app/Console/Commands` já é uma entrada
  inteira do `KitUpdate`, e `config/media-library.php` também já estava lá.

## Lacunas que seguem abertas

| # | O quê | Estado |
|---|---|---|
| **L2** | falha no meio da migração (mutante M16: linha do banco antes do arquivo) | sem matador. A ordem correta está no código e comentada; o cenário que a prova é o primeiro a escrever se a migração falhar em campo |
| **L3** | autorização por organização na rota assinada | decisão do ADR-03. **CT-11 fixa o limite** em vez de fechá-lo, e a documentação o declara (CT-21) |

## Blockers

Nenhum.

## Retrospectiva

- Entregou o que o requisito pediu: os dois caminhos de escrita concordam (`MEDIA_DISK` default `local`,
  `useDisk()` na coleção), `kit:midia-privada` migra o legado com `--dry-run`, 28 casos verdes.
- Três afirmações sobre o vendor estavam erradas e sustentavam decisões — virou a rule de
  `.ai/rules/specs.md`: justificativa de pacote se escreve depois de abrir o `vendor/`, com `file:line`.

## Candidatos a Rule de Projeto

**Dois**, e os dois estão gravados.

1. **Coleção de mídia declara o disco** — gravado em `.ai/rules/models.md`, com o porquê (quem
   decide é o disco, não o `visibility` do campo) e a consequência (`getUrl()` 403).
2. **Justificativa de comportamento de pacote se escreve depois de ler o vendor, não antes.**
   Evidência: nesta feature, três afirmações sobre
   `SpatieMediaLibraryFileUpload`, `Storage::fake()` e `phpunit.xml` estavam erradas, e as três
   **sustentavam decisões de desenho** — em todas a conclusão estava certa por outro motivo, o que
   torna o erro invisível até alguém tentar consertar o cenário pelo motivo escrito.
   **Aprovada pelo usuário e gravada** em `.ai/rules/specs.md`, glob `wikis/specs/**` — arquivo de
   área novo, e o `index.md` ganhou a linha correspondente. O glob é só `wikis/specs/**`, e não
   também `.ai/rules/**`: uma regra sobre como escrever regra, indexada apenas no diretório de
   regras, só seria lida por quem já estivesse editando regra — e o erro nasce na wiki.
