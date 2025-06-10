<?php
include 'includes/conexion.php';

if (isset($_POST['sumar'])) {
    $id = $_POST['id'];

    $stmt = $conexion->prepare("SELECT cantidad FROM herramientas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($cantidad_actual);
    $stmt->fetch();
    $stmt->close();

    $cantidad_nueva = $cantidad_actual + 1;

    $stmt = $conexion->prepare("INSERT INTO historial_stock (herramienta_id, stock_anterior, stock_nuevo) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $id, $cantidad_actual, $cantidad_nueva);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
    $stmt->bind_param("ii", $cantidad_nueva, $id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['deshacer'])) {
    $id = $_POST['id'];

    $stmt = $conexion->prepare("SELECT stock_anterior FROM historial_stock WHERE herramienta_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($cantidad_anterior);
    $stmt->fetch();
    $stmt->close();

    if (isset($cantidad_anterior)) {
        $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $cantidad_anterior, $id);
        $stmt->execute();
        $stmt->close();

        $conexion->query("DELETE FROM historial_stock WHERE herramienta_id = $id ORDER BY id DESC LIMIT 1");
    }
}

$resultado = $conexion->query("SELECT * FROM herramientas ORDER BY CAST(codigo AS UNSIGNED) ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Actualizar Cantidad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <style>
        .modal.is-active {
            display: flex;
        }
    </style>
</head>
<body class="section">
    <h1 class="title">Actualizar Cantidad (Stock)</h1>
    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Ubicación</th>
                <th>Cantidad</th>
                <th>Imagen</th>
                <th>+1</th>
                <th>Deshacer</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($h = $resultado->fetch_assoc()) { ?>
            <tr>
                <form method="post">
                    <td><?= htmlspecialchars($h['nombre']) ?></td>
                    <td><?= htmlspecialchars($h['codigo']) ?></td>
                    <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                    <td><?= $h['cantidad'] ?></td>
                    <td>
                        <?php if (!empty($h['imagen']) && file_exists($h['imagen'])): ?>
                            <img src="<?= $h['imagen'] ?>" style="width: 50px; cursor: zoom-in;" onclick="mostrarModal('<?= $h['imagen'] ?>')">
                        <?php else: ?>
                            <span>Sin imagen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
                        <button class="button is-success" name="sumar">+1</button>
                    </td>
                    <td>
                        <button class="button is-warning" name="deshacer">Deshacer</button>
                    </td>
                </form>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Modal para imagen -->
    <div class="modal" id="modalImagen">
        <div class="modal-background" onclick="cerrarModal()"></div>
        <div class="modal-content">
            <p class="image">
                <img id="imagenAmpliada" src="">
            </p>
        </div>
        <button class="modal-close is-large" aria-label="close" onclick="cerrarModal()"></button>
    </div>

    <script>
        function mostrarModal(src) {
            document.getElementById("imagenAmpliada").src = src;
            document.getElementById("modalImagen").classList.add("is-active");
        }

        function cerrarModal() {
            document.getElementById("modalImagen").classList.remove("is-active");
        }
    </script>
</body>
</html>
