document.addEventListener('DOMContentLoaded', () => {
    // Verificar que todos los elementos existan
    const registerServiceBtn = document.getElementById('registerServiceBtn');
    const serviceModalOverlay = document.getElementById('serviceModalOverlay');
    const closeServiceModalBtn = document.getElementById('closeServiceModal');
    const serviceRegistrationForm = document.getElementById('serviceRegistrationForm');
    const serviceModalTitle = document.getElementById('serviceModalTitle');
    const submitServiceBtn = document.getElementById('submitServiceBtn');
    const servicesTableBody = document.getElementById('servicesTableBody');
    const searchInput = document.querySelector('.search-input');
    const logoutBtn = document.getElementById('logoutBtn'); // Agregar aquí la referencia

    // Debug: Verificar elementos del DOM
    console.log('Elementos encontrados:');
    console.log('registerServiceBtn:', registerServiceBtn);
    console.log('serviceModalOverlay:', serviceModalOverlay);
    console.log('closeServiceModalBtn:', closeServiceModalBtn);
    console.log('serviceRegistrationForm:', serviceRegistrationForm);
    console.log('serviceModalTitle:', serviceModalTitle);
    console.log('submitServiceBtn:', submitServiceBtn);
    console.log('servicesTableBody:', servicesTableBody);
    console.log('searchInput:', searchInput);
    console.log('logoutBtn:', logoutBtn); // Debug para el botón logout

    // Verificar elementos críticos
    if (!registerServiceBtn) {
        console.error('No se encontró el botón registerServiceBtn');
        return;
    }
    if (!serviceModalOverlay) {
        console.error('No se encontró el serviceModalOverlay');
        return;
    }
    if (!servicesTableBody) {
        console.error('No se encontró el servicesTableBody');
        return;
    }

    // Esto asegura que se configure antes de cualquier otro código que pueda interferir
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir cualquier comportamiento por defecto
            e.stopPropagation(); // Evitar que el evento se propague
            console.log('Botón logout clickeado'); // Debug
            
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                console.log('Usuario confirmó logout, redirigiendo...'); // Debug
                window.location.href = 'logout.php';
            }
        });
        console.log('Event listener del logout configurado correctamente');
    } else {
        console.error('No se encontró el botón logoutBtn');
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
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            // Debug: ver qué devuelve exactamente la API
            console.log('Respuesta completa de la API:', result);
            
            if (result.success) {
                // Tu API devuelve los servicios en result.data
                const services = result.data || result.servicios || result.services || [];
                console.log('Servicios encontrados:', services);
                displayServices(services);
            } else {
                showNotification(result.message || 'Error al cargar servicios', 'error');
                displayServices([]); // Mostrar tabla vacía en caso de error
            }
        } catch (err) {
            console.error('Error en loadServices:', err);
            showNotification('Error de conexión', 'error');
            displayServices([]); // Mostrar tabla vacía en caso de error
        }
    }

    async function createService(serviceData) {
        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create_service', ...serviceData })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (result.success) {
                showNotification('Servicio creado exitosamente');
                loadServices(currentSearchTerm);
                closeServiceModal();
            } else {
                showNotification(result.message || 'Error al crear servicio', 'error');
            }
        } catch (err) {
            console.error('Error en createService:', err);
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
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (result.success) {
                showNotification('Servicio actualizado');
                loadServices(currentSearchTerm);
                closeServiceModal();
            } else {
                showNotification(result.message || 'Error al actualizar servicio', 'error');
            }
        } catch (err) {
            console.error('Error en updateService:', err);
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
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (result.success) {
                showNotification('Servicio eliminado');
                loadServices(currentSearchTerm);
            } else {
                showNotification(result.message || 'Error al eliminar servicio', 'error');
            }
        } catch (err) {
            console.error('Error en deleteService:', err);
            showNotification('Error de conexión', 'error');
        }
    }

    function displayServices(services) {
        // Verificar que services sea un array válido
        if (!services || !Array.isArray(services)) {
            console.error('displayServices: services no es un array válido:', services);
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
        console.log('openServiceModal llamado con modo:', mode, 'servicio:', service);
        
        if (!serviceModalOverlay) {
            console.error('serviceModalOverlay no existe');
            return;
        }
        
        // Agregar clase para mostrar el modal
        serviceModalOverlay.classList.add('activo');
        serviceModalOverlay.style.display = 'flex'; // Asegurar que se muestre
        
        if (mode === 'create') {
            if (serviceModalTitle) serviceModalTitle.textContent = 'Registrar Nuevo Servicio';
            if (submitServiceBtn) submitServiceBtn.textContent = 'Registrar Servicio';
            if (serviceRegistrationForm) {
                serviceRegistrationForm.reset();
                delete serviceRegistrationForm.dataset.id;
            }
            const statusElement = document.getElementById('service-status');
            if (statusElement) statusElement.value = 'activo';
        } else if (service) {
            if (serviceModalTitle) serviceModalTitle.textContent = 'Editar Servicio';
            if (submitServiceBtn) submitServiceBtn.textContent = 'Guardar Cambios';
            if (serviceRegistrationForm) serviceRegistrationForm.dataset.id = service.id;
            
            const serviceNameEl = document.getElementById('service-name');
            const serviceCategoryEl = document.getElementById('service-category');
            const serviceDescriptionEl = document.getElementById('service-description');
            const serviceDurationEl = document.getElementById('service-duration');
            const servicePriceEl = document.getElementById('service-price');
            const serviceStatusEl = document.getElementById('service-status');
            
            if (serviceNameEl) serviceNameEl.value = service.nombre;
            if (serviceCategoryEl) serviceCategoryEl.value = service.categoria;
            if (serviceDescriptionEl) serviceDescriptionEl.value = service.descripcion;
            if (serviceDurationEl) serviceDurationEl.value = service.duracion;
            if (servicePriceEl) servicePriceEl.value = service.precio;
            if (serviceStatusEl) serviceStatusEl.value = service.estado;
        }
        
        console.log('Modal debería estar visible ahora');
    }

    function closeServiceModal() {
        if (serviceModalOverlay) {
            serviceModalOverlay.classList.remove('activo');
            serviceModalOverlay.style.display = 'none'; // Asegurar que se oculte
        }
        if (serviceRegistrationForm) {
            serviceRegistrationForm.reset();
            delete serviceRegistrationForm.dataset.id;
        }
        console.log('Modal cerrado');
    }

    // Event listeners con validación
    if (registerServiceBtn) {
        registerServiceBtn.addEventListener('click', () => {
            console.log('Botón registrar servicio clickeado');
            openServiceModal('create');
        });
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
                nombre: document.getElementById('service-name')?.value.trim() || '',
                categoria: document.getElementById('service-category')?.value || '',
                descripcion: document.getElementById('service-description')?.value.trim() || '',
                duracion: parseFloat(document.getElementById('service-duration')?.value || 0),
                precio: parseFloat(document.getElementById('service-price')?.value || 0),
                estado: document.getElementById('service-status')?.value || 'activo'
            };

            if (!serviceData.nombre || !serviceData.categoria || !serviceData.descripcion || serviceData.duracion <= 0 || serviceData.precio <= 0) {
                showNotification('Completa todos los campos correctamente', 'error');
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
                console.log('Botón editar clickeado');
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
                console.log('Botón eliminar clickeado');
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

    // Cargar servicios al inicializar
    loadServices();
});