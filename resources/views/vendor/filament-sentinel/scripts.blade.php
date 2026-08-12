{{-- Intercepts failed Livewire (panel) requests and shows the branded 500 as a
     small window over the real page, instead of Livewire's default dark modal. --}}
<script>
    (function () {
        if (window.__filamentSentinel) {
            return;
        }
        window.__filamentSentinel = true;

        function showOverlay(content) {
            var doc = new DOMParser().parseFromString(content, 'text/html');
            var win = doc.querySelector('.sn5-dock');

            if (! win) {
                return false;
            }

            var styles = Array.prototype.filter
                .call(doc.querySelectorAll('style'), function (s) {
                    return s.textContent.indexOf('.sn5-') !== -1;
                })
                .map(function (s) { return s.textContent; })
                .join('\n');

            var existing = document.getElementById('sentinel-overlay');
            if (existing) {
                existing.remove();
            }

            var backdrop = document.createElement('div');
            backdrop.id = 'sentinel-overlay';
            // Transparent: the real page stays fully visible behind the window;
            // the backdrop only exists to catch a click-outside to dismiss.
            backdrop.setAttribute('style', 'position:fixed;inset:0;z-index:2147483000;background:transparent;animation:sn-overlay-in .15s ease-out');

            var styleTag = document.createElement('style');
            styleTag.textContent = styles + '@keyframes sn-overlay-in{from{opacity:0}to{opacity:1}}';
            backdrop.appendChild(styleTag);
            backdrop.appendChild(win);
            document.body.appendChild(backdrop);

            function close() {
                backdrop.remove();
                document.removeEventListener('keydown', onKeydown);
            }

            function onKeydown(event) {
                if (event.key === 'Escape') {
                    close();
                }
            }

            backdrop.addEventListener('click', function (event) {
                if (! win.contains(event.target)) {
                    close();
                }
            });
            document.addEventListener('keydown', onKeydown);

            var dismiss = win.querySelector('[data-sentinel="dismiss"]');
            if (dismiss) {
                dismiss.addEventListener('click', function (event) {
                    event.preventDefault();
                    close();
                });
            }

            var reload = win.querySelector('[data-sentinel="reload"]');
            if (reload) {
                reload.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.location.reload();
                });
            }

            return true;
        }

        document.addEventListener('livewire:init', function () {
            if (! window.Livewire || ! window.Livewire.hook) {
                return;
            }

            window.Livewire.hook('request', function (context) {
                if (! context.fail) {
                    return;
                }

                context.fail(function (payload) {
                    if (payload.status !== 500 || typeof payload.content !== 'string') {
                        return;
                    }

                    // Window style: show the branded 500 as a small dismissible
                    // window over the still-visible page.
                    if (payload.content.indexOf('sn5-dock') !== -1) {
                        if (showOverlay(payload.content)) {
                            payload.preventDefault();
                        }
                        return;
                    }

                    // Page style: the 500 is a full page, so let it take over the
                    // document — exactly like landing on a real error page.
                    if (payload.content.indexOf('sn-card-box') !== -1) {
                        payload.preventDefault();
                        document.open();
                        document.write(payload.content);
                        document.close();
                    }
                });
            });
        });
    })();
</script>
