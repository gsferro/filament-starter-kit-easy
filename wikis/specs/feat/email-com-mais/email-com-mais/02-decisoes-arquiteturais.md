# Decisões Arquiteturais — Permitir ou bloquear o "+" no e-mail do cadastro

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

- `App\Livewire\InformacoesPessoais` (novo): estende `Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo`, sem propriedade pública nova; só sobrescreve o método protegido `getEmailComponent()`. O estado público é o do pai (`$data`), que o usuário logado edita sobre a própria conta
- As páginas e resources tocados (`RegistroPorConvite`, `ConfiguracoesDoKit`, os `UserResource`, os formulários de convite) ganham regra de validação, sem propriedade pública nova
