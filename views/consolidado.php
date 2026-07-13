<?php
/**
 * consolidado.php - TABLA SIMPLE CON SELECTOR DE FECHA
 * Diseño compacto, tabs horizontal, selector fecha
 */

require_once __DIR__ . '/../app/models/ConsolidadoModel.php';
require_once __DIR__ . '/../app/controllers/ConsolidadoController.php';
require_once __DIR__ . '/../config/database.php';

use App\Controllers\ConsolidadoController;

date_default_timezone_set('America/Bogota');

// Obtener fecha del GET o usar hoy
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

$database = new Database();
$controller = new ConsolidadoController($database);
$datos = $controller->obtenerTurnosPorTipo($fecha);

$turnosPorTipo = $datos['turnosPorTipo'];
$totalTurnos = $datos['total'];
$fechaActual = $datos['fecha'];

$tiposLabels = array(
    'todos' => 'Todos',
    'domicilios' => 'Domicilios',
    'turno' => 'Turno',
    'mesas' => 'Mesas',
    'recoger' => 'Recoger'
);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            padding: 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-consolidado {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header-section {
            background: white;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            gap: 20px;
            flex-wrap: wrap;
        }

        .header-left h2 {
            margin: 0;
            font-size: 22px;
            color: #333;
            font-weight: 600;
        }

        .header-left p {
            margin: 3px 0 0 0;
            font-size: 12px;
            color: #666;
        }

        .header-filters {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .form-group {
            margin: 0;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            min-width: 150px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .btn-secundario {
            background-color: #757575;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }

        .btn-secundario:hover {
            background-color: #616161;
        }

        .nav-tabs-container {
            background: white;
            padding: 0;
            margin-bottom: 15px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .nav-tabs {
            border: none;
            background: white;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 0;
            flex-wrap: wrap;
        }

        .nav-item {
            margin: 0;
        }

        .nav-link {
            color: #666;
            border: none;
            padding: 14px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: 600;
            background-color: transparent;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid transparent;
            margin: 0;
        }

        .nav-link:hover {
            background-color: #fafafa;
            color: #333;
            border-bottom-color: #4CAF50;
        }

        .nav-link.active {
            background-color: white;
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }

        .badge {
            margin-left: 8px;
            background-color: #ff5252;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 12px;
        }

        .nav-link.active .badge {
            background-color: #4CAF50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tabla-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .tabla-header {
            background-color: #3a3a3a;
            color: white;
            padding: 0;
        }

        .tabla-header-row {
            display: grid;
            grid-template-columns: 60px 180px 90px 120px 1fr 150px;
            gap: 0;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabla-fila {
            display: grid;
            grid-template-columns: 60px 180px 90px 120px 1fr 150px;
            gap: 0;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            align-items: flex-start;
            transition: all 0.2s;
        }

        .tabla-fila:hover {
            background-color: #fafafa;
        }

        .tabla-fila:last-child {
            border-bottom: none;
        }

        .numero-turno {
            font-size: 18px;
            font-weight: 700;
            color: #4CAF50;
        }

        .cliente-cell {
            font-size: 13px;
            color: #333;
            font-weight: 500;
        }

        .cliente-detail {
            font-size: 11px;
            color: #999;
            margin-top: 2px;
        }

        .tiempo {
            font-size: 13px;
            color: #666;
        }

        .estado-cell {
            font-size: 12px;
            font-weight: 600;
        }

        .estado-nuevo {
            color: #FFB300;
        }

        .estado-espera {
            color: #2196F3;
        }

        .estado-listo {
            color: #4CAF50;
        }

        .estado-entregado {
            color: #9E9E9E;
        }

        .productos-cell {
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .productos-item {
            margin-bottom: 4px;
        }

        .total-producto {
            font-weight: 600;
            color: #333;
            margin-top: 4px;
        }

        .comentario-text {
            font-size: 11px;
            color: #999;
            font-style: italic;
            margin-top: 3px;
        }

        .acciones {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-accion {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }

        .btn-pagado {
            background-color: #4CAF50;
            cursor: default;
        }

        .btn-pagado:hover {
            background-color: #4CAF50;
        }

        .btn-caja {
            background-color: #2196F3;
        }

        .btn-caja:hover {
            background-color: #1565C0;
        }

        .no-turnos {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 6px;
            color: #999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

<div class="container-consolidado">
    <!-- HEADER CON FILTRO DE FECHA -->
    <div class="header-section">
        <div class="header-left">
            <h2>📋 Consolidado</h2>
            <p>Total: <strong><?php echo $totalTurnos; ?></strong> turnos</p>
        </div>
        <div class="header-filters">
            <div class="form-group">
                <label for="filtroFecha">📅 Seleccionar Fecha</label>
                <input type="date" id="filtroFecha" value="<?php echo $fechaActual; ?>" 
                       onchange="cambiarFecha(this.value)">
            </div>
            <button class="btn-secundario" onclick="window.location.href='index.php?page=creditos.php'">💰 Créditos</button>
        </div>
    </div>

    <!-- TABS MEJORADOS -->
    <?php if ($totalTurnos > 0): ?>

        <div class="nav-tabs-container">
            <ul class="nav nav-tabs" role="tablist">
                <?php $primeraTab = true; ?>
                <?php foreach ($tiposLabels as $tipo => $label): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $primeraTab ? 'active' : ''; ?>" 
                                onclick="cambiarTab(event, '<?php echo $tipo; ?>')"
                                type="button">
                            <?php echo $label; ?>
                            <span class="badge"><?php echo count($turnosPorTipo[$tipo]); ?></span>
                        </button>
                    </li>
                    <?php $primeraTab = false; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- TABLAS -->
        <?php $primeraTab = true; ?>
        <?php foreach ($tiposLabels as $tipo => $label): ?>
            <div id="tab-<?php echo $tipo; ?>" class="tab-content <?php echo $primeraTab ? 'active' : ''; ?>">
                <?php if (!empty($turnosPorTipo[$tipo])): ?>
                    <div class="tabla-container">
                        <div class="tabla-header">
                            <div class="tabla-header-row">
                                <div>N°</div>
                                <div>Cliente</div>
                                <div>Tiempo</div>
                                <div>Estado</div>
                                <div>Productos</div>
                                <div>Acción</div>
                            </div>
                        </div>

                        <?php foreach ($turnosPorTipo[$tipo] as $turno): ?>
                            <div class="tabla-fila">
                                <!-- N° -->
                                <div>
                                    <div class="numero-turno"><?php echo $turno['turno']; ?></div>
                                </div>

                                <!-- CLIENTE -->
                                <div>
                                    <div class="cliente-cell"><?php echo htmlspecialchars($turno['cliente']); ?></div>
                                    <?php if ($turno['celular']): ?>
                                        <div class="cliente-detail">📱 <?php echo htmlspecialchars($turno['celular']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($turno['barrio']): ?>
                                        <div class="cliente-detail">📍 <?php echo htmlspecialchars($turno['barrio']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- TIEMPO -->
                                <div>
                                    <div class="tiempo"><?php echo date('H:i:s', strtotime($turno['fecha'])); ?></div>
                                </div>

                                <!-- ESTADO -->
                                <div>
                                    <div class="estado-cell estado-<?php echo $turno['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $turno['estado'])); ?>
                                    </div>
                                </div>

                                <!-- PRODUCTOS -->
                                <div>
                                    <div class="productos-cell">
                                        <?php foreach ($turno['productos'] as $prod): ?>
                                            <div class="productos-item">
                                                <?php echo $prod['cantidad']; ?>x [<?php echo htmlspecialchars($prod['nombre']); ?>] — $ <?php echo number_format($prod['precio'], 0, ',', '.'); ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- DOMICILIARIO (si existe) -->
                                        <?php if (!empty($turno['domiciliario']) && $turno['domiciliario'] !== null): ?>
                                            <div class="productos-item" style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e0e0e0; font-weight: 600; color: #2196F3;">
                                                🚚 <?php echo htmlspecialchars($turno['domiciliario']['repartidor']); ?>
                                            </div>
                                            <div class="productos-item" style="font-size: 11px; color: #666; margin-top: 2px;">
                                                📱 <?php echo htmlspecialchars($turno['domiciliario']['celu_reparti']); ?>
                                            </div>
                                            <div class="productos-item" style="margin-top: 4px; font-weight: 600; color: #333;">
                                                Costo domicilio: $ <?php echo number_format($turno['costoDomicilio'], 0, ',', '.'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="total-producto">Total del pedido: $ <?php echo number_format($turno['totalGeneral'], 0, ',', '.'); ?></div>
                                        <?php if (!empty($turno['comentarios'])): ?>
                                            <?php foreach ($turno['comentarios'] as $comentario): ?>
                                                <div class="comentario-text">Comentario: [<?php echo htmlspecialchars($comentario); ?>]</div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- ACCIÓN -->
                                <div>
                                    <div class="acciones">
                                        <button class="btn-accion btn-pagado" title="Estado: Pagado">Pagado</button>
                                        <form action="index.php?page=caja_tm.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="numero_pedido" value="<?php echo $turno['id_pedidoc']; ?>">
                                            <button type="submit" class="btn-accion btn-caja">Caja</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-turnos">No hay turnos de este tipo en esta fecha</div>
                <?php endif; ?>
            </div>
            <?php $primeraTab = false; ?>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="no-turnos">ℹ️ No hay turnos para la fecha seleccionada</div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<script>
function cambiarTab(event, tipo) {
    event.preventDefault();
    
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Desactivar todos los botones
    document.querySelectorAll('.nav-link').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab seleccionado
    document.getElementById('tab-' + tipo).classList.add('active');
    event.target.classList.add('active');
}

function cambiarFecha(fecha) {
    // Redirigir con la fecha seleccionada
    window.location.href = 'index.php?page=consolidado.php&fecha=' + fecha;
}
</script>

</body>
</html>