<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$where = "1"; // No filtra nada al principio

if ($buscar) {
    $where .= " AND (codigo LIKE '%$buscar%' OR nombre LIKE '%$buscar%')";

    $orderBy = "ORDER BY 
        CASE 
            WHEN codigo = '' OR codigo IS NULL THEN 6
            WHEN codigo LIKE '$buscar%' THEN 1 
            WHEN codigo LIKE '%$buscar%' THEN 2 
            WHEN nombre LIKE '$buscar%' THEN 3 
            WHEN nombre LIKE '%$buscar%' THEN 4 
            ELSE 5 
        END,
        codigo + 0";
} else {
    $orderBy = "ORDER BY 
        CASE
            WHEN codigo = '' OR codigo IS NULL THEN 2
            ELSE 1
        END,
    codigo + 0";
}

if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where $orderBy";
$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Stock</title>
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
        <h2 class="title is-3">📦 Informe de Stock de Herramientas</h2>

        <div class="buttons">
            <a href="dashboard.php" class="button is-link is-light">🏠 Volver al panel</a>
            <a href="informe_stock_excel.php" class="button is-success is-light">📥 Exportar a Excel</a>
        </div>

        <form method="get" class="mb-4">
            <div class="columns is-multiline">
                <div class="column is-4">
                    <input class="input" type="text" name="buscar" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($buscar) ?>">
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
                    <button class="button is-info is-fullwidth" type="submit">🔎 Buscar</button>
                </div>
                <div class="column is-2">
                    <a href="informe_stock.php" class="button is-light is-fullwidth">🔄 Limpiar</a>
                </div>
            </div>
        </form>

        <table class="table is-striped is-fullwidth">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Cantidad</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $herramientas->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['codigo']) ?></td>
                        <td><?= htmlspecialchars($h['nombre']) ?></td>
                        <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                        <td><?= intval($h['cantidad']) ?></td>
                        <td>
                            <?php if ($h['cantidad'] == 0): ?>
                                <span class="tag is-danger is-light">🔴 Sin stock</span>
                            <?php else: ?>
                                <span class="tag is-success is-light">🟢 En stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
</body>
</html>
