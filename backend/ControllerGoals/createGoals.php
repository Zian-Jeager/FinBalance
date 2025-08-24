<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /Frontend/login.html");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $target_amount = floatval($_POST['target_amount']);
    $deadline = $_POST['deadline'];
    $description = trim($_POST['description'] ?? ''); 
    
    if (empty($title)) {
        $error = "El título es obligatorio.";
    } elseif ($target_amount <= 0) {
        $error = "El monto objetivo debe ser mayor a cero.";
    } elseif (empty($deadline) || strtotime($deadline) < strtotime('today')) {
        $error = "La fecha límite no es válida.";
    } else {
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO goals (user_id, title, target_amount, deadline, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $target_amount,
                $deadline,
                $description
            ]);
            
            header("Location: ../dashboard.php?goal_added=1");
            exit();
        } catch (PDOException $e) {
            $error = "Error al guardar la meta: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <style>      
        :root {
            --primary: #3498db;
            --dark: #2c3e50;
            --light: #ecf0f1;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 500px;
        }
        
        .form-title {
            color: var(--dark);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
            text-align: center;
        }</style>
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Nueva Meta Financiera</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="createGoals.php">
            <div class="form-group">
                <label for="title">Título de la meta</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="target_amount">Monto objetivo ($)</label>
                <input type="number" id="target_amount" name="target_amount" step="0.01" min="0.01" required>
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="deadline">Fecha límite</label>
                <input type="date" id="deadline" name="deadline" required 
                       min="<?= date('Y-m-d') ?>">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-bullseye"></i> Guardar Meta
            </button>
        </form>
    </div>
</body>
</html>

