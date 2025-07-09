<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}
include 'includes/conexion.php';

// Sucursal/tabla
$sucursal = $_GET['sucursal'] ?? 'Lanús';
$tabla = ($sucursal === 'Lanús') ? 'mecanicos' : 'nombres_personalizados';

$nombreAConfirmar = $_GET['confirmar'] ?? null;

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

// -- Agregar nombre --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_nombre'])) {
    $nuevo_nombre = trim($_POST['nuevo_nombre']);
    if ($nuevo_nombre !== '') {
        $stmt = $conexion->prepare("INSERT IGNORE INTO $tabla (nombre) VALUES (?)");
        $stmt->bind_param("s", $nuevo_nombre);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Nombre agregado correctamente.";
        $tipoMsg = "ok";
    }
    // PRG
    $params = $_GET;
    $params['msg'] = $mensaje;
    $params['msgtype'] = $tipoMsg;
    header("Location: gestion_nombres.php?" . http_build_query($params));
    exit();
}

// -- Editar nombre --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'], $_POST['editar_nombre'])) {
    $editar_id = intval($_POST['editar_id']);
    $editar_nombre = trim($_POST['editar_nombre']);
    if ($editar_id && $editar_nombre !== '') {
        $stmt = $conexion->prepare("UPDATE $tabla SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $editar_nombre, $editar_id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Nombre editado correctamente.";
        $tipoMsg = "ok";
    }
    // PRG
    $params = $_GET;
    $params['msg'] = $mensaje;
    $params['msgtype'] = $tipoMsg;
    header("Location: gestion_nombres.php?" . http_build_query($params));
    exit();
}

// -- Eliminar nombre --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $eliminar_id = intval($_POST['eliminar_id']);
    $eliminar_nombre = trim($_POST['eliminar_nombre']);
    $puedeBorrar = true;

    // Chequear préstamos
    if ($sucursal === 'Lanús') {
        $stmt1 = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE mecanico_id = ?");
        $stmt1->bind_param("i", $eliminar_id);
    } else {
        $stmt1 = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE nombre_personalizado = ?");
        $stmt1->bind_param("s", $eliminar_nombre);
    }
    $stmt1->execute();
    $stmt1->bind_result($cant_uso);
    $stmt1->fetch(); $stmt1->close();

    if ($cant_uso > 0) $puedeBorrar = false;

    if ($puedeBorrar) {
        $stmt = $conexion->prepare("DELETE FROM $tabla WHERE id = ?");
        $stmt->bind_param("i", $eliminar_id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Nombre eliminado correctamente.";
        $tipoMsg = "ok";
    } else {
        $mensaje = "No se puede eliminar porque el nombre ya fue usado en algún préstamo.";
        $tipoMsg = "error";
    }
    // PRG
    $params = $_GET;
    $params['msg'] = $mensaje;
    $params['msgtype'] = $tipoMsg;
    unset($params['confirmar']);
    header("Location: gestion_nombres.php?" . http_build_query($params));
    exit();
}

