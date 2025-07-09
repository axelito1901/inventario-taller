<?php
session_start();
include 'includes/conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $contraseña = $_POST['contraseña'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = ? AND tipo = 'gerente'");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if ($contraseña === $usuario['contraseña']) {
            $_SESSION['gerente'] = $nombre;
            header("Location: dashboard.php");
            exit();
        }
    }

    $error = "Usuario o contraseña incorrectos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Gerente</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        body {
            background: var(--vw-gray);
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 2rem;
            box-shadow: 0 6px 32px 0 rgba(0,0,0,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
        }
        .login-card img {
            height: 80px;
            margin-bottom: 1.5rem;
        }
        .login-card h2 {
            color: var(--vw-blue);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 2rem;
        }
        .alert-success {
            background: #e6f9ed;
            color: #1a7f37;
            border-radius: 1rem;
            padding: 1rem;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #b6e7c9;
        }
        .alert-error {
            background: #ffeaea;
            color: #c0392b;
            border-radius: 1rem;
            padding: 1rem;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f5b7b1;
        }
        .login-card input[type="text"],
        .login-card input[type="password"] {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border-radius: 2rem;
            border: 1.5px solid #e0e0e0;
            background: #f7fafd;
            color: #222;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 4px 0 rgba(0,0,0,0.03);
        }
        .login-card input:focus {
            border: 1.5px solid var(--vw-blue);
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 2px #b3c7f7;
        }
        .login-card button[type="submit"] {
            width: 100%;
            padding: 0.9rem 0;
            background: var(--vw-blue);
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
        .login-card button[type="submit"]:hover {
            background: #001a5c;
            box-shadow: 0 4px 16px 0 rgba(0,0,0,0.08);
        }
        .login-card .volver {
            display: inline-block;
            margin-top: 2rem;
            background: #fff;
            color: var(--vw-blue);
            border: 1.5px solid var(--vw-blue);
            padding: 0.7rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 4px 0 rgba(0,0,0,0.03);
        }
        .login-card .volver:hover {
            background: var(--vw-blue);
            color: #fff;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card flex flex-col items-center">
            <img src="logo-volskwagen.png" alt="Logo de la empresa">
            <h2>Inicio de Sesión</h2>

            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clave_actualizada'): ?>
                <div class="alert-success">
                    Contraseña actualizada. Iniciá sesión nuevamente.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <input type="text" name="nombre" placeholder="Usuario" required>
                <input type="password" name="contraseña" placeholder="Contraseña" required>
                <button type="submit">Iniciar Sesión</button>
            </form>

            <a href="index.php" class="volver">Volver al inicio</a>
        </div>
    </div>
</body>
</html>