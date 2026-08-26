{{--
    Bloco do perfil que manda o link de definição de senha por e-mail. Vive logo abaixo do
    bloco "Senha" do Breezy porque é a alternativa a ele: quem entrou por login social não tem
    senha atual para digitar. Ver o docblock de App\Livewire\DefinirSenhaPorEmail.
--}}
<x-filament::section
    :aside="true"
    heading="Definir senha por e-mail"
    description="Não tem senha ou não lembra a atual — quem entrou por login social, por exemplo. Enviamos um link para o seu e-mail; ao abri-lo você define uma senha nova. Com ela dá para trocar a senha aqui, ligar a autenticação de 2 fatores e desbloquear a sessão."
>
    <form wire:submit.prevent="enviar" class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Ao pedir o link a sua sessão termina, porque a página que define a senha só abre para quem está fora.
        </p>

        <div class="text-right">
            <x-filament::button type="submit" color="gray" wire:loading.attr="disabled">
                Receber link por e-mail
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
