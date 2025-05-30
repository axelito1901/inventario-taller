<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$buscar = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$where = "1";

if ($buscar) {
    $where .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%')";
}

if ($filtro === 'stock') {
    $where .= " AND cantidad > 0";
} elseif ($filtro === 'sin_stock') {
    $where .= " AND cantidad = 0";
}

$sql = "SELECT * FROM herramientas WHERE $where";
$herramientas = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listar herramientas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <h1 class="title is-3">🔧 Listado de Herramientas</h1>

        <div class="table-container">
            <table class="table is-fullwidth is-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Cantidad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($h = $herramientas->fetch_assoc()): ?>
                        <tr id="fila-<?= $h['id'] ?>">
                            <td><?= $h['id'] ?></td>
                            <td><?= htmlspecialchars($h['codigo']) ?></td>
                            <td><?= htmlspecialchars($h['nombre']) ?></td>
                            <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                            <td class="cantidad"><?= $h['cantidad'] ?></td>
                            <td>
                                <button class="button is-success is-small aumentar-stock" data-id="<?= $h['id'] ?>">
                                    Aumentar stock
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="dashboard.php" class="button is-link">Volver al panel</a>
    </div>
</section>

<script>
document.querySelectorAll('.aumentar-stock').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.dataset.id;
        const fila = document.querySelector('#fila-' + id);
        const cantidadCelda = fila.querySelector('.cantidad');
        const boton = this;

        fetch('aumentar_stock.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(id)
        })
        .then(response => response.text())
        .then(data => {
            if (data === 'ok') {
                cantidadCelda.textContent = '1';
                boton.disabled = true;
                boton.classList.remove('is-success');
                boton.classList.add('is-info');
                boton.textContent = '✔ Listo';
            }
        });
    });
});
</script>
</body>
</html>
