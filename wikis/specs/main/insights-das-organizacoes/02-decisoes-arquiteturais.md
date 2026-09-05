# Decisões Arquiteturais — Insights das organizações no `/admin`

## ADR-01: O painel é carimbado por hook `creating` no model, não por listener substituto

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

`authentication_log` não tem coluna de painel. Para RQ-01 existir, alguém precisa escrever o
painel corrente no momento em que a linha nasce.

O pacote `rappasoft/laravel-authentication-log` cria as linhas em quatro listeners
(`LoginListener`, `FailedLoginListener`, `LogoutListener`, `OtherDeviceLogoutListener`) e permite
trocar cada um por config (`authentication-log.listeners.login`, e irmãos —
`LaravelAuthenticationLogServiceProvider::configurePackage()`). O caminho "oficial" seria apontar a
config para uma subclasse do kit.

O `LoginListener::handle()` tem ~120 linhas: fingerprint de dispositivo, detecção de restauração de
sessão, atividade suspeita, notificação com rate limit e três webhooks. Nada disso é extensível por
gancho — a criação da linha está no meio do método.

### Decisão

Registrar, no `boot()` do `KitServiceProvider`, um hook de model:

```php
AuthenticationLog::creating(fn (AuthenticationLog $acesso) => /* carimba o painel */);
```

### Alternativas Consideradas

1. **Subclasse de `LoginListener` apontada pela config** — para carimbar, seria preciso
   sobrescrever `handle()` inteiro, ou seja **copiar as 120 linhas** do vendor. Toda correção de
   segurança do pacote (a detecção de restauração de sessão é uma delas, e tem comentário do
   mantenedor explicando o bug que ela corrige) passaria a ser ignorada em silêncio. Descartada.
2. **Segundo listener no evento `Login`, atualizando a última linha criada** — depende da ordem de
   registro dos listeners e de "descobrir" qual linha é a nova. Frágil e sem cobrir `Failed`.
3. **Coluna preenchida por observer dedicado (`app/Observers/`)** — o kit não tem observer próprio
   nenhum hoje, e a lógica é de duas linhas. Classe nova para isso é cerimônia.

### Consequências

- **Positivas**: cobre **todas** as origens de linha de uma vez — login com sucesso, tentativa
  falha, logout — sem conhecer o interior de nenhum listener. Imune a upgrade do pacote enquanto o
  model continuar sendo o mesmo.
- **Negativas**: `DB::table('authentication_log')->insert(...)` **não** dispara o hook. Existe
  exatamente um uso assim no projeto, no arranjo de `tests/Kit/PermissoesDeWidgetsTest.php`, e ali
  o painel nulo é irrelevante. Ficou registrado no PRD para não parecer defeito depois.
- **Riscos**: se o pacote passar a inserir em massa (`insertUsing`), o carimbo para de acontecer
  sem erro. Mitigado por um caso de teste que afirma o carimbo a partir do **evento `Login` real**,
  não da criação manual do model — assim ele fica vermelho se o caminho de escrita mudar.

### Referências

- `vendor/rappasoft/laravel-authentication-log/src/Listeners/LoginListener.php`
- `vendor/rappasoft/laravel-authentication-log/src/LaravelAuthenticationLogServiceProvider.php:36-40`
- `app/Providers/KitServiceProvider.php:294` — precedente de `Export::created(...)`

---

## ADR-02: Acesso por organização é derivado do vínculo, não carimbado no log

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

RQ-02 pede acessos por organização. A simetria com ADR-01 sugeriria uma coluna `tenant_id` no log,
carimbada do mesmo jeito.

Ela não funciona. No painel `/app` a organização é escolhida **depois** da autenticação — a tela
de login não é escopada por tenant, e `Filament::getTenant()` no instante do evento `Login` é nulo.
Carimbar ali gravaria nulo em todo login de `/app`, que é exatamente o painel onde a organização
importa.

### Decisão

Não criar coluna de organização. A métrica sai do join
`authentication_log` → `tenant_user` → `tenants`, pela `authenticatable_id`.

### Alternativas Consideradas

1. **Coluna `tenant_id` carimbada no `Login`** — nula justamente onde importa. Descartada por
   estar factualmente errada, não por custo.
