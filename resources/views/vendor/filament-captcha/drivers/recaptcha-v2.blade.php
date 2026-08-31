{{--
    View publicada do ddr/filament-captcha (vendor/ddr/filament-captcha/resources/views/drivers/recaptcha-v2.blade.php).

    Tres acrescimos do kit, e nada mais: `theme` segue a classe `dark` do <html>; o id do widget
    fica guardado; e `redefinir()` e chamado pelo evento que CampoAntiRobo despacha depois de cada
    verificacao (o token e de uso unico). ADR-05 da wiki adotar-ddr-filament-captcha.

    ATENCAO ao editar: comentario de blade NAO protege diretiva. Ver .ai/rules/views.md.
--}}
<div
  class="flex justify-center"
  wire:ignore
  x-data="{
    response: $wire.$entangle('{{ $getStatePath() }}'),
    widgetId: null,
    init() {
      if (typeof grecaptcha === 'undefined') {
        const script = document.createElement('script')
        script.src = '{{ $getScriptUrl() }}'
        script.async = true
        script.defer = true
        script.onload = () => {
          grecaptcha.ready(() => {
            this.renderCaptcha()
          })
        }
        document.head.appendChild(script)
      } else {
        grecaptcha.ready(() => {
          this.renderCaptcha()
        })
      }
    },
    renderCaptcha() {
      this.widgetId = grecaptcha.render(this.$refs.captcha, {
        sitekey: '{{ $getSiteKey() }}',
        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        callback: (token) => {
          this.response = token
        },
        'expired-callback': () => {
          this.response = ''
        },
        'error-callback': () => {
          this.response = ''
        },
      })
    },
    redefinir() {
      if (this.widgetId === null) {
        return
      }

      grecaptcha.reset(this.widgetId)
      this.response = ''
    },
  }"
  x-on:{{ \App\Filament\Forms\Components\CampoAntiRobo::EVENTO_REDEFINIR }}.window="redefinir()"
>
  <div x-ref="captcha"></div>
</div>
