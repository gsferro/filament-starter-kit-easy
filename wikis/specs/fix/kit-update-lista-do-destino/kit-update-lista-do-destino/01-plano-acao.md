# Plano de Ação — `kit:update` lê a lista de caminhos da versão destino

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/fix/spotlight-sem-estilo/spotlight-sem-estilo/` — é onde o achado foi registrado (passo 6 não planejado daquela wiki entregou o aviso da segunda rodada; esta wiki elimina a necessidade da segunda rodada quando a lista do destino é legível). O CHANGELOG 0.23.1 (sem wiki) é o precedente do mesmo defeito.
- **Motivo**: o aviso da segunda rodada é contorno; o defeito de fundo é o comando filtrar o diff por uma lista que pertence à versão **antiga**.
- **Toca infra compartilhada?**: **sim** — `app/Console/Commands/KitUpdate.php` é o mecanismo de entrega de todo o kit. Regressão obrigatória contra `tests/Kit/KitUpdateTest.php` inteiro (11 casos hoje).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | o diff considera a lista da versão destino | 1, 2, 3 | — |
| RQ-02 | arquivo coberto só pela lista nova entra na mesma rodada | 3, 7 | o passo 7 é a prova em instalação real |
| RQ-03 | provider tolerar a view ausente | — | ⚠️ **alternativa descartada** — ADR-01. Volta ao escopo se o solicitante negar a premissa (ver `00`, Ambiguidades) |
| RQ-04 | fallback para a lista da própria classe quando a do destino não puder ser lida | 2, 4 | — |
| RQ-05 | o aviso "rode de novo" só aparece quando fez falta | 4 | — |
| RQ-06 | o contorno continua documentado para instalações anteriores | 5 | — |

## Objetivo

Fazer o `kit:update` calcular o que mudou usando a **união** entre a lista de caminhos da classe em execução e a lista declarada no `KitUpdate.php` da versão **destino**, lida do próprio git. Com isso, um diretório que só a versão nova cobre (o caso de `resources/views/svg` na 0.23.0) chega na **primeira** rodada, junto com o código que depende dele — e o projeto não fica quebrado entre as duas rodadas.

Quando a lista do destino não puder ser lida (tag sem o arquivo, forma da constante irreconhecível, falha do git), o comportamento é exatamente o atual: lista da própria classe e aviso da segunda rodada.

## Contexto

O comando é um dos arquivos que ele mesmo atualiza. A lista `CAMINHOS_DO_KIT` é constante da classe, e a classe que roda é a **da instalação**. Resultado: caminho que entrou na lista **depois** da versão instalada é invisível na primeira rodada, e só a segunda rodada — com a classe nova — o enxerga. Isso já custou três vezes (0.9.1-0.9.3: só `config/kit.php` chegava; 0.9.8: metade do Filament; 0.23.0: a view da arte do login) e, na terceira, o intervalo entre as rodadas deixou o projeto sem boot: `IdentidadeDoKit.php` (em `app/Support`, coberto pela lista antiga) chegou e a view que ele renderiza no boot (`resources/views/svg`, fora da lista antiga) não.

A v0.30.0 mitigou a segunda rodada (imprime o comando pronto com `--from` e `--no-branch`). Esta wiki remove a causa: se o comando sabe qual é o destino, ele pode perguntar ao destino o que cobrir. O `git fetch` do comando já traz os objetos das tags (`KitUpdate.php:419`) e `aplicar()` já faz `git checkout destino -- caminho` (`:765`) — ler um arquivo do destino é a mesma capacidade, já em uso.

## Análise dos Arquivos Existentes

### `app/Console/Commands/KitUpdate.php`

- `:84` — `private const CAMINHOS_DO_KIT = [ … ];` — 59 caminhos, um por linha, entre aspas simples com vírgula, comentários `/* */` e `//` intercalados. Termina em `    ];` (linha 248).
- `:519-563` — `arquivosAlterados(?string $origem, string $destino): array` — monta `git diff --name-status … -- {CAMINHOS_DO_KIT}` e rotula cada linha. **Único** consumidor da constante no fluxo.
- `:852-918` — `encerrar(string $destino, array $aplicados, ?string $origem)` — imprime o aviso "O próprio `kit:update` foi atualizado nesta rodada" e o parágrafo "RODE O COMANDO DE NOVO … php artisan kit:update{$from} --tag={$versao} --no-branch" quando `app/Console/Commands/KitUpdate.php` está em `$aplicados`.
- `:952-958` — `git(array $args): string` — `Symfony\Component\Process\Process` em `base_path()`, devolve `getOutput()`; **não lança** em falha (saída vazia).
- `:419` — fetch das tags: `refs/tags/*:refs/tags/kit-*`. Os objetos vêm junto; `git show kit-vX:path` resolve.

