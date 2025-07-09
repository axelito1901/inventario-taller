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
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: #f4f4f4;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .container {
            max-width: 480px;
            margin: 60px auto;
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .notification {
            background: #e6f9ed;
            color: #1a7f37;
            border-radius: 1rem;
            padding: 1.2rem 1rem;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            border: 1px solid #b6e7c9;
            text-align: center;
        }
        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .button {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            border: none;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
            cursor: pointer;
        }
        .button.is-success {
            background: #1a7f37;
            color: #fff;
        }
        .button.is-success:hover {
            background: #176c2e;
        }
        .button.is-light {
            background: #f4f4f4;
            color: #222;
            border: 1px solid #e0e0e0;
        }
        .button.is-light:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
<section>
    <div class="container">
        <div class="notification">
            El informe fue generado correctamente como <strong><?= $nombreArchivo ?></strong>.
        </div>
        <div class="buttons">
            <a class="button is-success" href="<?= $rutaArchivo ?>" download>Descargar informe</a>
            <a class="button is-light" href="informe_stock.php">Volver al informe</a>
        </div>
    </div>
</section>
</body>
</html>