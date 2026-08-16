# Requisito — Lightbox em imagens e documentos

## Fonte

- **Origem**: pedido colado no chat pelo mantenedor do kit, invocando a skill `feature-wiki` (item 1 de 3 pacotes pedidos na mesma mensagem)
- **Data**: 2026-08-15
- **Autor / solicitante**: Guilherme Ferro (mantenedor do starter-kit-easy)
- **Fidelidade**: alta (texto escrito)
- **Wikis irmãs do mesmo pedido**: `hub-de-navegacao-em-cards` (item 2), `graficos-com-apexcharts` (item 3)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> Vamos adicionar mais funcionalidades ao starter-kit atras de pacotes filaments;
> 1. analise profundamente o pacote: https://filamentphp.com/plugins/solution-forest-simplelightbox veja como ele pode ser integrado ao projeto.
> - sempre que tiver uma foto, avatar, imagem ou for colocado um documento na table, devemos usar esse pacote exibir o lightbox na tela
> - deixe documentado no starter-kit sobre ele e seu uso, e também coloque no @README.md ele como dependencia
> - veja se é necessário algum tipo de teste sobre a funcionalidade
> - ele já pode ser usado na tela de User, para exibir o avatar e na tela de Organizações, para exibir a logo, isso se tiver sido feito o upload correspondente

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `solution-forest/filament-simplelightbox` é instalado e integrado ao projeto | "analise profundamente o pacote: … veja como ele pode ser integrado ao projeto" | funcional |
| RQ-02 | Foto, avatar e imagem exibidas em tabela abrem em lightbox | "sempre que tiver uma foto, avatar, imagem … na table, devemos usar esse pacote exibir o lightbox na tela" | funcional |
| RQ-03 | Documento colocado em tabela abre em lightbox | "ou for colocado um documento na table, devemos usar esse pacote exibir o lightbox na tela" | funcional |
| RQ-04 | A regra do RQ-02/RQ-03 vale como convenção permanente do kit, não só nas duas telas de hoje | "**sempre** que tiver … devemos usar esse pacote" | restrição |
| RQ-05 | O pacote e seu uso ficam documentados na documentação do kit (`wikis/`) | "deixe documentado no starter-kit sobre ele e seu uso" | não-funcional |
| RQ-06 | O pacote aparece no `README.md` na lista de dependências | "e também coloque no @README.md ele como dependencia" | não-funcional |
| RQ-07 | A necessidade de teste é avaliada e o teste cabível é escrito | "veja se é necessário algum tipo de teste sobre a funcionalidade" | não-funcional |
| RQ-08 | A tela de Usuários exibe o avatar com lightbox | "ele já pode ser usado na tela de User, para exibir o avatar" | funcional |
| RQ-09 | A tela de Organizações exibe a logo com lightbox | "e na tela de Organizações, para exibir a logo" | funcional |
| RQ-10 | Sem upload correspondente, a tela não quebra nem oferece lightbox vazio | "isso se tiver sido feito o upload correspondente" | funcional |

## Ambiguidades e Perguntas Abertas

### Resolvidas com o usuário em 2026-08-15

- **Escopo da entrega**: os 3 pacotes do pedido viram **3 wikis separadas**, uma por pacote — decisão do usuário. Consequência: esta wiki cobre só o item 1, e o quality gate dela não é bloqueado pelos outros dois.

### Abertas — decididas por premissa, sujeitas a correção

- **RQ-03 — não existe nenhuma coluna de documento no kit hoje.**
  Nenhum model tem campo de arquivo além de `users.avatar_url` e `tenants.logo`, ambos imagem.
  - **Assumido**: RQ-03 é atendido pela **convenção documentada + receita** (RQ-04/RQ-05), sem implementação em tela, porque não há tela onde implementar. Criar uma coluna de documento só para exercitar o pacote seria inventar superfície que o requisito não pediu.
  - **Se negado**: seria preciso escolher um model para receber um campo de anexo — decisão de negócio que o kit deliberadamente não toma (ver a nota do `TenantForm` sobre campos de organização).

- **RQ-03 — o preview de documento do pacote envia a URL para um terceiro.**
  O JS do pacote (`resources/js/index.js`, `getViewerURL`) monta o preview de PDF via
  `https://docs.google.com/viewer?url=…` e o de Office via
  `https://view.officeapps.live.com/op/embed.aspx?src=…`.
  Consequências: (a) a URL do documento sai da aplicação para Google/Microsoft; (b) o arquivo precisa ser **publicamente acessível** para o viewer conseguir buscá-lo, ou o preview aparece em branco.
  - **Assumido**: a convenção do kit vai recomendar lightbox para **imagem** sempre, e para documento **apenas quando o arquivo já for público e não sensível**; documento sensível segue com download autenticado. Ver ADR-03.
  - **Se negado** (usuário quiser lightbox em documento sensível): é preciso um viewer local (ex.: PDF.js embutido), que o pacote não oferece — vira outra feature.

- **RQ-08 — o avatar não é editável pelo `UserResource`.**
  Quem faz upload de avatar é o `jeffgreco13/filament-breezy` na página "Meu perfil" (`hasAvatars: true`), gravando em `users.avatar_url`. O `UserResource` do `/admin` e do `/app` não tem campo de avatar.
  - **Assumido**: RQ-08 é **exibição**, não edição — a coluna mostra o que o próprio usuário subiu no perfil dele. Nenhum campo de upload é adicionado ao `UserResource`.
  - **Se negado**: acrescentar `FileUpload` de avatar ao form do `UserResource` é decisão de produto (admin subindo foto de outra pessoa) e está fora desta entrega.

### Devolvidas pela derivação de testes (`feature-test-design`, 2026-08-15)

- **RQ-03 — o cenário de documento é inexpressável, não esquecido.**
  A derivação não conseguiu escrever nenhum caso de teste para documento, porque não há tela onde
  ele exista. Isso é consequência da premissa de escopo acima, não do conjunto de testes.
  - **Confirmar**: (a) fica só como convenção documentada, como assumido; ou (b) alguma entidade
    do kit deve ganhar campo de anexo nesta entrega?
  - **Se (b)**: RQ-03 volta a ter cenário, e o PRD ganha migration, campo e upload.

- **RQ-02 + ADR-03 — "sempre" tem uma exceção que o requisito não previu.**
  Para documento, o pacote envia a URL a `docs.google.com` / `view.officeapps.live.com`.
  - **Confirmar**: a restrição "só arquivo público e não sensível" é aceitável como leitura de
    "sempre que … for colocado um documento na table"?

- **RQ-10 — `avatar_url` vazio (`''`) e nulo se comportam igual?**
  O cenário CT-04 cobre o nulo. Se o Breezy conseguir gravar string vazia, há uma terceira
  partição sem cenário.
  - **Assumido**: a implementação usa `filled()`, e os dois casos colapsam.
  - **Se negado**: um cenário a mais em R2.

## Fora de Escopo (declarado)

- Adicionar campo de upload de avatar ao `UserResource` (ver premissa de RQ-08)
- Criar coluna/campo de documento em qualquer model só para exercitar o pacote (ver premissa de RQ-03)
- Lightbox em `ImageEntry`/`TextEntry` de infolist — o requisito fala em `table`; o macro existe e é documentado, mas nenhuma infolist do kit é alterada nesta entrega
- Viewer local de PDF/Office para substituir o Google Docs / Office Online do pacote
