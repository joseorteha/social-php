<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'ortega');
define('DB_PASS', 'jose2025');
define('DB_NAME', 'social_network');

// Configuración general
define('BASE_URL', 'http://localhost/social-netwok');
define('UPLOAD_PATH', 'images/');

// Conexión a la base de datos
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Función para sanitizar entradas
function sanitize_input($data) {
    global $conexion;
    return $conexion->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

// Función para verificar si el usuario está logueado
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener información del usuario de forma segura
function get_user_info($user_id) {
    global $conexion;
    $query = "SELECT * FROM users WHERE id = ?";
    if ($stmt = $conexion->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_info = $result->fetch_assoc();
            $stmt->close();
            return $user_info;
        }
        $stmt->close();
    }
    return null;
}

// Función para mostrar el tiempo transcurrido (ej. "hace 5 minutos")
function time_ago($date) {
    if (empty($date)) {
        return "Fecha desconocida";
    }
    $date = strtotime($date);
    $periods = array("segundo", "minuto", "hora", "día", "semana", "mes", "año");
    $lengths = array("60", "60", "24", "7", "4.35", "12", "365");
    $now = time();
    $difference = $now - $date;
    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
        $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if ($difference != 1) {
        // Añadir 's' para plural, excepto para 'mes' que es 'meses'
        if ($periods[$j] == "mes") {
            $periods[$j] = "meses";
        } else {
            $periods[$j] .= "s";
        }
    }
    return "hace $difference $periods[$j]";
}

// Función para iniciar sesión del usuario
function login_user($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['name'] = $user_data['name'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['profile_pic'] = $user_data['profile_pic'];
}

// Redireccionar si no está logueado
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'register.php') {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}
?>
