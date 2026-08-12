<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Pacotes Composer instalados</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-white/10">
                        <th class="py-2 font-semibold">Pacote</th>
                        <th class="py-2 font-semibold">Versão</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getPackages() as $package => $version)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 font-mono">{{ $package }}</td>
                            <td class="py-2 font-mono text-gray-500 dark:text-gray-400">{{ $version }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
