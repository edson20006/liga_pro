<?php 
include 'conexion.php'; 
include 'funciones.php';

require_auth();

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate_request()) {
    $mensaje = "<div class='alert alert-danger'>Solicitud no valida. Recarga la pagina e intenta de nuevo.</div>";
}

// 0. Lógica para ELIMINAR UNA TARJETA (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate_request() && isset($_POST['btn_eliminar_tarjeta'])) {
    $id_tarjeta_eliminar = (int)($_POST['id_tarjeta'] ?? 0);

    if ($id_tarjeta_eliminar <= 0) {
        $mensaje = "<div class='alert alert-warning'>Tarjeta no valida para eliminar.</div>";
    } else {
        $stmt_del_tarjeta = $conn->prepare("DELETE FROM tarjetas WHERE id_tarjeta = ?");
        if ($stmt_del_tarjeta) {
            $stmt_del_tarjeta->bind_param("i", $id_tarjeta_eliminar);
            if ($stmt_del_tarjeta->execute()) {
                if ($stmt_del_tarjeta->affected_rows > 0) {
                    csrf_regenerate_token();
                    $mensaje = "<div class='alert alert-success'>Tarjeta eliminada correctamente.</div>";
                } else {
                    $mensaje = "<div class='alert alert-warning'>No se encontro la tarjeta seleccionada.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>No se pudo eliminar la tarjeta: " . e($stmt_del_tarjeta->error) . "</div>";
            }
            $stmt_del_tarjeta->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>No se pudo preparar la eliminacion de la tarjeta.</div>";
        }
    }
}

// 1. Lógica para REGISTRAR UN GOL (INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate_request() && isset($_POST['btn_guardar_gol'])) {
    $id_partido = (int)($_POST['id_partido'] ?? 0);
    $id_jugador = (int)($_POST['id_jugador'] ?? 0);
    $minuto = (int)($_POST['minuto'] ?? 0);
    $tipo_gol = trim($_POST['tipo_gol'] ?? '');

    if ($id_partido <= 0 || $id_jugador <= 0 || $minuto < 1 || $minuto > 120 || $tipo_gol === '') {
        $mensaje = "<div class='alert alert-warning'>Verifica los datos del gol antes de guardar.</div>";
    } else {
        $sql_valida = "SELECT 1
                       FROM partidos p
                       JOIN jugadores j ON j.id_equipo IN (p.id_equipo_local, p.id_equipo_visitante)
                       WHERE p.id_partido = ? AND j.id_jugador = ? AND p.estado = 'Programado'
                       LIMIT 1";
        $stmt_valida = $conn->prepare($sql_valida);

        if ($stmt_valida) {
            $stmt_valida->bind_param("ii", $id_partido, $id_jugador);
            $stmt_valida->execute();
            $res_valida = $stmt_valida->get_result();
            $jugador_valido = $res_valida && $res_valida->num_rows > 0;
            $stmt_valida->close();

            if (!$jugador_valido) {
                $mensaje = "<div class='alert alert-warning'>El jugador seleccionado no pertenece al partido o el partido ya no está programado.</div>";
            } else {
                $stmt_gol = $conn->prepare("INSERT INTO goles (id_partido, id_jugador, minuto, tipo_gol) VALUES (?, ?, ?, ?)");
                if ($stmt_gol) {
                    $stmt_gol->bind_param("iiis", $id_partido, $id_jugador, $minuto, $tipo_gol);
                    if ($stmt_gol->execute()) {
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>¡Gol registrado al minuto " . e($minuto) . "!</div>";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al registrar gol: " . e($stmt_gol->error) . "</div>";
                    }
                    $stmt_gol->close();
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar el registro del gol.</div>";
                }
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>No se pudo validar el jugador del partido.</div>";
        }
    }
}

