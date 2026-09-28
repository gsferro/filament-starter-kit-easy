> Referência da feature-wiki 4.0.0. Lida em: step 4 (onde a wiki pode citar código), steps 3 e 5 (ao escrever uma citação) e step 10 (ao reverificar todas). Fonte única de: a tabela de path e número por arquivo, as duas classes de erro de citação, exemplos do formato e o que o `scripts/citacoes.sh` confere (o script é a fonte da lógica).

# Citações de código — `arquivo:símbolo:linha`

O formato obrigatório, a regra do path curto, o critério de que citação sem símbolo não passa e o
registro na Verificação Final ficam no corpo do `SKILL.md`, seção *Citações de código*. A regra de
onde a wiki pode citar código fica no corpo, step 4, *Path e número por arquivo*; a tabela, aqui.

## Path e número por arquivo

| Arquivo | Path de código / `arquivo:linha` / contagem derivada | Por quê |
|---|---|---|
| `00` | **proibido** — só o que o solicitante escreveu, `RQ`, `P-nn` e perguntas | é o oráculo; não tem reconciliação |
| `02` (ADR) | **proibido** na ADR: cita módulo ou classe por nome, nunca `arquivo:linha` nem contagem. Exceção: a `## Superfície Livewire`, re-varrida antes do step 9 e com as citações conferidas no step 10 | decisão envelhece devagar; path envelhece no próprio ciclo (3.5.1) |
| `01`, `03`, `04`, `05` | permitido | coberto pela reconciliação do step 10 (`citacoes.sh`, `ids-ct.sh`) |
| `07-tickets/` | permitido pela reconciliação, mas o ticket não leva path: aponta passos do `01` por número e link (regra da `feature-tickets`) | conferido no step 10 pelo `indice.sh --check` |

## Por que o símbolo — as duas classes de erro

O step 3 desta skill (e a rule `specs.md` do projeto-cobaia) pedia `arquivo:linha` para toda
afirmação sobre vendor ou padrão interno. O formato só com linha falha de dois jeitos distintos,
medidos na mesma feature:

| Classe | Exemplo real | O que pega |
|---|---|---|
| **Errada ao nascer** | `Login.php:165` para `return app(LoginResponse::class)`, que está na 169 — e o `composer.lock` não mudou em nenhum commit da feature | conferir **ao escrever** (step 5) |
| **Deslocada depois** | citações de arquivos da própria app, 3 a 10 linhas fora após Pint e imports novos | conferir **no step 10** |

"Reverificar depois" não pega a primeira classe; "conferir ao escrever" não pega a segunda. São
dois momentos, e o mesmo comando serve aos dois.

## Exemplos do formato

```text
vendor/filament/filament/src/Auth/Pages/Login.php:isUserAllowedToAccessPanel():172
app/Support/DestinoAposLogin.php:urlPara():41-58
config/logging.php:'autenticacao':132
```

## Conferência mecânica

```bash
bash {skills}/feature-wiki/scripts/citacoes.sh wikis/specs/{branch}/{feature}
```

Na raiz do projeto — os paths citados são relativos a ela. Silêncio e exit 0 = todas conferem; cada
linha de saída é `arquivo:linha: citação — problema`, e o problema é um destes: símbolo fora da linha
citada, arquivo que não existe, linha além do fim do arquivo, citação sem símbolo, símbolo fora do
formato (`Pedido::aprovar()`, `Pedido@aprovar`, `linha:coluna`), path curto sem o path completo antes
no mesmo documento — nas linhas de cima ou à esquerda na mesma linha. O mesmo comando serve aos dois momentos da tabela acima: ao
escrever (steps 3 e 5) e ao reverificar (step 10).

O que o script não confere, de propósito:

- o `00-requisito.md`: não leva path de código, e o Texto Original é imutável — citação que apareça
  nele é do solicitante e não se corrige;
- citação sem símbolo ou com símbolo fora do formato dentro de bloco de código cercado ou de
  comentário HTML: ali costuma ser saída colada de ferramenta (`FAILED tests/…Test.php:45`), não citação.

A lista de extensões e o formato aceito do símbolo (método com ou sem `()`, constante,
`$propriedade`, chave entre aspas) estão no cabeçalho do script.
