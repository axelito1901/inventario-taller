<?php
session_start();
include 'includes/conexion.php';

$timeout = 1800;

if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?mensaje=sesion_expirada");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$nombreGerente = $_SESSION['gerente'];

$prestamos = $conexion->query("
    SELECT p.*, h.nombre AS herramienta, m.nombre AS mecanico
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Gerente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">

        <h1 class="title is-3">Bienvenido, <?= htmlspecialchars($nombreGerente) ?></h1>

        <div class="buttons mb-5">
            <a href="listar_herramientas.php" class="button is-primary">🔧 Ver herramientas</a>
            <a href="informe_diario.php" class="button is-info">📅 Informe diario</a>
            <a href="exportar_informe_excel.php" class="button is-success">📁 Exportar Excel</a>
            <a href="historial_informes.php" class="button is-dark">🗂 Historial de informes</a>
            <a href="gestion_nombres.php" class="button is-warning">👤 Gestionar nombres</a>
            <a href="logout.php" class="button is-danger">🚪 Cerrar sesión</a>
        </div>

        <h2 class="subtitle is-4">Préstamos activos</h2>

        <?php if ($prestamos->num_rows > 0): ?>
            <table class="table is-fullwidth is-striped">
                <thead>
                    <tr>
                        <th>Herramienta</th>
                        <th>Prestado por</th>
                        <th>Sucursal</th>
                        <th>Fecha y hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $prestamos->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['herramienta']) ?></td>
                            <td><?= $row['mecanico'] ?? htmlspecialchars($row['nombre_personalizado']) ?></td>
                            <td><?= htmlspecialchars($row['sucursal']) ?></td>
                            <td><?= htmlspecialchars($row['fecha_hora']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="notification is-info">No hay préstamos activos en este momento.</div>
        <?php endif; ?>
    </div>
</section>
</body>
</html>
