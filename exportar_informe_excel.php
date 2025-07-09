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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans flex flex-col items-center justify-center">
    <div class="max-w-xl w-full mx-auto mt-32 bg-white p-8 rounded-2xl shadow border border-gray-200 flex flex-col items-center">
        <img src="logo-volskwagen.png" alt="Logo" class="h-16 w-auto mb-6 drop-shadow">
        <h1 class="text-2xl font-extrabold text-[var(--vw-blue)] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-file-excel text-green-600"></i>
            Informe generado
        </h1>
        <p class="text-gray-700 text-lg mb-6 text-center">
            El informe fue generado correctamente como<br>
            <span class="font-bold text-[var(--vw-blue)]"><?= htmlspecialchars($nombreArchivo) ?></span>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
            <a class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg font-semibold flex items-center gap-2 transition shadow w-full justify-center"
               href="<?= htmlspecialchars($rutaArchivo) ?>" download>
                <i class="fa-solid fa-download"></i> Descargar informe
            </a>
            <a class="bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-5 py-3 rounded-lg font-semibold flex items-center gap-2 transition shadow w-full justify-center"
               href="dashboard.php">
                <i class="fa-solid fa-arrow-left"></i> Volver al panel
            </a>
        </div>
    </div>
    <script src="fontawesome/js/all.min.js"></script>
</body>
</html>
