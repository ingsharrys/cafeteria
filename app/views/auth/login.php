<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PideYAPP - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/heiyubai/public/css/style.css?cache=efh">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body.cuerpo {
            background: #000000;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: #1a1a1a;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            border: 1px solid #333;
        }

        .brand-logo {
            max-width: 80px;
            height: auto;
            display: block;
            margin: 0 auto 20px;
        }

        .login-container h3 {
            color: #ffffff;
            text-align: center;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .login-container p {
            color: #999;
            text-align: center;
            margin-bottom: 30px;
            font-size: 13px;
        }

        .form-label {
            color: #e0e0e0;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            background-color: #222;
            border: 1px solid #333;
            color: #ffffff;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: #2a2a2a;
            border-color: #555;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(100, 100, 100, 0.2);
            outline: none;
        }

        .form-control::placeholder {
            color: #666;
        }

        .form-control::-webkit-autofill,
        .form-control::-webkit-autofill:hover,
        .form-control::-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #222 inset !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        .mb-3 {
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: #0066cc;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
            cursor: pointer;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 102, 204, 0.3);
            color: white;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.25);
            color: white;
        }

        .alert-danger {
            background-color: #2a1a1a;
            border: 1px solid #664444;
            color: #ff6b6b;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 25px;
            font-size: 13px;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 35px 25px;
            }

            .login-container h3 {
                font-size: 24px;
            }

            .brand-logo {
                max-width: 70px;
            }
        }
    </style>

    <?php if ($recaptchaEnabled && !empty($recaptchaSiteKey)): ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></script>
    <?php endif; ?>
</head>
<body class="cuerpo">
    <div class="login-container">
        <img src="../public/img/logo-pideyapp.png" alt="PideYAPP Logo" class="brand-logo">
        <h3>PideYAPP</h3>
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" id="login-form">
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                       placeholder="Tu correo" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="Tu contraseña" required>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
    </div>
    <?php if ($recaptchaEnabled && !empty($recaptchaSiteKey)): ?>
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo htmlspecialchars($recaptchaSiteKey); ?>', {action: 'login'})
                    .then(function(token) {
                        document.getElementById('recaptcha_token').value = token;
                    });
            });
        </script>
    <?php endif; ?>
</body>
</html>