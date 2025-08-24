<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$goal_id = $_GET['id'] ?? null;

$stmt = $conn->prepare("SELECT * FROM goals WHERE id = ? AND user_id = ?");
$stmt->execute([$goal_id, $user_id]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$goal) {
    $_SESSION['error'] = "Meta no encontrada o no tienes permiso para editarla";
    header("Location: listGoals.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $title = trim($_POST['title']);
    $target_amount = floatval($_POST['target_amount']);
    $current_amount = floatval($_POST['current_amount']);
    $deadline = $_POST['deadline'];
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || $target_amount <= 0 || $current_amount < 0) {
        $_SESSION['error'] = "Por favor complete todos los campos requeridos correctamente";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE goals SET title = ?, target_amount = ?, current_amount = ?, deadline = ?, description = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $target_amount, $current_amount, $deadline, $description, $goal_id, $user_id]);
            
            $_SESSION['success'] = "Meta actualizada correctamente";
            header("Location: listGoals.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al actualizar la meta: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Meta | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../frontend/StylesCSS/DashboardStyle.css">
    <style>
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php
    
    <div class="container">
        <h2><i class="fas fa-edit"></i> Editar Meta</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="title">Título de la meta</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($goal['title']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="target_amount">Monto objetivo ($)</label>
                <input type="number" id="target_amount" name="target_amount" step="0.01" min="0.01" value="<?= htmlspecialchars($goal['target_amount']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="current_amount">Monto actual ($)</label>
                <input type="number" id="current_amount" name="current_amount" step="0.01" min="0" value="<?= htmlspecialchars($goal['current_amount']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="deadline">Fecha límite</label>
                <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($goal['deadline']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción (opcional)</label>
                <textarea id="description" name="description"><?= htmlspecialchars($goal['description'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" name="update_goal" class="btn">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="goals.php" class="btn" style="background: #6c757d;">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </form>
    </div>
</body>
</html>


