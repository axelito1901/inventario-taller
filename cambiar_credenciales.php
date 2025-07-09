<?php
session_start();
include 'includes/conexion.php';

if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

$nombreGerente = $_SESSION['gerente'];
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['clave_actual'] ?? '';
    $nueva = $_POST['nueva_clave'] ?? '';
    $confirmar = $_POST['confirmar_clave'] ?? '';

    if ($nueva !== $confirmar) {
        $mensaje = 'Las nuevas contraseñas no coinciden.';
        $tipo_mensaje = 'error';
    } elseif (strlen($nueva) < 4) {
        $mensaje = 'La nueva contraseña debe tener al menos 4 caracteres.';
        $tipo_mensaje = 'error';
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
            $mensaje = 'La contraseña actual es incorrecta.';
            $tipo_mensaje = 'error';
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
  <style>
    body {
      background: #f4f4f4;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    }
    .card {
      background: #fff;
      padding: 2.5rem 2rem 2rem 2rem;
      border-radius: 2rem;
      box-shadow: 0 6px 32px 0 rgba(0,0,0,0.10);
      max-width: 400px;
      width: 100%;
    }
    .card h1 {
      color: #00247D;
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 2rem;
      text-align: center;
    }
    .alert-success {
      background: #e6f9ed;
      color: #1a7f37;
      border-radius: 1rem;
      padding: 1rem;
      font-size: 1rem;
      margin-bottom: 1.5rem;
      border: 1px solid #b6e7c9;
      text-align: center;
    }
    .alert-error {
      background: #ffeaea;
      color: #c0392b;
      border-radius: 1rem;
      padding: 1rem;
      font-size: 1rem;
      margin-bottom: 1.5rem;
      border: 1px solid #f5b7b1;
      text-align: center;
    }
    .form-group {
      margin-bottom: 1.2rem;
    }
    label {
      display: block;
      font-size: 0.98rem;
      font-weight: 500;
      margin-bottom: 0.4rem;
      color: #222;
    }
    input[type="password"] {
      width: 100%;
      padding: 0.9rem 1.2rem;
      border-radius: 2rem;
      border: 1.5px solid #e0e0e0;
      background: #f7fafd;
      color: #222;
      font-size: 1rem;
      transition: border 0.2s, box-shadow 0.2s;
      box-shadow: 0 1px 4px 0 rgba(0,0,0,0.03);
    }
    input[type="password"]:focus {
      border: 1.5px solid #00247D;
      outline: none;
      background: #fff;
      box-shadow: 0 0 0 2px #b3c7f7;
    }
    button[type="submit"] {
      width: 100%;
      padding: 0.9rem 0;
      background: #00247D;
      color: #fff;
      font-weight: 700;
      font-size: 1.1rem;
      border: none;
      border-radius: 2rem;
      margin-top: 0.5rem;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
      box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
    }
    button[type="submit"]:hover {
      background: #001a5c;
      box-shadow: 0 4px 16px 0 rgba(0,0,0,0.08);
    }
    .volver {
      display: inline-block;
      margin-top: 2rem;
      background: #fff;
      color: #00247D;
      border: 1.5px solid #00247D;
      padding: 0.7rem 1.5rem;
      border-radius: 2rem;
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      transition: background 0.2s, color 0.2s, box-shadow 0.2s;
      box-shadow: 0 1px 4px 0 rgba(0,0,0,0.03);
      text-align: center;
    }
    .volver:hover {
      background: #00247D;
      color: #fff;
      box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Cambiar contraseña</h1>

    <?php if (!empty($mensaje)): ?>
      <div class="<?= ($tipo_mensaje ?? 'error') === 'error' ? 'alert-error' : 'alert-success' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="form-group">
        <label>Contraseña actual</label>
        <input type="password" name="clave_actual" required>
      </div>
      <div class="form-group">
        <label>Nueva contraseña</label>
        <input type="password" name="nueva_clave" required>
      </div>
      <div class="form-group">
        <label>Confirmar nueva contraseña</label>
        <input type="password" name="confirmar_clave" required>
      </div>
      <button type="submit">Actualizar contraseña</button>
    </form>
    <div style="text-align:center";>
    <a href="dashboard.php" class="volver">Volver al panel</a>
    </div>
  </div>
</body>
</html>