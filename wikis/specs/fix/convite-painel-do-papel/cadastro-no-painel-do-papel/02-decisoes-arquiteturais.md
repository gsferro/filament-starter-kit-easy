# Decisões Arquiteturais — fix: cadastro por convite cai no painel do papel

Nenhuma decisão passou nos três portões. As escolhas desta correção são reversíveis e estão em
`## Decisões de Desenho` do `01`; a regra de fundo (sem tela de escolha no modo desligado) continua
sendo a ADR-04 de `wikis/specs/fix/destino-apos-cadastro/`.

## Superfície Livewire

Sem página, widget ou componente novo. A mudança é numa resposta HTTP (`RegistrationResponse`),
chamada pelo `register()` das páginas de cadastro já existentes; nenhuma propriedade ou método
público novo alcançável pelo cliente.
