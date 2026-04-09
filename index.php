<?php 
// 1. Incluimos la conexión a la base de datos
include 'conexion.php'; 
include 'funciones.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Liga Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/site.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">⚽ Liga Pro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="jugadores.php">Jugadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="partidos.php">Partidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <?php if (is_authenticated() && has_admin_access($conn)): ?>
                        <li class="nav-item"><a class="nav-link" href="administracion.php">Administracion</a></li>
                    <?php endif; ?>
                    <?php if (is_authenticated()): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 text-center mb-4">
                <h1 class="display-4">Panel de Control</h1>
                <p class="lead">Bienvenido al sistema de gestión de la Liga de Fútbol Amateur.</p>
            </div>
        </div>

        <div class="row panel-kpi">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Equipos</div>
                    <div class="card-body">
                        <?php
                            // Lógica de Backend: Contar cuántos equipos hay
                            $sql = "SELECT COUNT(*) as total FROM equipos";
                            $resultado = $conn->query($sql);
                            $fila = $resultado->fetch_assoc();
                        ?>
                        <h2 class="card-title">
                            <?php echo e($fila['total']); ?>
                        </h2>
                        <p class="card-text">Clubes inscritos en el torneo.</p>
                        <a href="equipos.php" class="btn btn-light btn-sm">Gestionar</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Jugadores</div>
                    <div class="card-body">
                        <?php
                            // Lógica de Backend: Contar cuántos jugadores hay
                            $sql = "SELECT COUNT(*) as total FROM jugadores";
                            $resultado = $conn->query($sql);
                            $fila = $resultado->fetch_assoc();
                        ?>
                        <h2 class="card-title">
                            <?php echo e($fila['total']); ?>
                        </h2>
                        <p class="card-text">Atletas registrados en los clubes.</p>
                        <a href="jugadores.php" class="btn btn-light btn-sm">Gestionar</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-header">Partidos</div>
                    <div class="card-body">
                        <?php
                            // Lógica de Backend: Contar cuántos partidos hay
                            $sql = "SELECT COUNT(*) as total FROM partidos";
                            $resultado = $conn->query($sql);
                            $fila = $resultado->fetch_assoc();
                        ?>
                        <h2 class="card-title">
                            <?php echo e($fila['total']); ?>
                        </h2>
                        <p class="card-text">Encuentros programados o jugados.</p>
                        <a href="partidos.php" class="btn btn-light btn-sm">Gestionar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>