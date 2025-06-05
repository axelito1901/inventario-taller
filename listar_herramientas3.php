<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

// Filtro de búsqueda (si lo tenés)
$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$where = "1";
if ($buscar) {
    $where .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%')";
}

// Ordenar por código numérico, los no numéricos al final
$sql = "SELECT * FROM herramientas 
        WHERE $where 
        ORDER BY 
            CASE 
                WHEN codigo REGEXP '^[0-9]+$' THEN 0 
                ELSE 1 
            END, 
            CAST(codigo AS UNSIGNED)";

$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Herramientas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title is-3">📋 Herramientas registradas</h1>
        <a href="dashboard.php" class="button is-light mb-4">⬅ Volver al panel</a>

        <table class="table is-striped is-fullwidth">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Cantidad</th>
                    <th>Imagen</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-herramientas">
                <?php while ($h = $herramientas->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['codigo'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($h['nombre']) ?></td>
                        <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                        <td><?= intval($h['cantidad']) ?></td>
                        <td>
                            <?php if ($h['imagen']): ?>
                                <img src="<?= htmlspecialchars($h['imagen']) ?>" alt="Imagen" width="60">
                            <?php else: ?>
                                <span class="has-text-grey">sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="editar_herramienta.php?id=<?= $h['id'] ?>" class="button is-small is-info">✏️ Editar</a>
                            <a href="eliminar_herramienta.php?id=<?= $h['id'] ?>" class="button is-small is-danger" onclick="return confirm('¿Seguro que querés eliminar esta herramienta?')">🗑️ Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
// WebSocket para actualizar en tiempo real
const socket = io("http://localhost:3000");

socket.on("herramientas", mensaje => {
    console.log("🔁 Herramientas actualizadas:", mensaje);
    location.reload(); // o podés usar fetch para actualizar solo #tabla-herramientas
});
</script>
</body>
</html>