### `tests/Kit/KitUpdateTest.php`

- `:14-33` — helpers `caminhosDoKit()` (reflexão sobre a constante privada) e `estaCoberto()`. Ficam como estão: a constante continua existindo e continua sendo a lista **desta** versão.
- `:131` — varredura "cobre todo o código do kit" — garante que a constante desta versão está completa. Não muda.
- `:294` — lê o **fonte** do comando e exige as strings do aviso da segunda rodada (`php artisan kit:update{$from} --tag={$versao} --no-branch` e `' --from='.str_replace('kit-v', '', $origem)`). O passo 4 **preserva** essas strings.

### `app/Support/IdentidadeDoKit.php` e `app/Providers/Filament/*PanelProvider.php`

- `IdentidadeDoKit.php:84` renderiza `svg.arte-do-login` no boot dos painéis (`AdminPanelProvider.php:139,146,156`, e o equivalente em `App` e `Infra`). **Não são tocados** — ADR-01 descarta a alternativa (b).

### `docs/pt|en/comecar/atualizando-o-projeto.md`

- Item "**O próprio `kit:update` se atualiza.**" (pt `:43`, en `:43`): explica que o comportamento novo só vale na execução seguinte. Ganha uma frase: a lista de caminhos é lida da versão destino a partir desta versão, e o que isso muda para quem atualiza de versão anterior.

## Autorização

Não se aplica — comando de console, sem usuário autenticado.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** Toda a interação é no terminal (Laravel Prompts já em uso no comando).

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`kit:update` inteiro**: `arquivosAlterados()` passa a receber uma lista possivelmente maior. Nada é aplicado sem aprovação, então o efeito visível é **mais arquivos oferecidos** na revisão — nunca menos (união, ADR-02).
- **Rótulo "removido do kit"**: continua correto. Caminho que existe na lista antiga e não na nova segue no diff (união) e aparece como `D`.
- **Aviso da segunda rodada**: passa a ser condicional (RQ-05). O teste `KitUpdateTest.php:294` lê o fonte e continua verde porque as strings ficam.
- **Instalações anteriores a esta versão**: nenhuma mudança na rodada 1 delas (classe antiga). Ganham a correção a partir da rodada 2.

## Rollback

- `git revert` do commit. Sem migration, sem config, sem dado.
- Se o parser da constante falhar em alguma tag futura, o fallback (RQ-04) já é o comportamento antigo — não há estado intermediário.

## Dependências

Nenhuma nova. `symfony/process` e `git` já são exigidos pelo comando.

## Riscos

- **A forma textual da constante muda** (por exemplo, alguém reescreve a lista como `array_merge(...)` ou muda a indentação): o parser devolve `[]`, o comando cai no fallback e avisa. Mitigação: CT que roda o parser sobre o **fonte atual** e exige igualdade com a constante (via reflexão) — quem mudar a forma quebra o teste antes de publicar a tag.
- **`git show` falha** (tag sem o arquivo — não existe desde a 0.9.x; ou git indisponível — já é pré-condição do comando): saída vazia → `[]` → fallback.
- **Caminho que existe na lista do destino mas não na árvore da tag** (a lista curada é conferida pelo teste `só lista caminhos que existem de fato`, `KitUpdateTest.php:234`): `git diff -- caminho-inexistente` não é erro; produz nada. Sem efeito.
- **Regex sobre PHP é frágil por natureza.** Aceito: a alternativa (arquivo de dados) não funcionaria para nenhuma tag já publicada. Se um dia a lista mudar de casa, o parser vira a camada de compatibilidade das tags antigas. Ver ADR-01.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `ai`, `tenancy`, `autenticacao`, `configuracoes` além dos padrões do Laravel. Nenhum para os comandos `kit:*`, e nenhum dos comandos do kit (`KitInstall`, `KitInfo`, `KitUpdate`, `KitArte`, `KitTenancy`) escreve em log — todos falam pelo terminal (`$this->components`, Laravel Prompts).

### Decisão

**Sem channel** (ADR-03). O comando é interativo, rodado à mão por quem está olhando o terminal; a saída **é** o registro, e é assim que os quatro irmãos se comportam. O único evento novo desta feature — "não consegui ler a lista do destino; usando a desta versão" — é um aviso ao operador no momento em que ele decide, e vai para `$this->components->warn()`. Log em arquivo aqui seria escrito uma vez por atualização e lido por ninguém.

## Estrutura de Implementação

