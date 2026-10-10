# Relatório de QA — unificar-logo-da-organizacao

**Data**: 2026-10-09.
**Escopo**: feature `683067a` e correções não commitadas sobre `50706ad`.
**Independência**: mesma sessão que corrigiu código e wiki, com revisão independente do diff de logos e launcher por subagente sem conversa ou PRD. Limpeza do arnês revisada manualmente. Gate em modo degradado.
**Perfil**: completo pela interação Livewire do formulário. Esta revalidação cobre os defeitos encontrados; dimensões não executadas estão declaradas abaixo.

## Veredito — Ciclo 2

**APROVADO COM DÉBITO** — correções verificadas nos quatro cenários locais; falhas iniciais registradas e arquivos afetados reexecutados verdes. Este gate não comprova publicação ou nova resolução de dependências. Nenhuma conclusão histórica de 100% de mutação sustenta este gate.

## Achados e resolução

| Achado | Evidência anterior | Correção e prova |
|---|---|---|
| Logo único desaparecia no escuro | `fi-logo-light` incondicional, sem imagem escura | Classe condicionada à presença do par; CT-07 e CT-B02. Browser: cinco testes, 41 assertions; inclui par separado ancestral |
| Escura antiga reaparecia sem clara própria | CT-11/CT-12 falharam com arquivo escuro antigo válido | `logosPara()` retorna par completo da instalação quando a organização unificada não resolve clara própria |
| Mutação reportava falso 100% | Filtro composto sem mutação saía com código 255 | Launcher preserva comando protegido pelo Symfony; quatro regressões/12 assertions, inclusive caminho do launcher com `%`, `!`, `^`, `&` e saída 23 |
| Backfill não era exercitado | CT-01 criava registros depois das migrations | CT-10 volta schema anterior, cria registros com/sem escura e verifica migration e preservação dos arquivos |
| Cache antigo de settings quebrava novo campo | Regressão lançou erro de propriedade não inicializada | Migration nova invalida cache configurado; valores do banco e cache do negócio preservados |
| Variável externa quebrava CT-02 do seletor | Ambiente externo `true` produzia falha | `env` e `server` fixados no phpunit.xml; CT-02 passou com variável externa `true` |
| Suítes concorrentes apagavam fixtures alheias | Falhas de GIF, `.env` e assets nos cenários isolados; cleanup por glob global confirmado | Diretórios e limpeza incluem PID; três reexecuções de 72 casos passaram (70 verdes, dois pulados, 397 assertions cada). Duas execuções simultâneas no mesmo diretório temporário passaram: 43 testes/236 assertions cada |
| Launcher corrigido não chegaria pelo update | Ausente de `KitUpdate::CAMINHOS_DO_KIT` | Launcher incluído; dataset de entrega da fundação passou |

Revisão independente encontrou caminho especial no próprio launcher e fixture de browser incapaz de distinguir organização de instalação. Ambos corrigidos; revisor não encontrou outro defeito concreto no diff dos logos e launcher. A correção posterior da limpeza do arnês foi revisada manualmente.

## Rastreabilidade

| Origem | Passo | Casos e prova |
|---|---|---|
| RQ-01 | análise anterior, fora da mudança | suporte preexistente confirmado no model/form |
| RQ-02, P-01 | 1–4 | CT-01..CT-06, CT-08, CT-10; persistência e backfill |
| RQ-03, P-03 | 4, 6 | CT-04..CT-07, CT-11, CT-12, CT-B02 |
| P-02 | 3 | CT-02; toggle condicionado à marca global |
| P-04 | 1 | CT-10; registros anteriores preservam separação |
| P-05 | contrato ancestral | CT-41 de LogoDarkModeTest; sem cenário duplicado |
| RQ-04 | 5 e processo de release | CT-09; tag local v0.47.0 existente. Publicação remota não verificada; estas correções ainda não publicadas |

`rastreabilidade.sh`, `ids-ct.sh`, `citacoes.sh`, `checkbox-sem-evidencia.sh` e `conformidade-rules.sh` passaram para esta wiki. Base de conformidade: `683067a^`. O script de conformidade não lê alterações não commitadas; estas foram confrontadas com as rules pelo diff de trabalho.

## Dimensões e limites

| Dimensão | Verificação |
|---|---|
| A — requisito | matrizes reconciliadas, incluindo backfill, fallback e duas superfícies |
| B — fronteiras | clara ausente/órfã com escura antiga válida; cache serializado anterior; ambiente externo; argumentos Windows |
| C — permissão | nenhuma policy/gate alterada; regressão dos testes de tenancy relacionada passou |
| D — log | sem channel novo; aviso existente preservado. Inspeção de log real e exposição de PII não repetidas |
| E — desempenho | clara resolvida uma vez; nenhum acesso adicional ao banco na resolução. Seletor pode consultar três vezes por layout; latência não medida |
| F — erro | configurações preservadas após invalidar cache; origem da imagem explicitamente verificada |
| G — tema | claro/escuro efetivos, imagem visível e carregada, sem erro JavaScript no browser |
| H — acessibilidade | axe não executado nesta rodada |
| I — segurança | revisão independente do diff; autorização e validação de upload existentes preservadas |
| J — regressão | 159 testes relacionados/639 assertions. Suíte ampla executou 4.252 casos: 4.246 passaram, três falharam, três pulados. Falhas de badge, citações e comparação de arquivo do HEAD com working tree corrigidas e casos específicos reexecutados verdes |
| K — adequação | regressões demonstraram falha antes da correção. Mutação real abaixo; não exigir 100% por relaxamento de oráculo |
| L — documentos | plano, matrizes, docs PT/EN e referências reconciliados; relatórios antigos identificados como históricos |

