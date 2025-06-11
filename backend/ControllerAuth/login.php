<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        
        // Mostrar alerta de éxito y redireccionar
        echo '<script type="text/javascript">
                alert("Inicio de sesión exitoso. Bienvenido!");
                window.location.href = "../dashboard.php";
              </script>';
        exit();
    } else {
        // Mostrar alerta de error y regresar a la página de login
        echo '<script type="text/javascript">
                alert("Credenciales incorrectas. Por favor intente nuevamente.");
                window.location.href = "/FinBalance/FinBalance/frontend/login.html";
              </script>';
        exit();
    }
}
?>