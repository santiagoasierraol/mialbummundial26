# Mi Álbum Mundial '26 ⚽🃏 - v0.7
Mi Álbum Mundial '26 es una aplicación web ligera y eficiente diseñada para gestionar, rastrear y optimizar la colección de láminas/cromos del álbum Panini de la Copa del Mundo 2026. El objetivo principal es ofrecer a los coleccionistas un panel interactivo para controlar sus faltantes, administrar sus repetidas y facilitar los intercambios.

Desplegado en producción: https://mialbummundial26.infinityfreeapp.com

🚀 Novedades de la Versión 0.7
Esta versión marca la transición hacia un entorno de producción seguro y optimizado, consolidando la arquitectura base del software:

Seguridad SSL de Extremo a Extremo: Migración completa de la infraestructura a HTTPS mediante el protocolo ZeroSSL ECC Domain Secure.

Enrutamiento Seguro Forzado: Implementación de reglas avanzadas en el servidor Apache (.htaccess) para mitigar vulnerabilidades de contenido mixto y forzar el uso de proxies seguros (HTTP_X_FORWARDED_PROTO).

Módulo de Autenticación Base: Estructura inicial para el registro e inicio de sesión seguro de usuarios coleccionistas.

Dashboard Inicial: Panel de control optimizado para la visualización del estado general del álbum.

🛠️ Tecnologías Utilizadas
Backend: PHP 8.x (Arquitectura limpia, manejo de sesiones seguras).

Servidor Web: Apache / Laragon (Entorno local) | InfinityFree (Producción).

Base de Datos: MySQL / MariaDB (Optimización de consultas de inventario).

Seguridad: SSL/TLS (ZeroSSL), Reglas .htaccess.

Frontend: HTML5, CSS3, JavaScript (diseño responsivo para móviles).

📂 Estructura del Proyecto
Plaintext
├── config/
│   └── conexion.php      # Configuración de base de datos y banderas de seguridad
├── css/
│   └── estilos.css       # Estilos globales de la aplicación
├── htdocs/
│   └── .htaccess         # Reglas de redirección HTTPS y optimización de proxy
├── includes/
│   └── header.php        # Cabecera global con políticas de seguridad (CSP)
├── index.php             # Punto de entrada de la aplicación / Login
├── dashboard.php         # Panel principal del usuario
└── README.md             # Documentación del proyecto
🔧 Configuración del Entorno de Producción
Para garantizar que el proyecto funcione sin alertas de seguridad ("No es seguro"), la v0.7 requiere la siguiente configuración en servidores compartidos:

1. Configuración del Servidor (.htaccess)
Asegúrate de que la raíz del servidor cuente con la redirección compatible con proxies inversos:

Apache
RewriteEngine On
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
2. Validación de Entorno Seguro en PHP (conexion.php / config.php)
Para evitar bucles de redirección y asegurar el manejo de sesiones en entornos compartidos, se debe emular el estado HTTPS antes de inicializar las cabeceras:

PHP
<?php
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
3. Mitigación de Contenido Mixto (HTML)
En la cabecera global (header.php), se incluye la política de seguridad para forzar la actualización de peticiones inseguras:

HTML
<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
🗺️ Roadmap hacia la v1.0
[x] Configuración de entorno seguro en producción (HTTPS/SSL) - v0.7

[ ] Sistema completo de inventario (Marcar láminas poseídas / repetidas).

[ ] Filtros avanzados por selección/país y número de lámina.

[ ] Generador de reportes en texto/imagen para compartir "Faltantes" en redes sociales.

[ ] Módulo de "Match" automático para emparejar repetidas con otros coleccionistas locales.

📄 Licencia
Este proyecto está bajo la Licencia MIT. Para más detalles, consulta el archivo LICENSE.

Desarrollado con ❤️ para los fanáticos del fútbol y del coleccionismo.
