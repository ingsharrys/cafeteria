<?php
/**
 * @var string   $celular
 * @var string   $nombreCliente
 * @var array    $pedidoPendiente
 * @var array    $otrosPedidosActivos
 * @var array    $otrosPedidosDia
 * @var array    $pedidoCancelado
 * @var array    $historialPedidos
 * @var callable $obtenerEstado
 * @var callable $obtenerTipo
 */

$baseUrl = dirname($_SERVER['PHP_SELF'] ?? '/');
$baseUrl = rtrim($baseUrl, '/');
if ($baseUrl === '.' || $baseUrl === '\\' || $baseUrl === '/') {
    $baseUrl = '';
}
$logoUrl = 'http://localhost/cafeteria-pombo/public/img/logo-pideyapp.png';

/**
 * Renderiza la lista de productos de un pedido.
 * Cada elemento de $productos tiene: nombre, cantidad, precio, detalle
 */
function renderProductos(array $productos): void
{
    if (empty($productos)) {
        echo '<p style="font-size:12px;color:#999;margin:0;">Sin detalle de productos</p>';
        return;
    }
    echo '<ul class="lista-productos">';
    foreach ($productos as $prod) {
        $subtotal = (int)$prod['precio'] * (int)$prod['cantidad'];
        echo '<li>';
        echo '<span class="prod-nombre">' . htmlspecialchars($prod['nombre']) . '</span>';
        echo '<span class="prod-cant">x' . (int)$prod['cantidad'] . '</span>';
        echo '<span class="prod-precio">$' . number_format($subtotal, 0, '', ',') . '</span>';
        if (!empty($prod['detalle'])) {
            echo '<span class="prod-detalle">' . htmlspecialchars($prod['detalle']) . '</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Pedido - Heiyubai</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding-top: 0;
            padding-bottom: 60px;
            color: #1a1a1a;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════ */
        /* HEADER */
        /* ═══════════════════════════════════ */
        .header-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            color: white;
            padding: 28px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .header-section img {
            height: 45px;
            width: auto;
            margin-right: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-info h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .header-info p {
            font-size: 12px;
            margin: 4px 0 0 0;
            color: #b0b0b0;
        }

        .header-right a {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-right a:hover {
            background: #059669;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16,185,129,0.3);
        }

        /* ═══════════════════════════════════ */
        /* CONTAINER */
        /* ═══════════════════════════════════ */
        .container {
            padding: 0;
            max-width: 580px;
            margin-top: 20px;
        }

        /* ═══════════════════════════════════ */
        /* PEDIDO PRINCIPAL */
        /* ═══════════════════════════════════ */
        .pedido-principal {
            background: white;
            border-radius: 12px;
            padding: 28px;
            margin: 0 16px 24px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .pedido-principal::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }

        .estado-animado {
            text-align: center;
            margin-bottom: 24px;
        }

        .icono-estado {
            font-size: 56px;
            margin-bottom: 12px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

        .estado-texto {
            font-size: 14px;
            color: #666666;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .estado-titulo {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        /* TIMELINE */
        .timeline-progreso {
            margin: 28px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .timeline-item:last-child { margin-bottom: 0; }

        .timeline-icon {
            font-size: 20px;
            margin-right: 12px;
            min-width: 24px;
            text-align: center;
        }

        .timeline-icon.completado { color: #10b981; }
        .timeline-icon.actual     { color: #f59e0b; animation: pulse 2s infinite; }
        .timeline-icon.pendiente  { color: #d1d5db; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }

        .timeline-content h6 {
            font-size: 12px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }

        .timeline-content p {
            font-size: 11px;
            color: #999999;
            margin: 4px 0 0 0;
        }

        /* INFO GRID */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 20px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 14px;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }

        .info-card.especial { border-left-color: #f59e0b; }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .info-valor {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
        }

        /* TOTAL BOX */
        .total-box {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 14px;
            margin: 4px 0 16px;
            border-left: 3px solid #10b981;
        }

        .total-box .info-valor {
            font-size: 20px;
            color: #059669;
        }

        /* ═══════════════════════════════════ */
        /* LISTA DE PRODUCTOS */
        /* ═══════════════════════════════════ */
        .productos-section {
            margin: 16px 0;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 14px;
            border-left: 3px solid #6366f1;
        }

        .productos-titulo {
            font-size: 11px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 10px;
        }

        .lista-productos {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .lista-productos li {
            display: flex;
            align-items: baseline;
            gap: 6px;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .lista-productos li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .prod-nombre {
            flex: 1;
            font-weight: 600;
            color: #1a1a1a;
            min-width: 120px;
        }

        .prod-cant {
            font-size: 12px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 1px 6px;
            border-radius: 10px;
            white-space: nowrap;
        }

        .prod-precio {
            font-weight: 700;
            color: #059669;
            white-space: nowrap;
        }

        .prod-detalle {
            width: 100%;
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
            padding-left: 4px;
        }

        /* ESTADO PAGO */
        .historial-pago {
            font-size: 11px;
            margin-top: 6px;
            padding: 6px 8px;
            border-radius: 4px;
            background: #f0fdf4;
            color: #15803d;
            font-weight: 600;
            text-align: center;
        }

        .historial-pago.pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        /* BOTONES */
        .btn-accion {
            background: white;
            border: 2px solid #10b981;
            color: #10b981;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 16px;
        }

        .btn-accion:hover {
            background: #10b981;
            color: white;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }

        /* HISTORIAL */
        .historial-section {
            margin-top: 40px;
            padding: 0 16px;
        }

        .historial-titulo {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .historial-item {
            background: white;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .historial-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .historial-turno {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
        }

        .historial-fecha {
            font-size: 11px;
            color: #999999;
            margin-bottom: 6px;
        }

        .historial-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-right: 8px;
            margin-top: 6px;
        }

        .badge-entregado { background: #dcfce7; color: #15803d; }

        .historial-info {
            font-size: 12px;
            color: #666666;
            margin-top: 8px;
            line-height: 1.5;
        }

        .historial-total {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            margin-top: 8px;
        }

        /* SIN DATOS */
        .sin-datos {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            margin: 40px 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .sin-datos i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .sin-datos p {
            font-size: 14px;
            color: #999999;
            margin: 0;
            margin-bottom: 16px;
        }

        .btn-nuevo-pedido {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-nuevo-pedido:hover {
            background: #059669;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }

        /* FOOTER */
        .footer-section {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-section p { font-size: 12px; color: #999999; margin: 0; }

        .footer-section a {
            color: #10b981;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
        }

        .footer-section a:hover { text-decoration: underline; }

        /* ANIMACIONES */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @media (max-width: 640px) {
            .header-section    { flex-direction: column; text-align: center; }
            .header-left       { justify-content: center; width: 100%; margin-bottom: 12px; }
            .header-right      { width: 100%; }
            .info-grid         { grid-template-columns: 1fr; }
            .icono-estado      { font-size: 48px; }
        }
    </style>
</head>
<body>

<!-- BARRA SHARRY'S TECH -->
<div style="background: linear-gradient(90deg, #c0392b 0%, #e74c3c 50%, #c0392b 100%); padding: 7px 20px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow: 0 2px 6px rgba(192,57,43,0.4);">
    <span style="color:rgba(255,255,255,0.7); font-size:11px; letter-spacing:0.3px;">Desarrollado por</span>
    <a href="https://wa.me/573173667467" target="_blank"
       style="color:#ffffff; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-decoration:none; display:flex; align-items:center; gap:6px; background:rgba(0,0,0,0.15); padding:4px 10px; border-radius:20px; transition:background 0.2s;"
       onmouseover="this.style.background='rgba(0,0,0,0.3)'" onmouseout="this.style.background='rgba(0,0,0,0.15)'">
        <i class="fab fa-whatsapp" style="font-size:14px;"></i> ⚡ Sharrys tech   </a>
</div>

<!-- HEADER -->
<div class="header-section">
    <div class="header-left">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="PideYAPP">
        <div class="header-info">
            <h3>👋 Hola, <?php echo htmlspecialchars(explode(' ', $nombreCliente)[0]); ?></h3>
            <p><?php echo htmlspecialchars($celular); ?></p>
        </div>
    </div>
    <div class="header-right">
        <a href="<?php echo $baseUrl; ?>/index.php?route=pedidos&pedido=call&numero=<?php echo urlencode($celular); ?>">
            <i class="fas fa-plus-circle"></i> Nuevo pedido
        </a>
    </div>
</div>

<div class="container">

    <!-- ═════════════════════════════════ -->
    <!-- PEDIDO PRINCIPAL                  -->
    <!-- ═════════════════════════════════ -->
    <?php if ($pedidoPendiente): ?>
        <div class="pedido-principal">
            <?php
                $infoEstado = $obtenerEstado($pedidoPendiente['estado']);
                $iconoEstado = '📦';
                if ($pedidoPendiente['estado'] === 'espera')  $iconoEstado = '👨‍🍳';
                if ($pedidoPendiente['estado'] === 'entregado')  $iconoEstado = '🎉';
            ?>
            <div class="estado-animado">
                <div class="icono-estado"><?php echo $iconoEstado; ?></div>
                <div class="estado-texto">Estado actual</div>
                <div class="estado-titulo"><?php echo $infoEstado[0]; ?></div>
            </div>

            <!-- TIMELINE -->
            <div class="timeline-progreso">
                <div class="timeline-item">
                    <div class="timeline-icon completado">✓</div>
                    <div class="timeline-content">
                        <h6>Pedido Recibido</h6>
                        <p id="hora-recibido"></p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon <?php echo ($pedidoPendiente['estado'] !== 'preparacion') ? 'completado' : 'actual'; ?>">
                        <?php echo ($pedidoPendiente['estado'] !== 'preparacion') ? '✓' : '●'; ?>
                    </div>
                    <div class="timeline-content">
                        <h6>Listo para Recoger 🏪</h6>
                        <p><?php echo ($pedidoPendiente['estado'] !== 'preparacion') ? 'Tu pedido te espera' : 'En progreso...'; ?></p>
                    </div>
                </div>

                <?php if ($pedidoPendiente['tipo_solicitud'] == 50): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($pedidoPendiente['estado'] === 'entregado') ? 'completado' : 'pendiente'; ?>">
                            <?php echo ($pedidoPendiente['estado'] === 'entregado') ? '✓' : '●'; ?>
                        </div>
                        <div class="timeline-content">
                            <h6>Salió de Cocina 📦</h6>
                            <p><?php echo ($pedidoPendiente['estado'] === 'entregado') ? 'Completado' : 'Próximamente...'; ?></p>
                        </div>
                    </div>

                    <?php if ($pedidoPendiente['id_domiciliario'] > 0): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon completado">✓</div>
                        <div class="timeline-content">
                            <h6>En Ruta 🚚</h6>
                            <p>Repartidor asignado ✅</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-icon pendiente">●</div>
                        <div class="timeline-content">
                            <h6>En Ruta 🚚</h6>
                            <p>Asignando repartidor...</p>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($pedidoPendiente['estado'] === 'entregado') ? 'completado' : 'pendiente'; ?>">
                            <?php echo ($pedidoPendiente['estado'] === 'entregado') ? '✓' : '●'; ?>
                        </div>
                        <div class="timeline-content">
                            <h6>Entregado</h6>
                            <p><?php echo ($pedidoPendiente['estado'] === 'entregado') ? 'Tu pedido te espera' : 'Próximamente...'; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TURNO / TIPO -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">🎫 Turno</div>
                    <div class="info-valor">#<?php echo htmlspecialchars($pedidoPendiente['turno']); ?></div>
                </div>
                <div class="info-card especial">
                    <div class="info-label">📦 Tipo</div>
                    <div class="info-valor">Plataforma</div>
                </div>
            </div>

            <!-- PRODUCTOS -->
            <div class="productos-section">
                <div class="productos-titulo"><i class="fas fa-utensils"></i> Productos del pedido</div>
                <?php renderProductos($pedidoPendiente['productos'] ?? []); ?>
            </div>
            
            <div class="total-box">
                <div class="info-label">💰 Total del pedido</div>
                <div class="info-valor">$<?php echo number_format($pedidoPendiente['total_productos'] + $pedidoPendiente['precio_domicilio'], 0, '', ','); ?></div>
            </div>

            <!-- PAGO -->
            <div class="historial-pago" style="text-align:center; margin:16px 0; <?php echo $pedidoPendiente['pagado'] ? 'background:#f0fdf4;color:#15803d;' : 'background:#fef3c7;color:#92400e;'; ?>">
                <?php echo $pedidoPendiente['pagado'] ? '✅ Pagado' : '⏳ Pendiente de pago'; ?>
            </div>

            <!-- Mensaje de pago NEQUI -->
            <div style="text-align:center; margin-top:12px;">
                <p style="font-weight:700; font-size:14px;">Realice el pago al número de NEQUI: 3112492225</p>
            </div>

            <!-- Formulario para subir una sola foto de pago -->
            <?php if (!empty($pedidoPendiente) && !empty($pedidoPendiente['id_pedido'])): ?>
                <?php
                    $storageDir = __DIR__ . '/../../../public/img/payments/';
                    $webDir = '/cafeteria-pombo/public/img/payments/';
                    $imgUrl = null;
                    if (is_dir($storageDir)) {
                        $files = glob($storageDir . $pedidoPendiente['id_pedido'] . '.*');
                        if (!empty($files)) {
                            $imgUrl = $webDir . basename($files[0]);
                        }
                    }
                ?>

                <div style="text-align:center; margin-top:10px;">
                    <?php if ($imgUrl): ?>
                        <div style="margin-bottom:8px;">
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Imagen de pago" style="max-width:220px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo $baseUrl; ?>/index.php?route=estado-pedido" method="POST" enctype="multipart/form-data" style="display:inline-block; text-align:left;">
                        <input type="hidden" name="id_pedido" value="<?php echo htmlspecialchars($pedidoPendiente['id_pedido']); ?>">
                        <input type="hidden" name="numero" value="<?php echo htmlspecialchars($celular); ?>">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="file" name="pago_img" accept="image/*" required style="padding:6px;">
                            <button type="submit" class="btn-accion" style="padding:10px 14px;">Subir foto de pago</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fecha = new Date('<?php echo $pedidoPendiente['fecha']; ?>Z');
            const fmt   = new Intl.DateTimeFormat('es-CO', { timeZone:'America/Bogota', hour:'2-digit', minute:'2-digit' });
            document.getElementById('hora-recibido').textContent = fmt.format(fecha);
        });
        </script>

    <?php else: ?>
        <div class="sin-datos">
            <i class="fas fa-check-double"></i>
            <p><strong>¡Qué alivio!</strong></p>
            <p>No tienes pedidos en preparación.</p>
            <a href="<?php echo $baseUrl; ?>/index.php?route=pedidos&pedido=call&numero=<?php echo urlencode($celular); ?>" class="btn-nuevo-pedido">
                <i class="fas fa-plus-circle"></i> Hacer un nuevo pedido
            </a>
        </div>
    <?php endif; ?>

    <!-- ═════════════════════════════════ -->
    <!-- OTROS PEDIDOS ACTIVOS DEL DÍA    -->
    <!-- ═════════════════════════════════ -->
    <?php foreach ($otrosPedidosActivos as $idx => $pedido): ?>
        <?php
            $infoEstado  = $obtenerEstado($pedido['estado']);
            $iconoEstado = '📦';
            if ($pedido['estado'] === 'espera') $iconoEstado = '👨‍🍳';
            if ($pedido['estado'] === 'entregado')  $iconoEstado = '🎉';
        ?>
        <div class="pedido-principal" style="margin-top:30px;">
            <div class="estado-animado">
                <div class="icono-estado"><?php echo $iconoEstado; ?></div>
                <div class="estado-texto">Pedido Activo</div>
                <div class="estado-titulo"><?php echo $infoEstado[0]; ?></div>
            </div>

            <div class="timeline-progreso">
                <div class="timeline-item">
                    <div class="timeline-icon completado">✓</div>
                    <div class="timeline-content">
                        <h6>Pedido Recibido</h6>
                        <p id="hora-pedido-<?php echo $pedido['id_pedido']; ?>"></p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon <?php echo ($pedido['estado'] !== 'preparacion') ? 'completado' : 'actual'; ?>">
                        <?php echo ($pedido['estado'] !== 'preparacion') ? '✓' : '●'; ?>
                    </div>
                    <div class="timeline-content">
                        <h6>En Preparación</h6>
                        <p><?php echo ($pedido['estado'] !== 'preparacion') ? 'Completado' : 'En progreso...'; ?></p>
                    </div>
                </div>

                <?php if ($pedido['tipo_solicitud'] == 50): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($pedido['estado'] === 'entregado') ? 'completado' : 'pendiente'; ?>">
                            <?php echo ($pedido['estado'] === 'entregado') ? '✓' : '●'; ?>
                        </div>
                        <div class="timeline-content">
                            <h6>Salió de Cocina 📦</h6>
                            <p><?php echo ($pedido['estado'] === 'entregado') ? 'Completado' : 'Próximamente...'; ?></p>
                        </div>
                    </div>

                    <?php if ($pedido['id_domiciliario'] > 0): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon completado">✓</div>
                        <div class="timeline-content"><h6>En Ruta 🚚</h6><p>Repartidor asignado ✅</p></div>
                    </div>
                    <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-icon pendiente">●</div>
                        <div class="timeline-content"><h6>En Ruta 🚚</h6><p>Asignando repartidor...</p></div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-icon <?php echo ($pedido['estado'] === 'entregado') ? 'completado' : 'pendiente'; ?>">
                            <?php echo ($pedido['estado'] === 'entregado') ? '✓' : '●'; ?>
                        </div>
                        <div class="timeline-content">
                            <h6>Listo para Recoger 🏪</h6>
                            <p><?php echo ($pedido['estado'] === 'entregado') ? 'Tu pedido te espera' : 'Próximamente...'; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TURNO / TIPO -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">🎫 Turno</div>
                    <div class="info-valor">#<?php echo htmlspecialchars($pedido['turno']); ?></div>
                </div>
                <div class="info-card especial">
                    <div class="info-label">📦 Tipo</div>
                    <div class="info-valor"><?php echo htmlspecialchars($obtenerTipo($pedido['tipo_solicitud'])); ?></div>
                </div>
            </div>

            <!-- PRODUCTOS -->
            <div class="productos-section">
                <div class="productos-titulo"><i class="fas fa-utensils"></i> Productos del pedido</div>
                <?php renderProductos($pedido['productos'] ?? []); ?>
            </div>

            <!-- DESGLOSE DE COSTOS -->
            <div class="info-grid" style="margin-top:0;">
                <div class="info-card">
                    <div class="info-label">🛒 Productos</div>
                    <div class="info-valor">$<?php echo number_format($pedido['total_productos'], 0, '', ','); ?></div>
                </div>
                <?php if ($pedido['precio_domicilio'] > 0): ?>
                <div class="info-card especial">
                    <div class="info-label">🛵 Domicilio</div>
                    <div class="info-valor">$<?php echo number_format($pedido['precio_domicilio'], 0, '', ','); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="total-box">
                <div class="info-label">💰 Total del pedido</div>
                <div class="info-valor">$<?php echo number_format($pedido['total_productos'] + $pedido['precio_domicilio'], 0, '', ','); ?></div>
            </div>

            <!-- PAGO -->
            <div class="historial-pago" style="text-align:center; margin:16px 0; <?php echo $pedido['pagado'] ? 'background:#f0fdf4;color:#15803d;' : 'background:#fef3c7;color:#92400e;'; ?>">
                <?php echo $pedido['pagado'] ? '✅ Pagado' : '⏳ Pendiente de pago'; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fecha = new Date('<?php echo $pedido['fecha']; ?>Z');
            const fmt   = new Intl.DateTimeFormat('es-CO', { timeZone:'America/Bogota', hour:'2-digit', minute:'2-digit' });
            document.getElementById('hora-pedido-<?php echo $pedido['id_pedido']; ?>').textContent = fmt.format(fecha);
        });
        </script>
    <?php endforeach; ?>

    <!-- ═════════════════════════════════ -->
    <!-- OTROS PEDIDOS DEL DÍA (COMPLETOS)-->
    <!-- ═════════════════════════════════ -->
    <?php if (!empty($otrosPedidosDia)): ?>
    <div class="historial-section" style="margin-top:30px;">
        <div class="historial-titulo">
            <i class="fas fa-check-circle"></i> Otros Pedidos del Día
        </div>

        <?php foreach ($otrosPedidosDia as $idx => $pedido): ?>
            <div class="historial-item" style="animation: slideIn 0.4s ease <?php echo $idx * 0.1; ?>s forwards; opacity:0;">
                <div class="historial-turno">
                    <i class="fas fa-receipt"></i> Turno #<?php echo htmlspecialchars($pedido['turno']); ?>
                </div>
                <div class="historial-fecha">
                    📅 <?php echo date('H:i', strtotime($pedido['fecha'])); ?>
                </div>

                <span class="historial-badge badge-entregado">✅ Entregado</span>

                <div class="historial-info">
                    <?php echo htmlspecialchars($obtenerTipo($pedido['tipo_solicitud'])); ?>
                    · <i class="fas fa-box"></i> <?php echo htmlspecialchars($pedido['cantidad_productos']); ?> artículo<?php echo $pedido['cantidad_productos'] != 1 ? 's' : ''; ?>
                </div>

                <!-- Productos -->
                <div class="productos-section" style="margin-top:10px;">
                    <div class="productos-titulo"><i class="fas fa-utensils"></i> Productos</div>
                    <?php renderProductos($pedido['productos'] ?? []); ?>
                </div>

                <div class="historial-info" style="margin-top:8px;">
                    🛒 Productos: $<?php echo number_format($pedido['total_productos'], 0, '', ','); ?>
                    <?php if ($pedido['precio_domicilio'] > 0): ?>
                        &nbsp;·&nbsp; 🛵 Domicilio: $<?php echo number_format($pedido['precio_domicilio'], 0, '', ','); ?>
                    <?php endif; ?>
                </div>

                <div class="historial-total">
                    💰 Total: $<?php echo number_format($pedido['total_productos'] + $pedido['precio_domicilio'], 0, '', ','); ?>
                </div>

                <div class="historial-pago">✅ Pagado</div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═════════════════════════════════ -->
    <!-- HISTORIAL DE PEDIDOS             -->
    <!-- ═════════════════════════════════ -->
    <div class="historial-section">
        <div class="historial-titulo">
            <i class="fas fa-history"></i> Historial de pedidos
        </div>

        <?php if (count($historialPedidos) > 0): ?>
            <div id="historial-container">
                <?php foreach ($historialPedidos as $idx => $pedido): ?>
                    <div class="historial-item historial-pagina" data-page="<?php echo floor($idx / 3) + 1; ?>" style="<?php echo $idx >= 3 ? 'display:none;' : ''; ?> animation: slideIn 0.4s ease <?php echo ($idx % 3) * 0.1; ?>s forwards; opacity:0;">
                        <div class="historial-turno">
                            <i class="fas fa-receipt"></i> Turno #<?php echo htmlspecialchars($pedido['turno']); ?>
                        </div>
                        <div class="historial-fecha">
                            📅 <?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?>
                            · <span class="hora-historial" data-fecha="<?php echo $pedido['fecha']; ?>">Cargando...</span>
                        </div>

                        <div class="historial-info">
                            <?php echo htmlspecialchars($obtenerTipo($pedido['tipo_solicitud'])); ?>
                            · <i class="fas fa-box"></i> <?php echo htmlspecialchars($pedido['cantidad_productos']); ?> artículo<?php echo $pedido['cantidad_productos'] != 1 ? 's' : ''; ?>
                        </div>

                        <!-- Productos -->
                        <div class="productos-section" style="margin-top:10px;">
                            <div class="productos-titulo"><i class="fas fa-utensils"></i> Productos</div>
                            <?php renderProductos($pedido['productos'] ?? []); ?>
                        </div>

                        <div class="historial-info" style="margin-top:8px;">
                            🛒 Productos: $<?php echo number_format($pedido['total_productos'], 0, '', ','); ?>
                            <?php if ($pedido['precio_domicilio'] > 0): ?>
                                &nbsp;·&nbsp; 🛵 Domicilio: $<?php echo number_format($pedido['precio_domicilio'], 0, '', ','); ?>
                            <?php endif; ?>
                        </div>

                        <div class="historial-total">
                            💰 Total: $<?php echo number_format($pedido['total_productos'] + $pedido['precio_domicilio'], 0, '', ','); ?>
                        </div>

                        <div class="historial-pago <?php echo $pedido['esta_pagado'] ? '' : 'pendiente'; ?>">
                            <?php echo $pedido['esta_pagado'] ? '✅ Pagado' : '❌ Pedido cancelado'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $totalPaginas = ceil(count($historialPedidos) / 3); ?>
            <?php if ($totalPaginas > 1): ?>
            <div id="historial-paginacion" style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:20px; flex-wrap:wrap;">
                <button onclick="cambiarPagina(-1)" id="btn-anterior"
                    style="background:white; border:2px solid #10b981; color:#10b981; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:all 0.2s; opacity:0.4;" disabled>
                    ← Anterior
                </button>
                <span id="pagina-info" style="font-size:12px; color:#666; font-weight:600; padding:0 8px;">
                    Página 1 de <?php echo $totalPaginas; ?>
                </span>
                <button onclick="cambiarPagina(1)" id="btn-siguiente"
                    style="background:#10b981; border:2px solid #10b981; color:white; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:all 0.2s;"
                    <?php echo $totalPaginas <= 1 ? 'disabled style="opacity:0.4;"' : ''; ?>>
                    Siguiente →
                </button>
            </div>
            <script>
            (function() {
                var paginaActual = 1;
                var totalPaginas = <?php echo $totalPaginas; ?>;

                function cambiarPagina(dir) {
                    paginaActual += dir;
                    if (paginaActual < 1) paginaActual = 1;
                    if (paginaActual > totalPaginas) paginaActual = totalPaginas;

                    document.querySelectorAll('.historial-pagina').forEach(function(el) {
                        el.style.display = parseInt(el.getAttribute('data-page')) === paginaActual ? '' : 'none';
                    });

                    document.getElementById('pagina-info').textContent = 'Página ' + paginaActual + ' de ' + totalPaginas;
                    document.getElementById('btn-anterior').disabled = paginaActual === 1;
                    document.getElementById('btn-anterior').style.opacity = paginaActual === 1 ? '0.4' : '1';
                    document.getElementById('btn-siguiente').disabled = paginaActual === totalPaginas;
                    document.getElementById('btn-siguiente').style.opacity = paginaActual === totalPaginas ? '0.4' : '1';

                    document.querySelector('.historial-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                window.cambiarPagina = cambiarPagina;
            })();
            </script>
            <?php endif; ?>

        <?php else: ?>
            <div class="sin-datos" style="margin:20px 0;">
                <i class="fas fa-inbox"></i>
                <p>Aún no tienes historial de pedidos</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="footer-section">
        <p>¿Problemas o sugerencias? 💬</p>
        <a href="https://wa.me/573173667467" target="_blank">
            <i class="fab fa-whatsapp"></i> Escríbenos por WhatsApp
        </a>
        </p>
    </div>
</div>

<!-- Script para horas en historial -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.hora-historial').forEach(el => {
        const fecha = new Date(el.getAttribute('data-fecha') + 'Z');
        const fmt   = new Intl.DateTimeFormat('es-CO', { timeZone:'America/Bogota', hour:'2-digit', minute:'2-digit' });
        el.textContent = fmt.format(fecha);
    });
});
</script>

</body>
</html>