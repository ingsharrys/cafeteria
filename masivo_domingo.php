<?php
// ==========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ==========================================
$dbHost = 'localhost';
$dbName = 'u936058592_restaurant';
$dbUser = 'u936058592_heiyu';
$dbPass = 'u;J7yx*F';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}

// ==========================================
// CONFIGURACIÓN DE PAGINACIÓN
// ==========================================
$porPagina = 10;

$paginaActual = isset($_GET['pagina']) && is_numeric($_GET['pagina'])
    ? max(1, (int) $_GET['pagina'])
    : 1;

// ==========================================
// RESUMEN GENERAL DE CLIENTES VÁLIDOS
// Solo celulares de 10 dígitos que inicien por 3
// ==========================================
$sqlResumen = "
    SELECT
        COUNT(*) AS total_validos,
        SUM(CASE WHEN envios_servicio_atencion > 0 THEN 1 ELSE 0 END) AS total_enviados,
        SUM(CASE WHEN envios_servicio_atencion = 0 THEN 1 ELSE 0 END) AS total_pendientes
    FROM clientes
    WHERE celular REGEXP '^3[0-9]{9}$'
";

$stmtResumen = $pdo->query($sqlResumen);
$resumen = $stmtResumen->fetch();

$totalClientes = (int) ($resumen['total_validos'] ?? 0);
$totalEnviados = (int) ($resumen['total_enviados'] ?? 0);
$totalPendientes = (int) ($resumen['total_pendientes'] ?? 0);

// ==========================================
// CÁLCULO DE PÁGINAS
// ==========================================
$totalPaginas = max(1, (int) ceil($totalClientes / $porPagina));

if ($paginaActual > $totalPaginas) {
    $paginaActual = $totalPaginas;
}

$offset = ($paginaActual - 1) * $porPagina;

// ==========================================
// CLIENTES VÁLIDOS PAGINADOS
// ==========================================
$sql = "
    SELECT 
        id,
        cliente,
        celular,
        email,
        direccion,
        cedula,
        barrio,
        envios_servicio_atencion
    FROM clientes
    WHERE celular REGEXP '^3[0-9]{9}$'
    ORDER BY cliente ASC
    LIMIT :limite OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$clientes = $stmt->fetchAll();

$desde = $totalClientes > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalClientes);

$porcentajeEnviado = $totalClientes > 0
    ? round(($totalEnviados / $totalClientes) * 100, 1)
    : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes válidos - Masivo Domingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .contenedor {
            max-width: 1250px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .subtitulo {
            color: #666;
            margin-top: 0;
            margin-bottom: 25px;
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #ddd;
        }

        .card .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .card .valor {
            font-size: 28px;
            font-weight: bold;
        }

        .card.total {
            background: #eef6ff;
            border-color: #cfe5ff;
        }

        .card.enviados {
            background: #eaf8ef;
            border-color: #c7ebd3;
        }

        .card.pendientes {
            background: #fff4e5;
            border-color: #f4ddb4;
        }

        .card.avance {
            background: #f4f0ff;
            border-color: #dfd5ff;
        }

        .barra-contenedor {
            width: 100%;
            height: 14px;
            background: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .barra {
            height: 100%;
            width: <?= $porcentajeEnviado ?>%;
            background: #198754;
        }

        .info-pagina {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .selector-pagina {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selector-pagina select {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: #fff;
        }

        .tabla-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            min-width: 1000px;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #222;
            color: #fff;
        }

        tr:hover {
            background: #f7f7f7;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-enviado {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-pendiente {
            background: #fff3cd;
            color: #664d03;
        }

        .sin-datos {
            text-align: center;
            padding: 20px;
            color: #777;
        }

        .paginacion-simple {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 22px;
        }

        .paginacion-simple a,
        .paginacion-simple span {
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #222;
            background: #fff;
        }

        .paginacion-simple a:hover {
            background: #f1f1f1;
        }

        .paginacion-simple .deshabilitado {
            color: #aaa;
            background: #f5f5f5;
        }

        @media (max-width: 900px) {
            body {
                padding: 15px;
            }

            .resumen-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 520px) {
            .resumen-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="contenedor">
    <h1>Clientes válidos para envío masivo domingo</h1>
    <p class="subtitulo">
        Seguimiento de la campaña con la plantilla <strong>servicio_de_atencion</strong>
    </p>

    <div class="resumen-grid">
        <div class="card total">
            <div class="label">Total válidos</div>
            <div class="valor"><?= $totalClientes ?></div>
        </div>

        <div class="card enviados">
            <div class="label">Enviados</div>
            <div class="valor"><?= $totalEnviados ?></div>
        </div>

        <div class="card pendientes">
            <div class="label">No enviados / pendientes</div>
            <div class="valor"><?= $totalPendientes ?></div>
        </div>

        <div class="card avance">
            <div class="label">Avance</div>
            <div class="valor"><?= $porcentajeEnviado ?>%</div>
        </div>
    </div>

    <div class="barra-contenedor">
        <div class="barra"></div>
    </div>

    <div class="info-pagina">
        <div>
            Mostrando del <strong><?= $desde ?></strong> al <strong><?= $hasta ?></strong>
            de <strong><?= $totalClientes ?></strong> clientes válidos
        </div>

        <form method="GET" class="selector-pagina">
            <label for="pagina">Ir a página:</label>
            <select name="pagina" id="pagina" onchange="this.form.submit()">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <option value="<?= $i ?>" <?= $i === $paginaActual ? 'selected' : '' ?>>
                        Página <?= $i ?> de <?= $totalPaginas ?>
                    </option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <div class="tabla-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Celular</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>Barrio</th>
                    <th>Envíos</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clientes)): ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <?php
                            $cantidadEnvios = (int) $cliente['envios_servicio_atencion'];
                            $enviado = $cantidadEnvios > 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['id']) ?></td>
                            <td><?= htmlspecialchars($cliente['cliente']) ?></td>
                            <td><?= htmlspecialchars($cliente['celular']) ?></td>
                            <td><?= htmlspecialchars($cliente['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['direccion']) ?></td>
                            <td><?= htmlspecialchars($cliente['barrio'] ?? '') ?></td>
                            <td><?= $cantidadEnvios ?></td>
                            <td>
                                <span class="badge <?= $enviado ? 'badge-enviado' : 'badge-pendiente' ?>">
                                    <?= $enviado ? 'Enviado' : 'Pendiente' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="sin-datos">
                            No hay clientes con celular válido.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="paginacion-simple">
        <?php if ($paginaActual > 1): ?>
            <a href="?pagina=<?= $paginaActual - 1 ?>">← Anterior</a>
        <?php else: ?>
            <span class="deshabilitado">← Anterior</span>
        <?php endif; ?>

        <span>Página <?= $paginaActual ?> de <?= $totalPaginas ?></span>

        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="?pagina=<?= $paginaActual + 1 ?>">Siguiente →</a>
        <?php else: ?>
            <span class="deshabilitado">Siguiente →</span>
        <?php endif; ?>
    </div>
</div>

</body>
</html>