2. **Carimbar na troca de tenant (evento `TenantSet` do Filament)** — registraria *sessão de
   organização*, que é uma métrica diferente e mais rica. É a evolução natural se alguém pedir
   "quanto tempo cada organização usou o sistema", e exige tabela própria, não coluna. Fora de
   escopo.

### Consequências

- **Positivas**: zero schema novo para RQ-02 e RQ-04. A métrica é sempre coerente com o vínculo
  atual — vinculou hoje, o histórico de acessos daquela pessoa passa a contar para a organização.
- **Negativas**: e é o mesmo fato lido ao contrário — **desvincular apaga o passado**. O acesso de
  ontem some da contagem da organização hoje. A métrica é "usuários vinculados que acessaram", não
  "acessos que aconteceram sob esta organização".
- **Riscos**: alguém ler o número como auditoria de acesso. Mitigado pela descrição do widget, que
  diz "pessoas distintas que entraram nos últimos 30 dias" — sujeito explícito.

### Referências

- Pivot `tenant_user` (`tenant_id`, `user_id`, única composta)
- `00-requisito.md` → `## Ambiguidades`, RQ-02

---

## ADR-03: Widget de Resource fica fora do `discoverWidgets()` e herda a barreira do Resource

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

Duas forças opostas.

De um lado, `.ai/rules/filament.md` é dura: "Page, Widget e Action novos nascem com a permissão
consultada", porque `vendor/filament/widgets/src/Widget.php:34-37` é `canView(): bool { return true; }`
e o kit já pagou por isso — 23 widgets com permission gerada e nenhuma consultada.

Do outro, o mecanismo que gera essa permission é o discovery: `ShieldPermissionsSeeder` roda
`shield:generate --all`, cuja descoberta de widgets é `Filament::getWidgets()`
(`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:36-41`) — ou seja, só o
que está **registrado no painel**. E o `AdminPanelProvider` registra por
`discoverWidgets(in: app_path('Filament/Admin/Widgets'), ...)`, que varre o diretório
**recursivamente** (`HasComponents::discoverComponents()` usa `$filesystem->allFiles()`).

Consequência medida: widget colocado em `app/Filament/Admin/Widgets/` — em qualquer subpasta —
entra em `$panel->widgets`, e `Dashboard::getWidgets()` devolve `Filament::getWidgets()` inteiro.
**Ele apareceria no dashboard**, que não é o que o requisito pede.

### Decisão

Os seis widgets ficam em `app/Filament/Admin/Resources/Tenants/Widgets/`, fora do diretório
varrido. Não usam `ExigePermissaoDoWidget`. Cada um declara:

```php
public static function canView(): bool
{
    return TenantResource::canAccess();
}
```

mais, quando a fonte for opcional, o `Schema::hasTable()`/`hasColumn()` no mesmo `&&`.

### Alternativas Consideradas

1. **`app/Filament/Admin/Widgets/` + `ExigePermissaoDoWidget`** — ganha a permission de graça e
   entra no sweep de `PermissoesDeWidgetsTest`, mas **põe os seis no dashboard**. Só sairiam de lá
   com um `Dashboard` customizado filtrando a lista, que é código novo para desfazer um efeito
   colateral que a escolha de pasta já evita.
2. **Diretório separado + `config('filament-shield.custom_permissions')`** — daria `View:{Widget}`
   de verdade. Custa seis entradas de `custom_permissions`, que **não conhecem painel**: apareceriam
   na matriz dos três painéis e exigiriam recorte manual em
   `PapeisSeeder::paineisDasPermissoesCustomizadas()`, mais seis linhas na matriz de papéis. Seis
   permissões novas para uma barreira que já existe uma camada acima.

### Consequências

- **Positivas**: barreira real e não redundante. Ninguém alcança um destes widgets sem alcançar
  `/admin/organizacoes`, e essa tela já exige `ViewAny:Tenant` **e**
  `config('kit.tenancy.enabled')`. `Page::getWidgetsSchemaComponents()` filtra por `canView()`,
  então o widget negado some sem buraco no grid. Zero permission nova, zero mexida em seeder.
- **Negativas**: estes widgets **não** aparecem em `PermissoesDeWidgetsTest`, que enumera
  `$panel->getWidgets()`. A regra do kit ("widget novo nasce com permissão consultada") continua
  valendo para widget de dashboard; esta feature abre a categoria "widget de Resource", com outra
  barreira. Isso precisa de enforço próprio, senão o próximo widget de Resource nasce aberto.
