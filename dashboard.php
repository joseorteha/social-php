<?php
session_start();
require_once 'config.php';
$conn = $conexion;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Crear nueva publicación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    $content = $_POST['content'];
    if (!empty($content)) {
        $sql = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $content);
        $stmt->execute();
        header("Location: dashboard.php");
        exit;
    }
}

// Obtener publicaciones del usuario
$sql = "SELECT content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Mi Red Social</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <a href="dashboard.php">Conexión+</a>
        </div>
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Inicio</a>
            <a href="search.php" class="nav-link"><i class="fas fa-search"></i> Buscar</a>
            <a href="my-profile.php" class="nav-link"><i class="fas fa-user"></i> Mi Perfil</a>
            <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Search Card -->
        <div class="card">
            <h4>Buscar Usuarios</h4>
            <form action="search.php" method="GET">
                <input type="text" name="query" class="form-control" placeholder="Escribe un nombre..." required>
                <button class="btn btn-primary" type="submit" style="width: auto; margin-top: 10px;">Buscar</button>
            </form>
        </div>

        <!-- Create Post Card -->
        <div class="card">
            <h3>Crear Publicación</h3>
            <form method="post" action="">
                <textarea class="form-control" name="content" rows="4" placeholder="¿Qué estás pensando, <?php echo htmlspecialchars($user_name); ?>?" required></textarea>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Publicar</button>
            </form>
        </div>

        <!-- Posts Feed Card -->
        <div class="card">
            <h3>Tus Publicaciones</h3>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="post">
                        <p><?php echo htmlspecialchars($row['content']); ?></p>
                        <small>Publicado el: <?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tienes publicaciones aún. ¡Crea la primera!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>