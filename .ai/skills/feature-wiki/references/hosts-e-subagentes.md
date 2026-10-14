# Hosts, ferramentas e subagentes

Leia esta referência na primeira execução em um host e quando faltar uma capacidade. O processo
das skills é comum: requisito, plano, testes derivados do requisito, implementação, revisão,
reconciliação e QA. `Agent`, `/code-review`, aliases de modelos e hooks são adaptadores, não requisitos universais.

## Detectar capacidades antes de despachar

1. Localize a skill pelo path informado pelo host. Se precisar resolver `{skills}`, procure a skill
   citada em `.ai/skills/`, `.agents/skills/`, `.claude/skills/`, `~/.agents/skills/`,
   `~/.codex/skills/` e `~/.claude/skills/`; o diretório deve conter o `SKILL.md` correspondente.
   Não percorra todos nem carregue cópias duplicadas. `{skills}` é o diretório de instalação que
   contém a pasta da skill, não a própria pasta: `.agents/skills/feature-wiki/SKILL.md` resolve
   `{skills}` para `.agents/skills`. Assim `{skills}/feature-wiki/scripts/` não duplica o nome.
   Esse diretório pode ser diferente para cada skill; o path explícito do host tem precedência.
2. Confirme ferramentas realmente expostas: execução local, subagente com contexto novo, MCP,
   navegador, modelos/esforços disponíveis e permissões efetivas. Não deduza disponibilidade pelo nome do produto.
3. Use o modelo da sessão por herança quando não houver mapeamento configurado. Nunca passe
   `haiku`, `sonnet` ou `opus` ao Codex. Modelo, esforço e capacidade são coisas diferentes.
4. Registre em `03 → Despachos` host, rota, contexto recebido, controles efetivos, modelo/esforço
   informado pelo host, duração/tokens quando disponíveis e limitações. Valor desconhecido é `não informado`.

## Mapear o contrato ao host

| Capacidade | Claude Code | Codex | Outro host / falta de recurso |
|---|---|---|---|
| Descobrir skill | diretórios configurados pelo host / Boost | `.agents/skills/`; path explícito também permite leitura | path real do SKILL.md |
| Subagente | `Agent`, perfil em `.claude/agents/*.md` | perfil em `.codex/agents/*.toml` ou ferramentas expostas na sessão | contexto novo equivalente; sem isso, independência não verificada |
| Contexto cego | prompt novo sem conversa | despacho sem herdar histórico; nesta interface, `fork_turns="none"` | sessão separada com só artefatos permitidos |
| Revisar diff | `/code-review` opcional + contrato `revisor-diff` | contrato `fw-revisor-diff`; não inventar `/code-review high` | revisão independente equivalente, ou degradação declarada |
| Restrição de escrita | tools/hook conforme instalado | sandbox efetivo e contrato do perfil | controles reais disponíveis |
| Leitura por path | hook de arquivo, sob limitações documentadas | contrato; `read-only` não limita leitura por path | declarar, sem chamar de isolamento técnico |
| MCP | ferramentas herdadas/configuradas | ferramentas expostas/configuradas | busca oficial, CLI ou fallback previsto pela skill |

Os cinco perfis Codex ficam em `agents/codex/` de suas skills donas. Instale-os em
`.codex/agents/` do projeto consumidor. Cada TOML reutiliza o corpo do contrato Markdown do agente;
o frontmatter Claude não se aplica. Não duplicar os procedimentos em um novo prompt completo.
Na interface desta sessão, `spawn_agent` recebe mensagem com o path do perfil e contexto mínimo;
não aceita um `agent_type` inventado. Só o cliente que expõe agentes nomeados carrega TOML automaticamente.

As rotas genéricas `mecânico`, `construtor` e `analista` são papéis, não modelos obrigatórios.
Execute checagens determinísticas localmente. Delegue investigação extensa, trabalho disjunto ou
julgamento independente. Não crie subagente para um comando barato sem julgamento. Respeite o limite
de concorrência do host. Dois escritores nunca compartilham o mesmo arquivo.

## Independência e limites

Não herdar conversa protege o contexto inicial. Não impede leitura posterior de arquivos ou acesso
por shell/MCP. Hook por padrões e `git status --porcelain` são sinais limitados; não provam isolamento.
Compare conteúdo/hashes dos arquivos relevantes antes/depois, inclusive arquivos já modificados e novos.
Não permita ferramentas com efeitos externos desnecessários em um revisor. Quando viável, forneça
um ambiente descartável com apenas os artefatos autorizados. Declare qualquer lacuna de controle.

Sem subagente, a sessão pode produzir verificação útil, mas não revisão independente. Declare a perda
no `03` e no `04`/`06` correspondente. Não autorrevise chamando o resultado de cego; QA com independência
não verificada não recebe `APROVADO` pleno. Se independência for requisito obrigatório do usuário, falta
de recurso é bloqueio dessa etapa, não aprovação tácita.

## Autorização e eficiência

Leia as instruções da skill invocada e apenas as referências exigidas pela etapa. Não execute
wiki, QA ou fatiamento completo para manutenção das próprias skills, typo ou tarefa fora de seu escopo.
Preserve decisões e autorizações existentes. Nome de arquivo, técnica de teste e reutilização de helper
são escolhas técnicas rotineiras quando o projeto já define o padrão. Pergunte quando faltar informação
material de requisito, quando uma escolha mudar o comportamento prometido ou quando precisar de autorização
adicional. Não repita aprovação de plano, costura ou ticket já concedida.

Esforço alto não exige mais arquivos, cenários ou agentes. Nesta auditoria o usuário pediu esforço máximo;
o projeto contém `model_reasoning_effort = "ultra"` para clientes/modelos compatíveis. Em consumidores,
escolha o maior esforço suportado quando solicitado; caso contrário, calibre conforme complexidade.
Configuração em disco não altera um turno em andamento. Não afirmar mudança de esforço sem confirmação do host.

## Windows e scripts

Os `.sh` exigem Bash; PowerShell não interpreta Bash. Use Git Bash explicitamente, com PHP no PATH,
ou WSL com PHP instalado dentro do WSL. `C:\Windows\System32\bash.exe` pode ser WSL e não equivale ao Git Bash.
Para executar os checks em PowerShell:

```powershell
& 'C:\Program Files\Git\bin\bash.exe' '.ai/skills/feature-wiki/scripts/rastreabilidade.sh' $wiki
```

Os hooks distribuídos para Claude exigem Git Bash no Windows. Esses hooks não são instalados nem
executados pelo Codex. Ausência de hook não impede usar o processo; exige declarar os controles reais.
`pestw.cmd` continua sendo o lançador de mutation testing Windows quando a configuração do projeto o exige.

## Fontes e versão do adaptador

Conferido em 2026-10-09, CLI local `codex-cli 0.162.0`. Documentação oficial:
[skills e descoberta](https://learn.chatgpt.com/docs/build-skills),
[subagentes e TOML](https://learn.chatgpt.com/docs/agent-configuration/subagents),
[configuração e esforço](https://learn.chatgpt.com/docs/config-file/config-reference).
Clientes antigos podem usar outro esquema; não prometer compatibilidade de TOML só porque o parser o aceita.
