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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .resaltado {
            background-color: #bbf7d0 !important;
            transition: background-color 0.7s;
        }
        .modal-imagen { display: none; }
        .modal-imagen.active { display: flex; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<div class="max-w-6xl w-full mx-auto my-10 bg-white p-8 rounded-2xl shadow-lg">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-blue-900 flex items-center gap-3">➕ Actualizar Stock de Herramientas</h1>
        <a href="dashboard.php" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded shadow transition">⬅ Volver al panel</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ($h = $resultado->fetch_assoc()) { ?>
        <div id="fila-<?= $h['id'] ?>" class="bg-blue-50 border border-blue-200 p-6 rounded-xl shadow-sm flex flex-col items-center group relative transition">
            <div class="w-full flex flex-col items-center mb-2">
                <?php if (!empty($h['imagen']) && file_exists($h['imagen'])): ?>
                    <img src="<?= $h['imagen'] ?>" class="w-24 h-24 object-contain rounded-lg border mb-3 shadow cursor-pointer hover:scale-105 transition"
                        onclick="mostrarModal('<?= $h['imagen'] ?>')" alt="Imagen de <?= htmlspecialchars($h['nombre']) ?>">
                <?php else: ?>
                    <div class="w-24 h-24 bg-gray-200 text-gray-400 flex items-center justify-center rounded mb-3">Sin imagen</div>
                <?php endif; ?>
                <div class="font-bold text-lg text-blue-900 mb-1"><?= htmlspecialchars($h['nombre']) ?></div>
                <div class="text-xs text-gray-500 mb-2">(<?= htmlspecialchars($h['codigo']) ?>) - <?= htmlspecialchars($h['ubicacion']) ?></div>
            </div>
            <div class="mb-4 flex items-center gap-2">
                <span class="text-gray-600 text-sm">Cantidad actual:</span>
                <span id="cantidad-<?= $h['id'] ?>" class="font-bold text-xl"><?= $h['cantidad'] ?></span>
                <span id="check-<?= $h['id'] ?>" class="ml-1 hidden text-green-600 text-2xl"><i class="fa-solid fa-check-circle"></i></span>
            </div>
            <div class="flex gap-2 w-full justify-center">
                <button class="px-5 py-2 bg-green-500 text-white font-semibold rounded-lg shadow hover:bg-green-600 transition"
                    onclick="actualizarCantidad(<?= $h['id'] ?>, 'sumar')">
                    <i class="fa-solid fa-plus"></i> Sumar
                </button>
                <button class="px-5 py-2 bg-yellow-400 text-gray-900 font-semibold rounded-lg shadow hover:bg-yellow-500 transition"
                    onclick="actualizarCantidad(<?= $h['id'] ?>, 'deshacer')">
                    <i class="fa-solid fa-undo"></i> Deshacer
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<!-- MODAL IMAGEN -->
<div id="modalImagen" class="modal-imagen fixed inset-0 bg-black bg-opacity-70 items-center justify-center z-50 transition-all" onclick="cerrarModal()">
    <img id="imagenAmpliada" src="" class="max-h-[70vh] max-w-[90vw] m-auto rounded-lg border-4 border-white shadow-xl">
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script>
function mostrarModal(src) {
    document.getElementById("imagenAmpliada").src = src;
    document.getElementById("modalImagen").classList.add("active");
}
function cerrarModal() {
    document.getElementById("modalImagen").classList.remove("active");
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
        checkIcon.classList.remove('hidden');
        fila.classList.add('resaltado');

        setTimeout(() => {
            checkIcon.classList.add('hidden');
            fila.classList.remove('resaltado');
        }, 1200);
    });
}
</script>
</body>
</html>
