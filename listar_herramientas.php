<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

// Búsqueda
$buscar = $_GET['buscar'] ?? '';
$buscar = $conexion->real_escape_string($buscar);

if ($buscar) {
    $sql = "SELECT * FROM herramientas WHERE nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%'";
} else {
    $sql = "SELECT * FROM herramientas";
}
$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de herramientas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <style>
        .imagen-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">📋 Herramientas registradas</h1>

    <form method="GET" class="mb-4">
        <div class="field has-addons">
            <div class="control is-expanded">
                <input class="input" type="text" name="buscar" placeholder="Buscar por nombre o código" value="<?= htmlspecialchars($buscar) ?>">
            </div>
            <div class="control">
                <button class="button is-link">Buscar</button>
            </div>
        </div>
    </form>

    <div class="buttons">
        <a href="agregar_herramienta.php" class="button is-primary is-light">➕ Nueva herramienta</a>
        <a href="dashboard.php" class="button is-light">⬅ Volver al panel</a>
    </div>

    <?php if ($herramientas->num_rows > 0): ?>
        <table class="table is-fullwidth is-striped">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $herramientas->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($h['imagen'] && file_exists($h['imagen'])): ?>
                                <img src="<?= $h['imagen'] ?>" class="imagen-preview">
                            <?php else: ?>
                                <span class="tag is-light">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($h['codigo']) ?></td>
                        <td><?= htmlspecialchars($h['nombre']) ?></td>
                        <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                        <td>
                            <a href="editar_herramienta.php?id=<?= $h['id'] ?>" class="button is-small is-warning">✏️ Editar</a>
                            <a href="eliminar_herramienta.php?id=<?= $h['id'] ?>" class="button is-small is-danger" onclick="return confirm('¿Seguro que querés eliminar esta herramienta?')">🗑 Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notification is-info">No se encontraron herramientas.</div>
    <?php endif; ?>
</div>
</section>
</body>
</html>
