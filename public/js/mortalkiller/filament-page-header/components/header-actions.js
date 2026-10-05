export function shouldKeepAction(compact, selected, interacting = false) {
    return !compact || selected || interacting;
}

/** Presentation of one native action tree, including its teleported panels. */
export class HeaderActionsController {
    constructor(root, onChange = () => {}) {
        this.root = root;
        this.document = root.ownerDocument;
        this.onChange = onChange;
        this.nodes = [];
        this.touched = new Set();
        this.panels = new Map();
        this.heldPanels = new Set();
        this.compact = false;
        this.destroyed = false;
        this.observer = new this.document.defaultView.MutationObserver(records => {
            if (records.some(record => record.attributeName !== 'style'
                || (this.panels.has(record.target) && this.panels.get(record.target) !== record.target.style.display))) {
                this.onChange();
            }
        });
        this.onFocus = event => {
            if (this.container?.contains(event.target)
                || [...this.panels.keys()].some(panel => panel.contains(event.target))) this.onChange();
        };
        this.document.addEventListener('focusin', this.onFocus);
        this.document.addEventListener('focusout', this.onFocus);
    }

    refresh() {
        if (this.destroyed) return;
        this.observer.disconnect();
        this.container = this.root.querySelector('[data-fph-actions]');
        const actions = this.container?.querySelector(':scope > .fi-ac');
        const visited = new Set();
        const panels = new Map();
        const read = element => {
            if (visited.has(element)) return null;
            visited.add(element);
            if (element.hasAttribute('data-fph-header-action')) return { element, action: true };
            if (element.matches('script, style, template, svg')) return null;
            if (element.matches('.fi-dropdown')) {
                const trigger = element.querySelector(':scope > .fi-dropdown-trigger [aria-controls]');
                const panel = this.document.getElementById(trigger?.getAttribute('aria-controls'))
                    ?? element.querySelector(':scope > [x-ref="panel"]');
                // The native component may not have finished its Alpine initialization yet.
                if (!panel) return { element, unknown: true };
                panels.set(panel, panel.style.display);
                const child = read(panel);
                return { element, panel, children: child ? [child] : [] };
            }
            if (element.matches('button, a, input, select, textarea, [tabindex]')) return { element, unknown: true };
            const children = [...element.children].map(read).filter(Boolean);
            return children.length || element.matches('.fi-ac, .fi-btn-group, .fi-dropdown-list, .fi-dropdown-panel')
                ? { element, children } : null;
        };
        this.nodes = actions ? [read(actions)].filter(Boolean) : [];
        if (this.container) visited.add(this.container);
        for (const element of this.touched) {
            if (visited.has(element)) continue;
            element.removeAttribute('data-fph-action-hidden');
            this.touched.delete(element);
        }
        for (const panel of this.heldPanels) {
            if (!panels.has(panel) || !this.isOpen(panel)) this.heldPanels.delete(panel);
        }
        this.panels = panels;
        const options = {
            childList: true, subtree: true, attributes: true,
            attributeFilter: ['aria-controls', 'aria-expanded', 'id', 'style', 'data-fph-action-compact', 'data-fph-header-action'],
        };
        if (this.container) this.observer.observe(this.container, options);
        for (const panel of panels.keys()) {
            if (!this.container?.contains(panel)) this.observer.observe(panel, options);
        }
    }

    isOpen(panel) {
        return panel.isConnected && panel.style.display === 'block';
    }

    setHidden(element, hidden) {
        this.touched.add(element);
        if (hidden) {
            if (element.getAttribute('data-fph-action-hidden') !== 'true') element.setAttribute('data-fph-action-hidden', 'true');
        } else {
            element.removeAttribute('data-fph-action-hidden');
        }
    }

    /** Temporary measurements must not consume the real interaction transition. */
    apply(compact, commit = false) {
        if (this.destroyed) return;
        const held = new Set([...this.heldPanels].filter(panel => this.isOpen(panel)));
        if (compact && !this.compact) {
            for (const panel of this.panels.keys()) {
                if (this.isOpen(panel)) held.add(panel);
            }
        }
        const visit = (node, interacting = false) => {
            let keep;
            if (node.action) {
                keep = shouldKeepAction(compact, node.element.dataset.fphActionCompact !== 'false',
                    interacting || node.element.contains(this.document.activeElement));
            } else if (node.unknown) {
                // A custom native action view is not silently discarded when its shape is unknown.
                keep = true;
            } else {
                const protect = interacting || (compact && held.has(node.panel));
                const children = node.children.map(child => visit(child, protect));
                keep = children.some(Boolean) || protect
                    || node.element.contains(this.document.activeElement);
            }
            this.setHidden(node.element, compact && !keep);
            return keep;
        };
        const visible = this.nodes.map(node => visit(node)).some(Boolean);
        if (this.container) this.setHidden(this.container,
            compact && !visible && this.container.dataset.fphActionsHasHooks !== 'true');
        if (commit) {
            this.compact = compact;
            this.heldPanels = compact ? held : new Set();
        }
    }

    destroy() {
        this.destroyed = true;
        this.observer.disconnect();
        this.document.removeEventListener('focusin', this.onFocus);
        this.document.removeEventListener('focusout', this.onFocus);
        for (const element of this.touched) element.removeAttribute('data-fph-action-hidden');
        this.touched.clear();
        this.panels.clear();
        this.heldPanels.clear();
        this.nodes = [];
    }
}
