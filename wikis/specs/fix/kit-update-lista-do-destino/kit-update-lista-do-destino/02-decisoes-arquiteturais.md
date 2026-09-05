# Decisões Arquiteturais — `kit:update` lê a lista de caminhos da versão destino

## ADR-01: Ler a lista do destino do próprio fonte via `git show` + regex; não tolerar a view ausente

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

A fonte do requisito oferece duas saídas: (a) o comando lê a lista de caminhos da versão destino antes de aplicar; (b) o provider tolera a ausência da view `svg.arte-do-login`. E, escolhida (a), há duas formas de ler a lista: extrair a constante do fonte `KitUpdate.php` da tag, ou mover a lista para um arquivo de dados e ler esse arquivo.

Fatos que pesam (medidos, `00-requisito.md` → "O mecanismo"):

- a constante `CAMINHOS_DO_KIT` tem a **mesma forma textual** em todas as tags relevantes — a regex extrai 55 caminhos na v0.22.3 e na v0.23.0, 57 na v0.23.1 e na v0.29.0;
- o `git fetch` do comando já traz os objetos das tags, e `aplicar()` já lê arquivos do destino com `git checkout destino -- caminho` (`KitUpdate.php:419,765`);
- o defeito já aconteceu **três vezes** com três diretórios diferentes (0.9.1-0.9.3, 0.9.8, 0.23.0). Não é um problema da view da arte.

### Decisão

1. Implementar **(a)**: `arquivosAlterados()` filtra o diff pela **união** da constante desta classe com a lista extraída de `git show {destino}:app/Console/Commands/KitUpdate.php`.
2. Extrair por expressão regular sobre o fonte (`caminhosDeclaradosEm()`), sem `include`, sem `eval`, sem arquivo temporário.
3. **Não** alterar `IdentidadeDoKit` nem os providers.

### Alternativas Consideradas

1. **(b) `IdentidadeDoKit::artePadrao()` tolerar a view ausente** — descartada como correção. Trata a instância e não a classe: o próximo diretório novo (um `resources/views/x` que outra classe renderize no boot, um `lang/` novo, um `config/` novo) quebra outra coisa entre as rodadas. Além disso o próprio arquivo documenta que arte `null` "é uma regressão visível, não um default" (`IdentidadeDoKit.php:65-66`) — tolerar a view ausente troca um erro barulhento por uma tela de login sem imagem, em silêncio. Fica registrada como **RQ-03 fora desta entrega**; volta se o solicitante negar a premissa.
2. **Mover a lista para um arquivo de dados** (`config/kit-caminhos.php`, JSON, texto) e ler esse arquivo do destino — descartada. Só funcionaria a partir da tag que criasse o arquivo; para **todas** as tags já publicadas ainda seria preciso o parser do fonte. Mais código, mesmo resultado, e uma segunda fonte da verdade para a varredura de `KitUpdateTest.php:131` acompanhar.
3. **Trocar a lista curada por varredura da árvore da tag** (`git ls-tree -r destino`) — descartada. A lista **exclui de propósito** o que mora nos mesmos diretórios e é do usuário (`app/Models/*` do negócio, resources do usuário); a varredura entregaria o kit inteiro por cima do projeto. Ver `KitUpdateTest.php:131-141`.
4. **Rodar a segunda rodada automaticamente** (o comando se re-executa via `Artisan::call` depois de aplicar a si mesmo) — descartada. O PHP já carregou a classe antiga; `Artisan::call` reutiliza a mesma instância de classe, e um `passthru('php artisan …')` cria um processo que herda um projeto ainda sem commit no meio da revisão interativa. Complexidade alta para um problema que a união resolve antes.

### Consequências

- **Positivas**: diretório novo chega na primeira rodada; a segunda rodada vira exceção (destino ilegível); zero arquivos novos; nenhuma tag antiga fica de fora.
- **Negativas**: regex sobre código PHP. Mitigada por um caso de teste que aplica o parser ao **fonte atual** e exige igualdade com a constante — a forma textual passa a ser contrato, e quem a mudar quebra o teste antes da tag.
- **Riscos**: a correção só age em instalações que **já a têm**. Quem está em v0.22.x continua rodando a classe v0.22.x na primeira rodada; para essas, o aviso com o comando pronto (v0.30.0) e o contorno do CHANGELOG 0.23.1 continuam sendo o caminho (RQ-06).

