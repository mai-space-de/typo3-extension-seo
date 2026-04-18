class StructuredDataTreeEditor {
    #container;
    #input;
    #autoData;
    #config;

    constructor(container) {
        this.#container = container;
        this.#input = document.getElementById(container.id + '-input');
        this.#autoData = JSON.parse(container.dataset.auto || '{}');
        this.#config = JSON.parse(container.dataset.config || '{}');
        this.#init();
    }

    #init() {
        this.#renderPlaceholder();
    }

    #renderPlaceholder() {
        const wrapper = document.createElement('div');
        wrapper.className = 'maiseo-tree-placeholder';

        const label = document.createElement('p');
        label.className = 'maiseo-tree-placeholder__label';
        label.textContent = 'Structured Data Editor (coming in v1.2)';
        wrapper.appendChild(label);

        const preview = document.createElement('pre');
        preview.className = 'maiseo-tree-placeholder__preview';
        preview.textContent = JSON.stringify(this.#autoData, null, 2);
        wrapper.appendChild(preview);

        this.#container.appendChild(wrapper);
    }

    getValue() {
        return this.#input?.value ?? '';
    }

    setValue(value) {
        if (this.#input) {
            this.#input.value = value;
        }
    }
}

export function initialize() {
    document.querySelectorAll('.maiseo-structured-data-tree').forEach(el => {
        if (!el.dataset.initialized) {
            el.dataset.initialized = '1';
            new StructuredDataTreeEditor(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', initialize);
