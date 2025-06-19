<?php
session_start();
require_once 'config.php';
$conn = $conexion;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];

// Procesar acciones de POST (like, comment, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Like/Unlike
    if (isset($_POST['like_post_id'])) {
        $post_id = (int)$_POST['like_post_id'];
        $check_like = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $check_like->bind_param("ii", $user_id, $post_id);
        $check_like->execute();
        $has_like = $check_like->get_result()->num_rows > 0;
        $check_like->close();
        if ($has_like) {
            $del = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $del->bind_param("ii", $user_id, $post_id);
            $del->execute();
            $del->close();
        } else {
            $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $ins->bind_param("ii", $user_id, $post_id);
            $ins->execute();
            $ins->close();
        }
        header("Location: dashboard.php#post-" . $post_id);
        exit;
    }

    // Comentar
    if (isset($_POST['comment_post_id']) && !empty(trim($_POST['comment_content']))) {
        $post_id = (int)$_POST['comment_post_id'];
        $content = trim($_POST['comment_content']);
        $ins = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $post_id, $user_id, $content);
        $ins->execute();
        $ins->close();
        header("Location: dashboard.php#post-" . $post_id);
        exit;
    }

    // Acción de Eliminar Post
    if (isset($_POST['delete_post_id'])) {
        $post_id_to_delete = $_POST['delete_post_id'];
        $check_post_query = "SELECT user_id, image FROM posts WHERE id = ?";
        $stmt = $conn->prepare($check_post_query);
        $stmt->bind_param("i", $post_id_to_delete);
        $stmt->execute();
        if ($post_data = $stmt->get_result()->fetch_assoc()) {
            if ($post_data['user_id'] == $user_id) {
                if (!empty($post_data['image']) && file_exists($post_data['image'])) {
                    unlink($post_data['image']);
                }
                $delete_post_query = "DELETE FROM posts WHERE id = ?";
                $stmt_delete = $conn->prepare($delete_post_query);
                $stmt_delete->bind_param("i", $post_id_to_delete);
                $stmt_delete->execute();
                $stmt_delete->close();
                $_SESSION['success'] = "Publicación eliminada correctamente.";
            }
        }
        $stmt->close();
        header("Location: dashboard.php");
        exit;
    }
}

// Obtener información del usuario actual
$user_info = get_user_info($_SESSION['user_id']);
if (!$user_info) {
    header('Location: logout.php');
    exit();
}

try {
    // Obtener las publicaciones para el feed
    $posts = [];
    $query = "SELECT p.*, u.username, u.name, u.profile_pic 
              FROM posts p 
              INNER JOIN users u ON p.user_id = u.id 
              ORDER BY p.created_at DESC 
              LIMIT 20";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    }

    // Obtener sugerencias de usuarios
    $suggestions = [];
    $query = "SELECT id, username, name, profile_pic 
              FROM users 
              WHERE id != ? AND id NOT IN (
                  SELECT follower_id FROM followers WHERE user_id = ?
              )
              ORDER BY RAND() 
              LIMIT 5";
              
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $suggestions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $e) {
    error_log("Error en dashboard.php: " . $e->getMessage());
    $_SESSION['error'] = "Ha ocurrido un error al cargar el contenido.";
}

