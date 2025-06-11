<?php
// =============================================
// CONFIGURACIÓN INICIAL
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1); // Debug en producción
header('X-Powered-By: FinBalance'); // Seguridad

$basePath = __DIR__;
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =============================================
// CONFIGURACIÓN ESPECÍFICA PARA TU ESTRUCTURA
// =============================================
$config = [
    'static_dir' => '/frontend',
    'api_routes' => [
        '/auth/' => '/backend/ControllerAuth/',
        '/expenses/' => '/backend/ControllerExpenses/',
        '/goals/' => '/backend/ControllerGoals/',
        '/api/' => '/backend/' // Para dashboard.php, profile.php
    ],
    'frontend_routes' => [
        '/' => '/index.html',
        '/login' => '/login.html',
        '/register' => '/register.html'
    ]
];

// =============================================
// FUNCIONES AUXILIARES
// =============================================
function serveStatic($file) {
    if (!file_exists($file)) return false;

    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg|jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        'html' => 'text/html'
    ];

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    foreach ($mimeTypes as $pattern => $mime) {
        if (preg_match("/$pattern/", $ext)) {
            header("Content-Type: $mime");
            break;
        }
    }
    readfile($file);
    return true;
}

function executeController($file) {
    if (!file_exists($file)) {
        return false;
    }

    // Configuración para ejecución segura
    $_SERVER['SCRIPT_FILENAME'] = $file;
    chdir(dirname($file));
    include $file;
    return true;
}

// =============================================
// MANEJO DE RUTAS
// =============================================

// 1. Archivos estáticos (CSS, JS, imágenes)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|html)$/i', $requestPath)) {
    $staticFile = $basePath . $config['static_dir'] . $requestPath;
    if (serveStatic($staticFile)) exit;
}

// 2. API Routes - Adaptado a tu estructura exacta
foreach ($config['api_routes'] as $prefix => $dir) {
    if (strpos($requestPath, $prefix) === 0) {
        $endpoint = substr($requestPath, strlen($prefix));
        $phpFile = $basePath . $dir . $endpoint;
        
        // Añadir .php si no está presente y el archivo existe
        if (!preg_match('/\.php$/i', $phpFile)) {
            if (file_exists($phpFile . '.php')) {
                $phpFile .= '.php';
            }
        }

        // Forzar JSON en POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
        }

        if (executeController($phpFile)) exit;
    }
}

// 3. Rutas frontend específicas
foreach ($config['frontend_routes'] as $route => $file) {
    if ($requestPath === $route) {
        $htmlFile = $basePath . $config['static_dir'] . $file;
        if (serveStatic($htmlFile)) exit;
    }
}

// 4. Manejo especial para dashboard y profile
$specialPages = ['/dashboard', '/profile'];
foreach ($specialPages as $page) {
    if ($requestPath === $page) {
        $phpFile = $basePath . '/backend' . $page . '.php';
        if (executeController($phpFile)) exit;
    }
}

// =============================================
// PÁGINA 404 PERSONALIZADA
// =============================================
http_response_code(404);
?>
<!DOCTYPE html>
<html>
<head>
    <title>404 - FinBalance</title>
    <link rel="stylesheet" href="/frontend/StylesCSS/styles.css">
</head>
<body>
    <div class="error-container">
        <h1>404 - Recurso no encontrado</h1>
        <p>La ruta solicitada no existe en el servidor.</p>
        <div class="debug-info">
            <p><strong>Ruta:</strong> <?= htmlspecialchars($requestPath) ?></p>
            <p><strong>Método:</strong> <?= $_SERVER['REQUEST_METHOD'] ?></p>
            <a href="/" class="btn">Volver al inicio</a>
        </div>
    </div>
</body>
</html>