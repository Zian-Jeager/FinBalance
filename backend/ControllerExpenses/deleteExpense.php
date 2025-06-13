<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ControllerAuth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de gasto no válido";
    header("Location: listExpenses.php");
    exit();
}

$expense_id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "El gasto no existe o no tienes permisos para eliminarlo";
        header("Location: listExpenses.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
    
    $_SESSION['success'] = "Gasto eliminado correctamente";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al eliminar el gasto: " . $e->getMessage();
}

header("Location: listExpenses.php");
exit();
?>
