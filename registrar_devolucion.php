<?php
session_start();
include 'includes/conexion.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prestamo_id = $_POST['prestamo_id'];
    $fecha_devolucion = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("UPDATE prestamos SET devuelta = 1, fecha_devolucion = ? WHERE id = ?");
    $stmt->bind_param("si", $fecha_devolucion, $prestamo_id);

    if ($stmt->execute()) {
        $mensaje = "Herramienta devuelta correctamente.";
    } else {
        $error = "Hubo un error al registrar la devolución.";
    }
}

// Obtener préstamos activos
$prestamos = $conexion->query("
    SELECT p.id, h.nombre AS herramienta, m.nombre AS mecanico
    FROM prestamos p
    INNER JOIN herramientas h ON p.herramienta_id = h.id
    INNER JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title is-3">Registrar devolución de herramienta</h1>

        <?php if ($mensaje): ?>
            <div class="notification is-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notification is-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label class="label">Herramienta prestada</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="prestamo_id" required>
                            <option value="">Seleccione una herramienta</option>
                            <?php while ($row = $prestamos->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>">
                                    <?= htmlspecialchars($row['herramienta']) ?> - <?= htmlspecialchars($row['mecanico']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="control mt-4">
                <button type="submit" class="button is-success">Registrar devolución</button>
                <a href="index.php" class="button is-light">Volver</a>
            </div>
        </form>
    </div>
</section>
</body>
</html>
