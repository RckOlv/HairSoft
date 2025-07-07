// Variables globales
let currentEditingRoleId = null;
let currentEditingPermisoId = null;

// Función para mostrar mensajes
function showMessage(message, type = 'success') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        ${type === 'success' ? 'background-color: #4CAF50;' : 'background-color: #f44336;'}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        document.body.removeChild(messageDiv);
    }, 3000);
}

// Función para crear modal
function createModal(title, content, buttons) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
    `;
    
    modalContent.innerHTML = `
        <h3 style="margin-top: 0;">${title}</h3>
        ${content}
        <div class="modal-buttons" style="margin-top: 20px; text-align: right;">
            ${buttons}
        </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    return modal;
}

// Función para cerrar modal
function closeModal(modal) {
    if (modal && modal.parentNode) {
        modal.parentNode.removeChild(modal);
    }
}

// Función para crear/editar rol
function showRoleModal(roleId = null) {
    const isEditing = roleId !== null;
    const title = isEditing ? 'Editar Rol' : 'Crear Rol';
    
    // Crear checkboxes para permisos
    let permisosHtml = '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">';
    for (const [id, nombre] of Object.entries(allPermisos)) {
        permisosHtml += `
            <div style="margin: 5px 0;">
                <label>
                    <input type="checkbox" name="permisos[]" value="${id}"> ${nombre}
                </label>
            </div>
        `;
    }
    permisosHtml += '</div>';
    
    const content = `
        <form id="roleForm">
            <div style="margin-bottom: 15px;">
                <label for="roleName">Nombre del Rol:</label>
                <input type="text" id="roleName" name="nombre" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="roleDescription">Descripción:</label>
                <textarea id="roleDescription" name="descripcion" rows="3" style="width: 100%; padding: 8px; margin-top: 5px;"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Permisos:</label>
                ${permisosHtml}
            </div>
        </form>
    `;
    
    const buttons = `
        <button type="button" onclick="closeModal(this.closest('.modal-overlay'))" style="margin-right: 10px; padding: 8px 16px; background: #ccc; border: none; border-radius: 4px;">Cancelar</button>
        <button type="button" onclick="saveRole(${roleId})" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px;">Guardar</button>
    `;
    
    const modal = createModal(title, content, buttons);
    
    // Si estamos editando, cargar los datos
    if (isEditing) {
        loadRoleData(roleId);
    }
}

