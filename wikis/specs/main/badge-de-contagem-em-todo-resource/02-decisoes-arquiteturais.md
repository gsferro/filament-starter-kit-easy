# Decisões Arquiteturais — Badge de contagem em todo Resource do kit

## ADR-01: O zero passa a aparecer, em cinza — e isto reverte uma decisão anterior do kit

**Status**: Aceita
**Data**: 2026-09-04
**Reverte**: a regra "zero não vira badge", escrita no docblock de `BadgeContagemNavegacao`

### Contexto

O trait dizia, textualmente: *"Zero não vira badge: um `0` cinza em todo item só polui o menu."*
O argumento é legítimo e foi escrito por alguém olhando o menu.

Mas ele produz um efeito que ninguém previu, e é ele que gerou este requisito: **badge ausente não
distingue "está vazio" de "o badge quebrou"**. O solicitante olhou "Convites" sem badge e concluiu
que faltava implementar — quando a tabela tinha 0 registros e o código estava correto. A regra
custou uma wiki inteira para ser diagnosticada.

Há uma assimetria de custo aqui. Um `0` cinza custa um pouco de ruído visual, todo dia. Um badge
ausente custa uma investigação, uma vez, e a conclusão errada de que a feature não existe.

### Decisão

O badge é sempre renderizado. `getNavigationBadgeColor()` devolve `'gray'` quando a contagem é
zero e `null` acima — e `null` é o default do Filament (`HasNavigation:158`), ou seja, exatamente a
cor que os oito badges do kit já exibem hoje.

### Alternativas Consideradas

1. **Manter "zero não vira badge"** — foi oferecida ao usuário e recusada. Preserva o menu limpo e
   preserva o defeito de diagnóstico que originou o pedido.
2. **Exibir o zero na mesma cor dos demais** — descartada pelo usuário na mesma pergunta: sem
   distinção visual, o menu ganha uma fileira de números iguais e o olho perde o que é sinal.
3. **Exibir traço (`—`) em vez de `0`** — não considerada com o usuário, e seria pior: `—` é mais
   um símbolo a decodificar, e o odômetro anima **número**, não texto.

### Consequências

- **Positivas**: o menu passa a ter uma leitura uniforme — todo item de Resource do app tem
  número, sempre. Ausência de badge volta a significar defeito, que é o que se quer que ela
  signifique.
- **Negativas**: a mudança é no trait, então **os oito resources que já o usam mudam junto**, nos
  três painéis. Uma instalação recém-criada vai mostrar `0` em quase tudo — e é exatamente aí que
  o menu fica mais poluído.
- **Riscos**: se a poluição incomodar na prática, a reversão precisa saber que está desfazendo uma
  decisão negociada, não corrigindo um descuido. É por isso que esta ADR existe e cita a pergunta
  feita ao usuário.

### Referências

- `app/Filament/Concerns/BadgeContagemNavegacao.php` — o docblock que esta ADR reverte
- `vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php:158`
- `00-requisito.md` → `## Ambiguidades` → RQ-01

---

## ADR-02: Colisão de trait em `RoleResource` se resolve com `insteadof`, e são três

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

`RoleResource` é a tela de papéis do Shield, publicada no projeto. Ela usa
`BezhanSalleh\PluginEssentials\Concerns\Resource\HasNavigation` (linha 97), que declara os **três**
métodos de badge, cada um delegando ao plugin:

```php
public static function getNavigationBadge(): ?string        // :41
public static function getNavigationBadgeColor(): …          // :46
public static function getNavigationBadgeTooltip(): ?string  // :51
```

`BadgeContagemNavegacao` declara os mesmos três (o `Color` a partir de ADR-01). Em PHP, **dois
traits declarando o mesmo método numa classe que não o declara é erro fatal** — e fatal no boot,
não teste vermelho.

Medido, aplicando o trait sem resolução:

```
PHP Fatal error:  Trait method BezhanSalleh\PluginEssentials\Concerns\Resource\HasNavigation::getNavigationBadge
has not been applied as App\Filament\Admin\Resources\Roles\RoleResource::getNavigationBadge,
because of collision with App\Filament\Concerns\BadgeContagemNavegacao::getNavigationBadge
```

`php artisan about` morre. A aplicação inteira morre.

### Decisão

Um bloco `use` único com três `insteadof`, dando a vitória ao trait do kit:

```php
use BadgeContagemNavegacao, Essentials\HasNavigation {
    BadgeContagemNavegacao::getNavigationBadge insteadof Essentials\HasNavigation;
    BadgeContagemNavegacao::getNavigationBadgeColor insteadof Essentials\HasNavigation;
    BadgeContagemNavegacao::getNavigationBadgeTooltip insteadof Essentials\HasNavigation;
}
```

**Executado no step 5**, não apenas escrito: `RoleResource::getNavigationBadge()` devolveu o
odômetro com `5`, e `getNavigationBadgeColor()` devolveu a cor do trait.

### Alternativas Consideradas

1. **Declarar os três métodos direto na classe `RoleResource`** — funciona (método de classe vence
   trait, sem fatal) e foi descartada: duplica o corpo do trait numa classe de 700 linhas, e o dia
   em que o trait mudar, `RoleResource` fica para trás em silêncio. É o mesmo defeito que o
   docblock de `ExigePermissaoDoWidget` descreve para `HasWidgetShield`.
2. **Configurar o badge pelo plugin do Shield** (`FilamentShieldPlugin::navigationBadge()`) —
   resolveria só `RoleResource`, e por um caminho diferente do resto do kit. RQ-03 pede **um**
   padrão; dois mecanismos para a mesma coisa é o que a ADR-01 da wiki `graficos-com-apexcharts`
   chama de contraexemplo vivo.
