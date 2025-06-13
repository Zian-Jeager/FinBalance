<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de meta no válido";
    header("Location: listGoals.php");
    exit();
}

$goal_id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT id FROM goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "La meta no existe o no tienes permisos para eliminarla";
        header("Location: listGoals.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$goal_id, $user_id]);
    
    $_SESSION['success'] = "Meta eliminada correctamente";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al eliminar la meta: " . $e->getMessage();
}

header("Location: listGoals.php");
exit();
?>