// Función para cargar datos del rol
function loadRoleData(roleId) {
    const formData = new FormData();
    formData.append('action', 'get_role');
    formData.append('id', roleId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('roleName').value = data.data.nombre_rol || '';
            document.getElementById('roleDescription').value = data.data.descripcion || '';
            
            // Marcar permisos
            if (data.data.permisos && Array.isArray(data.data.permisos)) {
                data.data.permisos.forEach(permisoId => {
                    const checkbox = document.querySelector(`input[name="permisos[]"][value="${permisoId}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al cargar los datos del rol', 'error');
    });
}

// Función para guardar rol
function saveRole(roleId) {
    const form = document.getElementById('roleForm');
    const formData = new FormData(form);
    
    // Validaciones previas
    const nombre = formData.get('nombre')?.trim() ?? '';
    const descripcion = formData.get('descripcion')?.trim() ?? '';
    
    if (nombre.length < 3 || nombre.length > 50) {
        showMessage('El nombre del rol debe tener entre 3 y 50 caracteres.', 'error');
        return;
    }
    if (descripcion.length > 150) {
        showMessage('La descripción del rol no debe superar los 150 caracteres.', 'error');
        return;
    }

    if (roleId) {
        formData.append('action', 'update_role');
        formData.append('id', roleId);
    } else {
        formData.append('action', 'create_role');
    }
    
    // Obtener permisos seleccionados
    const permisos = [];
    const checkboxes = document.querySelectorAll('input[name="permisos[]"]:checked');
    checkboxes.forEach(checkbox => {
        permisos.push(checkbox.value);
    });
    
    // Limpiar permisos del FormData y agregar el array
    formData.delete('permisos[]');
    permisos.forEach(permiso => {
        formData.append('permisos[]', permiso);
    });
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message);
            closeModal(document.querySelector('.modal-overlay'));
            location.reload(); // Recargar para mostrar los cambios
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al guardar el rol', 'error');
    });
}

// Función para eliminar rol
function deleteRole(roleId) {
    if (!confirm('¿Estás seguro de que deseas eliminar este rol?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_role');
    formData.append('id', roleId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message);
            document.querySelector(`tr[data-id="${roleId}"]`).remove();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al eliminar el rol', 'error');
    });
}

// Función para editar rol
function editRole(roleId) {
    showRoleModal(roleId);
}

// Función para crear/editar permiso
function showPermisoModal(permisoId = null) {
    const isEditing = permisoId !== null;
    const title = isEditing ? 'Editar Permiso' : 'Crear Permiso';
    
    const content = `
        <form id="permisoForm">
            <div style="margin-bottom: 15px;">
                <label for="permisoName">Nombre del Permiso:</label>
                <input type="text" id="permisoName" name="nombre" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="permisoDescription">Descripción:</label>
                <textarea id="permisoDescription" name="descripcion" rows="3" style="width: 100%; padding: 8px; margin-top: 5px;"></textarea>
            </div>
        </form>
    `;
    
    const buttons = `
        <button type="button" onclick="closeModal(this.closest('.modal-overlay'))" style="margin-right: 10px; padding: 8px 16px; background: #ccc; border: none; border-radius: 4px;">Cancelar</button>
        <button type="button" onclick="savePermiso(${permisoId})" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px;">Guardar</button>
    `;
    
    const modal = createModal(title, content, buttons);
    
    // Si estamos editando, cargar los datos
    if (isEditing) {
        loadPermisoData(permisoId);
    }
}

// Función para cargar datos del permiso
function loadPermisoData(permisoId) {
    const formData = new FormData();
    formData.append('action', 'get_permiso');
    formData.append('id', permisoId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('permisoName').value = data.data.nombre || '';
            document.getElementById('permisoDescription').value = data.data.descripcion || '';
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al cargar los datos del permiso', 'error');
    });
}

// Función para guardar permiso
function savePermiso(permisoId) {
    const form = document.getElementById('permisoForm');
    const formData = new FormData(form);
    
    // Validaciones previas
    const nombre = formData.get('nombre')?.trim() ?? '';
    const descripcion = formData.get('descripcion')?.trim() ?? '';
    
    if (nombre.length < 3 || nombre.length > 50) {
        showMessage('El nombre del permiso debe tener entre 3 y 50 caracteres.', 'error');
        return;
    }
    if (descripcion.length > 150) {
        showMessage('La descripción del permiso no debe superar los 150 caracteres.', 'error');
        return;
    }
    
    if (permisoId) {
        formData.append('action', 'update_permiso');
        formData.append('id', permisoId);
    } else {
        formData.append('action', 'create_permiso');
    }
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message);
            closeModal(document.querySelector('.modal-overlay'));
            location.reload(); // Recargar para mostrar los cambios
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al guardar el permiso', 'error');
    });
}

// Función para eliminar permiso
function deletePermiso(permisoId) {
    if (!confirm('¿Estás seguro de que deseas eliminar este permiso?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_permiso');
    formData.append('id', permisoId);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message);
            document.querySelector(`tr[data-id="${permisoId}"]`).remove();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al eliminar el permiso', 'error');
    });
}

// Función para editar permiso
function editPermiso(permisoId) {
    showPermisoModal(permisoId);
}

// Función para buscar roles
function searchRoles(query) {
    const formData = new FormData();
    formData.append('action', 'search_roles');
    formData.append('search', query);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateRolesTable(data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Función para buscar permisos
function searchPermisos(query) {
    const formData = new FormData();
    formData.append('action', 'search_permisos');
    formData.append('search', query);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updatePermisosTable(data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Función para actualizar tabla de roles
function updateRolesTable(roles) {
    const tbody = document.getElementById('rolesTableBody');
    tbody.innerHTML = '';
    
    roles.forEach(rol => {
        let permisosTexto = '-';
        if (rol.permisos && typeof rol.permisos === 'object') {
            const permisosArray = [];
            for (const [clave, acciones] of Object.entries(rol.permisos)) {
                if (Array.isArray(acciones)) {
                    permisosArray.push(clave + ': ' + acciones.join(', '));
                } else {
                    permisosArray.push(clave + ': ' + acciones);
                }
            }
            permisosTexto = permisosArray.join(' | ');
        }
        
        const row = document.createElement('tr');
        row.setAttribute('data-id', rol.id_rol);
        row.innerHTML = `
            <td>${rol.id_rol}</td>
            <td data-field="name">${rol.nombre_rol}</td>
            <td data-field="description">${rol.descripcion}</td>
            <td data-field="permissions">${permisosTexto}</td>
            <td>
                <div class="actions">
                    <button class="action-btn edit-role-btn" onclick="editRole(${rol.id_rol})">Editar</button>
                    <button class="action-btn delete-role-btn" onclick="deleteRole(${rol.id_rol})">Eliminar</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Función para actualizar tabla de permisos
function updatePermisosTable(permisos) {
    const tbody = document.getElementById('permisosTableBody');
    tbody.innerHTML = '';
    
    permisos.forEach(permiso => {
        const row = document.createElement('tr');
        row.setAttribute('data-id', permiso.id);
        row.innerHTML = `
            <td>${permiso.id}</td>
            <td data-field="name">${permiso.nombre}</td>
            <td data-field="description">${permiso.descripcion}</td>
            <td>
                <div class="actions">
                    <button class="action-btn edit-permiso-btn" onclick="editPermiso(${permiso.id})">Editar</button>
                    <button class="action-btn delete-permiso-btn" onclick="deletePermiso(${permiso.id})">Eliminar</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Event listeners cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Botón crear rol
    const createRoleBtn = document.getElementById('createRoleBtn');
    if (createRoleBtn) {
        createRoleBtn.addEventListener('click', () => showRoleModal());
    }
    
    // Botón crear permiso
    const createPermisoBtn = document.getElementById('createPermisoBtn');
    if (createPermisoBtn) {
        createPermisoBtn.addEventListener('click', () => showPermisoModal());
    }
    
    // Búsqueda de roles
    const searchRolesInput = document.getElementById('searchRoles');
    if (searchRolesInput) {
        let searchTimeout;
        searchRolesInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchRoles(this.value);
            }, 300);
        });
    }
    
    // Búsqueda de permisos
    const searchPermisosInput = document.getElementById('searchPermisos');
    if (searchPermisosInput) {
        let searchTimeout;
        searchPermisosInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchPermisos(this.value);
            }, 300);
        });
    }
    
    // Botón logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        });
    }
    
    // Cerrar modal al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal(e.target);
        }
    });
    
    // Cerrar modal con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                closeModal(modal);
            }
        }
    });
});
