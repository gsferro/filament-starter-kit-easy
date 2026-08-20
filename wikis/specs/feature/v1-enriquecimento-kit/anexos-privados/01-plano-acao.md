# Plano de Ação — Anexos privados

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: correção de segurança
- **Toca infra compartilhada?**: **sim** — `config/media-library.php` é o default de toda coleção
  de mídia de todo projeto nascido do kit.

## Objetivo

Fazer os dois caminhos de escrita concordarem, para que a chamada **idiomática** do spatie pare
de gravar em disco público.

O upload pela tela já está correto. O que vaza é `$model->addMedia(...)->toMediaCollection('x')` —
a chamada da documentação do spatie, a que o usuário do kit escreve, e a que o próprio kit usa
em `tests/BrowserTenancy/AnexosDoProjetoTest.php:66`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | abrir wiki | — | esta |
| RQ-02 | não recuperável sem sessão, por qualquer caminho | 1, 2 | |
| RQ-03 | miniatura sob a mesma regra | 1 | a coluna **já** está em `visibility=private`; passa a gerar URL assinada sozinha, sem alteração de código |
| RQ-04 | os dois defaults param de discordar | 1 | |
| RQ-05 | tratar as mídias já gravadas em público | 3 | comando idempotente |
| RQ-06 | documentação descreve o que o código faz | 5 | 6 documentos afirmam hoje o contrário |
| RQ-07 | ausência de autorização na rota assinada | — | **aceita e documentada** — ADR-03 |
| RQ-08 | corrigir | 1-5 | |

## O que NÃO muda, e por quê

| Não muda | Motivo |
|---|---|
| `SpatieMediaLibraryFileUpload` no `ProjetoResource` | já resolve para `local`; consertar o que não está quebrado |
| `SpatieMediaLibraryImageColumn` | já está em `visibility=private` (`ImageColumn.php:246-254`); com a mídia em disco servível, passa a assinar sozinha |
| avatar e logo | `->disk('public')` **explícito**, fora da media library — públicos de propósito, aparecem na tela de login |
| disco `public` em `filesystems.php` | continua existindo para identidade visual |
| `->simpleLightbox()` | consome o `src` renderizado, não reconstrói URL — funciona com URL assinada |

## Superfície de UI

**Nenhum elemento novo.** A mudança é de onde o arquivo mora e de como a URL é gerada. O que a
tela mostra é idêntico.

Isso torna a verificação visual enganosa: a tela continua igual **estando certa ou errada**. Por
isso o CT que vale é o de rede, não o de tela — ver `04-casos-de-teste.md`.

## Estrutura de Implementação

### 1. Fazer os defaults concordarem

`config/media-library.php:36`:

```php
'disk_name' => env('MEDIA_DISK', 'local'),
```

E `.env.example`. O disco `local` já existe com `'serve' => true` (`config/filesystems.php:36`),
logo já produz URL assinada — **nenhum disco novo, nenhuma rota nova**. Ver ADR-01.

### 2. `useDisk()` explícito na coleção da demo

`app/Models/Projeto.php:83`:

```php
$this->addMediaCollection('anexos')->useDisk('local');
```

Redundante com o passo 1 **de propósito**: é defesa em profundidade para uma propriedade de
segurança. Quem trocar `MEDIA_DISK` de volta para `public` não reabre o vazamento nesta coleção,
e o model passa a ensinar o padrão em vez de descrevê-lo em comentário. Ver ADR-02.

### 3. Comando de migração das mídias legadas

`php artisan kit:midia-privada` — idempotente, com `--dry-run`:

- move o arquivo de `storage/app/public/{id}/` para `storage/app/private/{id}/`
- move as conversões
- atualiza `media.disk` e `media.conversions_disk`
- pula o que já está no disco de destino
- **não** toca em mídia de coleção que declare `useDisk('public')` explicitamente

Sem ele a correção fecha a porta e deixa a janela: os arquivos já gravados continuam servidos
pelo symlink, e o vazamento medido continua reproduzível. Ver ADR-04.

### 4. Testes

Ver `04-casos-de-teste.md`. O caso central é de **rede**, não de model.

### 5. Documentação (RQ-06)

Seis documentos e dois comentários afirmam hoje que a mídia vive em disco público:

| Arquivo | Trecho |
|---|---|
| `README.md` | ~480-483 e ~935 |
| `README.en.md` | ~479-482 e ~933 |
| `wikis/arquitetura.md` | ~308-310 |
| `wikis/receitas.md` | ~414 |
| `.ai/rules/models.md` | ~34 |
| `wikis/pacotes.md` | seção de mídia |
| `app/Models/Projeto.php` | comentário da coleção |
| `app/Filament/App/Resources/Projetos/ProjetoResource.php` | 118-123 |

O comentário do `ProjetoResource` precisa parar de dizer que o `visibility('private')` é o que
protege: ele **participa**, mas quem decide é o disco. Escrever isso certo é metade da correção,
porque foi a leitura errada dele que me levou a atribuir a causa ao componente.

Acrescentar em `.ai/rules/models.md`: **coleção de mídia declara `useDisk()`**.

## Impacto em Features Existentes

| Feature | Impacto |
|---|---|
| `tests/Tenancy/PacotesTierSTenancyTest.php` | 2 casos usam `Storage::fake('public')` — passam a fazer fake do disco errado. Precisam virar `'local'` |
| `tests/BrowserTenancy/AnexosDoProjetoTest.php` | continua verde, mas **não verifica a `src`** — ficaria verde com a imagem quebrada. Precisa de asserção nova |
| Identidade visual (avatar, logo) | **nenhum** — `->disk('public')` explícito |
| Docker | `storage/` é volume nomeado; `app/private` nasce junto, sem ajuste |
| `kit:update` | comando novo em `app/Console/Commands/` entra em `CAMINHOS_DO_KIT` |
| Instalação existente | `kit:update` traz o novo default; **sem rodar o comando do passo 3, a mídia antiga segue pública** — precisa estar no aviso de release |

## Riscos

- **Colisão de URI.** A rota `storage.local` nasce em `/storage/{path}`, o mesmo caminho do
  symlink. Arquivo físico ganha da rota. *Mitigação*: o passo 3 esvazia `storage/app/public/{id}/`
  de mídia; o que sobra ali (`organizacoes/`) não colide com ids numéricos. Registrado em ADR-01
  como o motivo de a alternativa "disco dedicado" ter sido considerada.
- **Link assinado continua sem autorização.** É limite aceito, não resolvido — ADR-03.
- **Migração parcial.** Falha no meio deixa metade em cada disco. *Mitigação*: por item, com a
  linha do banco atualizada só após o arquivo chegar; reexecutável.

## Channel de Log da Feature

**`tenancy`** para o comando de migração — é onde o kit registra o que cruza organização, e mídia
pertence a registro de organização. Padrão `[KitMidiaPrivada@handle] ... | midia_id: X`.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [ ] `vendor/bin/filacheck`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`
- [ ] `composer test:browser`
- [ ] **`curl` sem sessão na URL de uma mídia recém-criada → não pode ser 200**

O último não é opcional: é o experimento que produziu o achado, e é o único que prova a correção
no mesmo plano em que o defeito foi medido.

## Commits

| # | Escopo |
|---|---|
| 1 | disco privado como default da media library |
| 2 | comando de migração das mídias legadas |
| 3 | testes de rede e ajuste dos fakes existentes |
| 4 | documentação e rule |
