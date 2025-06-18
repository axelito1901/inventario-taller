<?php
session_start();
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver_id'])) {
    $id = intval($_POST['devolver_id']);
    $comentario = trim($_POST['comentario'] ?? '');
    $fecha_devolucion = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("UPDATE prestamos SET devuelta = 1, fecha_devolucion = ?, comentario_devolucion = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fecha_devolucion, $comentario, $id);
    $success = $stmt->execute();

    echo json_encode(['success' => $success]);
    exit;
}

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">
<div class="max-w-4xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-blue-900 mb-6">📦 Devolver herramienta</h1>

    <div id="mensaje" class="hidden mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">✅ Herramienta devuelta correctamente.</div>

    <?php if ($prestamos->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded shadow">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="py-2 px-3 text-left">Herramienta</th>
                        <th class="py-2 px-3 text-left">Prestada a</th>
                        <th class="py-2 px-3 text-left">Sucursal</th>
                        <th class="py-2 px-3 text-left">Fecha del préstamo</th>
                        <th class="py-2 px-3 text-left">Acción</th>
                        <th class="py-2 px-3 text-left">Comentario</th>
                    </tr>
                </thead>
                <tbody id="tablaPrestamos">
                    <?php while ($p = $prestamos->fetch_assoc()): ?>
                        <tr id="fila-<?= $p['id'] ?>" class="border-t">
                            <td class="py-2 px-3"><?= htmlspecialchars($p['herramienta']) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars($p['mecanico'] ?? $p['nombre_personalizado']) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars($p['sucursal']) ?></td>
                            <td class="py-2 px-3"><?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?></td>
                            <td class="py-2 px-3">
                                <button class="devolver-btn bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded text-sm font-medium transition" data-id="<?= $p['id'] ?>">📦 Devolver</button>
                            </td>
                            <td>
                                <textarea class="textarea is-small" rows="1" placeholder="Comentarios..." id="comentario-<?= $p['id'] ?>"></textarea>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-4 bg-blue-100 text-blue-800 rounded border border-blue-300">
            No hay herramientas prestadas actualmente.
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="index.php" class="inline-block bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">⬅ Volver al inicio</a>
    </div>
</div>

<script>
$('.devolver-btn').click(function() {
    const id = $(this).data('id');
    const comentario = $(`#comentario-${id}`).val().trim();

    $.post('devolver.php', { devolver_id: id, comentario: comentario }, function(response) {
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