// --- Traer nombres
$nombres = $conexion->query("SELECT * FROM $tabla ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar nombres</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        .fade-pop {animation: fadePop .2s cubic-bezier(.6,0,.4,1);}
        @keyframes fadePop {0%{opacity:0;transform:scale(.95);}100%{opacity:1;transform:scale(1);}}
        .notificacion-flotante {
            position: fixed;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            background: #18181b;
            color: #fff;
            padding: 1.1em 2.2em;
            border-radius: 1em;
            font-size: 1.08em;
            font-weight: 600;
            box-shadow: 0 6px 32px #0005;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.8em;
            min-width: 320px;
            max-width: 90vw;
            text-align: center;
            animation: fadein 0.4s;
        }
        .notificacion-flotante .fa-check-circle { color: #22c55e; }
        .notificacion-flotante .fa-xmark { color: #f87171; }
        @keyframes fadein {
            from { opacity: 0; top: 0; }
            to { opacity: 1; top: 32px; }
        }
        @media (max-width: 500px) {
            .notificacion-flotante { font-size: 0.98em; min-width: 0; padding: 0.7em 1em; }
        }
        /* Modal confirmación */
        .modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-confirm {
            background: #fff;
            border-radius: 1.2em;
            padding: 2.5em 2em 2em 2em;
            max-width: 350px;
            width: 100%;
            box-shadow: 0 8px 32px #0005;
            text-align: center;
            position: relative;
        }
        .modal-confirm h2 {
            color: #b91c1c;
            font-size: 1.25em;
            font-weight: bold;
            margin-bottom: 0.7em;
            display: flex;
            align-items: center;
            gap: 0.5em;
            justify-content: center;
        }
        .modal-confirm p {
            color: #222;
            margin-bottom: 1.5em;
        }
        .modal-confirm .btns {
            display: flex;
            flex-direction: column;
            gap: 0.7em;
        }
        .modal-confirm button,
        .modal-confirm a {
            font-weight: bold;
            border: none;
            border-radius: 0.7em;
            padding: 0.7em 0;
            width: 100%;
            font-size: 1em;
            cursor: pointer;
            box-shadow: 0 2px 8px #0001;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
            display: block;
        }
        .modal-confirm button {
            background: #dc2626;
            color: #fff;
        }
        .modal-confirm button:hover {
            background: #b91c1c;
        }
        .modal-confirm a {
            background: #f3f4f6;
            color: #222;
        }
        .modal-confirm a:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">

<?php if ($mensaje): ?>
    <div class="notificacion-flotante">
        <?php if ($tipoMsg === "ok"): ?>
            <i class="fa-solid fa-check-circle"></i>
        <?php else: ?>
            <i class="fa-solid fa-xmark"></i>
        <?php endif; ?>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<?php if ($nombreAConfirmar): ?>
    <div class="modal-bg">
        <div class="modal-confirm">
            <h2><i class="fa-solid fa-triangle-exclamation"></i> Confirmar eliminación</h2>
            <p>¿Seguro que querés eliminar este nombre?<br>
                <span class="font-semibold text-[var(--vw-blue)]">
                    <?php
                    $id = intval($nombreAConfirmar);
                    $res = $conexion->query("SELECT nombre FROM $tabla WHERE id = $id");
                    echo $res && $res->num_rows > 0 ? htmlspecialchars($res->fetch_assoc()['nombre']) : 'ID ' . $id;
                    ?>
                </span>?
            </p>
            <form method="POST" class="btns">
                <input type="hidden" name="eliminar_id" value="<?= htmlspecialchars($nombreAConfirmar) ?>">
                <input type="hidden" name="eliminar_nombre" value="<?php
                    $id = intval($nombreAConfirmar);
                    $res = $conexion->query("SELECT nombre FROM $tabla WHERE id = $id");
                    echo $res && $res->num_rows > 0 ? htmlspecialchars($res->fetch_assoc()['nombre']) : '';
                ?>">
                <button type="submit">Sí, eliminar</button>
                <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') . (count($_GET) > 1 ? '?' . http_build_query(array_diff_key($_GET, ['confirmar' => '', 'msg' => '', 'msgtype' => ''])) : '') ?>">Cancelar</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-6">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Gestionar nombres</h1>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-gray-200 mb-8">
        <!-- Sucursal -->
        <form method="GET" class="flex flex-col sm:flex-row gap-2 items-start mb-3">
            <label class="font-semibold text-[var(--vw-blue)] mt-2">Sucursal:</label>
            <select name="sucursal" onchange="this.form.submit()" class="border-2 border-gray-200 rounded-lg px-4 py-2 w-full sm:w-auto focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none appearance-none">
                <option value="Lanús" <?= $sucursal === 'Lanús' ? 'selected' : '' ?>>Lanús</option>
                <option value="Osvaldo Cruz" <?= $sucursal === 'Osvaldo Cruz' ? 'selected' : '' ?>>Osvaldo Cruz</option>
            </select>
        </form>

        <!-- Agregar nombre -->
        <form method="POST" class="flex gap-2 mb-4 items-center">
            <input type="text" name="nuevo_nombre" autocomplete="off" placeholder="Nuevo nombre..." class="flex-1 border-2 border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none">
            <button type="submit" class="bg-[var(--vw-blue)] hover:bg-blue-900 text-white font-bold rounded-lg px-6 py-2 flex items-center gap-2 transition shadow">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
        </form>

        <!-- Tabla de nombres -->
        <div class="rounded-lg overflow-hidden border border-gray-200">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="bg-[var(--vw-blue)] text-white font-semibold px-4 py-3 text-left">Nombre</th>
                        <th class="bg-[var(--vw-blue)] text-white font-semibold px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="tbodyNombres">
                <?php while ($row = $nombres->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-blue-50 transition" data-id="<?= $row['id'] ?>">
                        <td class="px-4 py-3 nombreTxt"><?= htmlspecialchars($row['nombre']) ?></td>
                        <td class="px-4 py-3 flex gap-2 accionesTd">
                            <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1 font-semibold text-sm flex items-center gap-1 btnEditar">
                                <i class="fa-solid fa-pen"></i> Editar
                            </button>
                            <a href="?confirmar=<?= $row['id'] ?>&<?= http_build_query($_GET) ?>" class="bg-red-600 hover:bg-red-700 text-white rounded px-3 py-1 font-semibold text-sm flex items-center gap-1">
                                <i class="fa-solid fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a href="dashboard.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition mt-6">
        <i class="fa-solid fa-arrow-left"></i> Volver al panel
    </a>
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
let editando = null;
document.querySelectorAll('.btnEditar').forEach(btn => {
    btn.addEventListener('click', function() {
        if(editando) return; // Solo uno a la vez
        const tr = this.closest('tr');
        const nombreActual = tr.querySelector('.nombreTxt').innerText.trim();
        const id = tr.dataset.id;
        const tdNombre = tr.querySelector('.nombreTxt');
        const tdAcc = tr.querySelector('.accionesTd');

        // Reemplazar nombre por input
        tdNombre.innerHTML = `<form method="POST" class="flex gap-2 items-center m-0">
            <input type="hidden" name="editar_id" value="${id}">
            <input type="text" name="editar_nombre" value="${nombreActual}" class="border border-gray-300 rounded-lg px-2 py-1 w-32" required>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1 font-semibold text-sm flex items-center gap-1"><i class="fa-solid fa-check"></i> Guardar</button>
            <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-700 rounded px-2 py-1 font-semibold text-sm ml-2 btnCancelarEdicion"><i class="fa-solid fa-times"></i></button>
        </form>`;
        // Ocultar acciones normales
        tdAcc.style.display = 'none';
        editando = tr;

        // Cancelar edición
        tr.querySelector('.btnCancelarEdicion').onclick = function() {
            tdNombre.textContent = nombreActual;
            tdAcc.style.display = 'flex';
            editando = null;
        };
    });
});

// Notificación flotante auto-oculta
window.onload = function() {
    var notif = document.querySelector('.notificacion-flotante');
    if (notif) {
        setTimeout(function() {
            notif.style.display = 'none';
        }, 3000);
    }
}
</script>
</body>
</html>