// 1.1 Lógica para REGISTRAR UNA TARJETA (INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate_request() && isset($_POST['btn_guardar_tarjeta'])) {
    $id_partido_tarjeta = (int)($_POST['id_partido_tarjeta'] ?? 0);
    $id_jugador_tarjeta = (int)($_POST['id_jugador_tarjeta'] ?? 0);
    $color_tarjeta = trim($_POST['color_tarjeta'] ?? '');
    $minuto_tarjeta = (int)($_POST['minuto_tarjeta'] ?? 0);

    $colores_validos = ['Amarilla', 'Roja'];
    if (
        $id_partido_tarjeta <= 0 ||
        $id_jugador_tarjeta <= 0 ||
        !in_array($color_tarjeta, $colores_validos, true) ||
        $minuto_tarjeta < 1 ||
        $minuto_tarjeta > 120
    ) {
        $mensaje = "<div class='alert alert-warning'>Verifica los datos de la tarjeta antes de guardar.</div>";
    } else {
        $sql_valida_tarjeta = "SELECT 1
                               FROM partidos p
                               JOIN jugadores j ON j.id_equipo IN (p.id_equipo_local, p.id_equipo_visitante)
                               WHERE p.id_partido = ? AND j.id_jugador = ? AND p.estado = 'Programado'
                               LIMIT 1";
        $stmt_valida_tarjeta = $conn->prepare($sql_valida_tarjeta);

        if ($stmt_valida_tarjeta) {
            $stmt_valida_tarjeta->bind_param("ii", $id_partido_tarjeta, $id_jugador_tarjeta);
            $stmt_valida_tarjeta->execute();
            $res_valida_tarjeta = $stmt_valida_tarjeta->get_result();
            $tarjeta_valida = $res_valida_tarjeta && $res_valida_tarjeta->num_rows > 0;
            $stmt_valida_tarjeta->close();

            if (!$tarjeta_valida) {
                $mensaje = "<div class='alert alert-warning'>El jugador no pertenece al partido o el partido ya no está programado.</div>";
            } else {
                $stmt_tarjeta = $conn->prepare("INSERT INTO tarjetas (id_partido, id_jugador, color, minuto) VALUES (?, ?, ?, ?)");
                if ($stmt_tarjeta) {
                    $stmt_tarjeta->bind_param("iisi", $id_partido_tarjeta, $id_jugador_tarjeta, $color_tarjeta, $minuto_tarjeta);
                    if ($stmt_tarjeta->execute()) {
                        csrf_regenerate_token();
                        $mensaje = "<div class='alert alert-success'>¡Tarjeta " . e($color_tarjeta) . " registrada al minuto " . e($minuto_tarjeta) . "!</div>";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al registrar tarjeta: " . e($stmt_tarjeta->error) . "</div>";
                    }
                    $stmt_tarjeta->close();
                } else {
                    $mensaje = "<div class='alert alert-danger'>No se pudo preparar el registro de tarjeta.</div>";
                }
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>No se pudo validar la tarjeta para ese partido.</div>";
        }
    }
}

