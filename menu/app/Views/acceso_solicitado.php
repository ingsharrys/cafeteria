<?php
/**
 * Confirmación tras solicitar acceso.
 * Variables esperadas: $ok (bool), $mensaje (string)
 */
$ok      = $ok ?? false;
$mensaje = $mensaje ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $ok ? 'Solicitud enviada' : 'Solicitud'; ?></title>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:linear-gradient(135deg,#1a1a1a 0%,#333 100%); padding:24px; }
        .card { background:#fff; border-radius:16px; padding:40px 28px; max-width:420px; width:100%;
                text-align:center; box-shadow:0 12px 40px rgba(0,0,0,.3); }
        .icon { font-size:56px; margin-bottom:12px; }
        h1 { font-size:22px; margin:0 0 8px; color:#1a1a1a; }
        p { font-size:15px; color:#555; line-height:1.5; margin:0; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($ok): ?>
            <div class="icon">✅</div>
            <h1>¡Solicitud enviada!</h1>
            <p>Tu solicitud quedó registrada. Un administrador la revisará y te habilitará muy pronto. Cuando estés aprobado podrás hacer tu pedido. 🙌</p>
        <?php else: ?>
            <div class="icon">⚠️</div>
            <h1>No se pudo enviar</h1>
            <p><?php echo htmlspecialchars($mensaje ?: 'Revisa tus datos e inténtalo de nuevo.'); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
