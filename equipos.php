<?php 
// 1. Incluimos la conexión
include 'conexion.php'; 
include 'funciones.php';

require_auth();

// Asegura soporte de baja logica para equipos.
$columna_estado = $conn->query("SHOW COLUMNS FROM equipos LIKE 'estado'");
if ($columna_estado && $columna_estado->num_rows === 0) {
    $conn->query("ALTER TABLE equipos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'Activo'");
}
$conn->query("UPDATE equipos SET estado = 'Activo' WHERE estado IS NULL OR estado = ''");

// 2. Lógica para GUARDAR un nuevo equipo si se envió el formulario
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate_request()) {
        $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'crear_entrenador') {
        $nombre_entrenador = trim($_POST['nombre_entrenador'] ?? '');
        $apellido_entrenador = trim($_POST['apellido_entrenador'] ?? '');
        $anios_experiencia = (int)($_POST['anios_experiencia'] ?? 0);
        $id_equipo_entrenador = (int)($_POST['id_equipo_entrenador'] ?? 0);

        if ($nombre_entrenador === '' || $apellido_entrenador === '' || $id_equipo_entrenador <= 0) {
            $mensaje = "<div class='alert alert-warning'>Completa nombre, apellido y equipo del entrenador.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO entrenadores (nombre, apellido, anios_experiencia, id_equipo) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssii", $nombre_entrenador, $apellido_entrenador, $anios_experiencia, $id_equipo_entrenador);
                if ($stmt->execute()) {
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>Entrenador registrado correctamente.</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo registrar el entrenador: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar el registro del entrenador.</div>";
            }
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_entrenador') {
        $id_entrenador = (int)($_POST['id_entrenador'] ?? 0);

        if ($id_entrenador <= 0) {
            $mensaje = "<div class='alert alert-warning'>Entrenador no valido para eliminar.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM entrenadores WHERE id_entrenador = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id_entrenador);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>Entrenador eliminado correctamente.</div>";
                    } else {
                        $mensaje = "<div class='alert alert-warning'>No se encontro el entrenador seleccionado.</div>";
                    }
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo eliminar el entrenador: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar la eliminacion del entrenador.</div>";
            }
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado_equipo') {
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        $nuevo_estado = trim($_POST['nuevo_estado'] ?? '');

        if ($id_equipo <= 0) {
            $mensaje = "<div class='alert alert-warning'>Equipo no valido para actualizar.</div>";
        } elseif (!in_array($nuevo_estado, ['Activo', 'Inactivo'], true)) {
            $mensaje = "<div class='alert alert-warning'>Estado no valido para el equipo.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE equipos SET estado = ? WHERE id_equipo = ?");
            if ($stmt) {
                $stmt->bind_param("si", $nuevo_estado, $id_equipo);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>Estado del equipo actualizado a " . e($nuevo_estado) . ".</div>";
                    } else {
                        $mensaje = "<div class='alert alert-warning'>No se encontro el equipo o ya tenia ese estado.</div>";
                    }
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo actualizar el estado del equipo: " . e($stmt->error) . "</div>";
                }
                $stmt->close();
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo preparar la actualizacion del estado.</div>";
            }
        }
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_fundacion = $_POST['fecha_fundacion'] ?? '';

        if ($nombre === '' || $fecha_fundacion === '') {
            $mensaje = "<div class='alert alert-warning'>Completa todos los campos obligatorios.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO equipos (nombre, fecha_fundacion) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $fecha_fundacion);
                if ($stmt->execute()) {
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>¡Equipo registrado con éxito!</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al registrar: " . e($stmt->error) . "</div>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - Liga Pro</title>
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
                    <li class="nav-item"><a class="nav-link active" href="equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="jugadores.php">Jugadores</a></li>
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
        <h2 class="mb-4">Gestión de Equipos</h2>
        
        <?php echo $mensaje; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">Nuevo Equipo</div>
                    <div class="card-body">
                        <form method="POST" action="equipos.php">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label class="form-label">Nombre del Club</label>
                                <input type="text" name="nombre" class="form-control" required placeholder="Ej: Los Galácticos FC">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fecha de Fundación</label>
                                <input type="date" name="fecha_fundacion" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Guardar Equipo</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Equipos Registrados</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Equipo</th>
                                    <th>Fundación</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Lógica para LEER los equipos de la base de datos
                                $sql_leer = "SELECT id_equipo, nombre, fecha_fundacion, COALESCE(estado, 'Activo') AS estado FROM equipos ORDER BY id_equipo DESC";
                                $resultado = $conn->query($sql_leer);

                                if ($resultado->num_rows > 0) {
                                    // Recorremos cada fila de la base de datos y la mostramos en la tabla
                                    while($fila = $resultado->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . e($fila['id_equipo']) . "</td>";
                                        echo "<td><strong>" . e($fila['nombre']) . "</strong></td>";
                                        echo "<td>" . e($fila['fecha_fundacion']) . "</td>";
                                        $badge_estado = $fila['estado'] === 'Inactivo' ? 'bg-secondary' : 'bg-success';
                                        echo "<td><span class='badge " . e($badge_estado) . "'>" . e($fila['estado']) . "</span></td>";
                                        echo "<td class='text-end'>";
                                        if ($fila['estado'] === 'Inactivo') {
                                            echo "<form method='POST' action='equipos.php' class='d-inline' onsubmit='return confirm(\"Reactivar este equipo?\");'>";
                                            echo csrf_input();
                                            echo "<input type='hidden' name='accion' value='cambiar_estado_equipo'>";
                                            echo "<input type='hidden' name='nuevo_estado' value='Activo'>";
                                            echo "<input type='hidden' name='id_equipo' value='" . e($fila['id_equipo']) . "'>";
                                            echo "<button type='submit' class='btn btn-sm btn-outline-success'>Reactivar</button>";
                                            echo "</form>";
                                        } else {
                                            echo "<form method='POST' action='equipos.php' class='d-inline' onsubmit='return confirm(\"Desactivar este equipo?\");'>";
                                            echo csrf_input();
                                            echo "<input type='hidden' name='accion' value='cambiar_estado_equipo'>";
                                            echo "<input type='hidden' name='nuevo_estado' value='Inactivo'>";
                                            echo "<input type='hidden' name='id_equipo' value='" . e($fila['id_equipo']) . "'>";
                                            echo "<button type='submit' class='btn btn-sm btn-outline-warning'>Desactivar</button>";
                                            echo "</form>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Aún no hay equipos registrados.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">Nuevo Entrenador</div>
                    <div class="card-body">
                        <form method="POST" action="equipos.php">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="accion" value="crear_entrenador">
                            <div class="mb-2">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre_entrenador" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Apellido</label>
                                <input type="text" name="apellido_entrenador" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Años de experiencia</label>
                                <input type="number" name="anios_experiencia" class="form-control" min="0" value="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Equipo</label>
                                <select name="id_equipo_entrenador" class="form-select" required>
                                    <option value="">Seleccione un equipo...</option>
                                    <?php
                                    $equipos_entrenador = $conn->query("SELECT id_equipo, nombre FROM equipos WHERE COALESCE(estado, 'Activo') = 'Activo' ORDER BY nombre ASC");
                                    while($eqe = $equipos_entrenador->fetch_assoc()) {
                                        echo "<option value='" . e($eqe['id_equipo']) . "'>" . e($eqe['nombre']) . "</option>";
                                    }
                                    ?>
                                </select>
                                <small class="text-muted">Cada equipo puede tener un solo entrenador activo.</small>
                            </div>
                            <button type="submit" class="btn btn-secondary w-100">Guardar Entrenador</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Entrenadores Registrados</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Experiencia</th>
                                    <th>Equipo</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_entrenadores = "SELECT en.id_entrenador, en.nombre, en.apellido, en.anios_experiencia, e.nombre AS equipo
                                                    FROM entrenadores en
                                                    LEFT JOIN equipos e ON en.id_equipo = e.id_equipo
                                                    ORDER BY en.id_entrenador DESC";
                                $res_entrenadores = $conn->query($sql_entrenadores);

                                if ($res_entrenadores && $res_entrenadores->num_rows > 0) {
                                    while($en = $res_entrenadores->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><strong>" . e($en['nombre']) . " " . e($en['apellido']) . "</strong></td>";
                                        echo "<td>" . e($en['anios_experiencia']) . " años</td>";
                                        echo "<td>" . e($en['equipo'] ?? 'Sin equipo') . "</td>";
                                        echo "<td class='text-end'>";
                                        echo "<form method='POST' action='equipos.php' class='d-inline' onsubmit='return confirm(\"Eliminar este entrenador?\");'>";
                                        echo csrf_input();
                                        echo "<input type='hidden' name='accion' value='eliminar_entrenador'>";
                                        echo "<input type='hidden' name='id_entrenador' value='" . e($en['id_entrenador']) . "'>";
                                        echo "<button type='submit' class='btn btn-sm btn-outline-danger'>Eliminar</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>Aún no hay entrenadores registrados.</td></tr>";
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