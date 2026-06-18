<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
$usuario_correo = isset($_SESSION['usuario_correo']) ? htmlspecialchars($_SESSION['usuario_correo']) : 'usuario@apitech.com';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ayuda - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f5f5f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.ayuda-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

.ayuda-header {
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 40px;
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
    animation: slideDown 0.6s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ayuda-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
}

.ayuda-header p {
    font-size: 16px;
    opacity: 0.95;
}

.ayuda-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    justify-content: center;
}

.tab-btn {
    background: white;
    border: 2px solid #ddd;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    color: #333;
}

.tab-btn:hover {
    border-color: #FFC72C;
    color: #FFC72C;
}

.tab-btn.active {
    background: #FFC72C;
    color: #1a1a1a;
    border-color: #FFC72C;
}

.tab-content {
    display: none;
    animation: slideUp 0.6s ease;
}

.tab-content.active {
    display: block;
}

.ayuda-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    animation: slideUp 0.6s ease;
}

.ayuda-section h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #FFC72C;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ayuda-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 20px 0 10px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ayuda-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.ayuda-item:last-child {
    border-bottom: none;
}

.ayuda-item p {
    color: #666;
    line-height: 1.6;
    margin: 10px 0;
    font-size: 14px;
}

.faq-item {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid #FFC72C;
}

