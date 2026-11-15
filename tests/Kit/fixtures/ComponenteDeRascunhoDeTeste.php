<?php

namespace Tests\Kit\fixtures;

use Livewire\Component;

/** Componente mínimo: dá um id de Livewire para gravar rascunho pela costura pública do editor. */
class ComponenteDeRascunhoDeTeste extends Component
{
    public function render(): string
    {
        return '<div></div>';
    }
}