- **Riscos**: `TenantResource::canAccess()` cair para `true` numa refatoração levaria os seis
  junto, em silêncio. Mitigado por um caso de teste que afirma, para **cada** widget da pasta,
  que `canView()` é falso para usuário sem permissão nenhuma — o mesmo oráculo comportamental de
  CT-32, aplicado à pasta nova e derivado dela por varredura, não escrito à mão.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:36-41`
- `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:386-401` e `:515`
- `vendor/filament/filament/src/Pages/Dashboard.php:47-50`
- `vendor/filament/filament/src/Pages/Page.php:448-452`
- `tests/Kit/PermissoesDeWidgetsTest.php:234` — o helper `widgetsDePainelDoKit()` e o motivo dele
- `.ai/rules/filament.md` — "Page, Widget e Action novos nascem com a permissão consultada"

---

## ADR-04: Login sem painel carimbado vira fatia visível, não linha omitida

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

O carimbo do painel só existe do deploy em diante. Todo login anterior fica com `painel` nulo — e
numa instalação já em uso isso pode ser a maioria absoluta das linhas.

Um `whereNotNull('painel')` no widget faria o gráfico ficar bonito e **errado**: a soma das fatias
não bateria com o total de acessos, sem nada na tela dizendo por quê.

### Decisão

O widget agrupa os nulos numa fatia própria, rotulada "antes do registro por painel", em cinza,
com descrição informando desde quando o carimbo existe.

### Alternativas Consideradas

1. **Filtrar os nulos** — número menor e mentiroso. Descartada.
2. **Backfill inferindo o painel pelo papel do usuário** — foi oferecido ao solicitante como opção
   e recusado em favor do carimbo real. Inferir por papel conta duas vezes quem tem papel em dois
   painéis, e erra sem avisar.
3. **Janela do widget começando na data da migration** — esconderia o problema em vez de mostrá-lo,
   e quebraria a comparação com os outros widgets, todos em 30 dias.

### Consequências

- **Positivas**: o total do widget sempre fecha com o total de acessos da janela. A fatia cinza
  encolhe sozinha conforme o tempo passa — é auto-explicativa e temporária por construção.
- **Negativas**: nos primeiros 30 dias depois do deploy o gráfico é dominado pela fatia cinza.
- **Riscos**: nenhum material.

---

## ADR-05: Janela de 30 dias fixa no código, sem config e sem cache

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

Toda métrica desta feature precisa de uma janela. As opções eram: constante no código, chave em
`config/kit.php`, ou campo editável nas Configurações da aplicação.

E as consultas fazem join de três tabelas com `COUNT(DISTINCT)`, o que levanta a pergunta do cache
antes de existir qualquer medição.

### Decisão

30 dias como constante privada em cada widget. Sem chave de config, sem cache.

### Alternativas Consideradas

1. **Chave em `config/kit.php`** — configuração que ninguém pediu, para um valor que ninguém
   mostrou querer mudar. É exatamente o que a escada do Ponytail manda não construir.
2. **Campo nas Configurações da aplicação** — pior: a chave é lida por request, então tecnicamente
   caberia (ver `.ai/rules/settings.md`), mas acrescenta propriedade, linha no
   `mapaDeConfiguracao()` e migration de settings — os três lugares — para uma preferência
   hipotética.
3. **Cache das agregações** — sem medição, cache é chute com invalidação a manter. O kit já tem
   `model-caching-painel-app` como precedente de cache aplicado **depois** de o problema aparecer.

### Consequências

- **Positivas**: nenhuma superfície de configuração nova, nenhuma chave de cache a invalidar.
  A janela é a mesma nos seis widgets, então os números são comparáveis entre si por construção.
- **Negativas**: instalação com log muito grande pode sentir o carregamento da listagem. O
  `->limit(10)` no breakdown e o índice em `painel` são o que existe hoje contra isso.
- **Riscos**: se o custo aparecer, o caminho é medir com `--profile` e então decidir entre índice
  composto, cache ou materialização — nesta ordem. Registrado aqui para a decisão não ser
  refeita do zero.

### Referências

- `.ai/rules/settings.md` — "Chave lida no boot não pode virar Settings; chave lida por request pode"
- `wikis/specs/main/model-caching-painel-app/` — precedente de cache introduzido sob medição
