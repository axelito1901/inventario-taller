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
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      body { background: #f6f6f6; }
      header.header-fixed-vw {
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-bottom: 1px solid #e5e7eb;
      }
    </style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">

<!-- HEADER FIJO CON LOGO Y TÍTULO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-blue-900">Devolver herramienta</span>
  <a href="index.php" class="ml-auto text-sm bg-blue-100 text-blue-800 px-4 py-2 rounded hover:bg-blue-200 transition">⬅ Volver al inicio</a>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="max-w-4xl mx-auto pt-28 py-10 px-4">
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
</div>

<script>
$('.devolver-btn').click(function() {
    const id = $(this).data('id');
    const comentario = $(`#comentario-${id}`).val().trim();

    $.post('devolver.php', { devolver_id: id, comentario: comentario }, function(response) {
        const data = JSON.parse(response);
        if (data.success) {
            $(`#fila-${id}`).fadeOut(300, function() { $(this).remove(); });
            $('#mensaje').removeClass('hidden');
            setTimeout(() => $('#mensaje').addClass('hidden'), 2000);
        } else {
            alert('⚠️ Hubo un error al devolver la herramienta.');
        }
    });
});
</script>
</body>
</html>
