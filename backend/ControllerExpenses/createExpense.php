<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$categories = ['Comida', 'Transporte', 'Entretenimiento', 'Salud', 'Educación', 'Vivienda', 'Otros'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $date = $_POST['date'];

    if ($amount <= 0) {
        $error = "El monto debe ser mayor a cero.";
    } elseif (!in_array($category, $categories)) {
        $error = "Categoría no válida.";
    } elseif (empty($date)) {
        $error = "La fecha es obligatoria.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO expenses (user_id, amount, category, description, date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $amount, $category, $description, $date]);
            
            header("Location: ../dashboard.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error al guardar el gasto: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Gasto | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Agregar Nuevo Gasto</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="createExpense.php">
            <div class="form-group">
                <label for="amount">Monto ($)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="category">Categoría</label>
                <select id="category" name="category" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción (opcional)</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="date">Fecha</label>
                <input type="date" id="date" name="date" required 
                       value="<?= date('Y-m-d') ?>">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Guardar Gasto
            </button>
        </form>
    </div>
</body>
</html>