3. **Remover `Essentials\HasNavigation` de `RoleResource`** — descartada: ela entrega também
   `getNavigationGroup`, `getNavigationSort`, `shouldRegisterNavigation` e o parent item, todos
   delegando ao plugin. Tirá-la para ganhar um badge quebra a navegação da tela.

### Consequências

- **Positivas**: `RoleResource` passa a seguir o mesmo trait dos outros nove, e o comportamento do
  badge muda num lugar só.
- **Negativas**: a sintaxe de resolução é obscura o bastante para alguém "limpar" numa refatoração
  e derrubar o boot. Mitigado por comentário no bloco e por esta ADR.
- **Riscos**: **o passo 4 obriga o trait em todo Resource do app**, então esta armadilha vai
  reaparecer no primeiro resource futuro que usar um trait de vendor com métodos de navegação. É
  por isso que a mensagem de erro exata está transcrita aqui — para o próximo caso ser cinco
  minutos e não uma tarde.

### Referências

- `vendor/bezhansalleh/filament-plugin-essentials/src/Concerns/Resource/HasNavigation.php:41,46,51`
- `app/Filament/Admin/Resources/Roles/RoleResource.php:97`
- `app/Filament/Concerns/ExigePermissaoDoWidget.php` — o precedente do kit para conflito de trait

---

## ADR-03: A contagem é memoizada com `once()`, não com propriedade estática

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

A partir de ADR-01, número e cor precisam do **mesmo** `count()` no mesmo request. Sem memoização,
cada item de Resource do app custa duas consultas por carregamento de tela — dez itens, vinte
consultas, em todo request de todo painel.

### Decisão

`once(fn (): int => static::getEloquentQuery()->count())` num método privado do trait.

**Medido no step 5**: dentro de método estático, `once()` memoiza corretamente — duas chamadas,
uma execução do callback.

### Alternativas Consideradas

1. **Propriedade estática no trait** (`protected static ?int $contagem = null`) — cada classe que
   usa o trait ganha a própria cópia, então funcionaria dentro do request. Descartada por
   **Octane**: propriedade estática sobrevive entre requests no mesmo worker, e o badge congelaria
   no valor do primeiro request até o worker reciclar. Bug de "o número não atualiza" que só
   aparece em produção.
2. **Cache (`Cache::remember`)** — invalidação a manter, para uma consulta `count()` com índice.
   Cache antes de medir é chute com manutenção.
3. **Não memoizar** — duas consultas por item. Descartada por ser gratuitamente pior.

### Consequências

- **Positivas**: uma consulta por item de menu por request, e nada a invalidar.
- **Negativas**: `once()` é resolvido por contexto de chamada; o comportamento em subclasses de
  resource merece atenção se alguma vier a existir.
- **Riscos**: baixo. O pior caso é uma consulta a mais.

### Referências

- `01-plano-acao.md` → passo 1
- Medição registrada em `03-progresso.md` → *Revisão profunda*

---

## ADR-04: O padrão vira teste de arquitetura, e a rule é curta e aponta para ele

**Status**: Proposta — a Project Rule depende de aprovação do usuário no step 9
**Data**: 2026-09-04

### Contexto

RQ-05 pede "criar regra". Duas leituras convivem: regra como **texto** (`.ai/rules/`, lido por
agentes) e regra como **enforço** (teste que fica vermelho).

O histórico do próprio kit responde qual sozinha não basta. `RoleResource` e
`ComposerReleasePackageResource` ficaram sem badge não por falta de convenção, mas porque **nada
reprovava** a ausência. Rule em prosa teria o mesmo destino: ninguém lê a rule ao criar o décimo
resource copiando o nono.

### Decisão

**As duas, com papéis distintos.**

- O **teste** (`tests/Kit/BadgeDeNavegacaoTest.php`) enumera os resources registrados nos três
  painéis, filtra `App\Filament\`, e reprova o que não usa o trait. Lista derivada, nunca escrita à
  mão — é o que faz o caso pegar a classe nova.
- A **rule** é curta, escopada em `app/Filament/**/Resources/**`, aponta para o teste e carrega o
  que o teste **não** consegue dizer: a armadilha de ADR-02, que se manifesta como fatal no boot.

É a escada do Ponytail aplicada a rule: prosa só onde a máquina não alcança.

### Alternativas Consideradas

1. **Só a rule** — é o estado atual do kit para várias convenções, e foi o que permitiu os dois
   resources ficarem de fora.
2. **Só o teste** — reprova, mas com uma mensagem que diz *o que* falta e não *o que fazer quando
   colidir*. Quem cair na colisão de ADR-02 vê um fatal, não a mensagem do teste.
3. **`pest --arch`** — descartada: `arch()` inspeciona namespace, herança e dependência, não
   "resource registrado em painel X". A informação de que precisamos vem de
   `Filament::getPanel($id)->getResources()`, em runtime.

### Consequências

- **Positivas**: resource novo sem badge fica vermelho com o nome da classe na mensagem. O agente
  que ler a rule já sabe o que fazer se colidir.
- **Negativas**: mais uma rule no `.ai/rules/`, e toda rule é imposto de contexto permanente em
  todo arquivo que casa com o glob. É por isso que ela é curta e o teto da skill é 3 candidatos.
- **Riscos**: o teste depende do filtro por namespace. Se alguém mover resource do app para fora de
  `App\Filament\`, ele sai do enforço sem nada acusar. Mitigação: o mesmo filtro é usado por
  `PermissoesDeWidgetsTest`, então o padrão é consistente e conhecido.

### Referências

- `tests/Kit/PermissoesDeWidgetsTest.php:234` — o molde
- `01-plano-acao.md` → passos 4 e 5
