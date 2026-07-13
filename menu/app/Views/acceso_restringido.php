<?php
/**
 * Página mostrada cuando alguien intenta entrar al menú sin un enlace válido.
 * Lo invita a solicitar el acceso por WhatsApp.
 */
$waConfig   = @include __DIR__ . '/../config/whatsapp.php';
$asesorUrl  = is_array($waConfig) ? ($waConfig['advisor_url'] ?? $waConfig['asesor_url'] ?? '') : '';
if (!$asesorUrl) {
    $asesorUrl = getenv('WA_ADVISOR_URL') ?: 'https://wa.me/573173667467';
}
$negocio = is_array($waConfig) ? ($waConfig['business_name'] ?? 'la cafetería') : 'la cafetería';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al menú</title>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%); padding: 24px;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 40px 28px; max-width: 420px; width: 100%;
            text-align: center; box-shadow: 0 12px 40px rgba(0,0,0,.3);
        }
        .icon { font-size: 56px; margin-bottom: 12px; }
        h1 { font-size: 22px; margin: 0 0 8px; color: #1a1a1a; }
        p { font-size: 15px; color: #555; line-height: 1.5; margin: 0 0 24px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; background: #25d366; color: #fff;
            text-decoration: none; font-weight: 700; font-size: 16px; padding: 14px 24px; border-radius: 10px;
            transition: background .2s;
        }
        .btn:hover { background: #1ebe5b; }
        .nota { margin-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>Acceso al menú restringido</h1>
        <p>Para hacer un pedido en <strong><?php echo htmlspecialchars($negocio); ?></strong> debes abrir el menú desde el enlace que te enviamos por WhatsApp.</p>
        <a class="btn" href="<?php echo htmlspecialchars($asesorUrl); ?>" target="_blank" rel="noopener">
            💬 Pedir el enlace por WhatsApp
        </a>
        <div class="nota">Escríbenos y te enviaremos el botón para entrar al menú.</div>
    </div>
</body>
</html>
