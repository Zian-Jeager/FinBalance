<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($password)) {
        echo '<script>alert("Error: Todos los campos son obligatorios. Por favor complete toda la información requerida."); window.history.back();</script>';
    } else {
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo '<script>alert("Error: El correo electrónico ya está registrado. Por favor utilice una dirección de correo diferente o inicie sesión si ya tiene una cuenta."); window.history.back();</script>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password]);
            
            if ($stmt->rowCount() > 0) {
                echo '<script>alert("¡Registro exitoso! Su cuenta ha sido creada correctamente. Será redirigido al panel de control."); window.location.href = "../dashboard.php";</script>';
            } else {
                echo '<script>alert("Error: No se pudo completar el registro. Por favor intente nuevamente."); window.history.back();</script>';
            }
            exit();
        }
    }
}
?>
