<?php
session_start();
require_once 'config.php';
$conn = $conexion;

// Verificar sesión y usuario
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar que el usuario existe en la base de datos
$session_user_id = $_SESSION['user_id'];
$sql_verify = "SELECT id FROM users WHERE id = ?";
$stmt_verify = $conn->prepare($sql_verify);
$stmt_verify->bind_param("i", $session_user_id);
$stmt_verify->execute();
$user_exists = $stmt_verify->get_result()->num_rows > 0;
$stmt_verify->close();

if (!$user_exists) {
    session_destroy();
    header("Location: login.php?error=invalid_session");
    exit;
}

$user_name = $_SESSION['username']; // For navbar display

// Determinar qué perfil mostrar
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $profile_user_id = (int)$_GET['user_id'];
    // Verificar que el ID del perfil es válido
    $sql_check_profile = "SELECT id FROM users WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check_profile);
    $stmt_check->bind_param("i", $profile_user_id);
    $stmt_check->execute();
    $profile_exists = $stmt_check->get_result()->num_rows > 0;
    $stmt_check->close();
    
    if (!$profile_exists) {
        header("Location: dashboard.php?error=user_not_found");
        exit;
    }
} else {
    $profile_user_id = $session_user_id;
}

$is_own_profile = ($profile_user_id === $session_user_id);

// Usar una variable clara para el ID del usuario en el procesamiento de acciones
$user_id = $session_user_id; // Para acciones del usuario logueado

// Comprobar si el usuario logueado ya sigue a este perfil
$is_following = false;
if (!$is_own_profile) {
    $check_follow = $conn->prepare("SELECT id FROM followers WHERE user_id = ? AND follower_id = ?");
    $check_follow->bind_param("ii", $profile_user_id, $session_user_id);
    $check_follow->execute();
    $is_following = $check_follow->get_result()->num_rows > 0;
    $check_follow->close();
}

// Procesar acción de seguir/dejar de seguir (¡antes de cualquier salida HTML!)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_action']) && !$is_own_profile) {
    if ($is_following) {
        $unfollow = $conn->prepare("DELETE FROM followers WHERE user_id = ? AND follower_id = ?");
        $unfollow->bind_param("ii", $profile_user_id, $session_user_id);
        $unfollow->execute();
        $unfollow->close();
    } else {
        $follow = $conn->prepare("INSERT INTO followers (user_id, follower_id) VALUES (?, ?)");
        $follow->bind_param("ii", $profile_user_id, $session_user_id);
        $follow->execute();
        $follow->close();
    }
    header("Location: my-profile.php?user_id=$profile_user_id");
    exit;
}

// Procesar creación de post
if (isset($_POST['create_post']) && $is_own_profile) {
    $content = trim($_POST['content']);
    $image_path = null;

    if (!empty($content) || (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $img = $_FILES['image'];
            $upload_dir = 'images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
            $filename = 'post_' . $session_user_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;
            
            if (move_uploaded_file($img['tmp_name'], $dest)) {
                $image_path = $dest;
            }
        }

        $sql = "INSERT INTO posts (user_id, content, image, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $session_user_id, $content, $image_path);
        $stmt->execute();
        $stmt->close();
        header("Location: my-profile.php");
        exit;
    }
}

// Procesar subida de foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $is_own_profile) {
    $img = $_FILES['profile_image'];
    if ($img['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $dest = 'images/' . $filename;
        if (move_uploaded_file($img['tmp_name'], $dest)) {
            // Actualizar la ruta en la base de datos
            $sql = "UPDATE users SET profile_pic = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $dest, $session_user_id);
            $stmt->execute();
            $stmt->close();
            // Refrescar para mostrar la nueva imagen
            header("Location: my-profile.php");
            exit;
        }
    }
}

// Procesar edición de perfil
if (isset($_POST['edit_profile']) && $is_own_profile) {
    $new_name = trim($_POST['name']);
    $new_bio = trim($_POST['bio']);
    $new_email = trim($_POST['email']);
    if ($new_name && $new_email) {
        $sql = "UPDATE users SET name = ?, bio = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $new_name, $new_bio, $new_email, $session_user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['name'] = $new_name;
        header("Location: my-profile.php");
        exit;
    }
}

// Obtener información detallada del usuario
$sql_user = "SELECT name, username, email, bio, profile_pic, created_at FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $profile_user_id);
$stmt_user->execute();
$user_info_result = $stmt_user->get_result();
$user_info = $user_info_result->fetch_assoc();
$stmt_user->close();

