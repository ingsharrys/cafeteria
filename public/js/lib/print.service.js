/**
 * print_service.js - MEJORADO CON COMANDA PROFESIONAL + COMENTARIOS
 * Servicio de impresion - encapsula QZ Tray
 * 
 * CAMBIOS:
 * - Lee direccion, barrio y costo_domicilio de window.turnosData cuando tipo=50
 * - Captura y muestra comentarios del pedido en comanda y recibo
 * 
 * UBICACION: public/js/lib/print_service.js
 */

export class PrintService {
    constructor() {
        this.connected = false;
        this.qzAvailable = typeof qz !== 'undefined';
        this.apiBase = '../api.php?route=';
    }

    /**
     * Conectar a QZ Tray
     */
    async connect() {
        if (!this.qzAvailable) {
            console.warn('QZ Tray no disponible');
            return false;
        }

        if (this.connected) {
            return true;
        }

        try {
            await qz.websocket.connect({ host: 'localhost', secure: false });
            this.connected = true;
            console.log('Conectado a QZ Tray');
            return true;
        } catch (error) {
            console.error('Error conectando a QZ Tray:', error);
            return false;
        }
    }

    /**
     * Desconectar de QZ Tray
     */
    async disconnect() {
        if (!this.qzAvailable || !this.connected) {
            return;
        }

        try {
            await qz.websocket.disconnect();
            this.connected = false;
            console.log('Desconectado de QZ Tray');
        } catch (error) {
            console.error('Error desconectando:', error);
        }
    }

    /**
     * Imprimir ticket
     */
    async print(content, printerName = null) {
        console.log('\n%c=====================================', 'color: #3b82f6; font-weight: bold;');
        console.log('%cCONTENIDO A IMPRIMIR (RAW BYTES)', 'color: #3b82f6; font-weight: bold;');
        console.log('%c=====================================', 'color: #3b82f6; font-weight: bold;');
        console.log(content);
        console.log('%c=====================================\n', 'color: #3b82f6; font-weight: bold;');
        
        // Mostrar version legible en consola
        console.log('%cCONTENIDO A IMPRIMIR (LEGIBLE)', 'color: #10b981; font-weight: bold;');
        console.log('%c=====================================', 'color: #10b981; font-weight: bold;');
        const legible = this.convertirParaConsola(content);
        console.log(legible);
        console.log('%c=====================================\n', 'color: #10b981; font-weight: bold;');

        if (!this.qzAvailable) {
            console.warn('QZ Tray no disponible - mostrando en navegador');
            this.mostrarEnNavegador(content);
            return false;
        }

        try {
            // Asegurar conexion
            await this.connect();

            // Obtener impresora
            const printer = printerName || await qz.printers.getDefault();
            console.log('Impresora seleccionada: ' + printer);
            
            if (!printer) {
                throw new Error('No se encontro impresora');
            }

            // Configurar impresion
            const config = qz.configs.create(printer);
            const printData = [{ 
                type: 'raw', 
                format: 'plain', 
                data: content 
            }];

            // Imprimir
            await qz.print(config, printData);
            console.log('Impresion completada');
            return true;

        } catch (error) {
            console.error('Error al imprimir:', error);
            
            if (error.message && error.message.includes('Unable to establish connection')) {
                alert('QZ Tray no esta disponible.\n\nPara imprimir:\n1. Instala QZ Tray desde qz.io\n2. Inicia la aplicacion\n3. Intenta nuevamente');
                this.mostrarEnNavegador(content);
            }
            
            return false;
        }
    }

    /**
     * Convertir contenido para mostrar legible en consola
     */
    convertirParaConsola(content) {
        return content
            .replace(/\x1B\x40/g, '[RESET]')
            .replace(/\x1B\x61\x01/g, '[CENTER]')
            .replace(/\x1B\x61\x00/g, '[LEFT]')
            .replace(/\x1B\x21\x30/g, '[GRANDE]')
            .replace(/\x1B\x21\x20/g, '[BOLD]')
            .replace(/\x1B\x21\x00/g, '[NORMAL]')
            .replace(/\x1D\x56\x00/g, '[CORTE]')
            .replace(/\x1B\x70\x00\x19\xFA/g, '[CAJON]');
    }

