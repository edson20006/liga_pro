<?php 
include 'conexion.php'; 
include 'funciones.php';

require_auth();

$mensaje = "";

// 1. Lógica para GUARDAR un nuevo jugador
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_jugador') {
        $id_jugador = (int)($_POST['id_jugador'] ?? 0);

        if ($id_jugador <= 0) {
            $mensaje = "<div class='alert alert-warning'>Jugador no valido para eliminar.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM jugadores WHERE id_jugador = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id_jugador);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>Jugador eliminado correctamente.</div>";
                    } else {
                        $mensaje = "<div class='alert alert-warning'>No se encontro el jugador seleccionado.</div>";
                    }
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo eliminar el jugador: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar la eliminacion del jugador.</div>";
            }
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_jugadores_equipo') {
        $id_equipo_eliminar = (int)($_POST['id_equipo_eliminar'] ?? 0);

        if ($id_equipo_eliminar <= 0) {
            $mensaje = "<div class='alert alert-warning'>Selecciona un equipo valido para eliminar su plantilla.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM jugadores WHERE id_equipo = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id_equipo_eliminar);
                if ($stmt->execute()) {
                    $total = (int)$stmt->affected_rows;
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>Se eliminaron " . e($total) . " jugador(es) del equipo seleccionado.</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo eliminar la plantilla del equipo: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar la eliminacion por equipo.</div>";
            }
        }
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dorsal = (int)($_POST['dorsal'] ?? 0);
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        $id_posicion = (int)($_POST['id_posicion'] ?? 0);

        if ($nombre === '' || $apellido === '' || $dorsal < 1 || $dorsal > 99 || $id_equipo <= 0 || $id_posicion <= 0) {
            $mensaje = "<div class='alert alert-warning'>Verifica los datos del formulario antes de guardar.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellido, dorsal, id_equipo, id_posicion) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssiii", $nombre, $apellido, $dorsal, $id_equipo, $id_posicion);
                if ($stmt->execute()) {
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>¡Jugador inscrito con éxito!</div>";
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
    <title>Jugadores - Liga Pro</title>
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
                    <li class="nav-item"><a class="nav-link active" href="jugadores.php">Jugadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="partidos.php">Partidos</a></li>
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
        <h2 class="mb-4">Plantillas de Jugadores</h2>
        <?php echo $mensaje; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">Inscribir Jugador</div>
                    <div class="card-body">
                        <form method="POST" action="jugadores.php">
                            <?php echo csrf_input(); ?>
                            <div class="mb-2">
                                <label>Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label>Apellido</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label>Dorsal (Número)</label>
                                <input type="number" name="dorsal" class="form-control" required min="1" max="99">
                            </div>
                            
                            <div class="mb-2">
                                <label>Equipo</label>
                                <select name="id_equipo" class="form-select" required>
                                    <option value="">Seleccione un equipo...</option>
                                    <?php
                                    $equipos = $conn->query("SELECT id_equipo, nombre FROM equipos WHERE COALESCE(estado, 'Activo') = 'Activo' ORDER BY nombre ASC");
                                    while($eq = $equipos->fetch_assoc()) {
                                        echo "<option value='".e($eq['id_equipo'])."'>".e($eq['nombre'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Posición</label>
                                <select name="id_posicion" class="form-select" required>
                                    <option value="">Seleccione posición...</option>
                                    <?php
                                    $pos = $conn->query("SELECT id_posicion, nombre_posicion FROM posiciones");
                                    while($p = $pos->fetch_assoc()) {
                                        echo "<option value='".e($p['id_posicion'])."'>".e($p['nombre_posicion'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100">Guardar Jugador</button>
                        </form>

                        <hr>
                        <h6 class="text-danger mb-2">Eliminar Plantilla por Equipo</h6>
                        <form method="POST" action="jugadores.php" onsubmit="return confirm('Esta accion eliminara todos los jugadores del equipo seleccionado. Deseas continuar?');">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="eliminar_jugadores_equipo">
                            <div class="mb-2">
                                <label>Equipo</label>
                                <select name="id_equipo_eliminar" class="form-select" required>
                                    <option value="">Seleccione un equipo...</option>
                                    <?php
                                    $equipos_eliminar = $conn->query("SELECT id_equipo, nombre FROM equipos WHERE COALESCE(estado, 'Activo') = 'Activo' ORDER BY nombre ASC");
                                    while($eqe = $equipos_eliminar->fetch_assoc()) {
                                        echo "<option value='".e($eqe['id_equipo'])."'>".e($eqe['nombre'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100">Eliminar Plantilla</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Nómina General</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Equipo</th>
                                    <th>Posición</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // LA CONSULTA CLAVE (INNER JOIN)
                                $sql_leer = "SELECT j.id_jugador, j.dorsal, j.nombre, j.apellido, e.nombre AS nombre_equipo, p.nombre_posicion 
                                             FROM jugadores j 
                                             INNER JOIN equipos e ON j.id_equipo = e.id_equipo
                                             INNER JOIN posiciones p ON j.id_posicion = p.id_posicion
                                             ORDER BY e.nombre ASC, j.dorsal ASC";
                                
                                $resultado = $conn->query($sql_leer);

                                if ($resultado->num_rows > 0) {
                                    while($fila = $resultado->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><strong>" . e($fila['dorsal']) . "</strong></td>";
                                        echo "<td>" . e($fila['nombre']) . " " . e($fila['apellido']) . "</td>";
                                        echo "<td><span class='badge bg-primary'>" . e($fila['nombre_equipo']) . "</span></td>";
                                        echo "<td>" . e($fila['nombre_posicion']) . "</td>";
                                        echo "<td class='text-end'>";
                                        echo "<form method='POST' action='jugadores.php' class='d-inline' onsubmit='return confirm(\"Eliminar este jugador?\");'>";
                                        echo csrf_input();
                                        echo "<input type='hidden' name='accion' value='eliminar_jugador'>";
                                        echo "<input type='hidden' name='id_jugador' value='" . e($fila['id_jugador']) . "'>";
                                        echo "<button type='submit' class='btn btn-sm btn-outline-danger'>Eliminar</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No hay jugadores registrados.</td></tr>";
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