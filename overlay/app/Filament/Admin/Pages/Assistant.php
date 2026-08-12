<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Prism\Prism\Prism;
use Throwable;

/**
 * Assistente de IA mínimo usando Prism (https://prismphp.com).
 * Configure PRISM_AI_PROVIDER / PRISM_AI_MODEL e a API key do provedor no .env.
 */
class Assistant extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $title = 'Assistente IA';

    protected string $view = 'filament.admin.pages.assistant';

    public string $prompt = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public bool $loading = false;

    public function send(): void
    {
        $prompt = trim($this->prompt);

        if ($prompt === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $prompt];
        $this->prompt = '';

        try {
            $response = Prism::text()
                ->using(
                    config('kit.ai.provider', 'openai'),
                    config('kit.ai.model', 'gpt-4o-mini'),
                )
                ->withSystemPrompt('Você é o assistente interno desta aplicação. Responda de forma objetiva, em português.')
                ->withPrompt($prompt)
                ->asText();

            $this->messages[] = ['role' => 'assistant', 'content' => $response->text];
        } catch (Throwable $e) {
            Notification::make()
                ->title('Erro ao consultar o provedor de IA')
                ->body('Verifique as variáveis PRISM_AI_PROVIDER, PRISM_AI_MODEL e a API key no .env. Detalhe: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
