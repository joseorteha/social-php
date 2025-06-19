<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$servername = "localhost";
$username = "ortega";
$password = "jose2025";
$dbname = "social_network";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener información detallada del usuario
$sql_user = "SELECT name, email, bio, profile_image, created_at FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Obtener estadísticas del usuario
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM posts WHERE user_id = ?) as total_posts,
    (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following,
    (SELECT COUNT(*) FROM followers WHERE user_id = ?) as followers";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Obtener publicaciones del usuario
$sql_posts = "SELECT p.*, 
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Mi Red Social</title>
    <link rel="stylesheet" href="css/my-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="dashboard.php">Conexión+</a>
        </div>
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a>
            <a href="search.php" class="nav-link"><i class="fas fa-search"></i> Buscar</a>
            <a href="my-profile.php" class="nav-link active"><i class="fas fa-user"></i> Mi Perfil</a>
            <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-cover">
                <div class="profile-avatar">
                    <img src="<?php echo $user_info['profile_image'] ?? 'images/default-avatar.png'; ?>" alt="Foto de perfil">
                    <button class="edit-avatar-btn"><i class="fas fa-camera"></i></button>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user_info['name']); ?></h1>
                <p class="profile-bio"><?php echo htmlspecialchars($user_info['bio'] ?? 'No hay biografía aún'); ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['total_posts']; ?></span>
                        <span class="stat-label">Publicaciones</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['followers']; ?></span>
                        <span class="stat-label">Seguidores</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['following']; ?></span>
                        <span class="stat-label">Siguiendo</span>
                    </div>
                </div>
                <button class="btn btn-primary edit-profile-btn">
                    <i class="fas fa-edit"></i> Editar Perfil
                </button>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-tabs">
                <button class="tab-btn active" data-tab="posts">
                    <i class="fas fa-th"></i> Publicaciones
                </button>
                <button class="tab-btn" data-tab="about">
                    <i class="fas fa-info-circle"></i> Acerca de
                </button>
                <button class="tab-btn" data-tab="photos">
                    <i class="fas fa-images"></i> Fotos
                </button>
            </div>

            <div class="tab-content active" id="posts-tab">
                <div class="create-post">
                    <form action="create_post.php" method="POST" class="post-form">
                        <textarea name="content" placeholder="¿Qué estás pensando?" required></textarea>
                        <div class="post-actions">
                            <button type="button" class="btn btn-icon"><i class="fas fa-image"></i></button>
                            <button type="button" class="btn btn-icon"><i class="fas fa-video"></i></button>
                            <button type="submit" class="btn btn-primary">Publicar</button>
                        </div>
                    </form>
                </div>

                <div class="posts-grid">
                    <?php if ($posts_result->num_rows > 0): ?>
                        <?php while ($post = $posts_result->fetch_assoc()): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <img src="<?php echo $user_info['profile_image'] ?? 'images/default-avatar.png'; ?>" alt="Avatar" class="post-avatar">
                                    <div class="post-info">
                                        <h4><?php echo htmlspecialchars($user_info['name']); ?></h4>
                                        <small><?php echo date("d M Y H:i", strtotime($post['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>
                                <div class="post-actions">
                                    <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-heart"></i> <?php echo $post['likes_count']; ?>
                                    </button>
                                    <button class="action-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-comment"></i> <?php echo $post['comments_count']; ?>
                                    </button>
                                    <button class="action-btn share-btn">
                                        <i class="fas fa-share"></i> Compartir
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <i class="fas fa-pen-fancy"></i>
                            <p>Aún no tienes publicaciones</p>
                            <p>¡Comienza a compartir tus momentos!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-content" id="about-tab">
                <div class="about-section">
                    <div class="about-card">
                        <h3><i class="fas fa-user"></i> Información Personal</h3>
                        <div class="info-item">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_info['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_info['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Miembro desde:</span>
                            <span class="info-value"><?php echo date("d M Y", strtotime($user_info['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="photos-tab">
                <div class="photos-grid">
                    <!-- Aquí irían las fotos del usuario -->
                    <div class="no-photos">
                        <i class="fas fa-images"></i>
                        <p>Aún no hay fotos para mostrar</p>
                        <p>¡Comienza a compartir tus momentos!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funcionalidad para las pestañas
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Remover clase active de todos los botones y contenidos
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Agregar clase active al botón clickeado
                button.classList.add('active');
                
                // Mostrar el contenido correspondiente
                const tabId = button.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
    </script>
</body>
</html> 