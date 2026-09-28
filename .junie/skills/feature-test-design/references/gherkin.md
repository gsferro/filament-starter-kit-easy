> Referência da feature-test-design 1.16.0. Lida em: passo 5 (antes de escrever o primeiro cenário —
> a estrutura `Funcionalidade` → `Regra` → `Cenário` e o `Esquema do Cenário` como forma de EP e
> BVA). Fonte única de: o formato Gherkin dos cenários.

# Gherkin — formato e exemplos (passo 5)

As regras de escrita (cada uma com o anti-padrão que evita) estão no `SKILL.md` §Passo 5 — aqui
ficam a estrutura e o `Esquema do Cenário`.

> Isso não é meio-caminho: o BDD se decompõe em *Discovery → Formulation → Automation*, e a
> Formulation entrega valor sozinha. O próprio criador do Cucumber é explícito: quem só precisa
> executar teste não deve usar Cucumber.

**Estrutura:**

```gherkin
# language: pt
Funcionalidade: {título da feature}

  Regra: {a regra de negócio, em uma frase afirmativa}

    Cenário: [CT-01] {o comportamento, não o procedimento}
      Dado {estado inicial}
      E {mais estado}
      Quando {a única ação}
      Então {resultado observável}
      E {mais resultado}
```

**`Esquema do Cenário` é a forma canônica de expressar EP e BVA** — cada linha de `Exemplos`
é uma partição ou um valor de borda, com o rótulo dizendo qual:

```gherkin
    Esquema do Cenário: [CT-04] o limite de usos é inclusivo no último uso
      Dado um cupom com limite de <limite> usos e <ja_usado> usos já feitos
      Quando o comprador aplica o cupom
      Então o resultado é "<resultado>"

      Exemplos:
        | limite | ja_usado | resultado | # borda    |
        | 3      | 1        | aceito    | dentro     |
        | 3      | 2        | aceito    | borda−1    |
        | 3      | 3        | recusado  | borda      |
        | 3      | 4        | recusado  | borda+1    |
```
