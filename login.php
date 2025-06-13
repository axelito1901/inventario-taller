<?php
session_start();
include 'includes/conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $contraseña = $_POST['contraseña'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = ? AND contraseña = ? AND tipo = 'gerente'");
    $stmt->bind_param("ss", $nombre, $contraseña);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $_SESSION['gerente'] = $nombre;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Gerente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-[var(--vw-gray)]">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-lg">
        <h2 class="text-3xl font-bold text-center mb-6 text-[var(--vw-blue)]">Inicio de Sesión</h2>

        <?php if ($error): ?>
            <div class="mb-4 p-3 text-sm text-white bg-red-500 rounded-lg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <input type="text" name="nombre" placeholder="Usuario" required
                class="w-full px-4 py-3 rounded-full bg-gray-100 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--vw-blue)]">

            <input type="password" name="contraseña" placeholder="Contraseña" required
                class="w-full px-4 py-3 rounded-full bg-gray-100 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--vw-blue)]">

            <button type="submit"
                class="w-full py-3 bg-[var(--vw-blue)] text-white font-semibold rounded-full hover:bg-blue-900 transition">
                Iniciar Sesión
            </button>
        </form>
    </div>
</body>
</html>
