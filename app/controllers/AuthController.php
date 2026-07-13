<?php
namespace App\Controllers;

use Config\Config;
use Core\Session;
use Core\Token;
use Core\Validator;
use Core\RateLimiter;
use Core\Logger;
use Core\Response;
use App\Models\User;

/**
 * Controlador de autenticación con sistema de roles
 * 
 * FLUJO DE LOGIN:
 * 1. processLogin() → autentica email/password → guarda SOLO user_id en sesión
 * 2. Dashboard carga → footer.php detecta que NO hay cajero → muestra modal
 * 3. Modal envía código mesero → api.php?route=auth/validar_codigo
 * 4. AuthApiController valida código → guarda cajero/rol/permisos en sesión
 * 5. Página recarga → header muestra menú según rol del mesero
 */
class AuthController
{
    private $db;
    private $user;
    private $validator;
    private $rateLimiter;

    public function __construct()
    {
        Session::start();
        Response::setSecurityHeaders();
        
        $database = \Database::getInstance();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->validator = new Validator();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        if (Session::exists('user_id')) {
            Response::redirectToDashboard();
        }

        $data = [
            'recaptchaEnabled' => Config::get('RECAPTCHA_ENABLED', false),
            'recaptchaSiteKey' => Config::get('RECAPTCHA_SITE_KEY', ''),
            'csrfToken' => Token::generate(),
            'error' => Response::getError(),
            'success' => Response::getSuccess(),
            'email' => Response::old('email'),
        ];

        $this->loadView('auth/login', $data);
    }

    /**
     * Procesar login con validaciones y roles
     */
    public function processLogin()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Response::redirectToLogin();
        }

        // 1. CSRF
        if (!Token::validate($_POST['csrf_token'] ?? '')) {
            Logger::security('CSRF token inválido en intento de login');
            Response::redirectWithError(LOGIN_URL, 'Sesión expirada. Por favor, intenta nuevamente.');
        }

        // 2. Obtener datos
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';

        // 3. Validaciones
        $this->validator->required($email, 'email');
        $this->validator->required($password, 'password');

        if ($this->validator->hasErrors()) {
            Response::redirectWithError(LOGIN_URL, 'Por favor, completa todos los campos.', ['email' => $email]);
        }

        // 4. Email válido
        if (!$this->validator->validateEmail($email)) {
            Logger::security('Formato de email inválido', ['email' => $email]);
            Response::redirectWithError(LOGIN_URL, 'Las credenciales proporcionadas son incorrectas.', ['email' => $email]);
        }

        // 5. Rate limiting
        $rateLimitKey = RateLimiter::generateKey($email);
        
        if ($this->rateLimiter->tooManyAttempts($rateLimitKey)) {
            Logger::logAccountLocked($email);
            $message = $this->rateLimiter->getLockoutMessage($rateLimitKey);
            Response::redirectWithError(LOGIN_URL, $message);
        }

        // 6. reCAPTCHA
        if (Config::get('RECAPTCHA_ENABLED', false)) {
            if (!$this->verifyRecaptcha($recaptchaToken)) {
                $this->rateLimiter->hit($rateLimitKey);
                Logger::security('reCAPTCHA falló', ['email' => $email]);
                Response::redirectWithError(LOGIN_URL, 'Verificación de seguridad fallida.', ['email' => $email]);
            }
        }

        // 7. Intentar login
        $loginResult = $this->attemptLogin($email, $password);

        if ($loginResult['success']) {
            $this->rateLimiter->clear($rateLimitKey);
            Logger::logSuccessfulLogin($email);
            Response::redirectToDashboard();
        } else {
            $this->rateLimiter->hit($rateLimitKey);
            $attemptsRemaining = $this->rateLimiter->attemptsRemaining($rateLimitKey);
            
            Logger::logFailedLogin($email, $loginResult['reason']);
            
            $message = 'Las credenciales proporcionadas son incorrectas.';
            if ($attemptsRemaining > 0 && $attemptsRemaining <= 2) {
                $message .= " Te quedan {$attemptsRemaining} intentos.";
            }
            
            Response::redirectWithError(LOGIN_URL, $message, ['email' => $email]);
        }
    }

    /**
     * Intentar autenticación
     * 
     * IMPORTANTE: Solo guarda datos BÁSICOS de autenticación.
     * El cajero, rol y permisos se asignan desde el modal de código mesero
     * (AuthApiController::validarCodigo)
     */
    private function attemptLogin($email, $password)
    {
        $this->user->email = $email;
        
        if ($this->user->authenticate($password)) {
            // Regenerar ID de sesión
            session_regenerate_id(true);
            
            // ═══ SOLO DATOS BÁSICOS DE AUTH ═══
            // El cajero y permisos se asignan cuando el usuario
            // ingresa su código en el modal (validar_codigo)
            Session::set('user_id', $this->user->id);
            Session::set('username', $this->user->username);
            Session::set('user_email', $this->user->email);
            Session::set('last_activity', time());
            
            // NO establecer cajero/usuario/rol aquí
            // El modal de validación se encarga de eso
            
            return ['success' => true, 'reason' => null];
        }
        
        return ['success' => false, 'reason' => 'invalid_credentials'];
    }

    /**
     * Logout
     */
    public function logout()
    {
        $email = Session::get('user_email', 'unknown');
        Logger::logLogout($email);
        Session::destroy();
        Response::redirectWithSuccess(LOGIN_URL, 'Has cerrado sesión exitosamente.');
    }

    /**
     * Verificar reCAPTCHA v3
     */
    private function verifyRecaptcha($recaptchaToken)
    {
        $secretKey = Config::get('RECAPTCHA_SECRET_KEY');
        
        if (empty($secretKey) || empty($recaptchaToken)) {
            return !Config::isProduction();
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $secretKey,
            'response' => $recaptchaToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            Logger::error('Error al verificar reCAPTCHA');
            return true;
        }

        $response = json_decode($result, true);
        $minScore = Config::get('RECAPTCHA_MIN_SCORE', 0.5);
        $isValid = isset($response['success']) && $response['success'] === true;
        
        if (isset($response['score'])) {
            $isValid = $isValid && $response['score'] >= $minScore;
        }

        return $isValid;
    }

    /**
     * Cargar vista
     */
    /**
 * Cargar vista
 */
