<?php
session_start();
require_once 'config.php';
$conn = $conexion;

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email y contraseña son obligatorios.";
    } else {
        $sql = "SELECT id, name, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Email no encontrado.";
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
    <title>Iniciar Sesión - Mi Red Social</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required>
            <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
        <p style="margin-top: 1.5rem;">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    </div>
</body>
</html>