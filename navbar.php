<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">⚽ Mundial 2026 Collector</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if(isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="index.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="laminas.php">Mis Láminas</a>
          </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center text-white">
        <?php if(isset($_SESSION['username'])): ?>
          <span class="me-3 text-warning">👋 Hola, <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="logout.php" class="btn btn-sm btn-outline-light">Salir</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-sm btn-outline-success me-2">Login</a>
          <a href="registro.php" class="btn btn-sm btn-success">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>