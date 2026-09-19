const modes = new Set(['normal', 'sticky', 'compact']);
const controllers = new WeakMap();

export function resolveMode(options, width) {
    let mode = modes.has(options.mode) ? options.mode : 'normal';
    for (const breakpoint of [...(options.breakpoints ?? [])].sort((a, b) => a.minWidth - b.minWidth)) {
        if (width >= breakpoint.minWidth && modes.has(breakpoint.mode)) mode = breakpoint.mode;
    }
    if (Number.isFinite(options.compactBelow) && width < options.compactBelow) return 'compact';
    return mode;
}

export function resolveOffset(options, topbarBottoms, boundaryTop) {
    if (Number.isFinite(options.offset)) return Math.max(0, options.offset);
    return Math.max(0, ...topbarBottoms.map((bottom) => bottom - boundaryTop));
}

export function shouldStick(top, boundary, scrollTop, mode) {
    return mode !== 'normal' && scrollTop > 0 && top <= boundary + 1;
}

function scrollContainer(element, window) {
    for (let parent = element.parentElement; parent && parent !== element.ownerDocument.body; parent = parent.parentElement) {
        if (/(auto|scroll|hidden|overlay)/.test(window.getComputedStyle(parent).overflowY)) return parent;
    }
    return window;
}

/** One controller per DOM header; no framework state or network calls. */
export class HeaderController {
    constructor(root, options = {}) {
        this.root = root;
        this.header = root.querySelector('[data-fph-header]');
        this.document = root.ownerDocument;
        this.window = this.document.defaultView;
        this.options = options;
        this.frame = null;
        this.needsMeasure = true;
        this.measurementPending = false;
        this.destroyed = false;
        this.cleanups = [];
        this.expandedHeight = 0;
        this.compactHeight = 0;
        this.originalHeight = root.style.height;
        this.originalOffset = root.style.getPropertyValue('--fph-offset');
    }

    start() {
        if (!this.header || this.destroyed) return;
        controllers.get(this.root)?.destroy();
        controllers.set(this.root, this);
        this.scrollParent = scrollContainer(this.root, this.window);
        this.listen(this.scrollParent, 'scroll', () => this.schedule(), { passive: true });
        if (this.scrollParent !== this.window) this.listen(this.window, 'scroll', () => this.schedule(), { passive: true });
        this.listen(this.window, 'resize', () => this.schedule(true), { passive: true });
        this.listen(this.window.visualViewport, 'resize', () => this.schedule(true), { passive: true });
        this.listen(this.document, 'livewire:navigated', () => this.schedule(true));
        this.listen(this.document, 'focusin', (event) => {
            this.schedule();
            this.window.requestAnimationFrame(() => this.revealTarget(event.target));
        });
        this.listen(this.window, 'hashchange', () => {
            let id;
            try { id = decodeURIComponent(this.window.location.hash.slice(1)); } catch { return; }
            this.window.requestAnimationFrame(() => this.revealTarget(this.document.getElementById(id)));
        });

        if (this.window.ResizeObserver) {
            this.resizeObserver = new this.window.ResizeObserver(() => this.schedule(true));
            this.resizeObserver.observe(this.header);
            this.resizeObserver.observe(this.root.parentElement);
            for (const topbar of this.topbars()) this.resizeObserver.observe(topbar);
        }
        this.mutationObserver = new this.window.MutationObserver(() => this.schedule(true));
        this.mutationObserver.observe(this.header, {
            childList: true, characterData: true, subtree: true, attributes: true,
            attributeFilter: ['data-fph-exclude-compact', 'data-fph-hide-compact'],
        });
        this.optionsObserver = new this.window.MutationObserver(() => {
            try { this.options = JSON.parse(this.root.dataset.fphOptions ?? '{}'); } catch { return; }
            this.schedule(true);
        });
        this.optionsObserver.observe(this.root, { attributes: true, attributeFilter: ['data-fph-options'] });
        this.document.fonts?.ready.then(() => this.schedule(true));
        this.schedule(true);
    }

    listen(target, event, callback, options) {
        if (!target) return;
        target.addEventListener(event, callback, options);
        this.cleanups.push(() => target.removeEventListener(event, callback, options));
    }

    topbars() {
        if (!this.options.topbarSelector) return [];
        try { return [...this.document.querySelectorAll(this.options.topbarSelector)]; } catch { return []; }
    }

    schedule(measure = false) {
        if (this.destroyed) return;
        this.needsMeasure ||= measure;
        if (this.frame !== null) return;
        this.frame = this.window.requestAnimationFrame(() => {
            this.frame = null;
            this.update();
        });
    }

    measureWhenIdle() {
        const transitions = this.header.getAnimations().filter(animation =>
            animation instanceof this.window.CSSTransition && animation.playState === 'running');
        if (transitions.length === 0) {
            this.measure();
            return;
        }
        if (this.measurementPending) return;
        this.measurementPending = true;
        Promise.allSettled(transitions.map(transition => transition.finished)).then(() => {
            if (this.destroyed) return;
            this.measurementPending = false;
            this.schedule(true);
        });
    }