// 2. Lógica para FINALIZAR EL PARTIDO (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate_request() && isset($_POST['btn_finalizar'])) {
    $id_partido_fin = (int)($_POST['id_partido_fin'] ?? 0);

    if ($id_partido_fin <= 0) {
        $mensaje = "<div class='alert alert-warning'>Selecciona un partido válido para finalizar.</div>";
    } else {
        $stmt_update = $conn->prepare("UPDATE partidos SET estado = 'Finalizado' WHERE id_partido = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("i", $id_partido_fin);
            if ($stmt_update->execute()) {
                csrf_regenerate_token();
                $mensaje = "<div class='alert alert-info'>¡El partido ha finalizado correctamente!</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . e($stmt_update->error) . "</div>";
            }
            $stmt_update->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>No se pudo preparar la finalización del partido.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - Liga Pro</title>
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
                    <li class="nav-item"><a class="nav-link" href="partidos.php">Partidos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="resultados.php">Resultados</a></li>
                    <?php if (has_admin_access($conn)): ?>
                        <li class="nav-item"><a class="nav-link" href="administracion.php">Administracion</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link text-warning" href="logout.php">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Gestión de Resultados</h2>
        <?php echo $mensaje; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white">⚽ Registrar Gol</div>
                    <div class="card-body">
                        <form method="POST" action="resultados.php">
                            <?php echo csrf_input(); ?>
                            
                            <div class="mb-3">
                                <label>Partido (Solo Programados)</label>
                                <select name="id_partido" class="form-select" required>
                                    <option value="">Seleccione el partido...</option>
                                    <?php
                                    $partidos = $conn->query("
                                        SELECT p.id_partido, el.nombre as local, ev.nombre as visitante 
                                        FROM partidos p 
                                        JOIN equipos el ON p.id_equipo_local = el.id_equipo 
                                        JOIN equipos ev ON p.id_equipo_visitante = ev.id_equipo 
                                        WHERE p.estado = 'Programado'
                                    ");
                                    while($pt = $partidos->fetch_assoc()) {
                                        echo "<option value='".e($pt['id_partido'])."'>".e($pt['local'])." VS " .e($pt['visitante'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label>Jugador que anotó</label>
                                    <select name="id_jugador" class="form-select" required>
                                        <option value="">Seleccione jugador...</option>
                                        <?php
                                        // Agrupamos a los jugadores por equipo para que se vea más profesional
                                        $jugadores = $conn->query("
                                            SELECT j.id_jugador, j.nombre, j.apellido, e.nombre as equipo 
                                            FROM jugadores j 
                                            JOIN equipos e ON j.id_equipo = e.id_equipo 
                                            ORDER BY e.nombre, j.nombre
                                        ");
                                        $equipo_actual = "";
                                        while($j = $jugadores->fetch_assoc()) {
                                            if ($equipo_actual != $j['equipo']) {
                                                if ($equipo_actual != "") echo "</optgroup>";
                                                $equipo_actual = $j['equipo'];
                                                echo "<optgroup label='".e($equipo_actual)."'>";
                                            }
                                            echo "<option value='".e($j['id_jugador'])."'>".e($j['nombre'])." " .e($j['apellido'])."</option>";
                                        }
                                        if ($equipo_actual != "") echo "</optgroup>";
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Minuto</label>
                                    <input type="number" name="minuto" class="form-control" required min="1" max="120" placeholder="Ej: 45">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Tipo de Gol</label>
                                <select name="tipo_gol" class="form-select" required>
                                    <option value="Jugada">De Jugada</option>
                                    <option value="Penal">De Penal</option>
                                    <option value="Tiro Libre">Tiro Libre</option>
                                    <option value="Cabeza">De Cabeza</option>
                                </select>
                            </div>

                            <button type="submit" name="btn_guardar_gol" class="btn btn-success w-100">Guardar Gol</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">🏁 Finalizar Partido</div>
                    <div class="card-body">
                        <p class="text-muted">Una vez registrado el pitazo final, el partido ya no aceptará más goles y pasará al historial.</p>
                        <form method="POST" action="resultados.php">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label>Seleccione el partido a finalizar</label>
                                <select name="id_partido_fin" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php
                                    // Rebobinamos la consulta de partidos para volver a usarla
                                    $partidos->data_seek(0);
                                    while($pt = $partidos->fetch_assoc()) {
                                        echo "<option value='".e($pt['id_partido'])."'>".e($pt['local'])." VS " .e($pt['visitante'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="btn_finalizar" class="btn btn-danger w-100">Confirmar Fin del Partido</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-warning">
                    <div class="card-header bg-warning">🟨🟥 Registrar Tarjeta</div>
                    <div class="card-body">
                        <form method="POST" action="resultados.php">
                            <?php echo csrf_input(); ?>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label>Partido (Solo Programados)</label>
                                    <select name="id_partido_tarjeta" class="form-select" required>
                                        <option value="">Seleccione el partido...</option>
                                        <?php
                                        $partidos_tarjeta = $conn->query("
                                            SELECT p.id_partido, el.nombre as local, ev.nombre as visitante
                                            FROM partidos p
                                            JOIN equipos el ON p.id_equipo_local = el.id_equipo
                                            JOIN equipos ev ON p.id_equipo_visitante = ev.id_equipo
                                            WHERE p.estado = 'Programado'
                                        ");
                                        while($ptt = $partidos_tarjeta->fetch_assoc()) {
                                            echo "<option value='".e($ptt['id_partido'])."'>".e($ptt['local'])." VS " .e($ptt['visitante'])."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label>Jugador</label>
                                    <select name="id_jugador_tarjeta" class="form-select" required>
                                        <option value="">Seleccione jugador...</option>
                                        <?php
                                        $jugadores_tarjeta = $conn->query("
                                            SELECT j.id_jugador, j.nombre, j.apellido, e.nombre as equipo
                                            FROM jugadores j
                                            JOIN equipos e ON j.id_equipo = e.id_equipo
                                            ORDER BY e.nombre, j.nombre
                                        ");
                                        $equipo_actual_tarjeta = "";
                                        while($jt = $jugadores_tarjeta->fetch_assoc()) {
                                            if ($equipo_actual_tarjeta != $jt['equipo']) {
                                                if ($equipo_actual_tarjeta != "") echo "</optgroup>";
                                                $equipo_actual_tarjeta = $jt['equipo'];
                                                echo "<optgroup label='".e($equipo_actual_tarjeta)."'>";
                                            }
                                            echo "<option value='".e($jt['id_jugador'])."'>".e($jt['nombre'])." " .e($jt['apellido'])."</option>";
                                        }
                                        if ($equipo_actual_tarjeta != "") echo "</optgroup>";
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label>Color</label>
                                    <select name="color_tarjeta" class="form-select" required>
                                        <option value="Amarilla">Amarilla</option>
                                        <option value="Roja">Roja</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label>Minuto</label>
                                    <input type="number" name="minuto_tarjeta" class="form-control" required min="1" max="120" placeholder="Ej: 67">
                                </div>
                            </div>

                            <button type="submit" name="btn_guardar_tarjeta" class="btn btn-warning w-100">Guardar Tarjeta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Últimos Goles Registrados</div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Partido</th>
                                    <th>Goleador</th>
                                    <th>Minuto</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_goles = "SELECT g.minuto, g.tipo_gol, j.nombre, j.apellido, el.nombre as local, ev.nombre as visitante
                                              FROM goles g
                                              JOIN partidos p ON g.id_partido = p.id_partido
                                              JOIN jugadores j ON g.id_jugador = j.id_jugador
                                              JOIN equipos el ON p.id_equipo_local = el.id_equipo
                                              JOIN equipos ev ON p.id_equipo_visitante = ev.id_equipo
                                              ORDER BY g.id_gol DESC LIMIT 10";
                                $res_goles = $conn->query($sql_goles);
                                
                                if ($res_goles->num_rows > 0) {
                                    while($fila = $res_goles->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . e($fila['local']) . " VS " . e($fila['visitante']) . "</td>";
                                        echo "<td><strong>" . e($fila['nombre']) . " " . e($fila['apellido']) . "</strong></td>";
                                        echo "<td>" . e($fila['minuto']) . "'</td>";
                                        echo "<td>" . e($fila['tipo_gol']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No hay goles registrados.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">Últimas Tarjetas Registradas</div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Partido</th>
                                    <th>Jugador</th>
                                    <th>Color</th>
                                    <th>Minuto</th>
                                    <th class="text-end">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_tarjetas = "SELECT t.id_tarjeta, t.color, t.minuto, j.nombre, j.apellido, el.nombre AS local, ev.nombre AS visitante
                                                FROM tarjetas t
                                                JOIN partidos p ON t.id_partido = p.id_partido
                                                JOIN jugadores j ON t.id_jugador = j.id_jugador
                                                JOIN equipos el ON p.id_equipo_local = el.id_equipo
                                                JOIN equipos ev ON p.id_equipo_visitante = ev.id_equipo
                                                ORDER BY t.id_tarjeta DESC LIMIT 10";
                                $res_tarjetas = $conn->query($sql_tarjetas);

                                if ($res_tarjetas->num_rows > 0) {
                                    while($fila = $res_tarjetas->fetch_assoc()) {
                                        $badge = $fila['color'] === 'Roja' ? 'bg-danger' : 'bg-warning text-dark';
                                        echo "<tr>";
                                        echo "<td>" . e($fila['local']) . " VS " . e($fila['visitante']) . "</td>";
                                        echo "<td><strong>" . e($fila['nombre']) . " " . e($fila['apellido']) . "</strong></td>";
                                        echo "<td><span class='badge " . e($badge) . "'>" . e($fila['color']) . "</span></td>";
                                        echo "<td>" . e($fila['minuto']) . "'</td>";
                                        echo "<td class='text-end'>";
                                        echo "<form method='POST' action='resultados.php' class='d-inline' onsubmit='return confirm(\"Eliminar esta tarjeta?\");'>";
                                        echo csrf_input();
                                        echo "<input type='hidden' name='id_tarjeta' value='" . e($fila['id_tarjeta']) . "'>";
                                        echo "<button type='submit' name='btn_eliminar_tarjeta' class='btn btn-sm btn-outline-danger'>Eliminar</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No hay tarjetas registradas.</td></tr>";
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