### 1. Extrair a lista de caminhos de um fonte do `KitUpdate.php`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php`
- Método novo, **público e estático** (para o teste alcançar sem reflexão e sem git):

  ```php
  /**
   * Os caminhos declarados em `CAMINHOS_DO_KIT` num fonte deste arquivo — o desta
   * versão ou o de qualquer tag publicada.
   *
   * Regex, e não `include`: o fonte vem de `git show`, e a constante tem a mesma
   * forma textual em todas as tags desde a 0.9.x (medido: 55 caminhos na v0.22.3 e
   * na v0.23.0, 57 na v0.23.1 e na v0.29.0). Devolve `[]` quando não reconhece a
   * forma — quem chama trata isso como "lista do destino indisponível".
   *
   * @return list<string>
   */
  public static function caminhosDeclaradosEm(string $fonte): array
  {
      if (preg_match('/CAMINHOS_DO_KIT = \[(.*?)\n    \];/s', $fonte, $bloco) !== 1) {
          return [];
      }

      preg_match_all("/^\s+'([^']+)',/m", $bloco[1], $caminhos);

      return $caminhos[1];
  }
  ```

- A regex de linha (`^\s+'…',`) ignora comentários `/* */` e `//` por construção: nenhum deles começa com aspas simples depois da indentação.
- **Logs**: nenhum (ADR-03).

### 2. Unir a lista desta versão com a do destino

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php`
- Propriedade `private bool $listaDoDestinoLida = false;`
- União **pura e pública**, para o teste alcançar sem git (é o que o `04` exige — CT-05 a CT-07):

  ```php
  /**
   * A lista desta versão unida à do destino, sem repetição e reindexada. Com `[]`
   * devolve exatamente `CAMINHOS_DO_KIT` — é o fallback de `caminhosDoKit()`.
   *
   * @param  list<string>  $doDestino
   * @return list<string>
   */
  public static function caminhosUnidos(array $doDestino): array
  {
      return array_values(array_unique([...self::CAMINHOS_DO_KIT, ...$doDestino]));
  }
  ```

- A cola com o git, privada:

  ```php
  /**
   * A lista que filtra o diff: a desta classe UNIDA à da versão destino.
   *
   * A classe que roda é a da instalação — a versão ANTIGA. Caminho que entrou na
   * lista depois dela era invisível na primeira rodada, e na 0.23.0 isso deixou o
   * projeto sem boot entre as rodadas: `IdentidadeDoKit.php` chegou (coberto por
   * `app/Support`) e a view que ele renderiza no boot não (`resources/views/svg`
   * ainda não estava na lista). Ler a lista do destino fecha isso.
   *
   * União, e não substituição: caminho removido do kit precisa continuar no diff
   * para aparecer como "removido do kit", e o fallback para a lista própria
   * (destino ilegível) fica de graça.
   *
   * @return list<string>
   */
  private function caminhosDoKit(string $destino): array
  {
      $doDestino = self::caminhosDeclaradosEm(
          $this->git(['show', "{$destino}:app/Console/Commands/KitUpdate.php"]),
      );

      $this->listaDoDestinoLida = $doDestino !== [];

      if (! $this->listaDoDestinoLida) {
          $this->components->warn(
              "Não foi possível ler a lista de caminhos da versão {$destino}; usando a desta versão. "
              .'Se o próprio kit:update for atualizado, rode o comando de novo (o aviso final traz o comando pronto).'
          );
      }

      return self::caminhosUnidos($doDestino);
  }
  ```

- **Logs**: o aviso acima, no terminal. Sem arquivo.

### 3. `arquivosAlterados()` usa a união

> Skills: `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php:525`
- Trocar `...self::CAMINHOS_DO_KIT` por `...$this->caminhosDoKit($destino)`. Uma linha.
- O comentário da constante (`:62-83`) ganha uma frase: "A lista que filtra o diff é a união desta com a da versão destino — ver `caminhosDoKit()`."

### 4. O aviso da segunda rodada só quando fez falta

> Skills: `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php:852-918` (`encerrar()`)
- Hoje o `note()` tem dois assuntos num só bloco: (i) "o que você viu ainda é o comportamento anterior" e (ii) "RODE O COMANDO DE NOVO … `php artisan kit:update{$from} --tag={$versao} --no-branch` … `--from` obrigatório". Separar:
  - (i) continua sempre que `app/Console/Commands/KitUpdate.php` estiver em `$aplicados`;
  - (ii) só quando `! $this->listaDoDestinoLida` — porque com a lista do destino lida, tudo que a lista nova cobre **já entrou**, e mandar rodar de novo produz "Nada a atualizar".
- **Preservar literalmente** as duas strings que `tests/Kit/KitUpdateTest.php:294` procura no fonte: `php artisan kit:update{$from} --tag={$versao} --no-branch` e `' --from='.str_replace('kit-v', '', $origem)`.
- Atualizar o comentário do bloco (`:868-883`): a "consequência prática" agora tem exceção — só vale quando a lista do destino não pôde ser lida.
- **Logs**: nenhum.

### 5. CHANGELOG e documentação

> Skills: nenhuma

- **`CHANGELOG.md`**: seção `## [Unreleased]` → `### Corrigido`: "`kit:update` lê a lista de caminhos da versão destino …" — citar o caso 0.23.0, dizer que a segunda rodada deixa de ser necessária quando a lista é legível, e que instalações anteriores a esta versão ainda passam pela segunda rodada uma vez (a rodada 1 delas roda a classe antiga).
- **`docs/pt/comecar/atualizando-o-projeto.md`** e **`docs/en/comecar/atualizando-o-projeto.md`**, item "O próprio `kit:update` se atualiza": acrescentar que, a partir desta versão, a lista de caminhos é lida da versão destino e o aviso de segunda rodada só aparece quando isso não foi possível. Mencionar em uma frase o caso conhecido (v0.22.x → ≥ 0.23.0: `View [svg.arte-do-login] not found` entre as rodadas; contorno: rodar a segunda rodada com o comando que o aviso imprime, ou copiar a view). Isso atende RQ-06.
- Site: os testes de documentação (`tests/Kit/SiteDeDocumentacaoTest.php`, `RedeDeDocumentacaoTest.php`) precisam continuar verdes.

