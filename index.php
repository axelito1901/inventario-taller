<?php
session_start();
include 'includes/conexion.php';

$prestadasQuery = $conexion->query("
    SELECT h.nombre AS herramienta, h.codigo, h.ubicacion,
           p.fecha_hora, p.sucursal, m.nombre AS mecanico, p.nombre_personalizado
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora DESC
");

$prestadas = [];
while ($row = $prestadasQuery->fetch_assoc()) {
    $prestadas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Préstamo de herramienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #resultados, #resultados_nombres {
            max-height: 150px;
            overflow-y: auto;
            cursor: pointer;
        }
        #resultados div:hover, #resultados_nombres div:hover {
            background-color: #f5f5f5;
        }
        .herramienta-tag {
            margin: 3px;
            display: inline-block;
        }
    </style>
</head>
<body>
<section class="section">
<div class="container mb-4 is-flex is-justify-content-flex-end">
    <a href="login.php" class="button is-info is-light">🔐 Iniciar sesión</a>
</div>

<div class="container">
    <h1 class="title">Préstamo de herramientas</h1>

    <div id="mensaje-error" class="notification is-danger is-hidden"></div>

    <form action="registrar_prestamo.php" method="post" class="box" autocomplete="off" onsubmit="return validarFormulario()">
        <div class="field">
            <label class="label">Sucursal</label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="sucursal" id="sucursal" required>
                        <option value="Lanús">Lanús</option>
                        <option value="Osvaldo Cruz">Osvaldo Cruz</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="field">
            <label class="label">Nombre de quien retira</label>
            <div class="control">
                <input class="input" type="text" id="nombre_personalizado" name="nombre_personalizado" placeholder="Ej: Axel Perez" autocomplete="off">
                <div id="resultados_nombres" class="box" style="display:none;"></div>
            </div>
        </div>

        <div class="field">
            <label class="label">Buscar herramienta:</label>
            <div class="control">
                <input id="buscador_herramienta" class="input" type="text" placeholder="Escribí el nombre de la herramienta">
                <div id="resultados" class="box" style="display:none;"></div>
            </div>
        </div>

        <div class="field">
            <label class="label">Herramientas seleccionadas:</label>
            <div id="seleccionadas" class="tags"></div>
        </div>

        <div class="field mt-4">
            <div class="control">
                <button class="button is-info is-fullwidth" type="submit" id="btnEnviar" disabled>
                    Registrar préstamo
                </button>
            </div>
        </div>
    </form>

    <hr>

    <h3 class="title is-4">Herramientas actualmente prestadas</h3>

    <?php if (count($prestadas) > 0): ?>
        <ul>
            <?php foreach ($prestadas as $row): ?>
                <?php
                    $nombre = htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']);
                    $sucursal = htmlspecialchars($row['sucursal']);
                    $color = $sucursal === 'Osvaldo Cruz' ? 'has-text-link' : 'has-text-danger';
                    $icono = $sucursal === 'Osvaldo Cruz' ? '🔵' : '🔴';
                ?>
                <li class="mb-2">
                    <strong><?= htmlspecialchars($row['herramienta']) ?></strong>
                    (<?= htmlspecialchars($row['codigo']) ?> - <?= htmlspecialchars($row['ubicacion']) ?>)<br>
                    <?= $icono ?> <span class="<?= $color ?>">Prestada a <strong><?= $nombre ?></strong> (<?= $sucursal ?>)</span>
                    desde el <?= date('d/m/Y H:i', strtotime($row['fecha_hora'])) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hay herramientas prestadas actualmente.</p>
    <?php endif; ?>

    <p class="mt-4">
        <a href="devolver.php" class="button is-warning">📦 Devolver herramienta</a>
    </p>
</div>
</section>

<script>
const inputHerramienta = document.getElementById('buscador_herramienta');
const resultados = document.getElementById('resultados');
const seleccionadas = document.getElementById('seleccionadas');
const btnEnviar = document.getElementById('btnEnviar');
const mensajeError = document.getElementById('mensaje-error');
let herramientasSeleccionadas = [];

function actualizarFormulario() {
    seleccionadas.innerHTML = '';
    herramientasSeleccionadas.forEach(h => {
        const tag = document.createElement('span');
        tag.className = 'tag is-info herramienta-tag';
        tag.textContent = h.nombre + ' ✕';
        tag.onclick = () => {
            herramientasSeleccionadas = herramientasSeleccionadas.filter(e => e.id !== h.id);
            actualizarFormulario();
        };
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'herramienta_id[]';
        input.value = h.id;
        seleccionadas.appendChild(tag);
        seleccionadas.appendChild(input);
    });
    btnEnviar.disabled = herramientasSeleccionadas.length === 0;
}

function validarFormulario() {
    const nombre = document.getElementById('nombre_personalizado').value.trim();
    mensajeError.classList.add('is-hidden');
    mensajeError.innerText = '';

    if (nombre === '') {
        mensajeError.innerText = '⚠️ Por favor ingresá el nombre de quien retira.';
        mensajeError.classList.remove('is-hidden');
        return false;
    }
    if (herramientasSeleccionadas.length === 0) {
        mensajeError.innerText = '⚠️ Debés seleccionar al menos una herramienta.';
        mensajeError.classList.remove('is-hidden');
        return false;
    }
    return true;
}

inputHerramienta.addEventListener('input', () => {
    const query = inputHerramienta.value.trim();
    if (query.length < 2) {
        resultados.style.display = 'none';
        resultados.innerHTML = '';
        return;
    }

    fetch('buscar_herramientas.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                resultados.innerHTML = '<p>No se encontraron herramientas.</p>';
            } else {
                resultados.innerHTML = data.map(h => `
                    <div data-id="${h.id}" data-nombre="${h.nombre}">
                        <strong>${h.nombre}</strong> (${h.ubicacion})
                    </div>
                `).join('');
            }
            resultados.style.display = 'block';

            resultados.querySelectorAll('div').forEach(div => {
                div.addEventListener('click', () => {
                    const id = parseInt(div.getAttribute('data-id'));
                    const nombre = div.getAttribute('data-nombre');
                    if (!herramientasSeleccionadas.some(h => h.id === id)) {
                        herramientasSeleccionadas.push({ id, nombre });
                        actualizarFormulario();
                    }
                    inputHerramienta.value = '';
                    resultados.style.display = 'none';
                });
            });
        });
});

const inputNombre = document.getElementById('nombre_personalizado');
const resultadosNombres = document.getElementById('resultados_nombres');
const sucursalSelect = document.getElementById('sucursal');

inputNombre.addEventListener('input', () => {
    const query = inputNombre.value.trim();
    const sucursal = sucursalSelect.value;

    if (query.length < 2) {
        resultadosNombres.style.display = 'none';
        resultadosNombres.innerHTML = '';
        return;
    }

    fetch(`buscar_nombres.php?term=${encodeURIComponent(query)}&sucursal=${encodeURIComponent(sucursal)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                resultadosNombres.innerHTML = '<p>No se encontraron nombres.</p>';
            } else {
                resultadosNombres.innerHTML = data.map(n => `
                    <div data-nombre="${n}">
                        <strong>${n}</strong>
                    </div>
                `).join('');
            }

            resultadosNombres.style.display = 'block';

            resultadosNombres.querySelectorAll('div').forEach(div => {
                div.addEventListener('click', () => {
                    inputNombre.value = div.getAttribute('data-nombre');
                    resultadosNombres.style.display = 'none';
                });
            });
        });
});
</script>
</body>
</html>
