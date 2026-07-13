<?php
/**
 * instalador_simple.php
 * Instalador simplificado con output en tiempo real
 * 
 * EJECUTAR: http://localhost/heiyubai/instalador_simple.php
 */

// Forzar mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Deshabilitar output buffering para ver resultados en tiempo real
if (ob_get_level()) ob_end_clean();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador PideYApp</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #252526;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 30px;
            font-size: 2em;
            text-align: center;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 15px;
        }
        .log {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        .log-line {
            margin: 8px 0;
            padding: 5px;
            display: flex;
            align-items: flex-start;
        }
        .log-icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        .info { color: #569cd6; }
        .step {
            color: #dcdcaa;
            font-weight: bold;
            font-size: 1.1em;
            margin-top: 15px;
        }
        .code {
            background: #3c3c3c;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ce9178;
        }
        .summary {
            background: #2d2d30;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #4ec9b0;
        }
        .summary h2 {
            color: #4ec9b0;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4ec9b0;
            color: #1e1e1e;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #6fdfc0;
            transform: translateY(-2px);
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalador PideYApp - Modo Simple</h1>
        <div class="log" id="log">
<?php

flush(); // Enviar lo que llevamos al navegador

// ============================================
// FUNCIONES HELPER
// ============================================

function logMsg($type, $icon, $message) {
    echo "<div class='log-line $type'>";
    echo "<span class='log-icon'>$icon</span>";
    echo "<span>$message</span>";
    echo "</div>";
    flush();
    usleep(100000); // 0.1 segundos de pausa para que se vea
}

function logSuccess($msg) { logMsg('success', '✓', $msg); }
function logError($msg) { logMsg('error', '✗', $msg); }
function logWarning($msg) { logMsg('warning', '⚠', $msg); }
function logInfo($msg) { logMsg('info', 'ℹ', $msg); }
function logStep($msg) { logMsg('step', '▶', $msg); }

// ============================================
// VARIABLES
// ============================================

$root = __DIR__;
$errores = 0;
$exitos = 0;
$avisos = 0;

// ============================================
// INICIO
// ============================================

logStep("INICIO DE INSTALACIÓN");
logInfo("Directorio: <span class='code'>$root</span>");

// ============================================
// PASO 1: CREAR CARPETAS
// ============================================

logStep("PASO 1: Creando estructura de carpetas");

$carpetas = [
    'app',
    'app/controllers',
    'app/models',
    'app/middleware',
    'Core',
    'config',
    'routes',
    'storage',
    'storage/logs',
    'storage/sessions',
    'storage/cache'
];

foreach ($carpetas as $carpeta) {
    $path = "$root/$carpeta";
    if (!is_dir($path)) {
        if (@mkdir($path, 0755, true)) {
            logSuccess("Carpeta creada: <span class='code'>$carpeta</span>");
            $exitos++;
        } else {
            logError("No se pudo crear: <span class='code'>$carpeta</span>");
            $errores++;
        }
    } else {
        logInfo("Ya existe: <span class='code'>$carpeta</span>");
    }
}

// ============================================
// PASO 2: ORGANIZAR ARCHIVOS CORE
// ============================================

logStep("PASO 2: Organizando archivos Core");

$archivosCore = [
    'Session.php',
    'Token.php',
    'Response.php',
    'Logger.php',
    'Validator.php',
    'RateLimiter.php',
    'Cache.php'
];

foreach ($archivosCore as $archivo) {
    $origen = "$root/$archivo";
    $destino = "$root/Core/$archivo";
    
    if (file_exists($origen) && !file_exists($destino)) {
        if (@copy($origen, $destino)) {
            logSuccess("Core copiado: <span class='code'>$archivo</span>");
            $exitos++;
        } else {
            logError("Error copiando: <span class='code'>$archivo</span>");
            $errores++;
        }
    } elseif (file_exists($destino)) {
        logInfo("Ya existe en Core: <span class='code'>$archivo</span>");
    } else {
        logWarning("No encontrado: <span class='code'>$archivo</span>");
        $avisos++;
    }
}

// ============================================
// PASO 3: ORGANIZAR CONFIG
// ============================================

logStep("PASO 3: Organizando archivos de configuración");

$archivosConfig = [
    'app.php',
    'constants.php',
    'database.php'
];

foreach ($archivosConfig as $archivo) {
    $origen = "$root/$archivo";
    $destino = "$root/config/$archivo";
    
    if (file_exists($origen) && !file_exists($destino)) {
        if (@copy($origen, $destino)) {
            logSuccess("Config copiado: <span class='code'>$archivo</span>");
            $exitos++;
        } else {
            logError("Error copiando: <span class='code'>$archivo</span>");
            $errores++;
        }
    } elseif (file_exists($destino)) {
        logInfo("Ya existe en config: <span class='code'>$archivo</span>");
    } else {
        logWarning("No encontrado: <span class='code'>$archivo</span>");
        $avisos++;
    }
}

// ============================================
// PASO 4: INSTALAR CONTROLLERS
// ============================================

logStep("PASO 4: Instalando controllers");

$controllers = [
    'AuthController.php',
    'PedidoController.php',
    'MesaController.php',
    'TurnoController.php',
    'MeseroController.php',
    'CajaController.php'
];

foreach ($controllers as $controller) {
    $origen = "$root/$controller";
    $destino = "$root/app/controllers/$controller";
    
    if (file_exists($origen)) {
        if (file_exists($destino)) {
            logInfo("Controller ya existe: <span class='code'>$controller</span>");
        } else {
            if (@copy($origen, $destino)) {
                logSuccess("Controller instalado: <span class='code'>$controller</span>");
                $exitos++;
            } else {
                logError("Error instalando: <span class='code'>$controller</span>");
                $errores++;
            }
        }
    } else {
        logWarning("Controller no encontrado: <span class='code'>$controller</span>");
        $avisos++;
    }
}

// ============================================
// PASO 5: INSTALAR MODELS
// ============================================

logStep("PASO 5: Instalando modelos");

$models = [
    'User.php',
    'Pedido.php',
    'Mesa.php',
    'Turno.php',
    'Mesero.php',
    'Cliente.php',
    'Producto.php',
    'Caja.php',
    'Gasto.php',
    'Domicilio.php'
];

foreach ($models as $model) {
    $origen = "$root/$model";
    $destino = "$root/app/models/$model";
    
    if (file_exists($origen)) {
        if (file_exists($destino)) {
            logInfo("Modelo ya existe: <span class='code'>$model</span>");
        } else {
            if (@copy($origen, $destino)) {
                logSuccess("Modelo instalado: <span class='code'>$model</span>");
                $exitos++;
            } else {
                logError("Error instalando: <span class='code'>$model</span>");
                $errores++;
            }
        }
    } else {
        logWarning("Modelo no encontrado: <span class='code'>$model</span>");
        $avisos++;
    }
}

// ============================================
// PASO 6: INSTALAR ROUTER
// ============================================

logStep("PASO 6: Instalando sistema de routing");

// Router
if (file_exists("$root/Router.php")) {
    $destino = "$root/app/Router.php";
    if (file_exists($destino)) {
        logInfo("Router ya existe");
    } else {
        if (@copy("$root/Router.php", $destino)) {
            logSuccess("Router instalado: <span class='code'>app/Router.php</span>");
            $exitos++;
        } else {
            logError("Error instalando Router");
            $errores++;
        }
    }
} else {
    logWarning("Router.php no encontrado - descárgalo de Claude");
    $avisos++;
}

// Routes
if (file_exists("$root/routes.php")) {
    $destino = "$root/routes/api.php";
    if (file_exists($destino)) {
        logInfo("Rutas ya existen");
    } else {
        if (@copy("$root/routes.php", $destino)) {
            logSuccess("Rutas instaladas: <span class='code'>routes/api.php</span>");
            $exitos++;
        } else {
            logError("Error instalando rutas");
            $errores++;
        }
    }
} else {
    logWarning("routes.php no encontrado - descárgalo de Claude");
    $avisos++;
}

// Middleware
if (file_exists("$root/AuthMiddleware.php")) {
    $destino = "$root/app/middleware/AuthMiddleware.php";
    if (file_exists($destino)) {
        logInfo("Middleware ya existe");
    } else {
        if (@copy("$root/AuthMiddleware.php", $destino)) {
            logSuccess("Middleware instalado: <span class='code'>app/middleware/AuthMiddleware.php</span>");
            $exitos++;
        } else {
            logError("Error instalando middleware");
            $errores++;
        }
    }
} else {
    logWarning("AuthMiddleware.php no encontrado - descárgalo de Claude");
    $avisos++;
}

// ============================================
// PASO 7: CREAR ARCHIVOS .GITKEEP
// ============================================

logStep("PASO 7: Creando archivos .gitkeep");

$keepDirs = [
    'storage/logs',
    'storage/sessions',
    'storage/cache'
];

foreach ($keepDirs as $dir) {
    $file = "$root/$dir/.gitkeep";
    if (!file_exists($file)) {
        if (@file_put_contents($file, '')) {
            logSuccess("Creado: <span class='code'>$dir/.gitkeep</span>");
        }
    }
}

// ============================================
// RESUMEN
// ============================================

logStep("INSTALACIÓN COMPLETADA");

echo "</div>"; // Cerrar log

echo "<div class='summary'>";
echo "<h2>📊 Resumen de la Instalación</h2>";
echo "<p>✅ Éxitos: <strong>$exitos</strong></p>";
echo "<p>❌ Errores: <strong>$errores</strong></p>";
echo "<p>⚠️ Avisos: <strong>$avisos</strong></p>";

if ($errores > 0) {
    echo "<p style='color: #f48771; margin-top: 15px;'>";
    echo "⚠️ Se encontraron errores. Revisa el log arriba para más detalles.";
    echo "</p>";
}

if ($avisos > 0) {
    echo "<p style='color: #ce9178; margin-top: 15px;'>";
    echo "⚠️ Hay archivos faltantes. Descárgalos de Claude y vuelve a ejecutar el instalador.";
    echo "</p>";
}

if ($errores == 0 && $avisos == 0) {
    echo "<p style='color: #4ec9b0; margin-top: 15px; font-size: 1.2em;'>";
    echo "🎉 ¡Instalación exitosa! El sistema está listo.";
    echo "</p>";
}

echo "</div>";

echo "<div class='actions'>";
echo "<a href='test_diagnostico.php' class='btn'>🔍 Ejecutar Diagnóstico</a>";
echo "<a href='instalador_simple.php' class='btn'>🔄 Ejecutar Nuevamente</a>";

if ($errores == 0) {
    echo "<a href='index.php' class='btn' style='background: #569cd6;'>🏠 Ir al Sistema</a>";
}

echo "</div>";

?>
    </div>
</body>
</html>