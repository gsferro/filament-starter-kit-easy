# Requisito — Site de documentação do pacote em GitHub Pages

## Fonte

- **Origem**: três mensagens do mantenedor no chat, em sequência, ao longo de uma mesma sessão
- **Data**: 2026-09-01
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **alta** — as três são texto escrito, e a terceira fecha a decisão

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Mensagem 1 — a pergunta que abriu o assunto:**

> o @README.md esta muito completa, porem, muito longa. talvez seja melhor criamos uma pagina de site para termos mais espaço para tanta informação.
> - o que voce pensa sobre isso? é viavel usar uma page do github como site para hospedarmos o pacote e documentar melhor tudo o que oferecemos? penso que  podemos ter videos demostrando as funcionalidades implementadas. não precisa implementar nada agora, apenas pesquisa e me diga a melhor abordagem sobre

**Mensagem 2 — o recorte de mídia:**

> e se não houvesse video, so gifs e prints, qual outra opção?
> se for vitepress, a atualização se daria como?

**Mensagem 3 — a decisão:**

> sobre a decisão de virar site, vamos de github pages como kick-off. abra uma /feature-wiki e use sub-agentes, se necessário, e crie a pagina do pacote para ser mais facil a leitura de toda essa doc que esta nos readmes

## O estado de hoje, medido

| Fato | Valor |
|---|---|
| `README.md` | 2.522 linhas · 124 KB · 32 seções `h2` · 83 `h3` |
| `README.en.md` | 2.533 linhas · 120 KB · 32 seções `h2` · 83 `h3` |
| `art/` | 29 PNG + 25 thumbs + 2 GIF · 8,5 MB |
| Asserções de teste sobre os READMEs | **79**, em 6 suítes |
| GitHub Pages no repositório | **não existe** (`GET /repos/.../pages` → 404) |
| Workflows existentes | `.github/workflows/ci.yml`, `seguranca.yml` |

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A documentação passa a ter um **site**, hospedado em **GitHub Pages** | "vamos de github pages como kick-off" | funcional |
| RQ-02 | O site existe para tornar **mais fácil a leitura** da documentação que hoje está nos READMEs | "para ser mais facil a leitura de toda essa doc que esta nos readmes" | funcional |
| RQ-03 | O conteúdo migrado é **o dos READMEs** — não é documentação nova, escrita do zero | "toda essa doc que esta nos readmes" | restrição |
| RQ-04 | A entrega é um **kick-off**: o começo do site, não a migração completa e final | "vamos de github pages como **kick-off**" | restrição |
| RQ-05 | A mídia do site é **imagem estática e GIF** — vídeo saiu de escopo na mensagem 2 | "e se não houvesse video, so gifs e prints" | restrição |
| RQ-06 | O processo de **atualização** do site precisa estar definido e ser conhecido | "se for vitepress, a atualização se daria como?" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-04 — o que exatamente cabe num "kick-off"?** É a cláusula que decide o tamanho da entrega,
  e o requisito não a quantifica.
  - **Assumido**: o kick-off entrega o site **funcionando e publicado**, com a estrutura de
    navegação completa e um subconjunto de páginas migradas — não as 2.522 linhas de uma vez.
    Migração completa vira entrega seguinte, com o caminho já aberto.
  - **Se negado** (se "kick-off" significar migrar tudo): o passo de migração cresce de um
    subconjunto para 28 seções × 2 idiomas, e o cronograma muda de ordem de grandeza. A estrutura
    e o deploy não mudam.
  - **A ser confirmado com o solicitante antes de implementar a migração de conteúdo.**

- **RQ-03 — o README esvazia ou continua existindo?**
  - **Assumido**: **continua**, virando uma landing curta. Ele é a vitrine do **Packagist** e da
    página do GitHub, e quem chega pelo Packagist nunca vê o site. Esvaziá-lo tornaria o pacote
    pior para quem o descobre.
  - **Se negado**: o passo do README muda de "reduzir e apontar" para "substituir por link".

- **RQ-01/RQ-03 — o site nasce bilíngue?**
  - **Assumido**: **sim**, porque a documentação de origem é bilíngue e abandonar o inglês no meio
    do caminho deixaria o `README.en.md` como fonte órfã.
  - **Se negado**: cai pela metade o volume de migração do kick-off.

- **O gerador não foi escolhido pelo solicitante.** A mensagem 2 pergunta *"se for vitepress…"*,
  o que sugere inclinação, mas não fixa. A escolha vai para a ADR-01, com o VitePress como
  recomendação — e a decisão é do solicitante.

## Restrições Herdadas (não vêm do requisito, vêm do repositório)

Estas não são cláusulas do pedido, mas **limitam a solução** e foram levantadas na pesquisa. Um
plano que as ignore produz entrega quebrada:

1. **`package.json` não é `export-ignore` — e não pode ser.** Os scripts `build` e `dev` são
   necessários no projeto que nasce do `create-project`. Uma dependência de documentação declarada
   na raiz seria baixada por **todo projeto instalado**, para sempre.
2. **Os READMEs são testados** — 79 asserções em 6 suítes, incluindo asserção de **ausência** com
   filtro de citação (`readmeSemCitacao()`). É essa rede que impede a documentação de mentir sobre
   comportamento, e ela já pegou erro real. Conteúdo que se move sem o teste se mover junto perde
   a garantia.
3. **O que entra no repositório entra na instalação de quem usa o kit**, salvo `export-ignore`.
   É a mesma decisão já tomada para `/wikis/specs` e `/.github`.
4. **`art/` já é versionado e servido por `raw.githubusercontent`.** Duplicar 8,5 MB de imagem
   para dentro de uma pasta de documentação dobraria o peso do repositório sem ganho.
5. **Git LFS não é servido pelo GitHub Pages** — o Pages entrega o arquivo-ponteiro. Isso fecha a
   porta para mídia pesada versionada, e é parte do motivo de RQ-05 ter saído em vídeo.

## Fora de Escopo (declarado)

- **Vídeo**, e toda a infraestrutura que ele exigiria — retirado pelo próprio solicitante na
  mensagem 2.
- Domínio próprio (`CNAME`) — não foi pedido; o site nasce em `gsferro.github.io/…`.
- Reescrever ou reorganizar o **conteúdo** da documentação: o requisito é mudar o **meio**, não o
  texto. Correção de erro encontrado no caminho é bem-vinda, mas é achado, não escopo.
- Versionamento de documentação por release (doc da v0.22 vs v0.23).
- Analytics, comentários, newsletter.
