<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario de Atención - Heiyubai</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-closed {
            max-width: 500px;
            width: 100%;
        }

        .closed-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .clock-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }

        h1 {
            color: #1a1a1a;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #666666;
            font-size: 16px;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .schedule-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 24px;
            margin: 32px 0;
            text-align: left;
        }

        .schedule-title {
            font-size: 14px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .schedule-time {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 8px 0;
        }

        .schedule-description {
            font-size: 13px;
            color: #666666;
            margin-top: 8px;
        }

        .contact-section {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 32px;
        }

        .contact-label {
            font-size: 12px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .contact-link:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
            text-decoration: none;
            color: white;
        }

        .whatsapp-link {
            background: #25d366;
        }

        .whatsapp-link:hover {
            background: #20ba5a;
            box-shadow: 0 8px 16px rgba(37, 211, 102, 0.4);
        }

        .footer-text {
            margin-top: 40px;
            font-size: 12px;
            color: #999999;
            line-height: 1.6;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .badge-info {
            display: inline-block;
            background: #e7f5ff;
            color: #1971c2;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        @media (max-width: 480px) {
            .closed-card {
                padding: 40px 24px;
            }

            h1 {
                font-size: 28px;
            }

            .clock-icon {
                font-size: 60px;
                margin-bottom: 20px;
            }

            .schedule-time {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container-closed">
    <div class="closed-card">
        <!-- Logo -->
        <div class="logo">
            <img src="http://localhost/cafeteria-pombo/public/img/logo-pideyapp.png" alt="PideYAPP">
        </div>

        <!-- Icono de reloj -->
        <div class="clock-icon">
            <i class="fas fa-clock"></i>
        </div>

        <!-- Badge -->
        <span class="badge-info">
            <i class="fas fa-info-circle"></i> Servicio Cerrado
        </span>

        <!-- Título -->
        <h1>Estamos Cerrados en Este Momento</h1>

        <!-- Descripción -->
        <p class="subtitle">
            Nos encantaría atender tu pedido, pero en este momento nuestro servicio no está disponible.
        </p>

        <!-- Horario de atención -->
        <div class="schedule-box">
            <div class="schedule-title">
                <i class="fas fa-calendar-alt"></i> Horario de Atención
            </div>
            <div class="schedule-time">10:00 AM - 10:00 PM</div>
            <div class="schedule-description">
                Disponible todos los días de la semana para tu comodidad
            </div>
        </div>

        <!-- Información adicional -->
        <p class="subtitle" style="margin-top: 32px;">
            Pero no te preocupes, estamos listos para servir tu pedido en cuanto abra nuestro servicio. 
        </p>

        <!-- Contacto -->
        <div class="contact-section">
            <div class="contact-label">
                <i class="fas fa-headset"></i> ¿Preguntas o Sugerencias?
            </div>
            <p style="font-size: 13px; color: #666666; margin-bottom: 12px;">
                Contáctanos y te responderemos lo antes posible
            </p>
            <a href="https://wa.me/573174742056?text=Hola%20Heiyubai%2C%20tengo%20una%20pregunta%20sobre%20mis%20pedidos" 
               class="contact-link whatsapp-link" target="_blank">
                <i class="fab fa-whatsapp"></i> Escríbenos por WhatsApp
            </a>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            <p>
                <strong>Heiyubai</strong> © 2024<br>
                <em>Sabor, calidad y puntualidad en cada pedido</em>
            </p>
        </div>
    </div>
</div>

</body>
</html>