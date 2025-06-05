<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$where = "1"; // Por defecto no filtra nada

if ($buscar) {
    $where .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%')";
}

if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where
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
    <title>Listado de herramientas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <style>
        tbody {
            zoom: 90%;
        }
    </style>
</head>
<body>
<section class="section">
    <div class="container">
        <h2 class="title is-3">Herramientas registradas</h2>

        <div class="buttons">
            <a href="agregar_herramienta.php" class="button is-primary is-light">
                ➕ Agregar nueva herramienta
            </a>
            <a href="informe_stock.php" class="button is-success is-light">
                📋 Ver informe de stock
            </a>
            <a href="dashboard.php" class="button is-link is-light">
                🏠 Volver al panel
            </a>
        </div>

        <form method="get" class="mb-4">
            <div class="columns is-multiline">
                <div class="column is-4">
                    <input class="input" type="text" name="buscar" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($buscar) ?>">
                </div>
                <div class="column is-4">
                    <div class="select is-fullwidth">
                        <select name="filtro" onchange="this.form.submit()">
                            <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Mostrar todas</option>
                            <option value="stock" <?= $filtro === 'stock' ? 'selected' : '' ?>>Solo en stock</option>
                            <option value="sin_stock" <?= $filtro === 'sin_stock' ? 'selected' : '' ?>>Solo sin stock</option>
                        </select>
                    </div>
                </div>
                <div class="column is-2">
                    <button class="button is-info is-fullwidth" type="submit">Buscar</button>
                </div>
                <div class="column is-2">
                    <a href="listar_herramientas.php" class="button is-light is-fullwidth">🔄 Limpiar filtros</a>
                </div>
            </div>
        </form>

        <table class="table is-striped is-fullwidth">
            <thead>
                <tr>
                    <th class="has-text-centered">Imagen</th>
                    <th class="has-text-centered">Código</th>
                    <th class="has-text-centered">Nombre</th>
                    <th class="has-text-centered">Ubicación</th>
                    <th class="has-text-centered">Cantidad</th>
                    <th class="has-text-centered">Stock</th>
                    <th class="has-text-centered">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($herramienta = $herramientas->fetch_assoc()): ?>
                    <tr>
                        <td class="has-text-centered">
                            <?php if (!empty($herramienta['imagen']) && file_exists($herramienta['imagen'])): ?>
                                <img src="<?= $herramienta['imagen'] ?>" alt="<?= $herramienta['nombre'] ?>" width="100">
                            <?php else: ?>
                                <span class="has-text-grey-light">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td class="has-text-centered"><?= htmlspecialchars($herramienta['codigo']) ?></td>
                        <td class="has-text-centered"><?= htmlspecialchars($herramienta['nombre']) ?></td>
                        <td class="has-text-centered"><?= htmlspecialchars($herramienta['ubicacion']) ?></td>
                        <td class="has-text-centered"><?= intval($herramienta['cantidad']) ?></td>
                        <td class="has-text-centered">
                            <?php if ($herramienta['cantidad'] == 0): ?>
                                <span class="tag is-danger is-light">🔴 Sin stock</span>
                            <?php else: ?>
                                <span class="tag is-success is-light">🟢 En stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="has-text-centered">
                            <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                                <a href="editar_herramienta.php?id=<?= $herramienta['id'] ?>" class="button is-small is-warning">✏️ Editar</a>
                                <a href="eliminar_herramienta.php?id=<?= $herramienta['id'] ?>" class="button is-small is-danger" onclick="return confirm('¿Estás seguro de eliminar esta herramienta?')">🗑️ Eliminar</a>
                                <a href="historial_herramienta.php?id=<?= $herramienta['id'] ?>" class="button is-small is-info is-light">📜 Historial</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
</body>
</html>
