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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" />
</head>
<body>
<section class="section">
    <div class="container" style="max-width: 400px;">
        <h2 class="title has-text-centered">Login Gerente</h2>

        <?php if ($error): ?>
            <div class="notification is-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
                <label class="label">Usuario</label>
                <div class="control">
                    <input class="input" type="text" name="nombre" placeholder="Usuario" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Contraseña</label>
                <div class="control">
                    <input class="input" type="password" name="contraseña" placeholder="Contraseña" required>
                </div>
            </div>

            <div class="field">
                <div class="control">
                    <button class="button is-link is-fullwidth" type="submit">Ingresar</button>
                </div>
            </div>
        </form>
    </div>
</section>
</body>
</html>
