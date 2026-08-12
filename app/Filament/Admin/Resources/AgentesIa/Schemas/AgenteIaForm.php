<?php

namespace App\Filament\Admin\Resources\AgentesIa\Schemas;

use App\Ai\Guardrails\GuardrailRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Form do paper do agente, em três seções nomeadas pela decisão que se toma em cada uma:
 * quem é o agente, como ele executa, e o que o contém.
 *
 * O system prompt é o campo mais editado do formulário — por isso ocupa a largura inteira
 * com 14 linhas, em vez de dividir espaço com os parâmetros numéricos.
 */
class AgenteIaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('Como o agente é reconhecido pelo sistema e por quem o mantém.')
                    ->columns(2)
                    ->components([
                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Chave estável lida pela classe do agente (ex.: assistente). Mudar quebra o vínculo com o código.')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),
                        Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->helperText('Desligar é a "exclusão" do agente — o consumidor degrada com mensagem honesta.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Modelo e execução')
                    ->description('Parâmetros de runtime. Vazio = default de config/ai.php (env AI_PROVIDER).')
                    ->columns(3)
                    ->components([
                        Select::make('provider')
                            ->label('Provider')
                            // Opções vindas do próprio config: o painel nunca oferece um provider
                            // que a aplicação não sabe resolver.
                            ->options(fn (): array => collect(array_keys(config('ai.providers', [])))
                                ->mapWithKeys(fn (string $chave): array => [$chave => $chave])
                                ->all())
                            ->searchable()
                            ->placeholder('Default da aplicação'),
                        TextInput::make('modelo')
                            ->label('Modelo')
                            ->helperText('Vazio = default do provider.')
                            ->maxLength(120),
                        TextInput::make('temperatura')
                            ->label('Temperatura')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->step(0.1),
                        TextInput::make('max_tokens')
                            ->label('Max tokens')
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                        TextInput::make('versao')
                            ->label('Versão do paper')
                            ->helperText('Incremente a cada mudança nas instruções — é o que aparece na auditoria.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ]),

                Section::make('Comportamento')
                    ->description('O que o agente pode fazer e o que o contém. Sem guardrail o agente não sobe.')
                    ->columns(2)
                    ->components([
                        Textarea::make('instrucoes')
                            ->label('Instruções (system prompt)')
                            ->rows(14)
                            ->required()
                            ->columnSpanFull(),
                        TagsInput::make('tools')
                            ->label('Tools (allowlist)')
                            // Texto livre de propósito: as chaves são definidas no mapa de
                            // fábricas do agente (App\Ai\Agents\Assistente::tools()), que é
                            // código do projeto, não do kit.
                            ->helperText('Chaves das tools liberadas, conforme o mapa de fábricas do agente.'),
                        Select::make('guardrails')
                            ->label('Guardrails')
                            ->multiple()
                            // Só o que o GuardrailRegistry sabe resolver: nome desconhecido
                            // derrubaria o agente na primeira execução.
                            ->options(fn (): array => collect(array_keys(GuardrailRegistry::MAPA))
                                ->mapWithKeys(fn (string $chave): array => [$chave => $chave])
                                ->all())
                            ->helperText('Obrigatório: agente sem guardrail é bloqueado na subida. A ordem da lista é a ordem de execução.'),
                    ]),
            ]);
    }
}
