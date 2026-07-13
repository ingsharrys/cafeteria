<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;

// ✅ LÍNEA 1: Agregar el require del helper
require_once __DIR__ . '/../helpers/horario_helper.php';

class PedidoController
{
    public function index()
    {
        // ✅ LÍNEA 2 Y 3: Verificar horario (agregar AQUÍ, justo al inicio del método)
        if (!isOpen()) {
            $this->renderView('closed.php');
            return;
        }
        
        // ↓↓↓ TODO EL RESTO DEL CÓDIGO SIGUE EXACTAMENTE IGUAL ↓↓↓
        
        // Obtenemos los parámetros GET (corrige la URL para usar `pedido=qr`, NO `=qr`)
        $pedido   = $_GET['pedido'] ?? null;     // <<--- IMPORTANTE: aquí tomamos 'pedido' del query
        $telefono = $_GET['numero'] ?? null;

        // Conversión del teléfono, si empieza con +
        $celular  = (substr($telefono, 0, 1) === '+') ? substr($telefono, 3) : $telefono;

        /**
         * Definimos $tipo_solicitud con valor por defecto 1 si no hay coincidencia
         * Antes tenía: ( $pedido === 'call') ? 53 : null
         * Ahora usamos 1 en lugar de null, para evitar columnas vacías en la BD
         */
        $tipo_solicitud = ($pedido === 'qr')
            ? 51
            : (($pedido === 'wp')
                ? 50
                : (($pedido === 'call')
                    ? 53
                    : 1  // Valor por defecto si no coincide nada
                )
            );

        // Fecha de Colombia
        date_default_timezone_set('America/Bogota');
        $fecha_actual = date('Y-m-d');

        // Conexión a BD
        $database = new Database();
        $db = $database->getConnection();

        // Creamos instancias de los modelos
        $clienteModel = new Cliente($db);
        $pedidoModel  = new Pedido($db);
        $productoModel= new Producto($db);

        // Buscamos cliente por celular
        $cliente = $clienteModel->getClienteByCelular($celular);

        $pedidosPendientes = [];
        $nombreCliente     = '';
        $direccionCliente  = '';
        $emailCliente      = '';
        $cedulaCliente     = '';
        $barrioCliente     = '';

        if ($cliente) {
            // Si existe el cliente, obtenemos los pedidos pendientes
            $id_cliente = $cliente['id'];
            $pedidosPendientes = $pedidoModel->getPedidosPendientes($id_cliente, $fecha_actual);

            // Datos del cliente
            $nombreCliente    = $cliente['cliente'];
            $direccionCliente = $cliente['direccion'];
            $emailCliente     = $cliente['email'];
            $cedulaCliente    = $cliente['cedula'];
            $barrioCliente    = $cliente['barrio'];
        }

        // Cargamos productos
        $productos = $productoModel->getAllWithPrices();

        // Organizar productos por id_pro
        $productosOrganizados = [];
        foreach ($productos as $producto) {
            $id_pro = $producto['id_pro'];
            if (!isset($productosOrganizados[$id_pro])) {
               $productosOrganizados[$id_pro] = [
                'id_pro'    => $producto['id_pro'],
                'nombre'    => $producto['nombre'],
                'prefijo'   => $producto['prefijo'],
                'img'       => $producto['img'],
                'descript'  => $producto['descript'],
                'cat'       => $producto['cat'],
                'tcomida'   => $producto['tcomida'],  // Asegurar que tcomida esté aquí
                'precios'   => [],
            ];

            }
            $productosOrganizados[$id_pro]['precios'][] = [
                'tipo_prod'   => $producto['tipo_prod'],
                'precio_tipo' => $producto['precio_tipo'],
            ];
        }

        // Día de la semana
        $dia_semana = date('N');

        // Preparar datos para la vista
        $data = [
            'tipo_solicitud'       => $tipo_solicitud,  // <<-- Llevará 51,50,53 ó 1
            'celular'              => $celular,
            'pedidosPendientes'    => $pedidosPendientes,
            'pedidosPendientesJSON' => json_encode($pedidosPendientes, JSON_UNESCAPED_UNICODE),
            'nombreCliente'        => $nombreCliente,
            'direccionCliente'     => $direccionCliente,
            'emailCliente'         => $emailCliente,
            'cedulaCliente'        => $cedulaCliente,
            'barrioCliente'        => $barrioCliente,
            'productosOrganizados' => $productosOrganizados,
            'dia_semana'           => $dia_semana,
            'pedido'               => $pedido // Para uso en la vista
        ];

        // Renderizamos la vista
        $this->renderView('pedidos.view.php', $data);
    }



