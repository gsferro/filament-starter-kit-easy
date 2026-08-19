# Casos de Teste — Cache de views no Docker

> Arquivo: `tests/Kit/CacheDeViewsNoDockerTest.php`. **Sem CT-B** — não há superfície de UI.

## Perfil de risco

A entrega são três linhas de configuração. O risco não é elas quebrarem — é elas
**desaparecerem**, trocadas por algo que parece melhor.

Dois cenários concretos, e os dois deixam a suíte verde:

1. Alguém troca por `php artisan optimize`, que é o comando idiomático. Ele traz
   `config:cache` e `route:cache` junto, e o kit passa a ignorar o `.env` montado em runtime e
   a congelar o estado de tenancy.
2. Alguém "arruma" movendo o `view:cache` para o Dockerfile, onde parece pertencer. Funciona no
   primeiro `docker compose up` e some no segundo.

Nenhum dos dois produz erro. O primeiro quebra em produção; o segundo só devolve a lentidão.

| Dimensão | Risco | Coberto por |
|---|---|---|
| `view:cache` sumir do boot | alto | CT-01 |
| `;` no lugar de `&&` — degradação silenciosa | **alto** | CT-02 |
| Healthcheck sem folga — container nasce `unhealthy` | alto | CT-03 |
| `view:cache` migrar para o build (inócuo) | **alto** | CT-04 |
| `filament:optimize` sair do build | médio | CT-05 |
| `config:cache` / `route:cache` / `optimize` entrarem | **alto** | CT-06 |
| `kit:install` passar a cachear view | médio | CT-07 |

## Casos

| CT | Cenário | Oráculo |
|----|---------|---------|
| CT-01 | `docker-compose.yml` | contém `php artisan view:cache` |
| CT-02 | idem | casa a expressão `view:cache` seguido de `&&` e `php-fpm` |
| CT-03 | idem | contém `start_period: 90s` |
| CT-04 | `Dockerfile.laravel`, sem comentários | **não** contém `view:cache` |
| CT-05 | idem | contém `php artisan filament:optimize`; o compose **não** |
| CT-06 | ambos, sem comentários | **não** contêm `config:cache`, `route:cache`, `artisan optimize` |
| CT-07 | `KitInstall.php` | **não** contém `view:cache` |

**9 casos** (CT-06 é dataset de 3).

### As asserções de ausência ignoram comentários

Os dois arquivos **citam** `config:cache`, `route:cache` e `view:cache` nos comentários, de
propósito — é lá que está escrito por que cada um roda onde roda. Uma asserção sobre o texto
cru puniria exatamente a documentação que torna a decisão utilizável.

O `beforeEach` filtra as linhas iniciadas por `#`, e as asserções de ausência rodam sobre o
resultado. **Citar não é executar** — mesma distinção do `rector.php` em
`QualidadeDeCodigoTest`.

Foi defeito real: a primeira versão reprovou em **3 dos 9** casos por causa dos próprios
comentários.

## Mutantes previstos e o cenário que mata cada um

| # | Mutante | Morto por |
|---|---------|-----------|
| M1 | Remover o `command:` do serviço `app` | CT-01 |
| M2 | Trocar `&&` por `;` | **CT-02** |
| M3 | Remover o `start_period` | CT-03 |
| M4 | Mover o `view:cache` para o Dockerfile | **CT-04** e CT-01 |
| M5 | Trocar tudo por `php artisan optimize` | **CT-06** e CT-01 |
| M6 | Acrescentar `config:cache` ao `command:` | CT-06 |
| M7 | Mover o `filament:optimize` para o boot | CT-05 |
| M8 | Acrescentar `view:cache` ao `kit:install` | CT-07 |

## Lacuna de derivação assumida

**Nenhum caso sobe o container e mede.** Provar que o boot de fato compila as views e que o
php-fpm sobe depois exigiria `docker compose up` dentro da suíte — minutos por execução, e
dependência de daemon Docker na máquina de quem roda o teste.

**Assumido**: a verificação é manual. `docker compose config --quiet` passou, e a medição dos
38,9s foi feita com o mesmo comando fora do container. Se o `command:` estiver sintaticamente
errado, o `docker compose config` acusa; se estiver semanticamente errado, o container não
sobe — e o `&&` garante que isso seja barulhento em vez de silencioso.

## Cobertura do requisito

| RQ | Coberto por |
|----|-------------|
| RQ-01 (verificar `kit:install`) | CT-07 fixa o resultado da verificação |
| RQ-02 (verificar `Dockerfile`) | CT-04, CT-05 |
| RQ-04 (implementar) | CT-01, CT-02, CT-03 |

## Execução

```bash
php artisan test tests/Kit/CacheDeViewsNoDockerTest.php
```

**Resultado em 2026-08-19**: 9 casos, 13 asserções, verdes.
