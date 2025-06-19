<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$profile_user_id = $_GET['id'];

$servername = "localhost";
$username = "ortega";
$password = "jose2025";
$dbname = "social_network";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener información del usuario del perfil
$sql_user = "SELECT name FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $profile_user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows === 0) {
    // Usuario no encontrado
    header("Location: dashboard.php");
    exit;
}
$profile_user = $result_user->fetch_assoc();
$stmt_user->close();

// Obtener publicaciones del usuario del perfil
$sql_posts = "SELECT content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $profile_user_id);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();

$conn->close();
?>
<?php
$user_name = $_SESSION['user_name']; // For the navbar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo htmlspecialchars($profile_user['name']); ?> - Mi Red Social</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <a href="dashboard.php">Conexión+</a>
        </div>
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a>
            <a href="search.php" class="nav-link active"><i class="fas fa-search"></i> Buscar</a>
            <a href="my-profile.php" class="nav-link"><i class="fas fa-user"></i> Mi Perfil</a>
            <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-container">
        <div class="profile-header">
            <h2><?php echo htmlspecialchars($profile_user['name']); ?></h2>
        </div>

        <div class="card">
            <h3>Publicaciones</h3>
            <?php if ($posts_result->num_rows > 0): ?>
                <?php while ($row = $posts_result->fetch_assoc()): ?>
                    <div class="post">
                        <p><?php echo htmlspecialchars($row['content']); ?></p>
                        <small>Publicado el: <?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Este usuario aún no tiene publicaciones.</p>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 1.5rem;">
             <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>
    </div>
</body>
</html>
