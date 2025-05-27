<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$hoy = date('Y-m-d');
$nombreArchivo = "informe_{$hoy}.xls";
$directorio = "informes/";

if (!is_dir($directorio)) {
    mkdir($directorio, 0755, true);
}

$rutaArchivo = $directorio . $nombreArchivo;

$stmt = $conexion->prepare("
    SELECT h.nombre AS herramienta, m.nombre AS mecanico,
           p.nombre_personalizado, p.fecha_hora, p.devuelta,
           p.fecha_devolucion, p.sucursal
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE DATE(p.fecha_hora) = ?
    ORDER BY p.fecha_hora DESC
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$resultado = $stmt->get_result();

$contenido = "<table border='1'>";
$contenido .= "<tr>
    <th>Herramienta</th>
    <th>Retirado por</th>
    <th>Sucursal</th>
    <th>Fecha y hora del préstamo</th>
    <th>Estado</th>
    <th>Fecha y hora de devolución</th>
</tr>";

while ($row = $resultado->fetch_assoc()) {
    $contenido .= "<tr>";
    $contenido .= "<td>" . htmlspecialchars($row['herramienta']) . "</td>";
    $contenido .= "<td>" . htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']) . "</td>";
    $contenido .= "<td>" . htmlspecialchars($row['sucursal']) . "</td>";
    $contenido .= "<td>" . $row['fecha_hora'] . "</td>";
    $contenido .= "<td>" . ($row['devuelta'] ? 'Devuelta' : 'Activa') . "</td>";
    $contenido .= "<td>" . ($row['fecha_devolucion'] ? $row['fecha_devolucion'] : '-') . "</td>";
    $contenido .= "</tr>";
}

$contenido .= "</table>";
file_put_contents($rutaArchivo, $contenido);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe generado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <div class="notification is-success">
            El informe fue generado correctamente como <strong><?= $nombreArchivo ?></strong>.
        </div>
        <div class="buttons">
            <a class="button is-link" href="<?= $rutaArchivo ?>" download>📁 Descargar informe</a>
            <a class="button is-light" href="dashboard.php">⬅ Volver al panel</a>
        </div>
    </div>
</section>
</body>
</html>
    