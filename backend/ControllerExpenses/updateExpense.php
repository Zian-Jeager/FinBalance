<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$expense_id = $_GET['id'] ?? null;

$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
$stmt->execute([$expense_id, $user_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    $_SESSION['error'] = "Gasto no encontrado o no tienes permiso para editarlo";
    header("Location: listExpenses.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'];

    if ($amount <= 0 || empty($category)) {
        $_SESSION['error'] = "Por favor complete todos los campos requeridos correctamente";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE expenses SET amount = ?, category = ?, description = ?, date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$amount, $category, $description, $date, $expense_id, $user_id]);
            
            $_SESSION['success'] = "Gasto actualizado correctamente";
            header("Location: listExpenses.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al actualizar el gasto: " . $e->getMessage();
        }
    }
}

$categories_stmt = $conn->prepare("SELECT DISTINCT category FROM expenses WHERE user_id = ? ORDER BY category");
$categories_stmt->execute([$user_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Gasto | FinBalance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    
    <div class="container">
        <h2><i class="fas fa-edit"></i> Editar Gasto</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="amount">Monto ($)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="<?= htmlspecialchars($expense['amount']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Categoría</label>
                <select id="category" name="category" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $expense['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date">Fecha</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($expense['date']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción (opcional)</label>
                <textarea id="description" name="description"><?= htmlspecialchars($expense['description'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" name="update_expense" class="btn">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="listExpenses.php" class="btn" style="background: #6c757d;">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </form>
    </div>
</body>
</html>