    /**
     * Mostrar ticket en navegador (fallback)
     */
    mostrarEnNavegador(content) {
        const ventana = window.open('', 'Ticket', 'width=600,height=800');
        let html = '<pre style="font-family: "Courier New", monospace; white-space: pre; overflow-wrap: break-word; padding: 20px; font-size: 12px; line-height: 1.2;">';
        
        const legible = this.convertirParaConsola(content);
        html += legible.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        html += '</pre>';
        ventana.document.write(html);
        ventana.document.close();
        ventana.print();
    }

    /**
     * COMANDA PROFESIONAL - Formato optimizado para impresora termica
     */
    buildComandaProfesional(pedido, tipoSolicitud) {
        let content = "\x1B\x40"; // Reset
        
        // ENCABEZADO
        content += "\x1B\x61\x01"; // Centrar
        content += "\x1B\x21\x30"; // Ampliar
        content += "HEIYUBAI\n";
        content += "\x1B\x21\x00"; // Normal
        
        content += "=====================================\n";
        content += "\x1B\x21\x30"; // GRANDE
        content += "Pedido: " + pedido.numero_pedido + "\n";
        content += "Turno: " + (pedido.turno || 'N/A') + "\n";
        content += "Hora: " + this.getHora() + "\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "=====================================\n\n";
        
        // DATOS CLIENTE
        content += "\x1B\x61\x00"; // Alinear izquierda
        content += "\x1B\x21\x30"; // GRANDE
        if (pedido.cliente && pedido.cliente !== 'N/A') {
            content += "Cliente: " + pedido.cliente + "\n";
        }
        
        // Solo mostrar datos de domicilio si es tipo 50
        if (tipoSolicitud === '50') {
            if (pedido.direccion && pedido.direccion !== 'N/A' && pedido.direccion.trim() !== '') {
                content += "Direccion: " + pedido.direccion + "\n";
            }
            if (pedido.barrio && pedido.barrio !== 'N/A' && pedido.barrio.trim() !== '') {
                content += "Barrio: " + pedido.barrio + "\n";
            }
            if (pedido.telefono && pedido.telefono !== 'N/A') {
                content += "Telefono: " + pedido.telefono + "\n";
            }
            // 🎯 Mostrar costo domicilio si existe
            const costoDom = parseFloat(pedido.costo_domicilio || 0);
            if (costoDom > 0) {
                content += "\n";
                content += "Costo Domicilio: $" + Math.round(costoDom) + "\n";
                console.log('✅ Costo domicilio mostrado en comanda: $' + Math.round(costoDom));
            }
        }
        
        content += "\x1B\x21\x00"; // NORMAL
        content += "\n";
        
        // 🎯 COMENTARIOS EN COMANDA
        if (pedido.comentarios && pedido.comentarios.trim() !== '') {
            content += "\x1B\x21\x20"; // NEGRITA
            content += "NOTAS:\n";
            content += "\x1B\x21\x00"; // NORMAL
            content += pedido.comentarios + "\n";
            content += "\n";
            console.log('✅ Comentarios mostrados en comanda: "' + pedido.comentarios + '"');
        }
        
        // SEPARADOR
        content += "-------------------------------------\n";
        
        // PRODUCTOS EN TABLA PROFESIONAL
        content += "\x1B\x21\x30"; // GRANDE
        content += "ARTICULOS:\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "-------------------------------------\n";
        content += "\x1B\x21\x20"; // NEGRITA (un poco más grande que normal)

        let totalCalculado = 0;

        if (pedido.productos && pedido.productos.length > 0) {
            pedido.productos.forEach((p, idx) => {
                const nombre = (p.nombre_producto || p.nombre || 'Producto'); // Nombre completo
                const cantidad = parseInt(p.cantidad) || 1;
                const precio = parseFloat(p.precio) || 0;
                const subtotal = cantidad * precio;
                totalCalculado += subtotal;
                const tipo = (p.tipo_prod || p.tipo_prod || '').substring(0, 10);
                
                // Linea del producto con precio
                content += cantidad + "x [" + tipo + "] " + nombre + " $" + Math.round(subtotal) + "\n";
                
                // Detalle
                if (p.detalle && p.detalle.trim()) {
                    content += "   -> " + p.detalle + "\n";
                }
                
                // Línea separadora entre productos
                content += "-------------------------------------\n";
            });
        } else {
            content += "SIN PRODUCTOS\n\n";
        }

        content += "\x1B\x21\x00"; // NORMAL

        // TOTAL
        let totalFinal = pedido.total || totalCalculado || 0;
        
        // 🎯 Agregar costo de domicilio al total si es tipo 50
        if (tipoSolicitud === '50' && pedido.costo_domicilio && parseFloat(pedido.costo_domicilio) > 0) {
            console.log('🔍 Sumando costo domicilio:', pedido.costo_domicilio);
            totalFinal += parseFloat(pedido.costo_domicilio);
        }
        
        content += "\x1B\x21\x30"; // GRANDE
        content += "\x1B\x21\x20"; // Negrita
        content += "TOTAL A PAGAR: $" + Math.round(totalFinal) + "\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "-------------------------------------\n";
        
        // PIE
        content += "\x1B\x61\x01"; // Centrar
        content += "Fecha: " + pedido.fecha + "\n";
        content += "\x1B\x61\x00"; // Izquierda
        
        // PIE
        content += "\x1B\x61\x01"; // Centrar
        content += "\x1B\x21\x30"; // GRANDE
        content += "Preparar pedido!\n";
        content += "\x1B\x21\x00"; // NORMAL
        
        content += "\n\n\n";
        content += "\x1D\x56\x00"; // Corte
        content += "\x1B\x70\x00\x19\xFA"; // Abrir cajon

        return content;
    }

