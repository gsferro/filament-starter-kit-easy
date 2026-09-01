# Progresso — Site de documentação em GitHub Pages

> Feature **nova**, mas toca infra compartilhada em três pontos: `.gitattributes`,
> `.github/workflows/` e os dois READMEs (79 asserções em 6 suítes) → regressão obrigatória.

## 1. Esqueleto do Jekyll em `docs/`

- [ ] `docs/_config.yml` com `baseurl: /filament-starter-kit-easy`
- [ ] `remote_theme` de um tema de documentação (o build nativo aceita)
- [ ] `docs/pt/` e `docs/en/`
- [ ] **Sem `Gemfile` na raiz**; se houver para prévia local, fica em `docs/` (ADR-02)
- [ ] **`package.json` da raiz intocado** — `git diff --stat package.json` vazio

## 2. Imagens

- [x] **Nenhum trabalho necessário** — medido: 20 URLs absolutas, 7 badges, **0 relativas**. O
      markdown migrado já funciona. Passo cortado pela auditoria Ponytail.

## 3. Navegação (manual)

- [ ] `docs/_data/nav_pt.yml` e `nav_en.yml`, espelhando `90-mapa-do-conteudo.md`
- [ ] Seletor de idioma no layout
- [ ] Home de cada idioma com os 5 grupos
- [ ] Caso de teste que falsifica a paridade das duas árvores

## 4. Migração do conteúdo (22 páginas × 2 idiomas)

- [ ] `comecar/` (2 páginas)
- [ ] `autenticacao/` (6)
- [ ] `recursos/` (7)
- [ ] `operacao/` (5)
- [ ] `referencia/` (4)
- [ ] Cada trecho migrado levou **a asserção de teste junto, no mesmo commit** (ADR-05)

## 5. As divergências PT/EN, antes da migração

- [ ] Conta existente não consome convite — parágrafo ausente no inglês
- [ ] Nota das `wikis/specs` `export-ignore` — trocada por outra frase no inglês
- [ ] Bullet de `getTabs()` — ausente no inglês
- [ ] F-06 no roteiro de features — divergência pontual

## 6. Workflow de publicação

- [ ] **Nenhum workflow** — o build nativo dispensa Actions (ADR-01)
- [ ] Settings → Pages → **Deploy from a branch → `main` → `/docs`** (hoje dá 404 em `/pages`)
- [ ] `ci.yml` e `seguranca.yml` não afetados

## 7. READMEs viram landing

- [ ] `README.md` → ~340 linhas
- [ ] `README.en.md` → ~345 linhas
- [ ] Contagem origem × destino conferida — linha que sumiu dos dois lados é conteúdo perdido

## 8. `.gitattributes`

- [ ] `/docs export-ignore` (ADR-03)
- [ ] `KitUpdate::CAMINHOS_DO_KIT` conferido

## 9. Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] `git diff --stat package.json` vazio
- [ ] `docs/` fora do `git archive`
- [ ] **Site publicado** conferido no navegador, nos dois idiomas

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas verificadas contra o repositório real

| Premissa | O que a verificação mostrou | Efeito na wiki |
|---|---|---|
| O gerador processaria `{{ }}` como template e quebraria o build | **0 ocorrências** de `{{` e `{%` nos dois READMEs — medido primeiro fora de bloco de código (para o Vue), e depois **dentro também** (para o Liquid do Jekyll, que não poupa bloco de código) | risco afastado nas duas hipóteses de gerador |
| HTML inline e pseudo-tags (`<int>`, `<string>`, `<script>`) quebrariam o build | todas as ocorrências estão **dentro de crases**, e são escapadas antes de qualquer motor de template vê-las | risco afastado |
| A dependência do gerador poderia ir para a raiz | `package.json` **não é `export-ignore`**, e os scripts `build`/`dev` são necessários no projeto instalado | ADR-02 nasceu daqui, e sobreviveu à troca de gerador |
| GitHub Pages já estaria configurado | `GET /repos/.../pages` → **404** | passo 6 inclui habilitar |
| O site precisaria de configuração para servir `art/` | **não precisa**: 20 das 27 referências de imagem já são URLs absolutas, **0 relativas** | passo 2 **deletado**; ADR-06 reescrita |
| O build nativo do Pages aceitaria plugin de i18n | roda em `--safe`, lista fechada: **nenhum** plugin de i18n entra, e o `just-the-docs` também não tem | o bilíngue vira manual, e a paridade passa a depender **só de teste** (ADR-01) |
| As divergências PT/EN seriam cosméticas | **três confirmadas por leitura direta**, e são materiais — ver abaixo | passo 5 sobe para **antes** da migração |

