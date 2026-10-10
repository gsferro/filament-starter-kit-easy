# Casos de navegador — unificar-logo-da-organizacao

## CT-B02: logo único visível nos dois temas da tela de bloqueio

Origem: RQ-03, P-03. Regra: R3. Destino: `tests/Browser/LogoDarkModeTest.php`.

```gherkin
Esquema do Cenário: [CT-B02] logo unificado permanece visível
Dado uma tela de bloqueio com logo unificado de <origem>
Quando a página carrega no tema <tema>
Então a imagem está visível e carregou um arquivo válido
E não recebe classes exclusivas de claro ou escuro
E o tema efetivo do documento corresponde ao tema pedido
E não há erros de JavaScript

Exemplos:
| origem | tema |
| instalação | claro |
| instalação | escuro |
| organização | claro |
| organização | escuro |
```

O teste usa imagens reais no disco público e visita a página com preferência de tema definida antes do carregamento. `assertVisible` verifica o efeito do CSS; presença no HTML não basta.

O CT-B01 do mesmo arquivo pertence à wiki ancestral e continua cobrindo o par separado.

## CT-B01: regressão herdada do par separado

Referência: `wikis/specs/feat/logo-dark-mode/logo-dark-mode/05-casos-de-teste-browser.md`. Sem duplicar teste: o caso existente verifica clara visível/escura escondida no tema claro e a troca no tema escuro. Executar o arquivo completo inclui essa regressão.
