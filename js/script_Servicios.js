document.addEventListener('DOMContentLoaded', () => {
    const registerServiceBtn = document.getElementById('registerServiceBtn');
    const serviceModalOverlay = document.getElementById('serviceModalOverlay');
    const closeServiceModalBtn = document.getElementById('closeServiceModal');
    const serviceRegistrationForm = document.getElementById('serviceRegistrationForm');
    const serviceModalTitle = document.getElementById('serviceModalTitle');
    const submitServiceBtn = document.getElementById('submitServiceBtn');
    const servicesTableBody = document.getElementById('servicesTableBody');
    const searchInput = document.querySelector('.search-input');
    const logoutBtn = document.getElementById('logoutBtn');
    const grupoSelectEstadoServicio = document.getElementById('grupoSelectEstadoServicio');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        });
    }

    let currentSearchTerm = '';

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        }).format(price);
    }

    async function loadServices(search = '') {
        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'list_services', search })
            });

            const result = await response.json();

            if (result.success) {
                const services = result.data || [];
                displayServices(services);
            } else {
                showNotification(result.message || 'Error al cargar servicios', 'error');
                displayServices([]);
            }
        } catch (err) {
            showNotification('Error de conexión', 'error');
            displayServices([]);
        }
    }

    async function createService(serviceData) {
        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create_service', ...serviceData })
            });

            const result = await response.json();
            if (result.success) {
                showNotification('Servicio creado exitosamente');
                loadServices(currentSearchTerm);
                closeServiceModal();
            } else {
                showNotification(result.message || 'Error al crear servicio', 'error');
            }
        } catch (err) {
            showNotification('Error de conexión', 'error');
        }
    }

    async function updateService(id, serviceData) {
        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_service', id, ...serviceData })
            });

            const result = await response.json();
            if (result.success) {
                showNotification('Servicio actualizado');
                loadServices(currentSearchTerm);
                closeServiceModal();
            } else {
                showNotification(result.message || 'Error al actualizar servicio', 'error');
            }
        } catch (err) {
            showNotification('Error de conexión', 'error');
        }
    }

    async function deleteService(id) {
        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_service', id })
            });

            const result = await response.json();
            if (result.success) {
                showNotification('Servicio eliminado');
                loadServices(currentSearchTerm);
            } else {
                showNotification(result.message || 'Error al eliminar servicio', 'error');
            }
        } catch (err) {
            showNotification('Error de conexión', 'error');
        }
    }

    function displayServices(services) {
        if (!Array.isArray(services)) {
            servicesTableBody.innerHTML = '<tr><td colspan="8">No hay servicios disponibles</td></tr>';
            return;
        }

        servicesTableBody.innerHTML = '';

        if (services.length === 0) {
            servicesTableBody.innerHTML = '<tr><td colspan="8">No se encontraron servicios</td></tr>';
            return;
        }

        services.forEach(service => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${service.id_servicio || 'N/A'}</td>
                <td>${service.nombre || 'N/A'}</td>
                <td>${service.descripcion || 'N/A'}</td>
                <td>${service.duracion ? parseFloat(service.duracion).toFixed(1) : 'N/A'}</td>
                <td>${service.precio ? formatPrice(service.precio) : 'N/A'}</td>
                <td>${service.categoria || 'N/A'}</td>
                <td><span class="status-${service.estado}">${service.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div class="actions">
                        <button class="action-btn edit-btn" data-id="${service.id_servicio}">✏️</button>
                        <button class="action-btn delete-btn" data-id="${service.id_servicio}">🗑️</button>
                    </div>
                </td>
            `;
            servicesTableBody.appendChild(row);
        });
    }

    function openServiceModal(mode = 'create', service = null) {
        if (!serviceModalOverlay) return;

        serviceModalOverlay.classList.add('activo');
        serviceModalOverlay.style.display = 'flex';

        if (mode === 'create') {
            if (serviceModalTitle) serviceModalTitle.textContent = 'Registrar Nuevo Servicio';
            if (submitServiceBtn) submitServiceBtn.textContent = 'Registrar Servicio';
            if (serviceRegistrationForm) {
                serviceRegistrationForm.reset();
                delete serviceRegistrationForm.dataset.id;
            }
            const statusElement = document.getElementById('service-status');
            if (statusElement) statusElement.value = 'activo';
            if (grupoSelectEstadoServicio) grupoSelectEstadoServicio.style.display = 'none';
        } else if (service) {
            if (serviceModalTitle) serviceModalTitle.textContent = 'Editar Servicio';
            if (submitServiceBtn) submitServiceBtn.textContent = 'Guardar Cambios';
            if (serviceRegistrationForm) serviceRegistrationForm.dataset.id = service.id;

            document.getElementById('service-name').value = service.nombre;
            document.getElementById('service-category').value = service.categoria;
            document.getElementById('service-description').value = service.descripcion;
            document.getElementById('service-duration').value = service.duracion;
            document.getElementById('service-price').value = service.precio;
            document.getElementById('service-status').value = service.estado;
            if (grupoSelectEstadoServicio) grupoSelectEstadoServicio.style.display = 'block';
        }
    }

    function closeServiceModal() {
        if (serviceModalOverlay) {
            serviceModalOverlay.classList.remove('activo');
            serviceModalOverlay.style.display = 'none';
        }
        if (serviceRegistrationForm) {
            serviceRegistrationForm.reset();
            delete serviceRegistrationForm.dataset.id;
        }
    }

    if (registerServiceBtn) {
        registerServiceBtn.addEventListener('click', () => openServiceModal('create'));
    }

    if (closeServiceModalBtn) {
        closeServiceModalBtn.addEventListener('click', closeServiceModal);
    }

    if (serviceModalOverlay) {
        serviceModalOverlay.addEventListener('click', e => {
            if (e.target === serviceModalOverlay) closeServiceModal();
        });
    }

    if (serviceRegistrationForm) {
        serviceRegistrationForm.addEventListener('submit', async e => {
            e.preventDefault();

            const serviceData = {
                nombre: document.getElementById('service-name').value.trim(),
                categoria: document.getElementById('service-category').value,
                descripcion: document.getElementById('service-description').value.trim(),
                duracion: parseFloat(document.getElementById('service-duration').value || 0),
                precio: parseFloat(document.getElementById('service-price').value || 0),
                estado: document.getElementById('service-status').value
            };

            if (!serviceData.nombre || !serviceData.categoria || !serviceData.descripcion || serviceData.duracion <= 0 || serviceData.precio <= 0) {
                showNotification('Completa todos los campos correctamente', 'error');
                return;
            }

            // Validar que el nombre no contenga números
            if (!/^[a-zA-Z\s]+$/.test(serviceData.nombre)) {
                showNotification('El nombre del servicio no debe contener números', 'error');
                return;
            }

            const serviceId = serviceRegistrationForm.dataset.id;
            if (serviceId) {
                await updateService(parseInt(serviceId), serviceData);
            } else {
                await createService(serviceData);
            }
        });
    }

    if (servicesTableBody) {
        servicesTableBody.addEventListener('click', async e => {
            const editBtn = e.target.closest('.edit-btn');
            const deleteBtn = e.target.closest('.delete-btn');

            if (editBtn) {
                const id = parseInt(editBtn.dataset.id);
                const row = editBtn.closest('tr');
                const service = {
                    id,
                    nombre: row.children[1].textContent,
                    descripcion: row.children[2].textContent,
                    duracion: row.children[3].textContent,
                    precio: parseFloat(row.children[4].textContent.replace(/[^\d.-]/g, '').trim()),
                    categoria: row.children[5].textContent,
                    estado: row.children[6].textContent.toLowerCase() === 'activo' ? 'activo' : 'inactivo'
                };
                openServiceModal('edit', service);
            }

            if (deleteBtn) {
                const id = parseInt(deleteBtn.dataset.id);
                const name = deleteBtn.closest('tr').children[1].textContent;
                if (confirm(`¿Eliminar servicio "${name}"?`)) {
                    await deleteService(id);
                }
            }
        });
    }

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearchTerm = this.value.trim();
                loadServices(currentSearchTerm);
            }, 500);
        });
    }

    loadServices();
});
