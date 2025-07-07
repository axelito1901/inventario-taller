<?php
session_start();
include 'includes/conexion.php';

if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

$nombreGerente = $_SESSION['gerente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['clave_actual'] ?? '';
    $nueva = $_POST['nueva_clave'] ?? '';
    $confirmar = $_POST['confirmar_clave'] ?? '';

    if ($nueva !== $confirmar) {
        $mensaje = '❌ Las nuevas contraseñas no coinciden.';
    } elseif (strlen($nueva) < 4) {
        $mensaje = '❌ La nueva contraseña debe tener al menos 4 caracteres.';
    } else {
        $stmt = $conexion->prepare("SELECT contraseña FROM usuarios WHERE nombre = ? AND tipo = 'gerente'");
        $stmt->bind_param("s", $nombreGerente);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();

        if ($resultado && $actual === $resultado['contraseña']) {
            $stmt = $conexion->prepare("UPDATE usuarios SET contraseña = ? WHERE nombre = ? AND tipo = 'gerente'");
            $stmt->bind_param("ss", $nueva, $nombreGerente);
            $stmt->execute();

            session_unset();
            session_destroy();
            header("Location: login.php?mensaje=clave_actualizada");
            exit();
        } else {
            $mensaje = '❌ La contraseña actual es incorrecta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cambiar contraseña</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center p-6">
  <div class="bg-white p-8 rounded shadow max-w-md w-full">
    <h1 class="text-2xl font-bold text-blue-800 mb-6">🔒 Cambiar contraseña</h1>

    <?php if (!empty($mensaje)): ?>
      <div class="mb-4 px-4 py-2 rounded border <?= str_starts_with($mensaje, '✅') ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Contraseña actual</label>
        <input type="password" name="clave_actual" class="w-full border px-3 py-2 rounded" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nueva contraseña</label>
        <input type="password" name="nueva_clave" class="w-full border px-3 py-2 rounded" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Confirmar nueva contraseña</label>
        <input type="password" name="confirmar_clave" class="w-full border px-3 py-2 rounded" required>
      </div>
      <div>
        <button type="submit" class="w-full bg-blue-700 text-white py-2 rounded hover:bg-blue-800 transition">Actualizar contraseña</button>
      </div>
      <div class="text-center mt-4">
        <a href="dashboard.php" class="text-blue-700 text-sm hover:underline">⬅ Volver al panel</a>
      </div>
    </form>
  </div>
</body>
</html>
