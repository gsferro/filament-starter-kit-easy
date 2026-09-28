> Referência da feature-wiki 4.0.0. Lida em: step 3 (extrair locators reais), loop do CT-B nas falhas (a)/(c) e step 10 (evidência de console e rede). Fonte única de: por que o MCP, onde ele entra, configuração e fallback sem ele.

# Playwright MCP na validação

O princípio (*o `pest-plugin-browser` atesta; o Playwright MCP observa*), `--isolated`
obrigatório, somente `localhost` e as regras de uso ficam no corpo do `SKILL.md`, seção
*Playwright MCP na validação*.

As ferramentas de debug do próprio plugin (`debug()`, `tinker()`, `waitForKey()`, `--headed`)
**exigem um humano na frente** e travariam um agente autônomo. As que servem —
`screenshot()` e `content()` — devolvem um PNG caro ou a página inteira. O MCP resolve os três
casos em que isso não basta: descobrir o locator verdadeiro numa falha de seletor, observar
quando o elemento realmente aparece em UI assíncrona, e extrair seletores de tela existente.

| Etapa | Uso | Tools |
|---|---|---|
| **Step 3** — pesquisa | extrair locators reais das telas que a feature vai tocar | `browser_navigate`, `browser_find`, `browser_generate_locator` |
| **Loop do CT-B** — falha (a)/(c) | observar a página ao vivo e corrigir o CT-B | `browser_find`, `browser_generate_locator`, `browser_wait_for` |
| **Step 10** — evidência | anexar console e rede ao roteiro *Desenhado × Implementado* | `browser_console_messages`, `browser_network_requests` |

> Para o step 10, verificar primeiro se a tool **`browser-logs`** (Browser Logs) do Boost MCP já resolve — é uma tool
> que o projeto provavelmente já tem, sem adicionar servidor novo.

**Configuração obrigatória**:

```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["@playwright/mcp@latest",
               "--isolated", "--headless",
               "--caps=testing",
               "--test-id-attribute=data-testid"]
    }
  }
}
```

- **`--caps=testing`** habilita `browser_generate_locator` e os `browser_verify_*`.

**Se o MCP não estiver disponível**, a skill funciona: `screenshot()` no ponto da falha →
`content()` filtrado com `Grep` → ler o Blade/componente e derivar o seletor do código-fonte →
após 3 iterações, escalar ao usuário.
