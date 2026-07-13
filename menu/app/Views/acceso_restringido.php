<?php
/**
 * Formulario para solicitar acceso/aprobación al menú.
 * Se muestra cuando un cliente no aprobado intenta entrar.
 */
$waConfig  = @include __DIR__ . '/../config/whatsapp.php';
$asesorUrl = is_array($waConfig) ? ($waConfig['advisor_url'] ?? $waConfig['asesor_url'] ?? '') : '';
if (!$asesorUrl) {
    $asesorUrl = getenv('WA_ADVISOR_URL') ?: 'https://wa.me/573161658438';
}
$negocio     = is_array($waConfig) ? ($waConfig['business_name'] ?? 'la cafetería') : 'la cafetería';
$numeroPrev  = preg_replace('/\D+/', '', (string) ($_GET['numero'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar acceso al menú</title>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:linear-gradient(135deg,#1a1a1a 0%,#333 100%); padding:24px; }
        .card { background:#fff; border-radius:16px; padding:34px 26px; max-width:420px; width:100%;
                box-shadow:0 12px 40px rgba(0,0,0,.3); }
        .icon { font-size:52px; text-align:center; margin-bottom:8px; }
        h1 { font-size:21px; margin:0 0 6px; color:#1a1a1a; text-align:center; }
        p.sub { font-size:14px; color:#555; line-height:1.5; margin:0 0 20px; text-align:center; }
        label { display:block; font-size:13px; font-weight:700; color:#333; margin:12px 0 4px; }
        input { width:100%; padding:12px; font-size:15px; border:1px solid #ccc; border-radius:8px; }
        .btn { width:100%; margin-top:18px; background:#10b981; color:#fff; border:none; font-weight:700;
               font-size:16px; padding:14px; border-radius:10px; cursor:pointer; }
        .btn:hover { background:#059669; }
        .wa { display:block; text-align:center; margin-top:16px; font-size:13px; color:#25d366; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📝</div>
        <h1>Solicitar acceso</h1>
        <p class="sub">Para pedir en <strong><?php echo htmlspecialchars($negocio); ?></strong> necesitas que un administrador apruebe tu cuenta. Deja tus datos y te habilitaremos.</p>

        <form method="POST" action="index.php?route=solicitar-acceso">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre">

            <label for="numero">Teléfono (WhatsApp)</label>
            <input type="tel" id="numero" name="numero" required placeholder="3001234567"
                   value="<?php echo htmlspecialchars($numeroPrev); ?>">

            <button type="submit" class="btn">Enviar solicitud</button>
        </form>

        <a class="wa" href="<?php echo htmlspecialchars($asesorUrl); ?>" target="_blank" rel="noopener">💬 ¿Dudas? Escríbenos por WhatsApp</a>
    </div>
</body>
</html>
