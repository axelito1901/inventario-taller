<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $cantidad = intval($_POST['cantidad']);

    $imagen = null;
    if ($_FILES['imagen']['tmp_name']) {
        $imagen_nombre = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta = "imagenes/" . $imagen_nombre;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta);
        $imagen = $ruta;
    }

    $stmt = $conexion->prepare("INSERT INTO herramientas (codigo, nombre, imagen, ubicacion, cantidad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $codigo, $nombre, $imagen, $ubicacion, $cantidad);
    $stmt->execute();

    $mensaje = "Herramienta agregada correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar herramienta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen">
<main class="max-w-xl mx-auto p-6">
    <h2 class="text-3xl font-bold text-[var(--vw-blue)] mb-4">Agregar herramienta</h2>

    <?php if (isset($mensaje)): ?>
        <div class="p-4 bg-green-100 text-green-800 rounded mb-4 shadow"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5 bg-white p-6 rounded shadow">
        <div>
            <label class="block font-medium mb-1">Código</label>
            <input type="text" name="codigo" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Nombre</label>
            <input type="text" name="nombre" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Ubicación</label>
            <input type="text" name="ubicacion" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Cantidad</label>
            <input type="number" name="cantidad" min="0" required class="w-full px-4 py-2 border rounded">
        </div>

        <div>
            <label class="block font-medium mb-1">Imagen (opcional)</label>
            <input type="file" name="imagen" accept="image/*" class="block mt-1 text-sm text-gray-700">
        </div>

        <div class="flex justify-between items-center">
            <button type="submit" class="bg-[var(--vw-blue)] text-white px-4 py-2 rounded hover:bg-blue-900 transition">Guardar herramienta</button>
            <a href="listar_herramientas.php" class="text-sm text-[var(--vw-blue)] hover:underline">⬅ Volver a la lista</a>
        </div>
    </form>
</main>
</body>
</html>
