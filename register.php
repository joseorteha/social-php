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
    $terms = isset($_POST['terms']) ? 1 : 0;

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!$terms) {
        $error = "Debes aceptar los términos y condiciones.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $success = "¡Registro exitoso! Ahora puedes <a href='login.php'>iniciar sesión</a>.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Conectando</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-branding">
            <h1><i class="fas fa-users"></i> Conectando</h1>
            <p>Únete a nuestra comunidad y empieza a compartir tus momentos.</p>
            
            <div class="auth-features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-share-alt"></i></div>
                    <div class="feature-text">Comparte tus ideas con el mundo</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-heart"></i></div>
                    <div class="feature-text">Descubre contenido que te interesa</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-comments"></i></div>
                    <div class="feature-text">Conéctate con conversaciones significativas</div>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <form method="post" action="">
                <h2><i class="fas fa-user-plus"></i> Crear Cuenta</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle alert-icon"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="name" class="form-label">Nombre completo</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control with-icon" id="name" name="name" 
                               placeholder="Tu nombre completo" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control with-icon" id="email" name="email" 
                               placeholder="tucorreo@ejemplo.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control with-icon" id="password" name="password" 
                               placeholder="••••••••" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Mínimo 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span class="checkmark"></span>
                        Acepto los <a href="terms.php">Términos de Servicio</a> y <a href="privacy.php">Política de Privacidad</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus btn-icon"></i> Registrarse
                </button>
                
                <div class="divider">o</div>
                
                <div class="social-buttons">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google icon"></i> Registrarse con Google
                    </button>
                    <button type="button" class="social-btn facebook">
                        <i class="fab fa-facebook-f icon"></i> Registrarse con Facebook
                    </button>
                    <button type="button" class="social-btn apple">
                        <i class="fab fa-apple icon"></i> Registrarse con Apple
                    </button>
                </div>
                
                <div class="auth-links">
                    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    </script>
</body>
</html>