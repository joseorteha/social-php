<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "ortega";
$password = "jose2025";
$dbname = "social_network";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$search_query = isset($_GET['query']) ? $_GET['query'] : '';
$results = [];

if (!empty($search_query)) {
    $sql = "SELECT id, name FROM users WHERE name LIKE ? AND id != ?";
    $search_term = "%{$search_query}%";
    $current_user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $search_term, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<?php
$user_name = $_SESSION['user_name']; // Necesitamos el nombre para la navbar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Usuarios - Mi Red Social</title>
    <link rel="stylesheet" href="css/search.css">
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
        <div class="card">
            <h3>Resultados para "<?php echo htmlspecialchars($search_query); ?>"</h3>
            <?php if (!empty($results)): ?>
                <div class="list-group">
                    <?php foreach ($results as $user): ?>
                        <a href="profile.php?id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No se encontraron usuarios con ese nombre.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
