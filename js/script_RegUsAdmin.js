document.addEventListener('DOMContentLoaded', () => {
    const btnRegistrarUsuario = document.getElementById('btnRegistrarUsuario');
    const overlayModalUsuario = document.getElementById('overlayModalUsuario');
    const btnCerrarModalUsuario = document.getElementById('btnCerrarModalUsuario');
    const formUsuario = document.getElementById('formUsuario');

    const cuerpoTablaUsuarios = document.getElementById('cuerpoTablaUsuarios');
    const inputBuscarUsuario = document.getElementById('inputBuscarUsuario');

    // Campos del formulario
    const idUsuario = document.getElementById('idUsuario');
    const inputNombre = document.getElementById('inputNombre');
    const inputApellido = document.getElementById('inputApellido');
    const inputEmail = document.getElementById('inputEmail');
    const inputTelefono = document.getElementById('inputTelefono');
    const selectTipoDoc = document.getElementById('selectTipoDoc');
    const inputDocumento = document.getElementById('inputDocumento');
    const selectRol = document.getElementById('selectRol');
    const selectEstado = document.getElementById('selectEstado');
    const inputPassword = document.getElementById('inputPassword');
    const inputConfirmarPassword = document.getElementById('inputConfirmarPassword');
    const grupoSelectEstado = document.getElementById('grupoSelectEstado');

    const tituloModalUsuario = document.getElementById('tituloModalUsuario');
    const btnCambiarPassword = document.getElementById('btnCambiarPassword');

    function ocultarCamposPassword() {
        document.querySelectorAll('.grupo-password').forEach(el => el.style.display = 'none');
        inputPassword.value = '';
        inputConfirmarPassword.value = '';
        btnCambiarPassword.style.display = 'inline-block';
        btnCambiarPassword.textContent = 'Cambiar contraseña';
    }

    function mostrarCamposPassword() {
        document.querySelectorAll('.grupo-password').forEach(el => el.style.display = 'block');
        btnCambiarPassword.style.display = 'none';
    }

    function llenarFormularioDesdeFila(fila) {
        idUsuario.value = fila.dataset.id;
        inputNombre.value = fila.dataset.nombre;
        inputApellido.value = fila.dataset.apellido;
        inputEmail.value = fila.dataset.email;
        inputTelefono.value = fila.dataset.telefono;
        selectTipoDoc.value = fila.dataset.tipoDoc;
        inputDocumento.value = fila.dataset.documento;

        for (let opcion of selectRol.options) {
            if (opcion.text === fila.dataset.rol) {
                opcion.selected = true;
                break;
            }
        }

        selectEstado.value = fila.dataset.estado;

        tituloModalUsuario.textContent = 'Editar Usuario';

        ocultarCamposPassword();
        btnCambiarPassword.style.display = 'inline-block';
        grupoSelectEstado.style.display = 'block';

        overlayModalUsuario.style.display = 'flex';
    }

    btnRegistrarUsuario.addEventListener('click', () => {
        formUsuario.reset();
        idUsuario.value = '';
        tituloModalUsuario.textContent = 'Registrar Nuevo Usuario';

        mostrarCamposPassword();
        grupoSelectEstado.style.display = 'none';
        overlayModalUsuario.style.display = 'flex';
    });

    btnCerrarModalUsuario.addEventListener('click', () => {
        overlayModalUsuario.style.display = 'none';
    });

    btnCambiarPassword.addEventListener('click', () => {
        const gruposPassword = document.querySelectorAll('.grupo-password');
        const visible = gruposPassword[0].style.display === 'block';

        if (visible) {
            gruposPassword.forEach(el => el.style.display = 'none');
            inputPassword.value = '';
            inputConfirmarPassword.value = '';
            btnCambiarPassword.textContent = 'Cambiar contraseña';
        } else {
            gruposPassword.forEach(el => el.style.display = 'block');
            btnCambiarPassword.textContent = 'Ocultar contraseña';
        }
    });

    cuerpoTablaUsuarios.addEventListener('click', e => {
        if (e.target.closest('.btnEditar')) {
            const fila = e.target.closest('tr');
            llenarFormularioDesdeFila(fila);
        } else if (e.target.closest('.btnEliminar')) {
            const fila = e.target.closest('tr');
            if (confirm(`¿Seguro que desea eliminar al usuario ${fila.dataset.nombre} ${fila.dataset.apellido}?`)) {
                eliminarUsuario(fila.dataset.id);
            }
        }
    });

    function buscarUsuarios() {
        const search = inputBuscarUsuario.value.trim();

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'list_users',
                search: search
            })
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                cuerpoTablaUsuarios.innerHTML = '';
                data.data.forEach(usuario => {
                    const tr = document.createElement('tr');
                    tr.dataset.id = usuario.id_usuario;
                    tr.dataset.nombre = usuario.nombre;
                    tr.dataset.apellido = usuario.apellido;
                    tr.dataset.email = usuario.email;
                    tr.dataset.telefono = usuario.telefono;
                    tr.dataset.tipoDoc = usuario.tipo_documento;
                    tr.dataset.documento = usuario.documento;
                    tr.dataset.rol = usuario.id_rol;
                    tr.dataset.estado = usuario.estado;

                    tr.innerHTML = `
                        <td>${usuario.nombre}</td>
                        <td>${usuario.apellido}</td>
                        <td>${usuario.email}</td>
                        <td>${usuario.documento}</td>
                        <td>${usuario.telefono}</td>
                        <td>${usuario.estado}</td>
                        <td>
                            <button class="btnEditar">Editar</button>
                            <button class="btnEliminar">Eliminar</button>
                        </td>
                    `;
                    cuerpoTablaUsuarios.appendChild(tr);
                });
            }
        });
    }

    inputBuscarUsuario.addEventListener('input', buscarUsuarios);

    formUsuario.addEventListener('submit', e => {
    e.preventDefault();

    const id = idUsuario.value.trim();
    const nombre = inputNombre.value.trim();
    const apellido = inputApellido.value.trim();
    const email = inputEmail.value.trim();
    const telefono = inputTelefono.value.trim();
    const tipoDoc = selectTipoDoc.value;
    const documento = inputDocumento.value.trim();
    const rol = selectRol.value;
    const estado = selectEstado.value;
    const password = inputPassword.value;
    const confirmarPassword = inputConfirmarPassword.value;

    // Validaciones
    if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(nombre)) {
        alert('El nombre solo puede contener letras y espacios.');
        return;
    }

    if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(apellido)) {
        alert('El apellido solo puede contener letras y espacios.');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Ingrese un correo electrónico válido.');
        return;
    }

    if (telefono && !/^[\d\s\+\-]+$/.test(telefono)) {
        alert('El teléfono solo puede contener números, espacios, "+" o "-".');
        return;
    }

    if (!/^\d+$/.test(documento)) {
        alert('El documento debe contener solo números.');
        return;
    }

    if (rol === '') {
        alert('Debe seleccionar un rol.');
        return;
    }

    if (id === '') {
        if (password === '') {
            alert('La contraseña es obligatoria para un nuevo usuario.');
            return;
        }
        if (password !== confirmarPassword) {
            alert('Las contraseñas no coinciden.');
            return;
        }
    } else {
        if (password !== '' && password !== confirmarPassword) {
            alert('Las contraseñas no coinciden.');
            return;
        }
    }

    const dataToSend = {
        action: id === '' ? 'create_user' : 'update_user',
        id_usuario: id,
        nombre: nombre,
        apellido: apellido,
        email: email,
        telefono: telefono,
        tipo_documento: tipoDoc,
        documento: documento,
        id_rol: rol,
        estado: estado,
    };

    if (password !== '') {
        dataToSend.password = password;
    }

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSend)
    })
    .then(resp => resp.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error en la comunicación con el servidor.'));
});


    function eliminarUsuario(id) {
        if(!id) return;

        if(!confirm('¿Confirma que desea eliminar este usuario?')) return;

        const dataToSend = {
            action: 'delete_user',
            id_usuario: id
        };

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataToSend)
        })
        .then(resp => resp.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error en la comunicación con el servidor.'));
    }

    document.getElementById('btnCerrarSesion').addEventListener('click', () => {
        window.location.href = 'logout.php';
    });
});
