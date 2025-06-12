<?php
require 'vendor/autoload.php'; // Asegurate de haber instalado PhpSpreadsheet con Composer
include 'includes/conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$archivoExcel = 'informe_ordenado_codigo.xlsx'; // Cambiá esto por el nombre real del archivo

try {
    $spreadsheet = IOFactory::load($archivoExcel);
    $hoja = $spreadsheet->getActiveSheet();
    $filas = $hoja->toArray();

    $actualizados = [];
    foreach ($filas as $index => $fila) {
        if ($index === 0) continue; // salta encabezado

        $codigo = trim($fila[0]);
        $cantidad = intval($fila[1]);

        if ($codigo !== '' && is_numeric($cantidad)) {
            // Buscar nombre
            $stmt = $conexion->prepare("SELECT nombre FROM herramientas WHERE codigo = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $stmt->bind_result($nombre);

            if ($stmt->fetch()) {
                $stmt->close();
                $update = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE codigo = ?");
                $update->bind_param("is", $cantidad, $codigo);
                $update->execute();
                if ($update->affected_rows > 0) {
                    $actualizados[] = [$codigo, $nombre, $cantidad];
                }
                $update->close();
            } else {
                $stmt->close();
            }
        }
    }

    // Mostrar resumen
    if (count($actualizados) > 0) {
        echo "<h3>✅ Se actualizó el stock de " . count($actualizados) . " herramientas:</h3><ul>";
        foreach ($actualizados as [$codigo, $nombre, $cantidad]) {
            echo "<li><strong>$codigo</strong> - $nombre → Stock: $cantidad</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠️ No se actualizó ninguna herramienta.";
    }

} catch (Exception $e) {
    echo "❌ Error al procesar el archivo: " . $e->getMessage();
}
?>
