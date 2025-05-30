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
        .herramienta-preview {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            margin-bottom: 8px;
            padding: 8px;
            border-radius: 8px;
        }
        .herramienta-preview img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 10px;
            cursor: pointer;
        }
        .herramienta-preview .remove {
            margin-left: auto;
            cursor: pointer;
            color: red;
            font-weight: bold;
            font-size: 1.5rem;
            line-height: 1;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal img {
            max-width: 90%;
            max-height: 90%;
        }
    </style>
</head>
<body class="is-flex is-flex-direction-column" style="min-height: 100vh;">
<div class="modal" id="modalImagen">
    <img id="imagenAmpliada" src="" alt="Vista ampliada">
</div>

<section class="section">
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
                <input id="buscador_herramienta" class="input" type="text" placeholder="Escribí nombre o código de la herramienta">
                <div id="resultados" class="box" style="display:none;"></div>
            </div>
        </div>

        <div class="field">
            <label class="label">Herramientas seleccionadas:</label>
            <div id="seleccionadas"></div>
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

<footer class="footer-login has-text-right">
    <div class="container is-flex is-justify-content-flex-end">
        <a href="login.php" class="button is-info is-light">🔐 Iniciar sesión</a>
    </div>
</footer>

<script>
const inputHerramienta = document.getElementById('buscador_herramienta');
const resultados = document.getElementById('resultados');
const seleccionadas = document.getElementById('seleccionadas');
const btnEnviar = document.getElementById('btnEnviar');
const mensajeError = document.getElementById('mensaje-error');
const modalImagen = document.getElementById('modalImagen');
const imagenAmpliada = document.getElementById('imagenAmpliada');
let herramientasSeleccionadas = [];

function actualizarVista() {
    seleccionadas.innerHTML = '';
    herramientasSeleccionadas.forEach(h => {
        const contenedor = document.createElement('div');
        contenedor.className = 'herramienta-preview';

        if (h.imagen && h.imagen.trim() !== '') {
            const img = document.createElement('img');
            img.src = h.imagen;
            img.alt = h.nombre;
            img.onclick = () => {
                imagenAmpliada.src = h.imagen;
                modalImagen.style.display = 'flex';
            };
            contenedor.appendChild(img);
        } else {
            const texto = document.createElement('span');
            texto.innerHTML = '<em>Sin Imagen</em>';
            texto.className = 'has-text-grey is-italic';
            texto.style.marginRight = '10px';
            texto.style.fontSize = '0.9rem';
            contenedor.appendChild(texto);
        }

        const contenedorInfo = document.createElement('div');
        contenedorInfo.innerHTML = `
            <strong>${h.nombre}</strong><br>
            <span class="tag is-info is-light is-small">Código: ${h.codigo}</span><br>
            <span class="is-size-7 has-text-grey">📍 ${h.ubicacion}</span>
        `;

        const cerrar = document.createElement('span');
        cerrar.className = 'remove';
        cerrar.textContent = '✕';
        cerrar.onclick = () => {
            herramientasSeleccionadas = herramientasSeleccionadas.filter(e => e.id !== h.id);
            actualizarVista();
        };

        const inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = 'herramienta_id[]';
        inputHidden.value = h.id;

        contenedor.appendChild(contenedorInfo);
        contenedor.appendChild(cerrar);
        contenedor.appendChild(inputHidden);
        seleccionadas.appendChild(contenedor);
    });

    btnEnviar.disabled = herramientasSeleccionadas.length === 0;

    // Marcar como ya seleccionadas
    document.querySelectorAll('#resultados div[data-id]').forEach(div => {
        const id = parseInt(div.getAttribute('data-id'));
        if (herramientasSeleccionadas.some(h => h.id === id)) {
            div.classList.add('has-background-grey-lighter');
        } else {
            div.classList.remove('has-background-grey-lighter');
        }
    });
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
    if (query.length < 1) {
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
                resultados.innerHTML = data.map(h => {
                    const color = h.cantidad == 0 ? 'has-background-light' : (h.prestada ? 'has-background-danger-light' : 'has-background-success-light');
                    const imagenHtml = h.imagen
                        ? `<img src="${h.imagen}" alt="img" style="width: 50px; height: 50px; object-fit: contain;">`
                        : `<span class="has-text-grey is-italic" style="width: 50px; display: inline-block; text-align: center; font-size: 0.8rem;">Sin imagen</span>`;

                    const disabledClass = h.cantidad == 0 ? 'has-text-grey-light' : '';
                    const pointer = h.cantidad == 0 ? 'default' : 'pointer';
                    const disabledMsg = h.cantidad == 0 ? '<br><em>❌ Sin stock</em>' : '';

                    const esCoincidenciaExacta = h.codigo.toLowerCase() === inputHerramienta.value.trim().toLowerCase();
                    const extraStyle = esCoincidenciaExacta ? 'border: 2px solid #3273dc; background-color: #eff6ff;' : '';
                    const estrella = esCoincidenciaExacta ? ' ' : '';

                    return `
                        <div class="is-flex mb-2 ${color} ${disabledClass}" 
                                style="align-items: center; gap: 10px; cursor: ${pointer}; ${extraStyle}"
                                data-id="${h.id}" data-nombre="${h.nombre}" data-codigo="${h.codigo}" data-img="${h.imagen ?? ''}" data-ubi="${h.ubicacion}" data-cantidad="${h.cantidad}">
                            ${imagenHtml}
                            <div>
                                <strong>${estrella}${h.nombre}</strong><br>
                                <span class="tag is-info is-light is-small">Código: ${h.codigo}</span><br>
                                <small>📍 ${h.ubicacion} | ${h.cantidad} en stock</small>
                                ${disabledMsg}
                            </div>
                        </div>
                    `;
                }).join('');
            }
            resultados.style.display = 'block';

            resultados.querySelectorAll('div[data-id]').forEach(div => {
                if (parseInt(div.getAttribute('data-cantidad')) === 0) return;

                div.addEventListener('click', () => {
                    const id = parseInt(div.getAttribute('data-id'));
                    const nombre = div.getAttribute('data-nombre');
                    const codigo = div.getAttribute('data-codigo');
                    const imagen = div.getAttribute('data-img');
                    const ubicacion = div.getAttribute('data-ubi');
                    if (!herramientasSeleccionadas.some(h => h.id === id)) {
                        herramientasSeleccionadas.push({ id, nombre, codigo, imagen, ubicacion });
                        actualizarVista();
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
        inputNombre.classList.remove('is-danger', 'is-success');
        return;
    }

    fetch(`buscar_nombres.php?term=${encodeURIComponent(query)}&sucursal=${encodeURIComponent(sucursal)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                resultadosNombres.innerHTML = '<p>No se encontraron nombres.</p>';
                inputNombre.classList.add('is-danger');
                inputNombre.classList.remove('is-success');
            } else {
                resultadosNombres.innerHTML = data.map(n => `
                    <div data-nombre="${n}">
                        <strong>${n}</strong>
                    </div>
                `).join('');
                inputNombre.classList.remove('is-danger');
                inputNombre.classList.add('is-success');
            }

            resultadosNombres.style.display = 'block';

            resultadosNombres.querySelectorAll('div').forEach(div => {
                div.addEventListener('click', () => {
                    inputNombre.value = div.getAttribute('data-nombre');
                    resultadosNombres.style.display = 'none';
                    inputNombre.classList.add('is-success');
                    inputNombre.classList.remove('is-danger');
                });
            });
        });
});

// Cerrar modal al hacer clic fuera de la imagen
modalImagen.addEventListener('click', () => {
    modalImagen.style.display = 'none';
});
</script>
