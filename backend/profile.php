<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, created_at, budget FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$motivational_messages = [
    "Cada pequeño ahorro te acerca a tus grandes sueños. ¡Sigue así!",
    "El control financiero es el primer paso hacia la libertad. ¡Buen trabajo!",
    "Pequeños pasos, grandes resultados. Tu disciplina financiera dará frutos.",
    "Hoy es un buen día para revisar tus metas y celebrar tus progresos.",
    "La constancia es la clave del éxito financiero. ¡Vas por buen camino!",
    "Cada decisión financiera inteligente es una inversión en tu futuro."
];

$random_message = $motivational_messages[array_rand($motivational_messages)];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    $new_username = trim($_POST['username']);
    
    if (strlen($new_username) < 3) {
        $_SESSION['error'] = "El nombre de usuario debe tener al menos 3 caracteres";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$new_username, $user_id]);
        
        $_SESSION['user_name'] = $new_username;
        $_SESSION['success'] = "Nombre de usuario actualizado correctamente";
        header("Location: profile.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_budget'])) {
    $new_budget = floatval($_POST['budget']);
    
    if ($new_budget < 0) {
        $_SESSION['error'] = "El presupuesto no puede ser negativo";
    } else {
        $stmt = $conn->prepare("UPDATE users SET budget = ? WHERE id = ?");
        $stmt->execute([$new_budget, $user_id]);
        
        $_SESSION['success'] = "Presupuesto actualizado correctamente";
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--dark);
            color: white;
            padding: 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.1rem;
            margin-left: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            color: var(--light);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 25px;
            overflow-y: auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        /* Profile Container */
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        /* Profile Sections */
        .profile-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        /* Account Info */
        .account-info .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .account-info .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--dark);
        }
        
        .info-value {
            color: #666;
        }
        
        /* Motivational Message */
        .motivational-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin-top: 30px;
        }
        
        .message-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .motivational-message p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0;
            font-style: italic;
        }

        /* ===== MEDIA QUERIES RESPONSIVE ===== */
        /* Tabletas y dispositivos medianos (768px o menos) */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr !important;
                grid-template-rows: auto 1fr;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 220px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                padding-top: 50px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .menu-toggle {
                display: block !important;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--dark);
                color: white;
                border: none;
                border-radius: 4px;
                padding: 8px 12px;
                cursor: pointer;
            }
            
            .main-content {
                padding: 70px 15px 25px 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .profile-container {
                max-width: 100%;
            }
            
            .account-info .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }

        /* Dispositivos móviles pequeños (480px o menos) */
        @media (max-width: 480px) {
            .main-content {
                padding: 65px 10px 20px 10px;
            }
            
            .profile-section {
                padding: 15px;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .form-control {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
            
            .motivational-message {
                padding: 20px 15px;
            }
            
            .message-icon {
                font-size: 2rem;
            }
            
            .motivational-message p {
                font-size: 1rem;
            }
            
            .logout-btn {
                width: 100%;
                text-align: center;
                padding: 10px;
            }
        }

        /* Elementos adicionales necesarios para el menú móvil */
        .menu-toggle {
            display: none;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        /* Mejoras generales para móviles */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
            
            .sidebar-header {
                margin-bottom: 20px;
            }
            
            .logout-btn {
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Botón de menú móvil y overlay -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i> Menú
    </button>
    
    <div class="overlay" id="overlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-wallet"></i>
                <h2>FinBalance</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="ControllerExpenses/createExpense.php"><i class="fas fa-plus-circle"></i> Agregar Gasto</a></li>
                <li><a href="ControllerGoals/createGoals.php"><i class="fas fa-bullseye"></i> Nueva Meta</a></li>
                <li><a href="ControllerExpenses/listExpenses.php"><i class="fas fa-list-ul"></i> Lista de Gastos</a></li>
                <li><a href="ControllerGoals/listGoals.php"><i class="fas fa-tasks"></i> Mis Metas</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user-cog"></i> Configuración</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? '', 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></div>
                        <div style="font-size: 0.75rem; color: #777;"><?= date('d M Y') ?></div>
                    </div>
                </div>
                <a href="ControllerAuth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>

            <div class="profile-container">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-edit"></i>
                        Cambiar nombre de usuario
                    </h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Nuevo nombre de usuario</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        
                        <button type="submit" name="update_username" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </form>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-wallet"></i>
                        Configurar presupuesto mensual
                    </h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="budget">Presupuesto mensual ($)</label>
                            <input type="number" id="budget" name="budget" class="form-control" 
                                   value="<?= htmlspecialchars($user['budget'] ?? '5000') ?>" step="0.01" min="0" required>
                        </div>
                        
                        <button type="submit" name="update_budget" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar presupuesto
                        </button>
                    </form>
                </div>
                
                <div class="profile-section account-info">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información de tu cuenta
                    </h3>
                    
                    <div class="info-item">
                        <span class="info-label">Nombre de usuario actual:</span>
                        <span class="info-value"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Presupuesto actual:</span>
                        <span class="info-value">$<?= number_format($user['budget'] ?? 5000, 2) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Fecha de creación:</span>
                        <span class="info-value"><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'No disponible' ?></span>
                    </div>
                </div>
                
                <div class="motivational-message">
                    <div class="message-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <p><?= htmlspecialchars($random_message) ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para el funcionamiento del menú móvil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('overlay');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>
