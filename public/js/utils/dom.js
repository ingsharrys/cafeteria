/**
 * dom.js
 * Helpers para manipulación del DOM
 * 
 * UBICACIÓN: public/js/utils/dom.js
 */

export const DOM = {
    /**
     * Selector simple
     */
    $(selector) {
        return document.querySelector(selector);
    },

    /**
     * Selector múltiple
     */
    $$(selector) {
        return document.querySelectorAll(selector);
    },

    /**
     * Crear elemento
     */
    create(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'class') {
                element.className = value;
            } else if (key === 'dataset') {
                Object.entries(value).forEach(([dataKey, dataValue]) => {
                    element.dataset[dataKey] = dataValue;
                });
            } else {
                element.setAttribute(key, value);
            }
        });

        if (content) {
            element.innerHTML = content;
        }

        return element;
    },

    /**
     * Limpiar contenido
     */
    clear(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.innerHTML = '';
        }
    },

    /**
     * Mostrar elemento
     */
    show(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.style.display = '';
        }
    },

    /**
     * Ocultar elemento
     */
    hide(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.style.display = 'none';
        }
    },

    /**
     * Toggle visibilidad
     */
    toggle(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            if (element.style.display === 'none') {
                this.show(element);
            } else {
                this.hide(element);
            }
        }
    },

    /**
     * Agregar clase
     */
    addClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.add(className);
        }
    },

    /**
     * Remover clase
     */
    removeClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.remove(className);
        }
    },

    /**
     * Toggle clase
     */
    toggleClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.toggle(className);
        }
    }
};

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.DOM = DOM;
}