    /**
     * RECIBO PARA CLIENTE - Formato profesional
     */
    buildReciboProfesional(pedido, tipoSolicitud) {
        let content = "\x1B\x40"; // Reset
        
        // ENCABEZADO
        content += "\x1B\x61\x01"; // Centrar
        content += "\x1B\x21\x30"; // Ampliar
        content += "HEIYUBAI\n";
        content += "\x1B\x21\x00"; // Normal
        
        content += "=====================================\n";
        content += "\x1B\x21\x30"; // GRANDE
        content += "Pedido N: " + pedido.numero_pedido + "\n";
        content += "Turno: " + (pedido.turno || 'N/A') + "\n";
        content += "Fecha/Hora: " + pedido.fecha + "\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "=====================================\n\n";
        
        // DATOS
        content += "\x1B\x61\x00"; // Izquierda
        content += "\x1B\x21\x30"; // GRANDE
        content += "Cliente: " + (pedido.cliente || 'N/A') + "\n";
        
        // Solo mostrar datos de domicilio si es tipo 50
        if (tipoSolicitud === '50') {
            if (pedido.direccion && pedido.direccion !== 'N/A' && pedido.direccion.trim() !== '') {
                content += "Direccion: " + pedido.direccion + "\n";
            }
            if (pedido.barrio && pedido.barrio !== 'N/A' && pedido.barrio.trim() !== '') {
                content += "Barrio: " + pedido.barrio + "\n";
            }
            if (pedido.telefono && pedido.telefono !== 'N/A') {
                content += "Telefono: " + pedido.telefono + "\n";
            }
            // 🎯 Mostrar costo domicilio si existe
            const costoDom = parseFloat(pedido.costo_domicilio || 0);
            if (costoDom > 0) {
                content += "\n";
                content += "Costo Domicilio: $" + Math.round(costoDom) + "\n";
                console.log('✅ Costo domicilio mostrado en recibo: $' + Math.round(costoDom));
            }
        }
        
        content += "\x1B\x21\x00"; // NORMAL
        content += "\n";
        
        // 🎯 COMENTARIOS EN RECIBO
        if (pedido.comentarios && pedido.comentarios.trim() !== '') {
            content += "\x1B\x21\x20"; // NEGRITA
            content += "NOTAS:\n";
            content += "\x1B\x21\x00"; // NORMAL
            content += pedido.comentarios + "\n";
            content += "\n";
            console.log('✅ Comentarios mostrados en recibo: "' + pedido.comentarios + '"');
        }
        
        // SEPARADOR
        content += "-------------------------------------\n";
        content += "\x1B\x21\x30"; // GRANDE
        content += "ARTICULOS              CANT  PRECIO\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "-------------------------------------\n";
        content += "\x1B\x21\x20"; // NEGRITA (un poco más grande que normal)

        let totalCalculado = 0;

        if (pedido.productos && pedido.productos.length > 0) {
            pedido.productos.forEach((p, idx) => {
                const nombre = (p.nombre_producto || p.nombre || 'Producto'); // SIN .substring - nombre completo
                const cantidad = parseInt(p.cantidad) || 1;
                const precio = parseFloat(p.precio) || 0;
                const subtotal = cantidad * precio;
                totalCalculado += subtotal;
                
                // Formatear linea
                const nombrePad = nombre.padEnd(18);
                const cantPad = cantidad.toString().padStart(4);
                const precioPad = Math.round(subtotal).toString().padStart(10);
                
                content += nombrePad + cantPad + precioPad + "\n";
                
                // Detalle
                if (p.detalle && p.detalle.trim()) {
                    content += "   -> " + p.detalle + "\n";
                }
                
                // 🎯 AGREGAR LÍNEA SEPARADORA ENTRE PRODUCTOS
                content += "-------------------------------------\n";
            });
        } else {
            content += "SIN PRODUCTOS\n";
        }

        content += "\x1B\x21\x00"; // NORMAL

        // TOTAL
        let totalFinal = pedido.total || totalCalculado || 0;
        
        // Agregar costo de domicilio al total si es tipo 50
        if (tipoSolicitud === '50' && pedido.costo_domicilio && pedido.costo_domicilio > 0) {
            totalFinal += pedido.costo_domicilio;
        }
        
        content += "-------------------------------------\n";
        content += "\x1B\x21\x30"; // GRANDE
        content += "\x1B\x21\x20"; // Negrita
        content += "TOTAL A PAGAR:             $" + Math.round(totalFinal) + "\n";
        content += "\x1B\x21\x00"; // Normal
        content += "=====================================\n";
        
        // PIE
        content += "\x1B\x61\x01"; // Centrar
        content += "\x1B\x21\x30"; // GRANDE
        content += "Gracias por su compra!\n";
        content += "\x1B\x21\x00"; // NORMAL
        content += "\x1B\x61\x00"; // Izquierda
        
        content += "\n\n\n";
        content += "\x1D\x56\x00"; // Corte
        content += "\x1B\x70\x00\x19\xFA"; // Abrir cajon

        return content;
    }

