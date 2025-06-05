<?php
include 'includes/conexion.php';
$conexion->set_charset("utf8mb4");

$archivo = fopen("nuevas_herramientas.csv", "r");
if (!$archivo) die("❌ Error al abrir el archivo.");

$agregadas = 0;

while (($datos = fgetcsv($archivo, 1000, ";", '"')) !== false) {
    if (count($datos) < 3) continue;

    $codigo = trim($datos[0]);
    $nombre = trim($datos[1]);
    $ubicacion = trim($datos[2]);
    $cantidad = 1;

    if ($nombre == '') continue;

    $codigoValido = ($codigo === '') ? null : $codigo;

    $stmt = $conexion->prepare("INSERT INTO herramientas (codigo, nombre, ubicacion, cantidad) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $codigoValido, $nombre, $ubicacion, $cantidad);
    $stmt->execute();
    $agregadas++;
}
fclose($archivo);

echo "✅ Herramientas insertadas: $agregadas";
?>
