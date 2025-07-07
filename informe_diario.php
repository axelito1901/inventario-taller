<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$sucursal = $_GET['sucursal'] ?? 'todas';

$sql = "
    SELECT h.nombre AS herramienta, m.nombre AS mecanico,
           p.nombre_personalizado, p.fecha_hora, p.devuelta, 
           p.fecha_devolucion, p.sucursal
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE DATE(p.fecha_hora) = ?
";
if ($sucursal !== 'todas') {
    $sql .= " AND p.sucursal = ?";
}
$sql .= " ORDER BY p.fecha_hora DESC";

$stmt = $sucursal === 'todas'
    ? $conexion->prepare($sql)
    : $conexion->prepare($sql);
    
if ($sucursal === 'todas') {
    $stmt->bind_param("s", $fecha);
} else {
    $stmt->bind_param("ss", $fecha, $sucursal);
}

$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe diario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">Informe de préstamos</h1>

    <form method="GET" class="box">
        <div class="columns is-multiline">
            <div class="column is-4">
                <label class="label">Sucursal</label>
                <div class="select is-fullwidth">
                    <select name="sucursal">
                        <option value="todas" <?= $sucursal === 'todas' ? 'selected' : '' ?>>Todas</option>
                        <option value="Lanús" <?= $sucursal === 'Lanús' ? 'selected' : '' ?>>Lanús</option>
                        <option value="Osvaldo Cruz" <?= $sucursal === 'Osvaldo Cruz' ? 'selected' : '' ?>>Osvaldo Cruz</option>
                    </select>
                </div>
            </div>
            <div class="column is-4 is-flex is-align-items-end">
                <button class="button is-link">🔎 Ver informe</button>
            </div>
        </div>
    </form>

    <div class="buttons mb-4">
        <a href="dashboard.php" class="button is-light">⬅ Volver al panel</a>
    </div>

    <?php if ($resultado->num_rows > 0): ?>
        <table class="table is-striped is-fullwidth">
            <thead>
                <tr>
                    <th>Herramienta</th>
                    <th>Retirado por</th>
                    <th>Sucursal</th>
                    <th>Hora préstamo</th>
                    <th>Estado</th>
                    <th>Hora devolución</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['herramienta']) ?></td>
                        <td><?= htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']) ?></td>
                        <td><?= htmlspecialchars($row['sucursal']) ?></td>
                        <td><?= date('H:i', strtotime($row['fecha_hora'])) ?></td>
                        <td>
                            <?php if ($row['devuelta']): ?>
                                <span class="tag is-success">Devuelta</span>
                            <?php else: ?>
                                <span class="tag is-warning">Activa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $row['devuelta'] ? date('H:i', strtotime($row['fecha_devolucion'])) : '-' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notification is-info">No se encontraron préstamos para esta sucursal.</div>
    <?php endif; ?>
</div>
</section>
</body>
</html>