    /**
     * Obtener hora actual
     */
    getHora() {
        const now = new Date();
        return now.toLocaleString('es-CO', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    /**
     * Imprimir comanda para cocina
     */
    async printComanda(numeroPedido) {
        console.log('\n%c=====================================', 'color: #ff6b6b; font-weight: bold; font-size: 14px;');
        console.log('%cIMPRIMIENDO COMANDA DE COCINA', 'color: #ff6b6b; font-weight: bold; font-size: 14px;');
        console.log('%c=====================================', 'color: #ff6b6b; font-weight: bold; font-size: 14px;');
        console.log('Numero de Pedido: ' + numeroPedido);
        console.log('%c=====================================\n', 'color: #ff6b6b; font-weight: bold;');
        
        // Obtener tipo de solicitud
        const tipoSolicitud = document.getElementById('tipoSolicitud')?.value || 'normal';
        
        // Obtener datos del pedido desde API
        const pedido = await this.getPedidoData(numeroPedido, 'turno');
        if (!pedido) {
            console.error('No se pudieron obtener datos del pedido');
            alert('Error: No se pudo obtener datos del pedido');
            return false;
        }

        // CAMBIO: Obtener numero del turno y otros datos de window.turnosData si existe
        if (window.turnosData && window.turnosData[numeroPedido]) {
            const turnoData = window.turnosData[numeroPedido];
            console.log('%c🔍 DATOS EN window.turnosData[' + numeroPedido + ']:', 'color: #ff6b6b; font-weight: bold;');
            console.log(turnoData);
            console.log('%c🔍 turno.turno =', 'color: #ff6b6b;', turnoData.turno);
            console.log('%c🔍 turno.numero_pedido =', 'color: #ff6b6b;', turnoData.numero_pedido);
            
            if (turnoData.turno) {
                pedido.turno = turnoData.turno;
                console.log('%c✅ Asignado pedido.turno =', 'color: #10b981;', pedido.turno);
            } else {
                console.log('%c❌ NO hay turno.turno en window.turnosData', 'color: #ff4757;');
            }
            
            if (turnoData.direccion) pedido.direccion = turnoData.direccion;
            if (turnoData.barrio) pedido.barrio = turnoData.barrio;
            if (turnoData.costo_domicilio) pedido.costo_domicilio = parseFloat(turnoData.costo_domicilio);
        } else {
            console.log('%c⚠️ window.turnosData[' + numeroPedido + '] NO EXISTE', 'color: #ff9800;');
        }

        // MOSTRAR DATOS DEL PEDIDO
        console.log('%cDATOS DEL PEDIDO', 'color: #3b82f6; font-weight: bold; font-size: 12px;');
        console.log('%c-------------------------------------', 'color: #3b82f6;');
        console.log('  Numero:  ' + pedido.numero_pedido);
        console.log('  Turno:   ' + (pedido.turno || 'N/A'));
        console.log('  Cliente: ' + pedido.cliente);
        console.log('  Comentarios: ' + (pedido.comentarios ? '"' + pedido.comentarios + '"' : '(sin comentarios)'));
        if (tipoSolicitud === '50') {
            console.log('  Direccion: ' + (pedido.direccion || 'No disponible'));
            console.log('  Barrio: ' + (pedido.barrio || 'No disponible'));
            console.log('  Telefono: ' + (pedido.telefono || 'N/A'));
            console.log('  Costo Domicilio: $' + (pedido.costo_domicilio || 0));
        }
        console.log('  Fecha:   ' + pedido.fecha);
        console.log('%c-------------------------------------\n', 'color: #3b82f6;');

        // MOSTRAR PRODUCTOS
        console.log('%cPRODUCTOS A IMPRIMIR', 'color: #10b981; font-weight: bold; font-size: 12px;');
        console.log('%c-------------------------------------', 'color: #10b981;');
        
        let totalCalculado = 0;

        if (pedido.productos && pedido.productos.length > 0) {
            console.log('  Total de articulos: ' + pedido.productos.length);
            
            pedido.productos.forEach((p, idx) => {
                const nombre = p.nombre_producto || p.nombre || 'Producto';
                const cantidad = parseInt(p.cantidad) || 1;
                const precio = parseFloat(p.precio) || 0;
                const subtotal = cantidad * precio;
                totalCalculado += subtotal;
                const tipo = p.tipo_producto || p.tipo_prod || 'N/A';
                
                console.log('  ' + (idx + 1) + '. [' + cantidad + 'x] ' + nombre + ' $' + Math.round(precio) + ' = $' + Math.round(subtotal));
                console.log('     Tipo: ' + tipo);
            });
        } else {
            console.log('  SIN PRODUCTOS');
        }

        // TOTAL
        let totalFinal = pedido.total || totalCalculado || 0;
        if (tipoSolicitud === '50' && pedido.costo_domicilio && pedido.costo_domicilio > 0) {
            totalFinal += pedido.costo_domicilio;
        }
        console.log('%c-------------------------------------', 'color: #10b981;');
        console.log('%cTOTAL A PAGAR: $' + Math.round(totalFinal), 'color: #22c55e; font-weight: bold; font-size: 14px;');
        console.log('%c-------------------------------------\n', 'color: #10b981;');

        // Construir comanda
        const content = this.buildComandaProfesional(pedido, tipoSolicitud);

        // Imprimir
        return await this.print(content);
    }

    /**
     * Imprimir recibo para cliente
     */
    async printRecibo(numeroPedido) {
        console.log('\n%c=====================================', 'color: #06b6d4; font-weight: bold; font-size: 14px;');
        console.log('%cIMPRIMIENDO RECIBO PARA CLIENTE', 'color: #06b6d4; font-weight: bold; font-size: 14px;');
        console.log('%c=====================================', 'color: #06b6d4; font-weight: bold; font-size: 14px;');
        console.log('Numero de Pedido: ' + numeroPedido);
        console.log('%c=====================================\n', 'color: #06b6d4; font-weight: bold;');
        
        // Obtener tipo de solicitud
        const tipoSolicitud = document.getElementById('tipoSolicitud')?.value || 'normal';
        
        // Obtener datos del pedido desde API
        const pedido = await this.getPedidoData(numeroPedido, 'mesa');
        if (!pedido) {
            console.error('No se pudieron obtener datos del pedido');
            alert('Error: No se pudo obtener datos del pedido');
            return false;
        }

        // CAMBIO: Obtener numero del turno y otros datos de window.turnosData si existe
        if (window.turnosData && window.turnosData[numeroPedido]) {
            const turnoData = window.turnosData[numeroPedido];
            console.log('%c🔍 DATOS EN window.turnosData[' + numeroPedido + ']:', 'color: #ff6b6b; font-weight: bold;');
            console.log(turnoData);
            console.log('%c🔍 turno.turno =', 'color: #ff6b6b;', turnoData.turno);
            console.log('%c🔍 turno.numero_pedido =', 'color: #ff6b6b;', turnoData.numero_pedido);
            
            if (turnoData.turno) {
                pedido.turno = turnoData.turno;
                console.log('%c✅ Asignado pedido.turno =', 'color: #10b981;', pedido.turno);
            } else {
                console.log('%c❌ NO hay turno.turno en window.turnosData', 'color: #ff4757;');
            }
            
            if (turnoData.direccion) pedido.direccion = turnoData.direccion;
            if (turnoData.barrio) pedido.barrio = turnoData.barrio;
            if (turnoData.costo_domicilio) pedido.costo_domicilio = parseFloat(turnoData.costo_domicilio);
        } else {
            console.log('%c⚠️ window.turnosData[' + numeroPedido + '] NO EXISTE', 'color: #ff9800;');
        }

        // MOSTRAR DATOS DEL PEDIDO
        console.log('%cDATOS DEL PEDIDO', 'color: #3b82f6; font-weight: bold; font-size: 12px;');
        console.log('%c-------------------------------------', 'color: #3b82f6;');
        console.log('  Numero:   ' + pedido.numero_pedido);
        console.log('  Turno:    ' + (pedido.turno || 'N/A'));
        console.log('  Cliente:  ' + pedido.cliente);
        console.log('  Comentarios: ' + (pedido.comentarios ? '"' + pedido.comentarios + '"' : '(sin comentarios)'));
        if (tipoSolicitud === '50') {
            console.log('  Direccion: ' + (pedido.direccion || 'No disponible'));
            console.log('  Barrio: ' + (pedido.barrio || 'No disponible'));
            console.log('  Telefono: ' + (pedido.telefono || 'N/A'));
            console.log('  Costo Domicilio: $' + (pedido.costo_domicilio || 0));
        }
        console.log('  Fecha:    ' + pedido.fecha);
        console.log('%c-------------------------------------\n', 'color: #3b82f6;');

        // MOSTRAR PRODUCTOS
        console.log('%cDESGLOSE DE PRODUCTOS', 'color: #10b981; font-weight: bold; font-size: 12px;');
        console.log('%c-------------------------------------', 'color: #10b981;');
        
        let totalCalculado = 0;

        if (pedido.productos && pedido.productos.length > 0) {
            console.log('  Total de articulos: ' + pedido.productos.length);
            
            pedido.productos.forEach((p, idx) => {
                const nombre = p.nombre_producto || p.nombre || 'Producto';
                const cantidad = parseInt(p.cantidad) || 1;
                const precio = parseFloat(p.precio) || 0;
                const subtotal = cantidad * precio;
                totalCalculado += subtotal;
                
                console.log('  ' + (idx + 1) + '. ' + nombre);
                console.log('     Cantidad: ' + cantidad + 'x @ $' + Math.round(precio) + ' = $' + Math.round(subtotal));
            });
        } else {
            console.log('  SIN PRODUCTOS');
        }

        // TOTAL
        let totalFinal = pedido.total || totalCalculado || 0;
        if (tipoSolicitud === '50' && pedido.costo_domicilio && pedido.costo_domicilio > 0) {
            totalFinal += pedido.costo_domicilio;
        }
        console.log('%c-------------------------------------', 'color: #10b981;');
        console.log('%cTOTAL A PAGAR: $' + Math.round(totalFinal), 'color: #22c55e; font-weight: bold; font-size: 14px;');
        console.log('%c-------------------------------------\n', 'color: #10b981;');

        // Construir recibo
        const content = this.buildReciboProfesional(pedido, tipoSolicitud);

        // Imprimir
        return await this.print(content);
    }

    /**
     * Obtener datos del pedido desde API
     */
    async getPedidoData(numeroPedido, tipo) {
        try {
            // 1️⃣ Obtener datos de mesas (cabecera, productos, etc)
            const responseMesas = await fetch(this.apiBase + 'mesas&numero_pedido=' + numeroPedido);
            const dataMesas = await responseMesas.json();

            if (!dataMesas.success) {
                console.error('Pedido no encontrado en API:', dataMesas.error);
                return null;
            }

            // 2️⃣ Obtener comentarios de la ruta productos (donde SÍ están)
            let comentarioTexto = '';
            try {
                const responseProductos = await fetch(this.apiBase + 'productos&id_pedido=' + numeroPedido);
                const dataProductos = await responseProductos.json();
                if (dataProductos.comentario) {
                    comentarioTexto = dataProductos.comentario;
                    console.log('🎯 Comentario obtenido de ruta productos:', comentarioTexto);
                }
            } catch (e) {
                console.warn('⚠️ No se pudo obtener comentario de ruta productos:', e);
            }

            const pedido = {
                numero_pedido: dataMesas.numero_pedido || numeroPedido,
                turno: dataMesas.turno || '0',
                cliente: dataMesas.nombre_cliente || 'Cliente',
                telefono: dataMesas.telefono || dataMesas.celular || '',
                direccion: dataMesas.direccion || dataMesas.direccion_envio || dataMesas.address || '',
                barrio: dataMesas.barrio || dataMesas.nombre_barrio || '',
                fecha: dataMesas.fecha ? new Date(dataMesas.fecha).toLocaleString('es-CO') : new Date().toLocaleString('es-CO'),
                productos: dataMesas.productos || [],
                total: dataMesas.total || 0,
                costo_domicilio: dataMesas.costo_domicilio || 0,
                comentarios: comentarioTexto  // 🎯 COMENTARIOS DESDE RUTA PRODUCTOS
            };

            return pedido;

        } catch (error) {
            console.error('Error obteniendo datos de API:', error);
            return null;
        }
    }

    /**
     * Calcular total
     */
    calcularTotal(productos) {
        if (!productos || !Array.isArray(productos) || productos.length === 0) {
            return 0;
        }

        return productos.reduce((total, p) => {
            const precio = parseFloat(p.precio) || 0;
            const cantidad = parseInt(p.cantidad) || 1;
            return total + (precio * cantidad);
        }, 0);
    }

    /**
     * Verificar disponibilidad
     */
    isAvailable() {
        return this.qzAvailable;
    }
}

// Instancia singleton
const printInstance = new PrintService();

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.printService = printInstance;
    
    // Funciones de compatibilidad
    window.printInvoicepc = (numeroPedido) => {
        console.log('FUNCION LLAMADA: printInvoicepc()');
        printInstance.printComanda(numeroPedido);
    };
    
    window.printInvoicemesa = (numeroPedido) => {
        console.log('FUNCION LLAMADA: printInvoicemesa()');
        printInstance.printRecibo(numeroPedido);
    };

    // Configurar QZ Tray si esta disponible
    if (typeof qz !== 'undefined') {
        qz.security.setCertificatePromise((resolve, reject) => {
            resolve();
        });

        qz.security.setSignaturePromise((toSign) => {
            return (resolve, reject) => {
                resolve();
            };
        });
    }

    console.log('PrintService cargado');
}

export default printInstance;