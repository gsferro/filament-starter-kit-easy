> Referência da feature-wiki 4.0.0. Lida em: steps 3 a 5, antes de gravar o primeiro termo decidido na feature. Fonte única de: template de `wikis/glossario.md` e formato da pergunta de conflito.

# Glossário do projeto — `wikis/glossario.md`

Quando escrever, quem lê e por que ele existe ficam no corpo do `SKILL.md`, seção *Glossário do
Projeto*. O arquivo é **global**: fica em `wikis/glossario.md`, fora da pasta de qualquer feature,
e vai no PR da feature que o alterou.

## Template

```markdown
# Glossário do Projeto

> Vocabulário do domínio decidido nas features. Só glossário: sem implementação, sem spec, sem rascunho.
> Escrito pela feature-wiki (steps 3–5) no momento em que um termo é decidido — nunca em lote.
> Lido pela feature-test-design (Gherkin no vocabulário do projeto) e pelo feature-quality-gate (L7).

| Termo | Definição | Não confundir com | Origem | Data |
|---|---|---|---|---|
| Solicitante | quem abre a solicitação de compra | Aprovador | FERRO-830 · RQ-02 | 2026-09-21 |
```

- **Termo** — a palavra que o domínio usa, como o solicitante a escreve
- **Definição** — uma frase, do ponto de vista do domínio; sem classe, tabela ou path
- **Não confundir com** — o termo vizinho com que ele costuma ser trocado (`—` se não há)
- **Origem** — card e `RQ` (ou `P-nn`, ou `Qn` respondida) da feature em que o termo foi decidido

## Pergunta de conflito

Termo do requisito que conflita com o glossário vira pergunta no formato da entrevista
([`entrevista-tres-raias.md`](entrevista-tres-raias.md#formato-da-pergunta)):

```text
❓ Q4 · raia: requisito · afeta: RQ-06 · depende de: —
O glossário define "Aprovador" como quem decide uma etapa; o requisito parece usar "aprovador" como quem só visualiza — qual?
➡️ Recomendação: manter o sentido do glossário e chamar o outro papel de "Observador" — evita dois sentidos para o mesmo termo.
```

É raia requisito quando o conflito é de significado de domínio; se é só nome de classe ou de coluna,
é raia desenho.
