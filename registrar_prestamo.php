<?php
session_start();
include 'includes/conexion.php';

$mensaje = '';
$error = '';
$exito = false;
$detallePrestadas = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $herramientas = $_POST['herramienta_id'] ?? [];
    $sucursal = $_POST['sucursal'] ?? '';
    $nombre = ucwords(mb_strtolower(trim($_POST['nombre_personalizado'] ?? '')));
    $fecha_hora = date('Y-m-d H:i:s');

    if ($nombre === '') {
        $error = "Debés ingresar el nombre.";
    } elseif (empty($herramientas)) {
        $error = "No se seleccionó ninguna herramienta.";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM prestamos WHERE herramienta_id = ? AND devuelta = 0");

        $herramientasDisponibles = [];
        foreach ($herramientas as $id) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $herramientasDisponibles[] = intval($id);
            }
        }
        $stmt->close();

        if (empty($herramientasDisponibles)) {
            $error = "Todas las herramientas seleccionadas ya están prestadas.";
        } else {
            if ($sucursal === 'Lanús') {
                $conexion->query("INSERT IGNORE INTO mecanicos (nombre) VALUES ('$nombre')");
                $res = $conexion->query("SELECT id FROM mecanicos WHERE nombre = '$nombre'");
                $mecanico = $res->fetch_assoc();
                $mecanico_id = $mecanico['id'];

                $stmt = $conexion->prepare("INSERT INTO prestamos (herramienta_id, mecanico_id, fecha_hora, devuelta, sucursal) VALUES (?, ?, ?, 0, ?)");
                foreach ($herramientasDisponibles as $id) {
                    $stmt->bind_param("iiss", $id, $mecanico_id, $fecha_hora, $sucursal);
                    $stmt->execute();
                }
                $stmt->close();
            } else {
                $conexion->query("INSERT IGNORE INTO nombres_personalizados (nombre) VALUES ('$nombre')");
                $stmt = $conexion->prepare("INSERT INTO prestamos (herramienta_id, fecha_hora, devuelta, nombre_personalizado, sucursal) VALUES (?, ?, 0, ?, ?)");
                foreach ($herramientasDisponibles as $id) {
                    $stmt->bind_param("isss", $id, $fecha_hora, $nombre, $sucursal);
                    $stmt->execute();
                }
                $stmt->close();
            }

            if (!empty($herramientasDisponibles)) {
                $ids = implode(',', $herramientasDisponibles);
                $resultado = $conexion->query("SELECT nombre, codigo FROM herramientas WHERE id IN ($ids)");
                while ($h = $resultado->fetch_assoc()) {
                    $detallePrestadas[] = $h;
                }
            }

            $mensaje = "Préstamo registrado correctamente para " . count($herramientasDisponibles) . " herramienta(s).";
            $exito = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Registrar Préstamo</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/all.min.css" />
    <link rel="stylesheet" href="css/fontawesome.min.css" />
    <style>
        body {
            background-color: #f6f6f6;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 16px rgb(0 0 0 / 0.1);
        }
        h1 {
            color: #00247D;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .message {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
        }
        .message.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #22c55e;
        }
        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .message i {
            font-size: 1.3rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
            margin-bottom: 1.5rem;
        }
        thead {
            background-color: #00247D;
            color: white;
        }
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        a.btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #00247D;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.15);
            transition: background-color 0.2s;
        }
        a.btn:hover {
            background-color: #001a5c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-clipboard-list"></i> Registrar Préstamo</h1>

        <?php if ($mensaje): ?>
            <div class="message <?= $exito ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $exito ? 'fa-check-circle' : 'fa-xmark' ?>"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>

            <?php if ($exito && count($detallePrestadas) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Sucursal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detallePrestadas as $h): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['nombre']) ?></td>
                                    <td><?= htmlspecialchars($h['codigo']) ?></td>
                                    <td><?= htmlspecialchars($sucursal) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <a href="index.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
        <?php elseif ($error): ?>
            <div class="message error">
                <i class="fa-solid fa-xmark"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <a href="index.php" class="btn" style="background-color: #6b7280; color: white;">
                <i class="fa-solid fa-arrow-left"></i> Volver al inicio
            </a>
        <?php endif; ?>
    </div>

    <script src="fontawesome/js/all.min.js"></script>
</body>
</html>