// Obtener estadísticas del usuario
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM posts WHERE user_id = ?) as total_posts,
    (SELECT COUNT(*) FROM followers WHERE user_id = ?) as following,
    (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as followers";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("iii", $profile_user_id, $profile_user_id, $profile_user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Verificar si el usuario existe después de mostrar el contenido
if (!$user_info) {
    echo "<div class='error-message'>
        <h2>Usuario no encontrado</h2>
        <p>El usuario que intentas ver no existe o ha sido eliminado.</p>
        <a href='dashboard.php' class='btn btn-primary'>Volver al inicio</a>
    </div>";
    exit;
}

// Obtener publicaciones del usuario con información del autor
$sql_posts = "SELECT p.*, u.profile_pic as author_pic, u.name as author_name,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p 
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $profile_user_id);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();

// Obtener fotos del usuario para la galería
$user_photos = [];
$sql_photos = "SELECT image, created_at FROM posts WHERE user_id = ? AND image IS NOT NULL AND image != '' ORDER BY created_at DESC";
$stmt_photos = $conn->prepare($sql_photos);
$stmt_photos->bind_param("i", $profile_user_id);
$stmt_photos->execute();
$result_photos = $stmt_photos->get_result();
while ($row = $result_photos->fetch_assoc()) {
    $user_photos[] = $row;
}
$stmt_photos->close();