### Referências

- `app/Console/Commands/KitUpdate.php:84,519-525,765,419`
- `app/Support/IdentidadeDoKit.php:60-85`
- `tests/Kit/KitUpdateTest.php:131-186`
- `wikis/specs/fix/spotlight-sem-estilo/spotlight-sem-estilo/03-progresso.md` (Notas de Implementação)

---

## ADR-02: União das duas listas, nunca substituição

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

Lida a lista do destino, a escolha natural seria **usá-la no lugar** da constante local — afinal é a lista "certa" para o destino. Mas o diff tem dois sentidos.

### Decisão

`caminhosDoKit()` devolve `array_unique([...self::CAMINHOS_DO_KIT, ...$doDestino])`.

### Alternativas Consideradas

1. **Só a lista do destino** — descartada. Caminho que a versão nova **removeu** da lista sairia do diff e o usuário não veria o rótulo "removido do kit" (`arquivosAlterados()`, `:548`), que é exatamente o aviso de que ele tem um arquivo órfão. E o fallback (destino ilegível) exigiria um `if` a mais; com a união ele é automático — `[]` unido à constante é a constante.
2. **Interseção** — descartada; é o oposto do que se quer.

### Consequências

- **Positivas**: nunca oferece **menos** do que hoje; fallback sem código extra; rótulo "removido do kit" preservado.
- **Negativas**: nenhuma mensurável. `git diff -- caminho` com caminho ausente nas duas tags não é erro e não produz linha.

---

## ADR-03: Sem channel de log — o terminal é o registro

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

A skill `feature-wiki` pede channel de log por feature e log em toda etapa de execução. Nenhum dos cinco comandos `kit:*` escreve em arquivo de log; todos falam pelo terminal (`$this->components`, Laravel Prompts), e o `kit:update` em particular é interativo — a pessoa está olhando enquanto ele roda e decide arquivo a arquivo.

### Decisão

Nenhum channel. O único evento novo — "não foi possível ler a lista da versão destino; usando a desta versão" — vai para `$this->components->warn()` no momento em que o operador precisa dele.

### Alternativas Consideradas

1. **Channel `kit-update` em `config/logging.php`** — descartada. Seria escrito uma vez por atualização e lido por ninguém; e o `config/logging.php` **não** está em `CAMINHOS_DO_KIT`, então o channel nem chegaria a quem atualiza — o `Log::channel('kit-update')` cairia no `emergency` do Laravel numa instalação atualizada.

### Consequências

- **Positivas**: coerente com os irmãos; sem config nova; sem o caso "channel inexistente na instalação".
- **Negativas**: sem trilha em arquivo. Aceito: `git diff --staged` depois do comando **é** a trilha do que ele fez.

---

## ADR-04: O aviso da segunda rodada fica condicional, com as strings preservadas

**Status**: Aceita
**Data**: 2026-09-05

### Contexto

`encerrar()` imprime um `note()` com dois assuntos quando o próprio `KitUpdate.php` foi aplicado: (i) "o que você viu ainda é o comportamento anterior" e (ii) "RODE O COMANDO DE NOVO … `--from` obrigatório". Com a lista do destino lida, (ii) manda para uma rodada que responde "Nada a atualizar" — a surpresa que o requisito quer evitar (RQ-05). E `tests/Kit/KitUpdateTest.php:294` lê o **fonte** e exige as duas strings do comando pronto.

### Decisão

Separar os dois assuntos. (i) sempre; (ii) apenas quando `! $this->listaDoDestinoLida`. As strings `php artisan kit:update{$from} --tag={$versao} --no-branch` e `' --from='.str_replace('kit-v', '', $origem)` continuam **literalmente** no fonte.

### Alternativas Consideradas

1. **Remover (ii) de vez** — descartada: o fallback (RQ-04) precisa dele, e instalações que **hoje** têm a classe antiga verão o aviso da classe delas, não deste fonte — este texto só vale a partir da versão que o carrega.
2. **Manter (ii) sempre e acrescentar "talvez não seja necessário"** — descartada: instrução condicional para o operador decidir é pior do que o comando decidir com a informação que já tem.

### Consequências

- **Positivas**: quem atualiza a partir desta versão não roda uma segunda rodada vazia.
- **Negativas**: dois `note()` em vez de um quando o fallback acontece. Aceitável.
