{{--
    A seção "Aparência" do Meu perfil. Ver o docblock de App\Livewire\PreferenciasDeAparencia
    e a wiki feat/preferencias-de-aparencia.
--}}
<x-filament::section
    :aside="true"
    heading="Aparência"
    description="Densidade, fonte, cor e menu só para você, em todos os painéis. Cada campo em branco segue o padrão da aplicação."
>
    <form wire:submit.prevent="salvar" class="space-y-6">
        {{ $this->form }}

        @if (config('kit.tenancy.enabled'))
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Dentro de uma organização, a sua escolha só vale se a organização liberar a aparência pessoal. Nas que não liberam, você vê a aparência dela.
            </p>
        @endif

        <div class="text-right">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Salvar aparência
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
