# Glossário do Projeto

> Vocabulário do domínio decidido nas features. Só glossário: sem implementação, sem spec, sem rascunho.
> Escrito pela feature-wiki (steps 3–5) no momento em que um termo é decidido — nunca em lote.
> Lido pela feature-test-design (Gherkin no vocabulário do projeto) e pelo feature-quality-gate (L7).

| Termo | Definição | Não confundir com | Origem | Data |
|---|---|---|---|---|
| Aceito (en: Accepted) | estado do convite que o convidado aceitou, com conta nova ou com a conta que já tinha | Recusado (en: Declined), o convite a que o convidado disse não | `feat/diagramas-da-arquitetura` · P-34 (RQ-26) | 2026-09-29 |
| Expirado (en: Expired) | estado do convite cujo prazo venceu sem resposta; é o único que volta a Pendente, pelo reenvio | Recusado (en: Declined), que é resposta do convidado, e não prazo | `feat/diagramas-da-arquitetura` · P-34 (RQ-26) | 2026-09-29 |
| Excluída (en: Deleted) | estado da conta apagada sem perder o registro: ela sai de uso e, restaurada, volta ao estado que tinha antes da exclusão; nos diagramas, o nome interno do estado é o mesmo nos dois idiomas e sem acento (`Excluida`), e o acento fica só no rótulo pt | Inativa (en: Inactive), a conta desativada, que continua existindo e só não entra | `feat/diagramas-da-arquitetura` · P-35, que corrige P-21 (RQ-26) | 2026-09-29 |
| Organização (en: organization) | cada cliente ou grupo isolado quando o modo multi-organização está ligado, com os próprios usuários e dados; a tela mostra o nome que a configuração der (Organização por padrão) | tenant, o nome técnico do mecanismo (o endereço `/app/{tenant}`, os papéis por tenant, o próprio modo multi-tenancy); em frase sobre o estado ou o vínculo da organização (ativa, inativa, sem vínculo), o texto en diz organization, nunca tenant | `feat/diagramas-da-arquitetura` · QA-11 do quality gate, ciclo 1 (RQ-26) | 2026-09-29 |
| Sem vínculo (en: no link) | a conta que não pertence à organização: não entra nela, a menos que seja `master_global` | papel, que diz o que a conta pode fazer na organização, e não se ela pertence a ela | `feat/diagramas-da-arquitetura` · Q?2 do `04`, decidida pela sessão (RQ-10, RQ-26) | 2026-09-29 |
