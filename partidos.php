<?php 
include 'conexion.php'; 
include 'funciones.php';

require_auth();

$mensaje = "";

// 1. Lógica para GUARDAR un nuevo partido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
    } else {
        $id_local = (int)($_POST['id_equipo_local'] ?? 0);
        $id_visitante = (int)($_POST['id_equipo_visitante'] ?? 0);
        $id_estadio = (int)($_POST['id_estadio'] ?? 0);
        $id_temporada = (int)($_POST['id_temporada'] ?? 0);
        $id_arbitro = (int)($_POST['id_arbitro'] ?? 0);
        $fecha_hora = $_POST['fecha_hora'] ?? '';

        // LÓGICA DE NEGOCIO: Validar que no sea el mismo equipo
        if ($id_local <= 0 || $id_visitante <= 0 || $id_estadio <= 0 || $id_temporada <= 0 || $id_arbitro <= 0 || $fecha_hora === '') {
            $mensaje = "<div class='alert alert-warning'><strong>¡Error!</strong> Completa todos los datos del partido.</div>";
        } elseif ($id_local == $id_visitante) {
            $mensaje = "<div class='alert alert-warning'><strong>¡Error!</strong> Un equipo no puede jugar contra sí mismo. Selecciona equipos diferentes.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO partidos (id_equipo_local, id_equipo_visitante, id_estadio, id_temporada, id_arbitro, fecha_hora) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiiiis", $id_local, $id_visitante, $id_estadio, $id_temporada, $id_arbitro, $fecha_hora);
                if ($stmt->execute()) {
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>¡Partido programado con éxito!</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar la operación.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Partidos - Liga Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="jugadores.php">Jugadores</a></li>
                    <li class="nav-item"><a class="nav-link active" href="partidos.php">Partidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <?php if (has_admin_access($conn)): ?>
                        <li class="nav-item"><a class="nav-link" href="administracion.php">Administracion</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Programación de Partidos</h2>
        <?php echo $mensaje; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">Programar Encuentro</div>
                    <div class="card-body">
                        <form method="POST" action="partidos.php">
                            <?php echo csrf_input(); ?>
                            
                            <div class="mb-2">
                                <label>Equipo Local</label>
                                <select name="id_equipo_local" class="form-select" required>
                                    <option value="">Seleccione local...</option>
                                    <?php
                                    $equipos = $conn->query("SELECT id_equipo, nombre FROM equipos WHERE COALESCE(estado, 'Activo') = 'Activo' ORDER BY nombre ASC");
                                    while($eq = $equipos->fetch_assoc()) {
                                        echo "<option value='".e($eq['id_equipo'])."'>".e($eq['nombre'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label>Equipo Visitante</label>
                                <select name="id_equipo_visitante" class="form-select" required>
                                    <option value="">Seleccione visitante...</option>
                                    <?php
                                    // Rebobinamos el puntero para volver a usar la misma consulta de equipos
                                    $equipos->data_seek(0);
                                    while($eq = $equipos->fetch_assoc()) {
                                        echo "<option value='".e($eq['id_equipo'])."'>".e($eq['nombre'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label>Fecha y Hora</label>
                                <input type="datetime-local" name="fecha_hora" class="form-control" required>
                            </div>

                            <div class="mb-2">
                                <label>Estadio</label>
                                <select name="id_estadio" class="form-select" required>
                                    <?php
                                    $estadios = $conn->query("SELECT id_estadio, nombre FROM estadios");
                                    while($es = $estadios->fetch_assoc()) echo "<option value='".e($es['id_estadio'])."'>".e($es['nombre'])."</option>";
                                    ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label>Temporada</label>
                                    <select name="id_temporada" class="form-select" required>
                                        <?php
                                        $temps = $conn->query("SELECT id_temporada, nombre FROM temporadas");
                                        while($t = $temps->fetch_assoc()) echo "<option value='".e($t['id_temporada'])."'>".e($t['nombre'])."</option>";
                                        ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <label>Árbitro</label>
                                    <select name="id_arbitro" class="form-select" required>
                                        <?php
                                        $arbs = $conn->query("SELECT id_arbitro, apellido FROM arbitros");
                                        while($a = $arbs->fetch_assoc()) echo "<option value='".e($a['id_arbitro'])."'>".e($a['apellido'])."</option>";
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100">Guardar Partido</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Calendario</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover text-center">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Local</th>
                                    <th>VS</th>
                                    <th>Visitante</th>
                                    <th>Estadio</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // SÚPER CONSULTA: Uniendo múltiples tablas para mostrar los nombres reales
                                $sql_leer = "SELECT p.fecha_hora, p.estado, 
                                             el.nombre AS equipo_local, 
                                             ev.nombre AS equipo_visitante, 
                                             es.nombre AS estadio
                                             FROM partidos p
                                             INNER JOIN equipos el ON p.id_equipo_local = el.id_equipo
                                             INNER JOIN equipos ev ON p.id_equipo_visitante = ev.id_equipo
                                             INNER JOIN estadios es ON p.id_estadio = es.id_estadio
                                             ORDER BY p.fecha_hora DESC";
                                
                                $resultado = $conn->query($sql_leer);

                                if ($resultado->num_rows > 0) {
                                    while($fila = $resultado->fetch_assoc()) {
                                        // Formatear la fecha para que se vea más bonita
                                        $fecha_formateada = date("d/m/Y H:i", strtotime($fila['fecha_hora']));
                                        
                                        echo "<tr>";
                                        echo "<td><small>" . e($fecha_formateada) . "</small></td>";
                                        echo "<td><strong>" . e($fila['equipo_local']) . "</strong></td>";
                                        echo "<td>-</td>";
                                        echo "<td><strong>" . e($fila['equipo_visitante']) . "</strong></td>";
                                        echo "<td><small>" . e($fila['estadio']) . "</small></td>";
                                        echo "<td><span class='badge bg-warning text-dark'>" . e($fila['estado']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No hay partidos programados.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>