// Asegurarnos de que tenemos arrays válidos incluso si hay errores
$posts = $posts ?? [];
$suggestions = $suggestions ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Red Social</title>
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo htmlspecialchars($_SESSION['error']);
        unset($_SESSION['error']);
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['success']);
        unset($_SESSION['success']);
        ?>
    </div>
    <?php endif; ?>

    <div class="container">
        <!-- Sidebar - Menú lateral -->
        <aside class="sidebar">
            <div class="profile-brief">
                <img src="<?php echo !empty($user_info['profile_pic']) ? htmlspecialchars($user_info['profile_pic']) : 'images/default-avatar.png'; ?>" 
                     alt="Foto de perfil" 
                     class="profile-pic">
                <h3><?php echo htmlspecialchars($user_info['name']); ?></h3>
                <p class="username">@<?php echo htmlspecialchars($user_info['username'] ?? $user_info['name']); ?></p>
            </div>

            <!-- Campo de búsqueda -->
            <div class="search-bar">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Buscar usuarios...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="my-profile.php" class="menu-item">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
                <a href="search.php" class="menu-item">
                    <i class="fas fa-search"></i>
                    <span>Buscar</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content - Feed principal -->
        <main class="main-content">
            <!-- Crear publicación -->
            <div class="create-post">
                <img src="<?php echo !empty($user_info['profile_pic']) ? htmlspecialchars($user_info['profile_pic']) : 'images/default-avatar.png'; ?>" 
                     alt="Tu foto" 
                     class="profile-pic-small">
                <div class="post-input-container">
                    <form id="create-post-form" action="create_post.php" method="POST" enctype="multipart/form-data">
                        <textarea name="content" placeholder="¿Qué estás pensando?" class="post-input" required></textarea>
                        <div class="post-actions">
                            <label for="image-upload" class="post-action-btn">
                                <i class="fas fa-image"></i>
                                <input type="file" id="image-upload" name="image" accept="image/*" style="display: none;">
                            </label>
                            <button type="submit" class="post-btn">Publicar</button>
                        </div>
                        <div id="image-preview" class="image-preview"></div>
                    </form>
                </div>
            </div>

            <!-- Feed de publicaciones -->
            <div class="feed">
                <!-- Stories/Highlights -->
                <div class="stories-container">
                    <div class="story add-story">
                        <div class="add-story-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span>Crear historia</span>
                    </div>
                    <!-- Aquí irían más historias -->
                </div>

                <!-- Posts -->
                <div class="posts-container">
                    <?php if (empty($posts)): ?>
                    <div class="no-posts">
                        <i class="fas fa-newspaper"></i>
                        <p>No hay publicaciones para mostrar</p>
                        <p>¡Sé el primero en publicar algo!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($posts as $post):
                        $post_id = $post['id'];
                        // Likes
                        $likes_q = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
                        $likes_q->bind_param("i", $post_id);
                        $likes_q->execute();
                        $likes_total = $likes_q->get_result()->fetch_assoc()['total'];
                        $likes_q->close();
                        $likes_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
                        $likes_stmt->bind_param("i", $post_id);
                        $likes_stmt->execute();
                        $like_count = $likes_stmt->get_result()->fetch_assoc()['like_count'];
                        $user_liked_stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                        $user_liked_stmt->bind_param("ii", $user_id, $post_id);
                        $user_liked_stmt->execute();
                        $user_liked = $user_liked_stmt->get_result()->num_rows > 0;
                        $comments_stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                        $comments_stmt->bind_param("i", $post_id);
                        $comments_stmt->execute();
                        $comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                        <article class="post" id="post-<?php echo $post_id; ?>">
                            <div class="post-header">
                                <img src="<?php echo !empty($post['profile_pic']) ? htmlspecialchars($post['profile_pic']) : 'images/default-avatar.png'; ?>" class="profile-pic">
                                <div class="post-author-info">
                                    <a href="my-profile.php?user_id=<?php echo $post['user_id']; ?>" class="author-name"><?php echo htmlspecialchars($post['name']); ?></a>
                                    <span class="post-timestamp"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($post['created_at']))); ?></span>
                                </div>
                                <?php if ($post['user_id'] == $user_id): ?>
                                    <form method="POST" action="dashboard.php" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta publicación?');" class="delete-post-form">
                                        <input type="hidden" name="delete_post_id" value="<?php echo $post_id; ?>">
                                        <button type="submit" class="post-menu-btn"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php if(!empty($post['image'])): ?><img src="<?php echo htmlspecialchars($post['image']); ?>" class="post-image"><?php endif; ?>
                            </div>
                            <div class="post-stats">
                                <span class="likes-count"><?php echo $like_count; ?> Me gusta</span>
                                <span class="comments-count"><?php echo count($comments); ?> Comentarios</span>
                            </div>
                            <div class="post-actions">
                                <form method="POST" action="dashboard.php" class="action-form"><input type="hidden" name="like_post_id" value="<?php echo $post_id; ?>"><button type="submit" class="action-btn <?php echo $user_liked ? 'liked' : ''; ?>"><i class="fa-heart <?php echo $user_liked ? 'fas' : 'far'; ?>"></i> Me gusta</button></form>
                                <button class="action-btn" onclick="toggleComments('comments-<?php echo $post_id; ?>')"><i class="far fa-comment"></i> Comentar</button>
                                <button class="action-btn"><i class="far fa-share-square"></i> Compartir</button>
                            </div>
                            <div class="comments-section" id="comments-<?php echo $post_id; ?>" style="display: none;">
                                <div class="comments-list">
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment">
                                            <img src="<?php echo !empty($comment['profile_pic']) ? htmlspecialchars($comment['profile_pic']) : 'images/default-avatar.png'; ?>" class="comment-avatar">
                                            <div class="comment-content">
                                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                <span style="color:#888;font-size:0.9em;"> <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?></span>
                                                <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <form method="POST" action="dashboard.php" class="comment-form" style="display:flex;gap:8px;margin-top:10px;">
                                    <input type="hidden" name="comment_post_id" value="<?php echo $post_id; ?>">
                                    <input type="text" name="comment_content" placeholder="Escribe un comentario..." required style="flex:1;padding:6px 10px;border-radius:8px;border:1px solid #ccc;">
                                    <button type="submit" class="btn btn-primary">Comentar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Sidebar derecho - Tendencias y sugerencias -->
        <aside class="right-sidebar">
            <div class="trending-section">
                <h3>Tendencias</h3>
                <div class="trending-topics">
                    <div class="trending-topic">
                        <span class="topic">#PHP</span>
                        <span class="posts-count">1.2K posts</span>
                    </div>
                    <div class="trending-topic">
                        <span class="topic">#WebDev</span>
                        <span class="posts-count">856 posts</span>
                    </div>
                    <div class="trending-topic">
                        <span class="topic">#Programación</span>
                        <span class="posts-count">543 posts</span>
                    </div>
                </div>
            </div>
            <div class="suggestions-section">
                <h3>Sugerencias para ti</h3>
                <div class="suggested-users">
                    <?php foreach($suggestions as $user): ?>
                    <div class="suggested-user">
                        <img src="<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'images/default-avatar.png'; ?>" 
                             alt="<?php echo htmlspecialchars($user['name']); ?>" 
                             class="profile-pic-small">
                        <div class="user-info">
                            <a href="my-profile.php?user_id=<?php echo $user['id']; ?>" class="suggestion-name">
                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                            </a>
                            <span class="username">@<?php echo htmlspecialchars($user['username'] ?? $user['name']); ?></span>
                        </div>
                        <button class="follow-btn" onclick="followUser(<?php echo $user['id']; ?>)">Seguir</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    <script>
    document.getElementById('image-upload')?.addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    function toggleComments(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            const isHidden = section.style.display === 'none' || section.style.display === '';
            section.style.display = isHidden ? 'block' : 'none';
        }
    }
    </script>
</body>
</html>