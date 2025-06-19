<?php
session_start();
require_once 'config.php';
$conn = $conexion;

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$success = "";
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $success = "Registro exitoso. <a href='login.php'>Inicia sesión</a>.";
        } else {
            $error = "Error al registrarse. El email ya está en uso.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Mi Red Social</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="auth-container">
        <h2>Crear Cuenta</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="text" class="form-control" name="name" placeholder="Nombre completo" required>
            <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required>
            <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn btn-primary">Registrarse</button>
        </form>
        <p style="margin-top: 1.5rem;">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </div>
</body>
</html>