### 6. Testes

> Skills: `pest-testing`

- **Path**: `tests/Kit/KitUpdateTest.php` (mesmo arquivo — é a suíte do comando)
- Cenários em `04-casos-de-teste.md`, derivados pela skill `feature-test-design` a partir do `00`.
- Regra da suíte: `.ai/rules/testes.md` — asserção de **ausência** sobre o fonte filtra comentários (`preg_replace('~/\*.*?\*/~s', '', $codigo)`); asserção de presença roda sobre o texto cru.

### 7. Prova em instalação real (manual, registrada no `03`)

> Skills: nenhuma

Reproduz RQ-02 com a classe **nova** rodando — o que nenhum teste da suíte consegue sem um repositório git de verdade.

1. Em `TESTES KIT/v0290-sem-tenancy` (v0.29.0, git limpo): copiar o `KitUpdate.php` desta branch por cima do da instalação (simula "instalação já tem esta correção") e commitar.
2. No clone do kit, criar uma tag local descartável `v99.0.0` a partir desta branch com **um diretório novo** na lista e um arquivo dentro dele (por exemplo `resources/views/kit-prova/x.blade.php`), commitados só nessa tag.
3. Na instalação: `php artisan kit:update --repo=<caminho do clone> --from=0.29.0 --tag=99.0.0 --dry-run`.
4. **Esperado**: `resources/views/kit-prova/x.blade.php` aparece como "novo no kit" — sem segunda rodada. Sem a correção (passo 1 desfeito), o mesmo comando **não** o lista.
5. Contraprova do fallback (RQ-04): apontar `--tag` para uma tag cujo `KitUpdate.php` foi editado para uma forma irreconhecível da constante → aviso "Não foi possível ler a lista…" e diff idêntico ao da lista própria.
6. Apagar a tag local e reverter a instalação (`git checkout -- . && git tag -d …`).

Resultado com data vai para `03-progresso.md` → Notas de Implementação.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Dois métodos novos (um deles de três linhas úteis), uma troca de expressão em `arquivosAlterados()`, uma condição em `encerrar()`. Sem classe nova, sem arquivo de dados, sem service. A regex é o atalho deliberado: marcar com `ponytail:` no comentário do passo 1, com o teto declarado ("a forma da constante muda → fallback, e o CT-xx reprova antes da tag").
>
> **Caveman ativo** na conversa; os arquivos desta wiki, o código e os commits em prosa normal.

## Mapeamentos

Não se aplica.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05`: não há superfície de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` (gate `types:check` — o método público novo precisa de `@return list<string>`)
- [ ] `php artisan test tests/Kit/KitUpdateTest.php --compact`
- [ ] `php artisan test tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php --compact` (docs tocadas)
- [ ] `composer test:kit` — nada mais nas suítes Kit e Tenancy quebrou (o projeto roda `--parallel`; `--tia` exige PCOV, não garantido — divergência declarada no `04`)
- [ ] Prova em instalação real (passo 7) registrada no `03`

## Commits

- `🐛 fix(kit:update): lê a lista de caminhos da versão destino — diretório novo chega na primeira rodada`
- `✅ test(kit:update): a lista do destino — parser, união, fallback e o aviso condicional`
- `📝 docs: kit:update e a lista do destino — CHANGELOG e atualizando-o-projeto (pt/en)`
- `📝 docs(wiki): fix/kit-update-lista-do-destino — requisito, plano, ADRs, casos e progresso`