## Mutação real

Comando:

```text
pestw.cmd tests/Kit/UnificaLogoDoTenantTest.php tests/Kit/LogoDarkModeTest.php tests/Tenancy/CabecalhoDoPainelTenancyTest.php --mutate --path=app/Support/IdentidadeDoKit.php --covered-only --no-tia --compact
```

Baseline: **101 testes, 518 assertions, 95,96 s**. Resultado: **42 mutantes, 37 mortos, cinco sobreviventes, 88,10%, 448,10 s**. Resultado de 37/100% anterior invalidado.

Sobreviventes no escopo dos três arquivos cobridores:

- `RemoveBooleanCast` e `TrueToFalse` em `unificaLogo()` (linha 82): coerção e default sem chave; não exercitados pela seleção com valores booleanos explícitos.
- `AlwaysReturnNull` em `favicon()` (linha 118): favicon não pertence à mudança de logos e não foi incluído no conjunto cobridor selecionado.
- `ConcatRemoveLeft` e `ConcatSwitchSides` no texto do warning de `doDisco()` (linha 182): corpo textual do aviso não é oráculo desses testes; contexto e fallback não mudaram.

Essas lacunas não foram ocultadas adicionando assertions sobre detalhes sem contrato de produto. A pontuação mede o arquivo completo sob seleção de testes, não somente o trecho alterado.

## Entrega local

Quatro simulações concluídas. Artefato usa `export-ignore`; dependências e assets foram reutilizados. Atualização parte da v0.46.0 e usa `kit:update` real com repositório temporário. A tag temporária v0.47.0 pertence apenas à cópia descartável; tag do repositório de trabalho permaneceu intacta.

| Cenário | Inicial: casos / verdes / falhas+erros / pulados | Assertions iniciais | Reexecução afetada |
|---|---|---|---|
| Instalação sem tenancy | 4249 / 3320 / 5 / 924 | 14957 | 163 verdes, 30 pulados, 536 assertions, zero falhas |
| Instalação com tenancy | 4250 / 3317 / 9 / 924 | 14906 | 70 verdes, 2 pulados, 397 assertions, zero falhas |
| Atualização sem tenancy | 4250 / 3324 / 2 / 924 | 14970 | 70 verdes, 2 pulados, 397 assertions, zero falhas |
| Atualização com tenancy | 4250 / 3326 / 0 / 924 | 14974 | 70 verdes, 2 pulados, 397 assertions, zero falhas |

Falhas iniciais de arte/instalação eram causadas pela limpeza global dos temporários; corrigida com PID e verificada também por duas execuções concorrentes compartilhando o mesmo diretório (43 testes/236 assertions cada). Timeout de PHPStan no cenário 3 passou na reexecução, sem aumentar o limite. Teto candidato medido: **924 pulados em 32 arquivos**, idêntico nos quatro; +2 sobre 922 são CT-09 PT/EN que leem documentação excluída. Decomposição completa no `CHANGELOG.md`.

Cenário 1 começou antes da inclusão de `pestw.cmd` na lista de entrega: 4.249 casos iniciais, contra 4.250 nos demais. Arquivos de entrega e launcher reexecutados após receber a correção. A suíte integral não foi repetida após os ajustes finais; resultados iniciais não foram reescritos como verdes.

Regressões consolidadas anteriores: 42 testes/71 assertions; browser cinco testes/41 assertions. Pint passou com `--dirty --format agent`; PHPStan passou sem erros. Filacheck passou nas 17 rules. `git diff --check` passou.

## Não verificado

- Instalação pelo Packagist, resolução nova de dependências e build novo do frontend nos quatro cenários.
- Publicação remota, CI remoto e upgrade de banco de produção.
- Axe, inspeção completa de logs reais e benchmark de latência.
- Nova execução integral das suítes depois das correções finais de texto, oráculo de extração, lista de entrega e limpeza do arnês; arquivos e casos afetados foram reexecutados.

## Publicação posterior ao gate — 2026-10-09

Correções commitadas em `365dc29` e publicadas na tag `v0.47.1`, commit `11c408a`.
Push de main e tag confirmado; release pública no GitHub. A tag local `v0.47.0` permaneceu intacta.
O check remoto `Release/marcador` passou; CI completo estava em execução no fechamento.
Esta confirmação substitui apenas a pendência de publicação remota acima; os limites de
instalação, dependências, acessibilidade, build e suíte integral permanecem declarados.
