# Progresso — Model Caching padrão no painel /app

## Estrutura de Implementação

- [x] 1. Criar `App\Traits\ModeloCacheavel`
- [x] 2. Aplicar `ModeloCacheavel` em `User`, `Convite`, `Projeto`
- [x] 3. Criar `.ai/rules/models.md` e atualizar `index.md`
- [x] 4. Criar `tests/Kit/ModelCachingTest.php`
- [x] 5. Criar teste de arquitetura para models do painel `/app`
- [x] 6. Documentar no `README.md`
- [x] 7. Avaliar seção de quantidade/cobertura de testes

## Testes

- [x] `tests/Kit/ModelCachingTest.php` — CT-01 a CT-05

## Verificação Final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/pest tests/Kit/ModelCachingTest.php --compact`
- [x] `vendor/bin/pest tests/Kit --compact`
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pest --parallel --tia`
- [x] `git commit` dos arquivos alterados individualizados — commitado e mergeado em `main` (`git branch --no-merged main` vazio)
