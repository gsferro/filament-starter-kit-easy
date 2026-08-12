@props(['text'])

<x-filament::button
    tag="button"
    type="button"
    color="gray"
    size="sm"
    icon="heroicon-m-clipboard-document"
    data-copy="{{ $text }}"
    data-copied="{{ __('sentinel::sentinel.labels.copied') }}"
    onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.copy); var l = this.querySelector('.sn-copy-lbl'); if (l) { var o = l.textContent; l.textContent = this.dataset.copied; setTimeout(function () { l.textContent = o; }, 1500); }"
>
    <span class="sn-copy-lbl">{{ __('sentinel::sentinel.labels.copy_detail') }}</span>
</x-filament::button>
