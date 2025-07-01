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

    const tituloModalUsuario = document.getElementById('tituloModalUsuario');
    const btnCambiarPassword = document.getElementById('btnCambiarPassword');

    // Función para mostrar u ocultar campos de contraseña y ajustar botón
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
        // No limpiamos aquí para no borrar lo que pueda haber ingresado el usuario
    }

    // Abrir modal para nuevo usuario
    btnRegistrarUsuario.addEventListener('click', () => {
        formUsuario.reset();
        idUsuario.value = '';
        tituloModalUsuario.textContent = 'Registrar Nuevo Usuario';

        mostrarCamposPassword();

        overlayModalUsuario.style.display = 'flex';
    });

    // Cerrar modal
    btnCerrarModalUsuario.addEventListener('click', () => {
        overlayModalUsuario.style.display = 'none';
    });

    // Función para llenar formulario con datos de fila para editar
    function llenarFormularioDesdeFila(fila) {
        idUsuario.value = fila.dataset.id;
        inputNombre.value = fila.dataset.nombre;
        inputApellido.value = fila.dataset.apellido;
        inputEmail.value = fila.dataset.email;
        inputTelefono.value = fila.dataset.telefono;
        selectTipoDoc.value = fila.dataset.tipoDoc;
        inputDocumento.value = fila.dataset.documento;

        // Seleccionar rol por texto
        for (let opcion of selectRol.options) {
            if (opcion.text === fila.dataset.rol) {
                opcion.selected = true;
                break;
            }
        }

        selectEstado.value = fila.dataset.estado;

        tituloModalUsuario.textContent = 'Editar Usuario';

        ocultarCamposPassword();

        btnCambiarPassword.style.display = 'inline-block'; // botón visible al editar

        overlayModalUsuario.style.display = 'flex';
    }

    // Botón "Cambiar contraseña" que alterna campos de password en edición
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

    // Editar y eliminar usuario (delegación)
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

    // Filtro de búsqueda en la tabla
    inputBuscarUsuario.addEventListener('input', () => {
        const filtro = inputBuscarUsuario.value.toLowerCase();
        const filas = cuerpoTablaUsuarios.querySelectorAll('tr');
        filas.forEach(fila => {
            const nombre = fila.dataset.nombre.toLowerCase();
            const apellido = fila.dataset.apellido.toLowerCase();
            const email = fila.dataset.email.toLowerCase();
            if(nombre.includes(filtro) || apellido.includes(filtro) || email.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    });

    // Enviar formulario para crear/editar usuario
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

        if(id === '') {
            // Nuevo usuario: contraseña obligatoria y debe coincidir
            if(password === '') {
                alert('La contraseña es obligatoria para un nuevo usuario.');
                return;
            }
            if(password !== confirmarPassword) {
                alert('Las contraseñas no coinciden.');
                return;
            }
        } else {
            // Edición: si ponen contraseña, debe coincidir; si no la ponen, no se cambia
            if(password !== '' && password !== confirmarPassword) {
                alert('Las contraseñas no coinciden.');
                return;
            }
        }
        
        console.log(id);
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

    // Función para eliminar usuario via AJAX
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

    // Botón cerrar sesión
    document.getElementById('btnCerrarSesion').addEventListener('click', () => {
        window.location.href = 'logout.php';
    });
});
