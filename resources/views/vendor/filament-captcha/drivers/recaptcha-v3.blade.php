{{--
    View publicada do ddr/filament-captcha (vendor/ddr/filament-captcha/resources/views/drivers/recaptcha-v3.blade.php).

    O v3 nao tem caixa: o script gera um token sozinho e o servidor compara a pontuacao com o
    limiar. Um acrescimo do kit: `redefinir()` pede um token NOVO quando CampoAntiRobo despacha o
    evento depois de cada verificacao — o token do v3 tambem e de uso unico (e expira em 2 min).
    Sem `theme`: o badge do v3 nao aceita tema. ADR-05 da wiki adotar-ddr-filament-captcha.

    ATENCAO ao editar: comentario de blade NAO protege diretiva. Ver .ai/rules/views.md.
--}}
<div
  wire:ignore
  x-data="{
    response: $wire.$entangle('{{ $getStatePath() }}'),
    siteKey: '{{ $getSiteKey() }}',
    init() {
      if (typeof grecaptcha === 'undefined') {
        const script = document.createElement('script')
        script.src = '{{ $getScriptUrl() }}'
        script.async = true
        script.defer = true
        script.onload = () => {
          grecaptcha.ready(() => {
            this.executeRecaptcha()
          })
        }
        document.head.appendChild(script)
      } else {
        grecaptcha.ready(() => {
          this.executeRecaptcha()
        })
      }
    },
    executeRecaptcha() {
      grecaptcha.execute(this.siteKey, { action: 'submit' }).then((token) => {
        this.response = token
      })
    },
    redefinir() {
      this.response = ''

      if (typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(() => {
          this.executeRecaptcha()
        })
      }
    },
  }"
  x-on:{{ \App\Filament\Forms\Components\CampoAntiRobo::EVENTO_REDEFINIR }}.window="redefinir()"
>
  <input type="hidden" x-model="response" />
</div>
