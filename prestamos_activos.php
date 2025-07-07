<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$prestamos = $conexion->query("
    SELECT p.id, h.nombre, h.codigo, h.ubicacion, m.nombre AS mecanico, p.fecha_hora, p.devuelta
    FROM prestamos p
    JOIN herramientas h ON p.herramienta_id = h.id
    JOIN mecanicos m ON p.mecanico_id = m.id
    ORDER BY p.fecha_hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de préstamos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <section class="section">
        <div class="container">
            <h2 class="title is-3">Préstamos registrados</h2>
            <a href="dashboard.php" class="button is-link is-light mb-4">⬅ Volver al panel</a>

            <div class="table-container">
                <table class="table is-fullwidth is-striped is-hoverable is-bordered">
                    <thead>
                        <tr>
                            <th>Herramienta</th>
                            <th>Código</th>
                            <th>Ubicación</th>
                            <th>Mecánico</th>
                            <th>Fecha y hora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($prestamos->num_rows === 0): ?>
                            <tr><td colspan="6" class="has-text-centered">No hay préstamos registrados.</td></tr>
                        <?php else: ?>
                            <?php while ($row = $prestamos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['codigo']) ?></td>
                                    <td><?= htmlspecialchars($row['ubicacion']) ?></td>
                                    <td><?= htmlspecialchars($row['mecanico']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['fecha_hora'])) ?></td>
                                    <td>
                                        <?php if ($row['devuelta']): ?>
                                            <span class="tag is-success">Devuelta</span>
                                        <?php else: ?>
                                            <span class="tag is-warning">Prestada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</body>
</html>