    // Método para guardar el pedido (inserción) y devolver JSON:
    // ↓↓↓ ESTE MÉTODO NO CAMBIA NADA ↓↓↓
    public function store()
    {
        // 1. Conexión a la BD
        $database = new Database();
        $db = $database->getConnection();

        // 2. Instanciar modelos
        $clienteModel = new Cliente($db);
        $pedidoModel  = new Pedido($db);
        // (No necesitas Producto aquí para insertar pedidos, a menos que lo requieras por otra lógica)

        // 3. Recibir datos de $_POST
        //    (Mismo nombre de campos que en tu JS: name, phone, address, barrio, email, etc.)
        $name          = $_POST['name']            ?? '';
        $phone         = $_POST['phone']           ?? '';
        $address       = $_POST['address']         ?? '';
        $barrio        = $_POST['barrio']          ?? '';
        $email         = $_POST['email']           ?? 'sincorreo';
        $cedula        = $_POST['id']              ?? '0';
        $tipo_solicitud= $_POST['tipo_solicitud']  ?? 1;
        $metodo_pago   = $_POST['metodo_pago']     ?? 'Efectivo';
        $comments      = $_POST['comments']        ?? '';

        // Los productos llegan como JSON (FormData). Se admite también un
        // array directo por compatibilidad con envíos antiguos.
        $products = $_POST['products'] ?? [];
        if (is_string($products)) {
            $decoded  = json_decode($products, true);
            $products = is_array($decoded) ? $decoded : [];
        }

        // Si el método es Transferencia, el comprobante (imagen) es obligatorio
        if ($metodo_pago === 'Transferencia') {
            $errFile = $_FILES['payment_evidence']['error'] ?? UPLOAD_ERR_NO_FILE;

            if (empty($_FILES['payment_evidence']) || $errFile === UPLOAD_ERR_NO_FILE) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Debes adjuntar la imagen del comprobante de la transferencia.'
                ]);
                return;
            }
            if ($errFile === UPLOAD_ERR_INI_SIZE || $errFile === UPLOAD_ERR_FORM_SIZE) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'La imagen del comprobante es demasiado grande. Usa una foto más liviana (máx. 12 MB).'
                ]);
                return;
            }
            if ($errFile !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No se pudo subir el comprobante. Inténtalo de nuevo.'
                ]);
                return;
            }
        }

        try {
            // 4. Verificar si el cliente existe por su teléfono
            $existe = $clienteModel->getClienteByCelular($phone);
            if ($existe) {
                // Actualizar cliente
                $clienteModel->updateCliente($existe['id'], [
                    'name'    => $name,
                    'email'   => $email,
                    'address' => $address,
                    'barrio'  => $barrio,
                    'cedula'  => $cedula
                ]);
                $clientId = $existe['id'];
            } else {
                // Crear cliente
                $clientId = $clienteModel->createCliente([
                    'name'    => $name,
                    'phone'   => $phone,
                    'email'   => $email,
                    'address' => $address,
                    'barrio'  => $barrio,
                    'cedula'  => $cedula
                ]);
            }

            // 4b. Control de aprobación: solo clientes aprobados pueden pedir.
            //     El cliente ya quedó creado/actualizado arriba (pendiente si es
            //     nuevo), así que el administrador podrá aprobarlo desde el panel.
            require_once __DIR__ . '/../helpers/menu_access.php';
            $esAdmin = !empty($_SESSION['menu_acceso']['admin']);
            if (menu_require_approval() && !$esAdmin) {
                $cli = $clienteModel->getClienteByCelular($phone);
                // Si la columna 'aprobado' no existe todavía, no se bloquea.
                $aprobado = array_key_exists('aprobado', (array) $cli) ? (int) $cli['aprobado'] : 1;
                if ($aprobado !== 1) {
                    echo json_encode([
                        'status'  => 'pending',
                        'message' => 'Tu cuenta está pendiente de aprobación. Un administrador te habilitará muy pronto. 🙌'
                    ]);
                    return;
                }
            }

            // 5. Insertar pedido (productos, turno, comentarios...)
            //    Para eso, define un método createPedido() en tu modelo Pedido
            $dataPedido = [
                
                'tipo_solicitud' => $tipo_solicitud,
                'products'       => $products,
                'comments'       => $comments
            ];

            // Llamamos a createPedido del modelo Pedido
            $result = $pedidoModel->createPedido($dataPedido, $clientId);

            if ($result['status'] === 'success') {
                // 🆕 INSERTAR COSTO DE DOMICILIO
                $clienteModel->insertCostoDomicilioCliente($barrio, $result['order_number']);

                // 🆕 GUARDAR COMPROBANTE DE TRANSFERENCIA (si aplica)
                if ($metodo_pago === 'Transferencia' && !empty($_FILES['payment_evidence'])) {
                    $this->savePaymentEvidence((int) $result['order_number'], $_FILES['payment_evidence']);
                }
            }

            // 6. Responder en JSON
            echo json_encode($result);

        } catch (\PDOException $e) {
            // En caso de error de la base de datos
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Guardar la imagen del comprobante de transferencia asociada a un pedido.
     * Se almacena como public/img/payments/{numero_pedido}.{ext}, igual que el
     * flujo de subida desde la vista de estado del pedido.
     *
     * @return bool  true si se guardó correctamente
     */
    private function savePaymentEvidence(int $orderNumber, array $file): bool
    {
        if ($orderNumber <= 0 || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }

        // Validar que realmente sea una imagen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (strpos((string) $mime, 'image/') !== 0) {
            return false;
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            $ext = 'jpg';
        }

        $uploadsDir = __DIR__ . '/../../../public/img/payments/';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
            return false;
        }

        // Eliminar comprobantes previos del mismo pedido (otras extensiones)
        foreach (glob($uploadsDir . $orderNumber . '.*') ?: [] as $prev) {
            if (is_file($prev)) {
                @unlink($prev);
            }
        }

        $target = $uploadsDir . $orderNumber . '.' . $ext;

        return move_uploaded_file($file['tmp_name'], $target);
    }

    private function renderView($viewName, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewName;
    }
}