    measure() {
        const compact = this.root.dataset.fphCompact;
        this.root.dataset.fphMeasuring = 'true';
        this.root.dataset.fphCompact = 'false';
        this.updateMetadataDividers();
        this.expandedHeight = Math.ceil(this.header.getBoundingClientRect().height);
        this.root.dataset.fphCompact = 'true';
        this.updateMetadataDividers(true);
        this.compactHeight = Math.ceil(this.header.getBoundingClientRect().height);
        this.root.dataset.fphCompact = compact ?? 'false';
        // Commit the restored layout while transitions are still disabled.
        this.header.getBoundingClientRect();
        delete this.root.dataset.fphMeasuring;
        this.needsMeasure = false;
    }

    updateMetadataDividers(compact = false) {
        for (const schema of this.header.querySelectorAll('.fph-metadata > .fi-sc, .fph-summary > .fi-sc')) {
            let previousTop = null;
            for (const field of schema.children) {
                if (!field.classList.contains('fi-grid-col')) continue;
                const rect = field.getBoundingClientRect();
                const visible = rect.width > 0 && rect.height > 0;
                const divider = visible && previousTop !== null && Math.abs(rect.top - previousTop) <= 1;
                field.dataset[compact ? 'fphDividerCompact' : 'fphDivider'] = String(divider);
                if (visible) previousTop = rect.top;
            }
        }
    }

    update() {
        if (this.destroyed) return;
        if (!this.root.isConnected) { this.destroy(); return; }
        if (this.needsMeasure) this.measureWhenIdle();

        // Sticky children stop at the inner padded edge of a nested scrollport.
        const boundaryTop = this.scrollParent === this.window ? 0
            : this.scrollParent.getBoundingClientRect().top + this.scrollParent.clientTop
                + (parseFloat(this.window.getComputedStyle(this.scrollParent).paddingTop) || 0);
        const bottoms = this.topbars().filter((element) => element.getClientRects().length > 0).map((element) => element.getBoundingClientRect().bottom);
        const offset = resolveOffset(this.options, bottoms, boundaryTop);
        let mode = resolveMode(this.options, this.window.innerWidth);
        const available = Math.min(this.window.visualViewport?.height ?? this.window.innerHeight, this.scrollParent === this.window ? Infinity : this.scrollParent.clientHeight) - offset;
        const pinnedHeight = mode === 'compact' ? this.compactHeight : this.expandedHeight;

        // A virtual keyboard or unusually tall header must never trap the page.
        if (pinnedHeight > Math.max(0, available) * 0.6) mode = 'normal';
        this.root.dataset.fphMode = mode;
        this.root.style.setProperty('--fph-offset', `${offset}px`);
        this.root.style.height = mode === 'normal' ? this.originalHeight : `${this.expandedHeight}px`;
        const scrollTop = this.scrollParent === this.window ? this.window.scrollY : this.scrollParent.scrollTop;
        const stuck = shouldStick(this.root.getBoundingClientRect().top, boundaryTop + offset, scrollTop, mode);
        this.root.dataset.fphStuck = String(stuck);
        this.root.dataset.fphCompact = String(stuck && mode === 'compact');
        this.root.dataset.fphReady = 'true';
    }

    revealTarget(target) {
        if (this.destroyed || this.root.dataset.fphStuck !== 'true' || !(target instanceof this.window.Element) || this.root.contains(target)) return;
        const pageContent = this.root.closest('.fi-page') ?? this.root.parentElement;
        // Panel chrome and unrelated pages must not move when they receive focus.
        if (!pageContent?.contains(target)) return;
        if (this.scrollParent !== this.window && !this.scrollParent.contains(target)) return;
        if (target.closest('[role="dialog"], [role="alertdialog"], .fi-modal, [data-fph-root]')) return;
        const rect = target.getBoundingClientRect();
        const bottom = this.header.getBoundingClientRect().bottom + 8;
        if (rect.top >= bottom || rect.bottom < 0) return;
        this.scrollParent.scrollBy({ top: rect.top - bottom, behavior: 'instant' });
    }

    destroy() {
        if (this.destroyed) return;
        this.destroyed = true;
        if (this.frame !== null) this.window.cancelAnimationFrame(this.frame);
        this.resizeObserver?.disconnect();
        this.mutationObserver?.disconnect();
        this.optionsObserver?.disconnect();
        this.cleanups.forEach((cleanup) => cleanup());
        this.cleanups = [];
        this.header?.querySelectorAll('[data-fph-divider], [data-fph-divider-compact]').forEach(field => {
            delete field.dataset.fphDivider;
            delete field.dataset.fphDividerCompact;
        });
        this.root.style.height = this.originalHeight;
        if (this.originalOffset) this.root.style.setProperty('--fph-offset', this.originalOffset);
        else this.root.style.removeProperty('--fph-offset');
        for (const key of ['fphMode', 'fphStuck', 'fphCompact', 'fphReady', 'fphMeasuring']) delete this.root.dataset[key];
        if (controllers.get(this.root) === this) controllers.delete(this.root);
    }
}

export default function pageHeader(options = {}) {
    let controller;
    let disposed = false;
    return {
        init() {
            this.$nextTick(() => {
                if (disposed || !this.$el.isConnected) return;
                controller = new HeaderController(this.$el, options);
                controller.start();
            });
        },
        destroy() {
            disposed = true;
            controller?.destroy();
        },
    };
}
