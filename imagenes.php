<?php
include 'includes/conexion.php';

$directorio_imagenes = 'imagenes/';

$resultado = $conexion->query("SELECT id, nombre FROM herramientas");

while ($herramienta = $resultado->fetch_assoc()) {
    $id = $herramienta['id'];
    $nombre_original = $herramienta['nombre'];

    // Generar nombre de archivo: sin "/", ni espacios
    $nombre_limpio = trim(str_replace(["/", " "], ["_", ""], $nombre_original));
    $extensiones = ['jpg', 'png', 'jpeg', 'webp']; // extensiones posibles
    $imagen_encontrada = null;

    foreach ($extensiones as $ext) {
        $ruta = $directorio_imagenes . $nombre_limpio . "." . $ext;
        if (file_exists($ruta)) {
            $imagen_encontrada = $ruta;
            break;
        }
    }

    // Si se encontró imagen, actualizar en BD
    if ($imagen_encontrada) {
        $stmt = $conexion->prepare("UPDATE herramientas SET imagen = ? WHERE id = ?");
        $stmt->bind_param("si", $imagen_encontrada, $id);
        $stmt->execute();
        echo "✅ Asignada imagen a '$nombre_original': $imagen_encontrada<br>";
    } else {
        echo "⚠️ No se encontró imagen para '$nombre_original'<br>";
    }
}
?>
