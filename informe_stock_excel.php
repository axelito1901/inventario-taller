<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

require 'vendor/autoload.php'; // ¡Importante! Asegurate que existe

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

include 'includes/conexion.php';

// Crear el Excel
$spreadsheet = new Spreadsheet();

// =================== HOJA 1: EN STOCK ===================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('En stock');

// Encabezados
$sheet1->fromArray(['Código', 'Nombre', 'Ubicación', 'Cantidad'], NULL, 'A1');

$en_stock = $conexion->query("SELECT * FROM herramientas WHERE cantidad > 0 ORDER BY nombre");
$fila = 2;
while ($h = $en_stock->fetch_assoc()) {
    $sheet1->setCellValue("A$fila", $h['codigo']);
    $sheet1->setCellValue("B$fila", $h['nombre']);
    $sheet1->setCellValue("C$fila", $h['ubicacion']);
    $sheet1->setCellValue("D$fila", $h['cantidad']);
    $fila++;
}

// =================== HOJA 2: SIN STOCK ===================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Sin stock');

$sheet2->fromArray(['Código', 'Nombre', 'Ubicación'], NULL, 'A1');

$sin_stock = $conexion->query("SELECT * FROM herramientas WHERE cantidad = 0 ORDER BY nombre");
$fila = 2;
while ($h = $sin_stock->fetch_assoc()) {
    $sheet2->setCellValue("A$fila", $h['codigo']);
    $sheet2->setCellValue("B$fila", $h['nombre']);
    $sheet2->setCellValue("C$fila", $h['ubicacion']);
    $fila++;
}

// =================== GUARDAR ARCHIVO ===================
$fecha = date('Y-m-d');
$nombreArchivo = "informe_stock_$fecha.xls";
$directorio = "informes/";

if (!is_dir($directorio)) {
    mkdir($directorio, 0755, true);
}

$rutaArchivo = $directorio . $nombreArchivo;
$writer = new Xls($spreadsheet);
$writer->save($rutaArchivo);
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
            <a class="button is-success" href="<?= $rutaArchivo ?>" download>📥 Descargar informe</a>
            <a class="button is-light" href="informe_stock.php">⬅ Volver al informe</a>
        </div>
    </div>
</section>
</body>
</html>
