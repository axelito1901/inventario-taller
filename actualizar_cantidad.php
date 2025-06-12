<?php
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id']);

    if ($_POST['accion'] === 'sumar') {
        $stmt = $conexion->prepare("SELECT cantidad FROM herramientas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($cantidad_actual);
        $stmt->fetch();
        $stmt->close();

        $nueva = $cantidad_actual + 1;

        $stmt = $conexion->prepare("INSERT INTO historial_stock (herramienta_id, stock_anterior, stock_nuevo) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id, $cantidad_actual, $nueva);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva, $id);
        $stmt->execute();
        $stmt->close();

        echo $nueva;
        exit;
    }

    if ($_POST['accion'] === 'deshacer') {
        $stmt = $conexion->prepare("SELECT stock_anterior FROM historial_stock WHERE herramienta_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($anterior);
        $stmt->fetch();
        $stmt->close();

        if (isset($anterior)) {
            $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
            $stmt->bind_param("ii", $anterior, $id);
            $stmt->execute();
            $stmt->close();

            $conexion->query("DELETE FROM historial_stock WHERE herramienta_id = $id ORDER BY id DESC LIMIT 1");

            echo $anterior;
            exit;
        }
    }

    echo 'error';
    exit;
}

$resultado = $conexion->query("SELECT * FROM herramientas ORDER BY CAST(codigo AS UNSIGNED) ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Actualizar Cantidad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .modal.is-active {
            display: flex;
        }
        .resaltado {
            background-color: #d4edda !important;
            transition: background-color 0.5s ease;
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
            <tr id="fila-<?= $h['id'] ?>">
                <td><?= htmlspecialchars($h['nombre']) ?></td>
                <td><?= htmlspecialchars($h['codigo']) ?></td>
                <td><?= htmlspecialchars($h['ubicacion']) ?></td>
                <td>
                    <span id="cantidad-<?= $h['id'] ?>"><?= $h['cantidad'] ?></span>
                    <span id="check-<?= $h['id'] ?>" class="icon has-text-success" style="display:none;">
                        <i class="fas fa-check-circle"></i>
                    </span>
                </td>
                <td>
                    <?php if (!empty($h['imagen']) && file_exists($h['imagen'])): ?>
                        <img src="<?= $h['imagen'] ?>" style="width: 50px; cursor: zoom-in;" onclick="mostrarModal('<?= $h['imagen'] ?>')">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="button is-success" onclick="actualizarCantidad(<?= $h['id'] ?>, 'sumar')">+1</button>
                </td>
                <td>
                    <button class="button is-warning" onclick="actualizarCantidad(<?= $h['id'] ?>, 'deshacer')">Deshacer</button>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Modal -->
    <div class="modal" id="modalImagen">
        <div class="modal-background" onclick="cerrarModal()"></div>
        <div class="modal-content">
            <p class="image"><img id="imagenAmpliada" src=""></p>
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

        function actualizarCantidad(id, accion) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('accion', accion);

            fetch('actualizar_cantidad.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(nuevaCantidad => {
                const cantidadSpan = document.getElementById(`cantidad-${id}`);
                const checkIcon = document.getElementById(`check-${id}`);
                const fila = document.getElementById(`fila-${id}`);

                cantidadSpan.innerText = nuevaCantidad;
                checkIcon.style.display = "inline";
                fila.classList.add('resaltado');

                setTimeout(() => {
                    checkIcon.style.display = "none";
                    fila.classList.remove('resaltado');
                }, 1000);
            });
        }
    </script>
</body>
</html>