.faq-item:hover {
    background: rgba(255, 199, 44, 0.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.faq-question {
    font-weight: 600;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 12px;
    user-select: none;
}

.faq-answer {
    display: none;
    margin-top: 12px;
    color: #666;
    line-height: 1.6;
    font-size: 14px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.faq-answer.show {
    display: block;
}

.faq-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.faq-item.active .faq-icon {
    transform: rotate(180deg);
}

.step-list {
    list-style: none;
    padding: 0;
}

.step-list li {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 12px;
    border-left: 4px solid #FFC72C;
}

.step-list li strong {
    color: #FFC72C;
    font-size: 16px;
    display: block;
    margin-bottom: 5px;
}

.step-list li p {
    color: #666;
    margin: 5px 0 0 0;
    font-size: 14px;
}

.contact-box {
    background: linear-gradient(135deg, rgba(255, 199, 44, 0.1) 0%, rgba(255, 199, 44, 0.05) 100%);
    border: 2px solid #FFC72C;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    text-align: center;
}

.contact-box h3 {
    color: #1a1a1a;
    margin-bottom: 10px;
    border: none;
    font-size: 18px;
}

.contact-box p {
    color: #666;
    margin: 10px 0;
    font-size: 14px;
}

.contact-links {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 15px;
    flex-wrap: wrap;
}

.contact-btn {
    background: #FFC72C;
    color: #1a1a1a;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.contact-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 199, 44, 0.3);
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f3f4f6;
    color: #333;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin-bottom: 30px;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.feature-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #FFC72C;
}

.feature-card h4 {
    color: #1a1a1a;
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.feature-card p {
    color: #666;
    font-size: 13px;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .ayuda-container {
        padding: 20px 15px;
    }

    .ayuda-header {
        padding: 30px 20px;
    }

    .ayuda-header h1 {
        font-size: 24px;
    }

    .ayuda-tabs {
        flex-direction: column;
    }

    .tab-btn {
        width: 100%;
    }

    .feature-grid {
        grid-template-columns: 1fr;
    }

    .contact-links {
        flex-direction: column;
    }

    .contact-btn {
        justify-content: center;
    }
}
</style>
</head>

<body>

<div class="ayuda-container">

<!-- BOTÓN VOLVER -->
<a href="../DASHBOARD/dashboard.php" class="back-btn">
    <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
</a>

<!-- HEADER -->
<div class="ayuda-header">
    <h1><i class="fa-solid fa-circle-question"></i> Centro de Ayuda</h1>
    <p>Encuentra respuestas a tus preguntas sobre ApiTech</p>
</div>

<!-- TABS -->
<div class="ayuda-tabs">
    <button class="tab-btn active" onclick="mostrarTab('inicio')">
        <i class="fa-solid fa-home"></i> Inicio
    </button>
    <button class="tab-btn" onclick="mostrarTab('guias')">
        <i class="fa-solid fa-book"></i> Guías
    </button>
    <button class="tab-btn" onclick="mostrarTab('faq')">
        <i class="fa-solid fa-question"></i> Preguntas Frecuentes
    </button>
    <button class="tab-btn" onclick="mostrarTab('contacto')">
        <i class="fa-solid fa-envelope"></i> Contacto
    </button>
</div>

<!-- TAB: INICIO -->
<div id="tab-inicio" class="tab-content active">
    <div class="ayuda-section">
        <h2><i class="fa-solid fa-rocket"></i> Bienvenido a ApiTech</h2>
        <p>ApiTech es una plataforma de gestión inteligente para apiarios. Te ayudamos a monitorear, controlar y optimizar tus colmenas con tecnología avanzada de sensores IoT.</p>
    </div>

    <div class="ayuda-section">
        <h2><i class="fa-solid fa-star"></i> Características Principales</h2>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h4><i class="fa-solid fa-chart-line"></i> Dashboard</h4>
                <p>Visualiza en tiempo real métricas de tus colmenas con gráficos interactivos.</p>
            </div>
            <div class="feature-card">
                <h4><i class="fa-solid fa-microchip"></i> Sensores</h4>
                <p>Monitorea temperatura, humedad y otros parámetros de tus colmenas.</p>
            </div>
            <div class="feature-card">
                <h4><i class="fa-solid fa-jar"></i> Producción</h4>
                <p>Registra y analiza datos de producción de miel de cada colmena.</p>
            </div>
            <div class="feature-card">
                <h4><i class="fa-solid fa-triangle-exclamation"></i> Alertas</h4>
                <p>Recibe notificaciones automáticas sobre eventos importantes.</p>
            </div>
            <div class="feature-card">
                <h4><i class="fa-solid fa-file"></i> Reportes</h4>
                <p>Genera reportes detallados para análisis y toma de decisiones.</p>
            </div>
            <div class="feature-card">
                <h4><i class="fa-solid fa-box"></i> Colmenas</h4>
                <p>Gestiona todas tus colmenas en un solo lugar.</p>
            </div>
        </div>
    </div>
</div>

<!-- TAB: GUÍAS -->
<div id="tab-guias" class="tab-content">
    <div class="ayuda-section">
        <h2><i class="fa-solid fa-book"></i> Guías de Uso</h2>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-box"></i> Gestión de Colmenas</h3>
        <ul class="step-list">
            <li>
                <strong>Paso 1: Crear una Colmena</strong>
                <p>Ve a la sección "Colmenas" y haz clic en "Agregar Colmena". Completa los datos básicos como nombre, ubicación y estado.</p>
            </li>
            <li>
                <strong>Paso 2: Instalar Sensores</strong>
                <p>Una vez creada la colmena, ve a "Sensores" y registra los dispositivos IoT que has instalado.</p>
            </li>
            <li>
                <strong>Paso 3: Monitorear Datos</strong>
                <p>Accede al Dashboard para ver en tiempo real los datos de temperatura, humedad y otros parámetros.</p>
            </li>
            <li>
                <strong>Paso 4: Configurar Alertas</strong>
                <p>En "Configuración", personaliza qué alertas deseas recibir y los umbrales de activación.</p>
            </li>
        </ul>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-jar"></i> Registro de Producción</h3>
        <ul class="step-list">
            <li>
                <strong>Paso 1: Acceder a Producción</strong>
                <p>Desde el menú principal, selecciona "Producción".</p>
            </li>
            <li>
                <strong>Paso 2: Registrar Datos</strong>
                <p>Haz clic en "Nuevo Registro" e ingresa la cantidad de miel producida y la fecha.</p>
            </li>
            <li>
                <strong>Paso 3: Ver Estadísticas</strong>
                <p>Visualiza gráficos de producción histórica para análisis comparativos.</p>
            </li>
        </ul>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-file"></i> Generación de Reportes</h3>
        <ul class="step-list">
            <li>
                <strong>Paso 1: Ir a Reportes</strong>
                <p>Selecciona "Reportes" en el menú principal.</p>
            </li>
            <li>
                <strong>Paso 2: Seleccionar Parámetros</strong>
                <p>Elige el rango de fechas, tipo de reporte y colmenas a incluir.</p>
            </li>
            <li>
                <strong>Paso 3: Descargar o Compartir</strong>
                <p>Genera el reporte y descárgalo en PDF o comparte el enlace.</p>
            </li>
        </ul>
    </div>
</div>

<!-- TAB: FAQ -->
<div id="tab-faq" class="tab-content">
    <div class="ayuda-section">
        <h2><i class="fa-solid fa-question"></i> Preguntas Frecuentes</h2>
    </div>

    <div class="ayuda-section">
        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Cómo puedo crear una cuenta en ApiTech?
            </div>
            <div class="faq-answer">
                Visita la página de registro e ingresa tu email y contraseña. Luego confirma tu email siguiendo el enlace que te enviaremos. Una vez confirmado, podrás acceder a todas las funcionalidades de ApiTech.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Cuántos sensores puedo conectar a cada colmena?
            </div>
            <div class="faq-answer">
                Puedes conectar múltiples sensores a cada colmena. Cada sensor registrará datos independientes (temperatura, humedad, etc.). No hay límite de sensores, pero recomendamos un máximo de 5 por colmena para óptimo funcionamiento.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Con qué frecuencia se actualizan los datos?
            </div>
            <div class="faq-answer">
                Los datos de los sensores se actualizan en tiempo real, generalmente cada 5-10 minutos dependiendo de la configuración de tu dispositivo IoT. El Dashboard muestra siempre los datos más recientes disponibles.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Cómo cambio mi contraseña?
            </div>
            <div class="faq-answer">
                Ve a "Configuración" en el menú principal. En la sección "Seguridad", haz clic en "Cambiar Contraseña" e ingresa tu nueva contraseña dos veces para confirmar.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Puedo descargar mis datos?
            </div>
            <div class="faq-answer">
                Sí. Accede a "Reportes" y selecciona el período que deseas descargar. Puedes exportar los datos en formato PDF o CSV para análisis posterior.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Qué pasa si olvido mi contraseña?
            </div>
            <div class="faq-answer">
                En la página de login, haz clic en "¿Olvidaste tu contraseña?" e ingresa tu email. Te enviaremos un enlace para restaurar tu contraseña. El enlace es válido por 24 horas.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Es segura mi información?
            </div>
            <div class="faq-answer">
                Sí. Utilizamos cifrado SSL/TLS para proteger toda la comunicación, contraseñas encriptadas con BCRYPT, y cumplimos con estándares de seguridad internacionales. Tus datos están siempre protegidos.
            </div>
        </div>

        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">
                <div class="faq-icon"><i class="fa-solid fa-chevron-down"></i></div>
                ¿Cuál es el costo de ApiTech?
            </div>
            <div class="faq-answer">
                ApiTech ofrece diferentes planes. Contáctanos para conocer opciones personalizadas según el tamaño de tu apiario y necesidades específicas.
            </div>
        </div>
    </div>
</div>

<!-- TAB: CONTACTO -->
<div id="tab-contacto" class="tab-content">
    <div class="ayuda-section">
        <h2><i class="fa-solid fa-envelope"></i> Ponte en Contacto</h2>
        <p>¿No encuentras la respuesta que buscas? Nuestro equipo de soporte está disponible para ayudarte.</p>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-phone"></i> Información de Contacto</h3>
        
        <div class="contact-box">
            <h3>📞 Soporte por Teléfono</h3>
            <p>Lunes a Viernes: 9:00 AM - 6:00 PM</p>
            <p><strong style="font-size: 16px;">+1 (555) 123-4567</strong></p>
        </div>

        <div class="contact-box" style="margin-top: 20px;">
            <h3>📧 Correo Electrónico</h3>
            <p>Respuesta garantizada en 24 horas</p>
            <p><strong style="font-size: 16px;">soporte@apitech.com</strong></p>
        </div>

        <div class="contact-box" style="margin-top: 20px;">
            <h3>💬 Chat en Vivo</h3>
            <p>Disponible de lunes a viernes de 10:00 AM a 5:00 PM</p>
            <button class="contact-btn" onclick="alert('Chat en vivo iniciado')">
                <i class="fa-solid fa-message"></i> Iniciar Chat
            </button>
        </div>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-network-wired"></i> Redes Sociales</h3>
        <div class="contact-links">
            <a href="#" class="contact-btn">
                <i class="fa-brands fa-facebook"></i> Facebook
            </a>
            <a href="#" class="contact-btn">
                <i class="fa-brands fa-twitter"></i> Twitter
            </a>
            <a href="#" class="contact-btn">
                <i class="fa-brands fa-instagram"></i> Instagram
            </a>
            <a href="#" class="contact-btn">
                <i class="fa-brands fa-linkedin"></i> LinkedIn
            </a>
        </div>
    </div>

    <div class="ayuda-section">
        <h3><i class="fa-solid fa-map-location-dot"></i> Ubicación</h3>
        <p><strong>ApiTech Headquarters</strong></p>
        <p>Calle Principal 123</p>
        <p>Ciudad, Estado 12345</p>
        <p>País</p>
    </div>
</div>

</div>

<script>
function mostrarTab(tabName) {
    // Ocultar todos los tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remover clase active de los botones
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => btn.classList.remove('active'));
    
    // Mostrar el tab seleccionado
    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Marcar botón como activo
    event.target.classList.add('active');
}

function toggleFaq(element) {
    element.classList.toggle('active');
    const answer = element.querySelector('.faq-answer');
    answer.classList.toggle('show');
}

console.log('✓ Página de Ayuda cargada');
</script>

</body>
</html>