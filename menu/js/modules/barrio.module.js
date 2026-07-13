/**
 * barrio.module.js - Módulo de Barrios con Select2
 * Ubicación: /menu/js/modules/barrio.module.js
 * - Muestra barrios activos
 * - Envía id_barrio (no nombre)
 * - Diseño mejorado
 */

const BarrioModule = {
    barrios: [],
    barrioMap: {}, // Mapeo nombre → id_barrio
    selectInitialized: false,

    /**
     * Inicializar
     */
    init: function() {
        if (typeof jQuery === 'undefined') {
            Utils.log('Esperando jQuery...', 'warning');
            setTimeout(() => this.init(), 500);
            return;
        }

        if (typeof jQuery.fn.select2 === 'undefined') {
            Utils.log('Esperando Select2...', 'warning');
            setTimeout(() => this.init(), 500);
            return;
        }

        this.loadBarrios();
    },

    /**
     * Cargar barrios desde API
     */
    loadBarrios: function() {
        fetch('/menu/api/get_barrios.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.barrios = data.data;
                    
                    // Crear mapeo nombre → id para búsqueda inversa
                    this.barrios.forEach(barrio => {
                        this.barrioMap[barrio.nombre_barrio] = barrio.id_barrio;
                    });
                    
                    Utils.log(`Cargados ${data.count} barrios desde API`, 'success');
                    this.populateSelect();
                    
                    setTimeout(() => {
                        this.initializeSelect2();
                    }, 100);
                }
            })
            .catch(error => {
                Utils.log('Error cargando barrios: ' + error, 'error');
                console.error(error);
            });
    },

    /**
     * Llenar el SELECT con opciones
     */
    populateSelect: function() {
        const $select = jQuery('#customerBarrio');
        
        if ($select.length === 0) {
            Utils.log('Campo #customerBarrio no encontrado', 'warning');
            return;
        }

        const savedBarrio = localStorage.getItem('customerBarrio');

        // Limpiar opciones
        $select.find('option:not(:first)').remove();

        // Agregar opciones
        this.barrios.forEach(barrio => {
            const isSelected = savedBarrio === barrio.nombre_barrio;
            const option = jQuery('<option></option>')
                // Guardamos el id_barrio como value
                .attr('value', barrio.nombre_barrio)
                .attr('data-barrio-name', barrio.nombre_barrio)
                .text(barrio.nombre_barrio)
                .prop('selected', isSelected);
            
            $select.append(option);
        });

        Utils.log('Select poblado con ' + this.barrios.length + ' barrios', 'success');
    },

    /**
     * Inicializar Select2 con diseño mejorado
     */
    initializeSelect2: function() {
        const $select = jQuery('#customerBarrio');

        if ($select.length === 0) {
            Utils.log('Campo #customerBarrio no encontrado', 'warning');
            return;
        }

        if (this.selectInitialized) {
            try {
                $select.select2('destroy');
            } catch (e) {
                // Ignorar
            }
        }

        try {
            $select.select2({
                placeholder: '🔍 Buscar barrio...',
                allowClear: true,
                width: '100%',
                language: 'es',
                minimumInputLength: 0,
                
                // Datos locales del select HTML existente
                data: this.barrios.map(barrio => ({
                    id: barrio.id_barrio,      // Enviar id
                    text: barrio.nombre_barrio  // Mostrar nombre
                })),
                
                // Clase CSS personalizada
                containerCss: {
                    'width': '100%'
                },
                
                // Opciones de búsqueda
                matcher: this.matchCustom.bind(this),
                
                // Template personalizado
                templateResult: this.formatOption.bind(this),
                templateSelection: this.formatSelection.bind(this),
                
                // Estilos mejorados
                dropdownCssClass: 'barrio-dropdown',
                selectionCssClass: 'barrio-selection'
            });

            // Agregar estilos CSS dinámicos
            this.addCustomStyles();

            this.selectInitialized = true;
            Utils.log('✅ Select2 inicializado con diseño mejorado', 'success');
            
            $select.trigger('change');

        } catch (error) {
            Utils.log('Error al inicializar Select2: ' + error.message, 'error');
            console.error('Select2 Error:', error);
        }
    },

    /**
     * Agregar estilos CSS personalizados
     */
    addCustomStyles: function() {
        // Si ya se agregó, no agregar de nuevo
        if (document.getElementById('barrio-select2-styles')) {
            return;
        }

        const styles = document.createElement('style');
        styles.id = 'barrio-select2-styles';
        styles.textContent = `
            /* Select2 Container */
            .select2-container--default .select2-selection--single {
                height: 42px;
                padding: 6px 12px;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
                font-size: 1rem;
                background-color: #fff;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 30px;
                padding: 0;
                color: #333;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 42px;
                right: 8px;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow b {
                border-color: #666 transparent transparent transparent;
                border-width: 6px 4px 0 4px;
                top: 50%;
                margin-top: -2px;
            }

            /* Dropdown */
            .select2-container--default .select2-dropdown {
                border: 1px solid #ced4da;
                border-top: none;
            }

            .select2-container--default .select2-results__option {
                padding: 10px 12px;
                line-height: 1.5;
                font-size: 0.95rem;
            }

            .select2-container--default .select2-results__option--highlighted {
                background-color: #007bff;
            }

            .select2-container--default .select2-results__option[aria-selected=true] {
                background-color: #e7f3ff;
                color: #007bff;
            }

            /* Search box */
            .select2-container--default .select2-search--dropdown .select2-search__field {
                padding: 8px 12px;
                border: 1px solid #ddd;
                font-size: 0.95rem;
                height: 40px;
            }

            /* Focus state */
            .select2-container--default.select2-container--focus .select2-selection--single {
                border-color: #80bdff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            /* Custom classes */
            .barrio-dropdown {
                margin-top: 0 !important;
            }

            .barrio-selection {
                min-height: 42px;
            }

            /* Texto en el formulario */
            .select2-container--default .select2-selection--single {
                background-color: #fff;
            }
        `;

        document.head.appendChild(styles);
        Utils.log('Estilos CSS agregados para Select2', 'info');
    },

    /**
     * Función de búsqueda personalizada
     */
    matchCustom: function(params, data) {
        if (params.term === '') {
            return data;
        }

        const searchTerm = params.term.toLowerCase();
        const barrioName = data.text.toLowerCase();

        if (barrioName.includes(searchTerm)) {
            return data;
        }

        return null;
    },

    /**
     * Formatear opción en dropdown
     */
    formatOption: function(data) {
        if (!data.id) {
            return data.text;
        }
        return jQuery('<span>' + data.text + '</span>');
    },

    /**
     * Formatear selección mostrada
     */
    formatSelection: function(data) {
        if (!data.id) {
            return 'Seleccionar barrio...';
        }
        return data.text;
    },

    /**
     * Obtener id_barrio del valor seleccionado
     */
    getSelectedBarrioId: function() {
        const $select = jQuery('#customerBarrio');
        return $select.val(); // Retorna id_barrio
    },

    /**
     * Obtener nombre del barrio del id
     */
    getBarrioNameById: function(barrioId) {
        const barrio = this.barrios.find(b => b.id_barrio == barrioId);
        return barrio ? barrio.nombre_barrio : null;
    }
};

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Utils !== 'undefined') {
            BarrioModule.init();
        }
    });
} else {
    if (typeof Utils !== 'undefined') {
        BarrioModule.init();
    }
}