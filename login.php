<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email y contraseña son obligatorios.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                login_user($user);
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Conectando</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-branding">
            <h1><i class="fas fa-users"></i> Conectando</h1>
            <p>Una nueva forma de conectar con tus amigos y el mundo que te rodea.</p>
            
            <div class="auth-features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <div class="feature-text">Descubre contenido relevante en tiempo real</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-user-friends"></i></div>
                    <div class="feature-text">Conéctate con personas que comparten tus intereses</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-globe"></i></div>
                    <div class="feature-text">Únete a una comunidad global de usuarios</div>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <form method="post" action="">
                <h2><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

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
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt btn-icon"></i> Iniciar Sesión
                </button>
                
                <div class="divider">o</div>
                
                <div class="social-buttons">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google icon"></i> Continuar con Google
                    </button>
                    <button type="button" class="social-btn facebook">
                        <i class="fab fa-facebook-f icon"></i> Continuar con Facebook
                    </button>
                    <button type="button" class="social-btn apple">
                        <i class="fab fa-apple icon"></i> Continuar con Apple
                    </button>
                </div>
                
                <div class="auth-links">
                    <p>¿No tienes una cuenta? <a href="register.php">Regístrate ahora</a></p>
                    <p><a href="forgot-password.php">¿Olvidaste tu contraseña?</a></p>
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