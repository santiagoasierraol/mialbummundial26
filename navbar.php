<?php
// ==============================================================================
// navbar.php - Componente de Navegación Global Reutilizable (V07)
// ==============================================================================

// Validamos si la sesión ya está iniciada antes de intentar leer el nombre de usuario
$usuario_logueado = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Coleccionista';
?>
<!-- BARRA DE NAVEGACIÓN GLOBAL -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <!-- Logo Principal -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <span class="fs-4 me-2">⚽</span> 
            <span>Álbum Mundial 2026</span>
        </a>
        
        <!-- Botón para colapsar en pantallas de Celular (Responsivo) -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Contenedor de los enlaces -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3 text-white-50" href="index.php">🏠 Panel de Control</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3 text-white-50" href="laminas.php">📋 Gestionar Mi Álbum</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3 text-white-50" href="repetidas.php">📋 Gestionar Repetidas</a>
                </li>
            </ul>
            
            <!-- Zona del Perfil y Cierre de Sesión -->
            <div class="d-flex align-items-center gap-3">
                <!-- Dropdown con el nombre del usuario -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle fw-bold px-3 py-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        👤 <?= $usuario_logueado ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <h6 class="dropdown-header text-dark fw-bold">Mi Perfil</h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-danger d-flex align-items-center justify-content-between" href="logout.php">
                                <span>Cerrar Sesión</span>
                                <span class="small">🚪</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>