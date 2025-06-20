# Conexión+

Conexión+ es una aplicación web de red social simple y funcional desarrollada con PHP puro, HTML, CSS y MySQL. Permite a los usuarios registrarse, iniciar sesión, personalizar su perfil, seguir a otros usuarios, crear publicaciones, dar "me gusta" y comentar.

## ✨ Características

-   **👤 Autenticación de Usuarios:** Sistema completo de registro e inicio de sesión con sesiones seguras.
-   **🖼️ Perfil Personalizable:** Cada usuario tiene su propio perfil donde puede:
    -   Subir y cambiar su foto de perfil.
    -   Editar su nombre de usuario, biografía y correo electrónico.
    -   Ver sus propias publicaciones.
-   **👥 Sistema de Seguidores:** Los usuarios pueden seguir y dejar de seguir a otros para personalizar su experiencia.
-   **📝 Publicaciones, Comentarios y Me Gusta:**
    -   Crear nuevas publicaciones con texto.
    -   Dar "me gusta" a las publicaciones de otros.
    -   Dejar comentarios en las publicaciones.
-   **🌐 Feed Global:** Una página principal (`dashboard.php`) que muestra las publicaciones más recientes de todos los usuarios en la plataforma, creando una comunidad activa.
-   **🎨 Diseño Modular:** La interfaz utiliza archivos CSS dedicados para cada página, manteniendo el código limpio y organizado.

## 🛠️ Tecnologías Utilizadas

-   **Backend:** PHP
-   **Base de Datos:** MySQL
-   **Frontend:** HTML5, CSS3

## 🚀 Instalación y Puesta en Marcha

Sigue estos pasos para instalar y ejecutar el proyecto en un entorno de desarrollo local:

1.  **Clonar el repositorio:**
    ```bash
    git clone https://URL_DEL_REPOSITORIO.git
    cd social-netwok
    ```

2.  **Configurar la Base de Datos:**
    -   Abre tu gestor de base de datos (como phpMyAdmin).
    -   Crea una nueva base de datos.
    -   Importa el archivo `database.sql` en la base de datos que acabas de crear. Esto configurará todas las tablas necesarias.

3.  **Configurar las credenciales:**
    -   En la raíz del proyecto, encontrarás un archivo llamado `config.php`.
    -   Ábrelo y edita las siguientes variables con tus propias credenciales de la base de datos:
      ```php
      define('DB_SERVER', 'localhost');
      define('DB_USERNAME', 'tu_usuario_de_bd');
      define('DB_PASSWORD', 'tu_contraseña_de_bd');
      define('DB_NAME', 'nombre_de_tu_bd');
      ```

4.  **Ejecutar el proyecto:**
    -   Mueve la carpeta del proyecto al directorio de tu servidor web local (por ejemplo, `htdocs` en XAMPP).
    -   Inicia tu servidor Apache y MySQL.
    -   Abre tu navegador y ve a `http://localhost/social-netwok`.

¡Y listo! Ya puedes registrar un nuevo usuario y empezar a usar Conexión+. 