private function loadView($view, $data = [])
{
    extract($data);
    
    // Primera opción: buscar en /views/
    $viewPath = dirname(dirname(__DIR__)) . '/views/' . str_replace('.', '/', $view) . '.php';
    
    // Si no existe, intentar en raíz (para login.php, register.php)
    if (!file_exists($viewPath)) {
        $fileName = basename($view);
        if ($view === 'auth/login') {
            $viewPath = dirname(dirname(__DIR__)) . '/app/views/auth/login.php';
        } elseif ($view === 'auth/register') {
            $viewPath = dirname(dirname(__DIR__)) . '/register.php';
        }
    }
    
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        Response::notFound("Vista no encontrada: {$view} (buscada en: {$viewPath})");
    }
}

    /**
     * Mostrar página de registro
     */
    public function showRegister()
    {
        if (Session::exists('user_id')) {
            Response::redirectToDashboard();
        }

        $data = [
            'csrfToken' => Token::generate(),
            'error' => Response::getError(),
            'success' => Response::getSuccess(),
        ];

        $this->loadView('auth/register', $data);
    }

    /**
     * Procesar registro
     */
    public function processRegister()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Response::redirect(BASE_URL . '/views/register.php');
        }

        if (!Token::validate($_POST['csrf_token'] ?? '')) {
            Response::redirectWithError(BASE_URL . '/views/register.php', 'Token de seguridad inválido.');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $this->validator->required($username, 'username');
        $this->validator->required($email, 'email');
        $this->validator->required($password, 'password');
        $this->validator->validateEmail($email);
        $this->validator->validatePassword($password);
        $this->validator->matches($password, $passwordConfirm, 'password_confirm');

        if ($this->validator->hasErrors()) {
            $error = $this->validator->getFirstError();
            Response::redirectWithError(
                BASE_URL . '/views/register.php', $error,
                ['username' => $username, 'email' => $email]
            );
        }

        if ($this->user->emailExists($email)) {
            Response::redirectWithError(
                BASE_URL . '/views/register.php', 'Este email ya está registrado.',
                ['username' => $username, 'email' => $email]
            );
        }

        $this->user->username = $username;
        $this->user->email = $email;
        $this->user->password = $password;

        if ($this->user->create()) {
            Logger::info("Nuevo usuario registrado: {$email}");
            Response::redirectWithSuccess(LOGIN_URL, 'Cuenta creada exitosamente. Ya puedes iniciar sesión.');
        } else {
            Logger::error("Error al crear usuario: {$email}");
            Response::redirectWithError(
                BASE_URL . '/views/register.php', 'Error al crear la cuenta.',
                ['username' => $username, 'email' => $email]
            );
        }
    }
}