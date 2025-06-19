<?php
session_start();
require_once 'config.php';
$conn = $conexion;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_info = get_user_info($user_id);

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];

if (!empty($search_query)) {
    $search_term = "%{$search_query}%";
    $query = "SELECT id, username, name, profile_pic 
              FROM users 
              WHERE (username LIKE ? OR name LIKE ?) AND id != ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ssi", $search_term, $search_term, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de búsqueda para "<?php echo htmlspecialchars($search_query); ?>"</title>
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar - Menú lateral (reutilizado de dashboard.php) -->
        <aside class="sidebar">
            <div class="profile-brief">
                <img src="<?php echo !empty($user_info['profile_pic']) ? htmlspecialchars($user_info['profile_pic']) : 'images/default-avatar.png'; ?>" 
                     alt="Foto de perfil" 
                     class="profile-pic">
                <h3><?php echo htmlspecialchars($user_info['name']); ?></h3>
                <p class="username">@<?php echo htmlspecialchars($user_info['username'] ?? $user_info['name']); ?></p>
            </div>

            <div class="search-bar">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="profile.php?user_id=<?php echo htmlspecialchars($user_id); ?>" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content - Resultados de búsqueda -->
        <main class="main-content">
            <div class="search-results-container">
                <h2>Resultados para "<?php echo htmlspecialchars($search_query); ?>"</h2>
                
                <?php if (!empty($results)): ?>
                    <div class="user-list">
                        <?php foreach ($results as $user): ?>
                            <div class="user-card">
                                <img src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'images/default-avatar.png'; ?>" alt="Foto de <?php echo htmlspecialchars($user['name']); ?>" class="profile-pic-small">
                                <div class="user-info">
                                    <h4>
                                        <a href="my-profile.php?user_id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></a>
                                    </h4>
                                    <span class="username">@<?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                                <a href="my-profile.php?user_id=<?php echo $user['id']; ?>" class="follow-btn">Ver Perfil</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No se encontraron usuarios que coincidan con tu búsqueda.</p>
                        <p>Intenta con otro nombre o usuario.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Sidebar derecho (opcional, podría estar vacío o mostrar otra cosa) -->
        <aside class="right-sidebar">
             <!-- Podrías añadir contenido aquí si lo deseas -->
        </aside>
    </div>
</body>
</html>
