<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID de herramienta no especificado.";
    exit();
}

$id = $_GET['id'];

$stmt = $conexion->prepare("SELECT * FROM herramientas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$herramienta = $resultado->fetch_assoc();

if (!$herramienta) {
    echo "Herramienta no encontrada.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $cantidad = intval($_POST['cantidad']);
    $imagen = $herramienta['imagen'];

    if ($_FILES['imagen']['tmp_name']) {
        $imagen_nombre = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta = "imagenes/" . $imagen_nombre;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta);
        $imagen = $ruta;
    }

    $update = $conexion->prepare("UPDATE herramientas SET codigo = ?, nombre = ?, ubicacion = ?, imagen = ?, cantidad = ? WHERE id = ?");
    $update->bind_param("ssssii", $codigo, $nombre, $ubicacion, $imagen, $cantidad, $id);
    $update->execute();

    $mensaje = "Herramienta actualizada correctamente.";

    $stmt = $conexion->prepare("SELECT * FROM herramientas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $herramienta = $resultado->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar herramienta</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
<main class="max-w-xl mx-auto p-6">
    <h2 class="text-3xl font-bold text-[var(--vw-blue)] mb-4">Editar herramienta</h2>

    <?php if (isset($mensaje)): ?>
        <div class="p-4 bg-green-100 text-green-800 rounded mb-4 shadow"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5 bg-white p-6 rounded shadow">
        <div>
            <label class="block font-medium mb-1">Código</label>
            <input type="text" name="codigo" value="<?= htmlspecialchars($herramienta['codigo']) ?>" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Nombre</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($herramienta['nombre']) ?>" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Ubicación</label>
            <input type="text" name="ubicacion" value="<?= htmlspecialchars($herramienta['ubicacion']) ?>" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Cantidad</label>
            <input type="number" name="cantidad" value="<?= intval($herramienta['cantidad']) ?>" min="0" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <?php if ($herramienta['cantidad'] == 0): ?>
                <span class="inline-block bg-red-100 text-red-700 text-sm px-3 py-1 rounded-full">🔴 Sin stock</span>
            <?php else: ?>
                <span class="inline-block bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full">🟢 En stock</span>
            <?php endif; ?>
        </div>

        <div>
            <label class="block font-medium mb-1">Imagen actual</label>
            <?php if ($herramienta['imagen']): ?>
                <img src="<?= htmlspecialchars($herramienta['imagen']) ?>" alt="Imagen actual" class="h-32 object-contain mb-2 border rounded">
            <?php else: ?>
                <p class="text-sm text-gray-500 italic">No hay imagen.</p>
            <?php endif; ?>

            <label class="block mt-2">
                <input type="file" name="imagen" accept="image/*" class="block text-sm text-gray-700 mt-1">
            </label>
        </div>

        <div class="flex justify-between items-center">
            <button type="submit" class="bg-[var(--vw-blue)] text-white px-4 py-2 rounded hover:bg-blue-900 transition">Guardar cambios</button>
            <a href="listar_herramientas.php" class="text-sm text-[var(--vw-blue)] hover:underline">⬅ Volver a la lista</a>
        </div>
    </form>
</main>
</body>
</html>
