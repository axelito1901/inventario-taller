<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

$directorio = "informes/";
$archivos = [];
$filtrados = [];

$fechaSeleccionada = $_GET['fecha'] ?? '';
$mesSeleccionado = $_GET['mes'] ?? '';
$tipoSeleccionado = $_GET['tipo'] ?? 'todos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $archivo = basename($_POST['eliminar']);
    $ruta = $directorio . $archivo;

    if (file_exists($ruta)) {
        unlink($ruta);
        $mensaje = "El archivo '$archivo' fue eliminado correctamente.";
    } else {
        $error = "No se encontró el archivo.";
    }
}

if (is_dir($directorio)) {
    $archivos = array_diff(scandir($directorio), ['.', '..']);
    rsort($archivos);

    foreach ($archivos as $archivo) {
        if (!str_ends_with($archivo, '.xls')) continue;

        $coincideTipo =
            $tipoSeleccionado === 'todos' ||
            ($tipoSeleccionado === 'stock' && str_contains($archivo, 'stock')) ||
            ($tipoSeleccionado === 'prestamo' && !str_contains($archivo, 'stock'));

        $coincideFecha = $fechaSeleccionada && str_contains($archivo, $fechaSeleccionada);
        $coincideMes = $mesSeleccionado && str_starts_with($archivo, 'informe_' . $mesSeleccionado);

        if ($coincideTipo && (
            ($fechaSeleccionada && $coincideFecha) ||
            ($mesSeleccionado && $coincideMes) ||
            (!$fechaSeleccionada && !$mesSeleccionado)
        )) {
            $filtrados[] = $archivo;
        }

        if ($tipoSeleccionado === 'todos' && !$fechaSeleccionada && !$mesSeleccionado) {
            $filtrados[] = $archivo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de informes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <script>
        function confirmar(nombreArchivo) {
            return confirm("¿Querés eliminar el archivo '" + nombreArchivo + "'?");
        }
    </script>
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title is-3">Historial de informes generados</h1>

        <div class="buttons mb-4">
            <a href="dashboard.php" class="button is-light">⬅ Volver al panel</a>
        </div>

        <form method="GET" class="box">
            <div class="columns is-multiline">
                <div class="column is-3">
                    <label class="label">Filtrar por fecha exacta</label>
                    <input type="date" name="fecha" class="input" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
                </div>
                <div class="column is-3">
                    <label class="label">Filtrar por tipo</label>
                    <div class="select is-fullwidth">
                        <select name="tipo" onchange="this.form.submit()">
                            <option value="todos" <?= $tipoSeleccionado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="prestamo" <?= $tipoSeleccionado === 'prestamo' ? 'selected' : '' ?>>Préstamos</option>
                            <option value="stock" <?= $tipoSeleccionado === 'stock' ? 'selected' : '' ?>>Stock</option>
                        </select>
                    </div>
                </div>
                <div class="column is-3 is-flex is-align-items-end">
                    <button type="submit" class="button is-link">🔎 Filtrar</button>
                </div>
                <div class="column is-3 is-flex is-align-items-end">
                    <a href="historial_informes.php" class="button is-light">🔄 Limpiar filtros</a>
                </div>
            </div>
        </form>

        <?php if (isset($mensaje)): ?>
            <div class="notification is-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="notification is-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (count($filtrados) > 0): ?>
            <table class="table is-fullwidth is-striped">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtrados as $archivo): ?>
                        <tr>
                            <td><?= htmlspecialchars($archivo) ?></td>
                            <td class="is-flex">
                                <a href="<?= $directorio . $archivo ?>" class="button is-link is-small mr-2" download>📥 Descargar</a>
                                <form method="POST" onsubmit="return confirmar('<?= $archivo ?>')">
                                    <input type="hidden" name="eliminar" value="<?= htmlspecialchars($archivo) ?>">
                                    <button type="submit" class="button is-danger is-small">🗑 Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="notification is-info">No se encontraron informes con esos filtros.</div>
        <?php endif; ?>
    </div>
</section>
</body>
</html>