### As três divergências, confirmadas linha a linha

Reportadas pelo sub-agente do mapa e **verificadas uma a uma** antes de virarem passo do plano:

| Trecho | Português | Inglês |
|---|---|---|
| Conta existente **não consome convite** — rota GET sem CSRF, SSO silencioso, tela "Convites recebidos" | 4 linhas com a decisão e a wiki de origem (`README.md:708`) | **ausente**; substituído por frase genérica (`README.en.md:718`) |
| `wikis/specs` do kit são `export-ignore` e nascem vazias no projeto | nota completa com link do repositório (`README.md:1366`) | trocada por *"The wiki is written in pt-BR"* (`README.en.md:1379`) |
| Convenção de `getTabs()` vs filtro de modal | 8 bullets, um deles o de `getTabs()` (`README.md:2057`) | **7 bullets**, sem o de `getTabs()` |

A primeira é a mais grave: quem lê só o inglês não sabe **por que** o convite não é consumido no
login social — é uma decisão de segurança, não um detalhe.

**Elas são o argumento da ADR-04 e da ADR-05 virando evidência**: duas fontes do mesmo texto
divergem, e divergem em silêncio. Nesta mesma sessão já havia aparecido uma quarta (o provedor
anti-robô padrão errado no inglês), corrigida antes desta wiki existir.

### Auditoria Ponytail (step 6)

| # | Achado | Aplicado? |
|---|---|---|
| 1 | `delete:` o passo de imagens inteiro (plano A/B com `publicDir` não documentado + cópia no workflow) — as imagens já são absolutas | **sim** — passo 2 do `01` virou duas linhas, ADR-06 reescrita, seção 2 daqui fechada |
| 2 | `shrink:` três seções de template dizendo "Nenhum" (Variáveis de Ambiente, Eventos, Jobs) | **sim** — fundidas numa |

**O achado 1 era erro meu de análise**: planejei configuração para um problema que o repositório já
tinha resolvido. Uma medição de trinta segundos — contar quantas imagens são relativas — teria
evitado uma ADR inteira e dois passos de plano. `net: ~-70 linhas`.

## Blockers

- Nenhum. **O gerador foi resolvido**: ver abaixo.

### Como o gerador foi decidido (e por que demorou três perguntas)

Perguntado qual gerador, o solicitante respondeu **"github pages"** — duas vezes —, e na terceira
devolveu o link da documentação com a pergunta **"esse jekyll é o github pages?"**. A confusão era
legítima, e minha primeira pergunta a alimentou ao listar "Jekyll (nativo do Pages)" como se fosse
uma opção de hospedagem.

Esclarecida a distinção — o Pages **hospeda** ("serviço de hospedagem de sites estáticos", na própria
doc), o Jekyll **gera**, e o Pages hospeda a saída de qualquer gerador —, ele escolheu o **Jekyll
embutido**, com o custo do bilíngue manual já apresentado na tabela.

**Lição para a próxima wiki**: quando a resposta a uma pergunta de escolha não é nenhuma das opções,
o problema costuma estar na pergunta. Insistir na mesma forma teria produzido uma terceira resposta
igual.

## Desvios do Plano

<!-- pós-implementação -->

## Notas de Implementação

- **O sub-agente do mapa encontrou o que a leitura casual não encontraria.** As três divergências
  PT/EN estão em pontos distantes de arquivos de 2.500 linhas; nenhuma apareceria numa revisão de
  diff, porque nenhuma nasceu nesta sessão.

## Retrospectiva

<!-- pós-implementação -->
