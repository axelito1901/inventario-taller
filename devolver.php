<?php
session_start();
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver_id'])) {
    $id = intval($_POST['devolver_id']);
    $fecha_devolucion = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("UPDATE prestamos SET devuelta = 1, fecha_devolucion = ? WHERE id = ?");
    $stmt->bind_param("si", $fecha_devolucion, $id);
    $success = $stmt->execute();

    echo json_encode(['success' => $success]);
    exit;
}

// Cargar préstamos activos
$prestamos = $conexion->query("
    SELECT p.id, h.nombre AS herramienta, p.fecha_hora, m.nombre AS mecanico, p.nombre_personalizado, p.sucursal
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Devolver herramienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">📦 Devolver herramienta</h1>

    <div id="mensaje" class="notification is-success is-hidden">✅ Herramienta devuelta correctamente.</div>

    <?php if ($prestamos->num_rows > 0): ?>
        <table class="table is-striped is-fullwidth" id="tablaPrestamos">
            <thead>
                <tr>
                    <th>Herramienta</th>
                    <th>Prestada a</th>
                    <th>Sucursal</th>
                    <th>Fecha del préstamo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $prestamos->fetch_assoc()): ?>
                    <tr id="fila-<?= $p['id'] ?>">
                        <td><?= htmlspecialchars($p['herramienta']) ?></td>
                        <td><?= htmlspecialchars($p['mecanico'] ?? $p['nombre_personalizado']) ?></td>
                        <td><?= htmlspecialchars($p['sucursal']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?></td>
                        <td>
                            <button class="button is-warning is-small devolver-btn" data-id="<?= $p['id'] ?>">📦 Devolver</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notification is-info">No hay herramientas prestadas actualmente.</div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="index.php" class="button is-light">⬅ Volver al inicio</a>
    </div>
</div>
</section>

<script>
$('.devolver-btn').click(function() {
    const id = $(this).data('id');

    $.post('devolver.php', { devolver_id: id }, function(response) {
        const data = JSON.parse(response);
        if (data.success) {
            $(`#fila-${id}`).fadeOut(300, function() { $(this).remove(); });
            $('#mensaje').removeClass('is-hidden');
            setTimeout(() => $('#mensaje').addClass('is-hidden'), 2000);
        } else {
            alert('⚠️ Hubo un error al devolver la herramienta.');
        }
    });
});
</script>
</body>
</html>
