<?php
/**
 * views/inc/header.php - CORREGIDO
 * UBICACIÓN: heiyubai/views/inc/header.php
 * 
 * ✅ Tarifas agregado CORRECTAMENTE para admin
 * ✅ Respeta $paginas_permitidas pero agrega tarifas si es admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar si el usuario ya validó su código de mesero
$cajeroValidado = isset($_SESSION['cajero']) && !empty($_SESSION['cajero']);

if ($cajeroValidado) {
    // Cargar header_controller para obtener totales
    $headerControllerPath = dirname(dirname(__DIR__)) . '/app/controllers/header_controller.php';

    if (file_exists($headerControllerPath)) {
        require_once $headerControllerPath;
    } else {
        $total_gastos = 0;
        $total_base = 0;
        $total_efectivo = 0;
        $total_tarjeta = 0;
        $total_transferencia = 0;
        $total_breve = 0;
    }
} else {
    // Sin cajero validado: valores por defecto
    $total_gastos = 0;
    $total_base = 0;
    $total_efectivo = 0;
    $total_tarjeta = 0;
    $total_transferencia = 0;
    $total_breve = 0;
}

// Datos de la sesión
$cajero = $_SESSION['cajero'] ?? 'Usuario';
$rol_nombre = $_SESSION['rol_nombre'] ?? 'default';

// Mapeo de páginas a títulos
$pagina_titulos = [
    'llamadas.php'       => 'Recoger/WP',
    'productos.php'      => 'Productos',
    'meseros.php'        => 'Colaboradores',
    'consolidado.php'    => 'Consolidado',
    'reportes.php'       => 'Reportes',
];

// Construir menú
$paginas_permitidas = $_SESSION['paginas_permitidas'] ?? [];
$cargo = $_SESSION['rol_nombre'] ?? 'default';

// ✅ SI EL USUARIO ES ADMIN, AGREGA TARIFAS A PAGINAS PERMITIDAS
if ($cargo === 'admin' && is_array($paginas_permitidas)) {
    if (!in_array('tarifas.php', $paginas_permitidas)) {
        $paginas_permitidas[] = 'tarifas.php';
    }
}

if (!$cajeroValidado) {
    $menu_items = ['dashboard.php' => 'Pedidos'];
} elseif (!empty($paginas_permitidas)) {
    $menu_items = [];
    foreach ($paginas_permitidas as $pag) {
        if (isset($pagina_titulos[$pag])) {
            $menu_items[$pag] = $pagina_titulos[$pag];
        }
    }
} else {
    $menus = [
        'domi'    => ['whatsapp.php' => 'Domicilios', 'llamadas.php' => 'Recoger/WP'],
        'turno'   => ['dashboard.php' => 'Pedidos', 'whatsapp.php' => 'Domicilios', 'llamadas.php' => 'Recoger/WP'],
        'admin'   => [
            'dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'whatsapp.php' => 'Domicilios', 
            'productos.php' => 'Productos', 'estadistica.php' => 'Estadísticas', 
            'domiciliarios.php' => 'Domiciliarios', 'meseros.php' => 'Colaboradores', 
            'gastos.php' => 'Gastos', 'consolidado.php' => 'Consolidado', 
            'register.php' => 'Registrar', 'reportes.php' => 'Reportes',
            'tarifas.php' => 'Tarifas'
        ],
        'cajero'  => ['dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'gastos.php' => 'Gastos', 'whatsapp.php' => 'Domicilios', 'consolidado.php' => 'Consolidado', 'domiciliarios.php' => 'Domiciliarios'],
        'default' => ['dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'whatsapp.php' => 'Domicilios']
    ];
    $menu_items = $menus[$cargo] ?? $menus['default'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css?cache=<?php echo rand(10,100); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://kit.fontawesome.com/744a196ea0.js" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
</head>
<body>

    
    <div class="sidebar">
        <h2><?php  echo($_SESSION['usuario']['id_mese']);  echo htmlspecialchars($cajero); ?></h2>
        <p style="text-align:center; font-size:0.7rem; color:var(--slate-400,#9ca3af); margin:-10px 0 10px; text-transform:uppercase; letter-spacing:0.05em;">
            <?php echo htmlspecialchars($rol_nombre); ?>
        </p>
        <ul>
            <?php 
            $url_index = BASE_URL . "/public/index.php?page=";
            $currentPage = $_GET['page'] ?? '';

            foreach ($menu_items as $page => $title) {
                $active_class = ($currentPage === $page) ? 'active' : '';
                echo "<li class='$active_class'><a href='$url_index$page' class='menu-link'>$title</a></li>";
            }
            ?>
            <li><a href="<?php echo LOGOUT_URL; ?>" class="menu-link">Cerrar sesión</a></li>
        </ul>

        <?php if ($cajeroValidado): ?>
        <button class="btn btn-warning btn-block mt-3" onclick="abrirModalBase()" style="margin:8px; width:calc(100% - 16px);">Base</button>

        <!-- ✅ TOTALES REALES CON BREVE -->
        <div class="info-box">
            <p><strong>Gastos:</strong> 
               <span>$<?php echo number_format($total_gastos ?? 0, 0, '.', ','); ?></span>
            </p>
            <p><strong>Base:</strong> 
               <span>$<?php echo number_format($total_base ?? 0, 0, '.', ','); ?></span>
            </p>
            <p><strong>Efectivo:</strong> 
               <span>$<?php echo number_format($total_efectivo ?? 0, 0, '.', ','); ?></span>
            </p>
            <p><strong>Tarjeta:</strong> 
               <span>$<?php echo number_format($total_tarjeta ?? 0, 0, '.', ','); ?></span>
            </p>
            <p><strong>Transferencia:</strong> 
               <span>$<?php echo number_format($total_transferencia ?? 0, 0, '.', ','); ?></span>
            </p>
            <p><strong>Breve:</strong> 
               <span>$<?php echo number_format($total_breve ?? 0, 0, '.', ','); ?></span>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($cajeroValidado): ?>
    <div id="modalBase" class="modal-container" style="display: none;">
        <div class="modal-content" style="max-width:360px;">
            <span class="close-btn" onclick="cerrarModalBase()" style="cursor:pointer; float:right; font-size:1.5rem;">&times;</span>
            <h3>Ingresar Base</h3>
            <input type="hidden" id="cajero" value="<?php echo htmlspecialchars($_SESSION['cajero']); ?>">
            <input type="number" id="valorBase" class="form-control" placeholder="Ingrese la base">
            <button class="btn btn-primary mt-2 w-100" onclick="guardarBase()">Guardar</button>
        </div>
    </div>

    <script>
        function abrirModalBase() {
            document.getElementById('modalBase').style.display = 'flex';
        }

        function cerrarModalBase() {
            document.getElementById('modalBase').style.display = 'none';
        }

        function guardarBase() {
            let valorBase = document.getElementById('valorBase').value;
            let cajero = document.getElementById('cajero').value;

            if (!valorBase || valorBase <= 0) {
                alert("Ingrese un valor válido.");
                return;
            }

            // ✅ POST DIRECTO SIN INTERFERENCIAS
            fetch('<?php echo BASE_URL; ?>/controllers/guardar_base.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'base=' + encodeURIComponent(valorBase) + '&cajero=' + encodeURIComponent(cajero)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta:', data);
                if (data.status === 'success') {
                    alert("Base guardada correctamente.");
                    cerrarModalBase();
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Error en la conexión.");
            });
        }
    </script>
    <?php endif; ?>