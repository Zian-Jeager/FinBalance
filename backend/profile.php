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
    <link rel="stylesheet" href="../frontend/StylesCSS/Profile.css">
</head>
<body>
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
</body>
</html>