// --- LÓGICA DE LIKE Y COMENTARIOS EN PERFIL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Like/Unlike
    if (isset($_POST['like_post_id'])) {
        $post_id = (int)$_POST['like_post_id'];
        $check_like = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $check_like->bind_param("ii", $session_user_id, $post_id);
        $check_like->execute();
        $has_like = $check_like->get_result()->num_rows > 0;
        $check_like->close();
        if ($has_like) {
            $del = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $del->bind_param("ii", $session_user_id, $post_id);
            $del->execute();
            $del->close();
        } else {
            $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $ins->bind_param("ii", $session_user_id, $post_id);
            $ins->execute();
            $ins->close();
        }
        header("Location: my-profile.php?user_id=$profile_user_id#post-$post_id");
        exit;
    }
    // Comentar
    if (isset($_POST['comment_post_id']) && !empty(trim($_POST['comment_content']))) {
        $post_id = (int)$_POST['comment_post_id'];
        $content = trim($_POST['comment_content']);
        $ins = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $post_id, $session_user_id, $content);
        $ins->execute();
        $ins->close();
        header("Location: my-profile.php?user_id=$profile_user_id#post-$post_id");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_info['name']); ?> - Conexión+</title>
    <link rel="stylesheet" href="css/my-profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar - Menú lateral -->
        <aside class="sidebar">
            <div class="profile-brief">
                <img src="<?php echo $user_info['profile_pic'] ? htmlspecialchars($user_info['profile_pic']) : 'images/default-avatar.png'; ?>" 
                     alt="Foto de perfil" 
                     class="profile-pic">
                <h3><?php echo htmlspecialchars($user_info['name']); ?></h3>
                <p class="username">@<?php echo htmlspecialchars($user_info['username'] ?? $user_info['name']); ?></p>
            </div>
            <div class="search-bar">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Buscar usuarios...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="my-profile.php" class="menu-item active">
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
        <!-- Main Content - Perfil -->
        <main class="main-content">
            <div class="profile-header">
                <div class="profile-cover">
                    <div class="profile-avatar">
                        <form id="profile-pic-form" method="POST" enctype="multipart/form-data" style="position: relative;">
                            <img id="profile-pic" src="<?php echo $user_info['profile_pic'] ? htmlspecialchars($user_info['profile_pic']) : 'images/default-avatar.png'; ?>" alt="Foto de perfil" class="profile-pic-large">
                            <?php if ($is_own_profile): ?>
                                <input type="file" name="profile_image" id="profile-image-input" accept="image/*" style="display: none;">
                                <button type="button" id="change-pic-btn" class="edit-avatar-btn" style="position: absolute; bottom: 10px; right: 10px;"><i class="fas fa-camera"></i></button>
                                <div id="profile-pic-preview-container" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.95); align-items:center; justify-content:center; flex-direction:column; border-radius:50%; z-index:10;">
                                    <img id="profile-pic-preview" src="" alt="Preview" style="width:100%; height:100%; object-fit:cover; border-radius:50%; margin-bottom:10px;">
                                    <div style="display:flex; gap:10px;">
                                        <button type="button" id="cancel-pic-btn" class="btn btn-secondary">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>
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
                    <?php if ($is_own_profile): ?>
                        <div class="profile-actions">
                            <button id="edit-profile-btn" class="btn btn-primary">Editar Perfil</button>
                            <button class="btn btn-primary">Mensaje</button>
                        </div>
                    <?php else: ?>
                        <div class="profile-actions">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="follow_action" value="1">
                                <button type="submit" class="btn <?php echo $is_following ? 'btn-unfollow' : 'btn-primary'; ?>">
                                    <?php echo $is_following ? 'Dejar de seguir' : 'Seguir'; ?>
                                </button>
                            </form>
                            <button class="btn btn-primary">Mensaje</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-actions">
                    <?php if ($is_own_profile): ?>
                        <button id="edit-profile-btn" class="btn btn-secondary">Editar Perfil</button>
                        <button class="btn btn-primary">Mensaje</button>
                    <?php else: ?>
                        <button class="btn btn-primary">Seguir</button>
                        <button class="btn btn-secondary">Mensaje</button>
                    <?php endif; ?>
                </div>
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
                    <?php if ($is_own_profile): ?>
                    <div class="create-post-card">
                        <form action="my-profile.php" method="POST" enctype="multipart/form-data" class="post-form">
                            <textarea name="content" placeholder="¿Qué estás pensando, <?php echo htmlspecialchars($user_info['name']); ?>?"></textarea>
                            <div id="image-preview" class="image-preview-container"></div>
                            <div class="form-actions">
                                <label for="image-upload" class="image-upload-label"><i class="far fa-image"></i> Foto/Video</label>
                                <input type="file" name="image" id="image-upload" accept="image/*" style="display: none;">
                                <button type="submit" name="create_post" class="btn-post">Publicar</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="posts-grid">
                        <?php if ($posts_result->num_rows > 0): ?>
                            <?php while ($post = $posts_result->fetch_assoc()): ?>
                                <?php
                                // Likes
                                $post_id = $post['id'];
                                $likes_q = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
                                $likes_q->bind_param("i", $post_id);
                                $likes_q->execute();
                                $likes_total = $likes_q->get_result()->fetch_assoc()['total'];
                                $likes_q->close();
                                $user_like_q = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
                                $user_like_q->bind_param("ii", $session_user_id, $post_id);
                                $user_like_q->execute();
                                $user_liked = $user_like_q->get_result()->num_rows > 0;
                                $user_like_q->close();
                                // Comentarios
                                $comments_q = $conn->prepare("SELECT c.*, u.name, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                                $comments_q->bind_param("i", $post_id);
                                $comments_q->execute();
                                $comments = $comments_q->get_result()->fetch_all(MYSQLI_ASSOC);
                                $comments_q->close();
                                ?>
                                <div class="post-card" id="post-<?php echo $post_id; ?>">
                                    <div class="post-header">
                                        <img src="<?php echo !empty($post['author_pic']) ? htmlspecialchars($post['author_pic']) : 'images/default-avatar.png'; ?>" alt="Avatar" class="post-avatar">
                                        <div class="post-info">
                                            <h4><?php echo htmlspecialchars($post['author_name']); ?></h4>
                                            <small><?php echo date("d M Y H:i", strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                        <?php if (!empty($post['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($post['image']); ?>" class="post-image" alt="Imagen de la publicación">
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-actions">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="like_post_id" value="<?php echo $post_id; ?>">
                                            <button class="action-btn like-btn<?php echo $user_liked ? ' liked' : ''; ?>" type="submit">
                                                <i class="fas fa-heart"></i> <?php echo $likes_total; ?>
                                            </button>
                                        </form>
                                        <button class="action-btn comment-btn" type="button" onclick="document.getElementById('comments-<?php echo $post_id; ?>').style.display = (document.getElementById('comments-<?php echo $post_id; ?>').style.display === 'block' ? 'none' : 'block');">
                                            <i class="fas fa-comment"></i> <?php echo count($comments); ?>
                                        </button>
                                    </div>
                                    <div class="comments-section" id="comments-<?php echo $post_id; ?>" style="display:none;">
                                        <div class="comments-list">
                                            <?php foreach ($comments as $comment): ?>
                                                <div class="comment">
                                                    <img src="<?php echo !empty($comment['profile_pic']) ? htmlspecialchars($comment['profile_pic']) : 'images/default-avatar.png'; ?>" class="comment-avatar">
                                                    <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                    <span style="color:#888;font-size:0.9em;"> <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?></span>
                                                    <div><?php echo htmlspecialchars($comment['content']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <form method="POST" style="margin-top:10px;display:flex;gap:8px;">
                                            <input type="hidden" name="comment_post_id" value="<?php echo $post_id; ?>">
                                            <input type="text" name="comment_content" placeholder="Escribe un comentario..." required style="flex:1;padding:6px 10px;border-radius:8px;border:1px solid #ccc;">
                                            <button type="submit" class="btn btn-primary">Comentar</button>
                                        </form>
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
                        <?php if (count($user_photos) > 0): ?>
                            <?php foreach ($user_photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo htmlspecialchars($photo['image']); ?>" alt="Foto de usuario">
                                    <span class="photo-date"><?php echo date('d M Y', strtotime($photo['created_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-photos">
                                <i class="fas fa-images"></i>
                                <p>Aún no hay fotos para mostrar</p>
                                <p>¡Comienza a compartir tus momentos!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para Editar Perfil -->
    <div id="edit-profile-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2><i class="fas fa-user-edit"></i> Editar Perfil</h2>
            <form id="edit-profile-form" method="POST" autocomplete="off">
                <input type="hidden" name="edit_profile" value="1">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Nombre</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_info['name']); ?>" class="form-control" required>
                    <div class="input-feedback" id="name-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" class="form-control" required>
                    <div class="input-feedback" id="email-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="bio"><i class="fas fa-info-circle"></i> Biografía</label>
                    <textarea id="bio" name="bio" class="form-control"><?php echo htmlspecialchars($user_info['bio'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <div id="edit-success" class="success-message" style="display:none;">¡Perfil actualizado correctamente!</div>
            </form>
        </div>
    </div>

    <script>
        // Funcionalidad para las pestañas
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });

        // --- MODAL DE EDICIÓN DE PERFIL ---
        const modal = document.getElementById('edit-profile-modal');
        const openModalBtn = document.getElementById('edit-profile-btn'); // <-- CORREGIDO
        const closeModalBtn = document.getElementsByClassName('close-btn')[0];

        if (openModalBtn && modal) {
            openModalBtn.onclick = function() {
                modal.style.display = 'block';
            }
        }
        if (closeModalBtn && modal) {
            closeModalBtn.onclick = function() {
                modal.style.display = 'none';
            }
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // --- CAMBIO Y PREVIEW DE FOTO DE PERFIL ---
        const changePicBtn = document.getElementById('change-pic-btn');
        const profileImageInput = document.getElementById('profile-image-input');
        const previewContainer = document.getElementById('profile-pic-preview-container');
        const previewImg = document.getElementById('profile-pic-preview');
        const cancelPicBtn = document.getElementById('cancel-pic-btn');
        const profilePicForm = document.getElementById('profile-pic-form');

        if (changePicBtn && profileImageInput && previewContainer && previewImg && cancelPicBtn && profilePicForm) {
            changePicBtn.onclick = function(e) {
                e.preventDefault();
                profileImageInput.click();
            };
            profileImageInput.onchange = function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewImg.src = ev.target.result;
                        previewContainer.style.display = 'flex';
                        changePicBtn.style.display = 'none';
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            };
            cancelPicBtn.onclick = function(e) {
                e.preventDefault();
                previewContainer.style.display = 'none';
                profileImageInput.value = '';
                changePicBtn.style.display = 'flex';
            };
            profilePicForm.onsubmit = function() {
                previewContainer.style.display = 'none';
                changePicBtn.style.display = 'flex';
            }
        }

        // --- FEEDBACK VISUAL EN MODAL DE EDICIÓN ---
        const editForm = document.getElementById('edit-profile-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const nameFeedback = document.getElementById('name-feedback');
        const emailFeedback = document.getElementById('email-feedback');
        const successMsg = document.getElementById('edit-success');

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        if (editForm && nameInput && emailInput) {
            nameInput.addEventListener('input', function() {
                if (nameInput.value.trim().length < 2) {
                    nameInput.classList.add('invalid');
                    nameFeedback.textContent = 'El nombre es muy corto.';
                } else {
                    nameInput.classList.remove('invalid');
                    nameFeedback.textContent = '';
                }
            });
            emailInput.addEventListener('input', function() {
                if (!validateEmail(emailInput.value)) {
                    emailInput.classList.add('invalid');
                    emailFeedback.textContent = 'Email no válido.';
                } else {
                    emailInput.classList.remove('invalid');
                    emailFeedback.textContent = '';
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>