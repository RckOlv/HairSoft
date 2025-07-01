document.addEventListener('DOMContentLoaded', () => {
    // Función para cargar estadísticas reales del dashboard
    function cargarEstadisticas() {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_dashboard_stats'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar estadísticas
                const statsValues = document.querySelectorAll('.stat-value');
                if (statsValues.length >= 4) {
                    statsValues[0].textContent = data.data.usuarios.toLocaleString();
                    statsValues[1].textContent = data.data.servicios.toLocaleString();
                    statsValues[2].textContent = data.data.turnos.toLocaleString();
                    statsValues[3].textContent = `$${data.data.ingresos.toLocaleString()}`;
                }

                // Actualizar actividad reciente si hay datos
                if (data.data.actividad_reciente && data.data.actividad_reciente.length > 0) {
                    actualizarActividadReciente(data.data.actividad_reciente);
                }
            } else {
                console.error('Error al cargar estadísticas:', data.message);
                // Mantener valores por defecto en caso de error
                mostrarEstadisticasPorDefecto();
            }
        })
        .catch(error => {
            console.error('Error de conexión:', error);
            // Mostrar valores por defecto en caso de error de conexión
            mostrarEstadisticasPorDefecto();
        });
    }

    // Función para mostrar estadísticas por defecto en caso de error
    function mostrarEstadisticasPorDefecto() {
        const statsValues = document.querySelectorAll('.stat-value');
        if (statsValues.length >= 4) {
            statsValues[0].textContent = '0';
            statsValues[1].textContent = '0';
            statsValues[2].textContent = '0';
            statsValues[3].textContent = '$0';
        }
    }

    // Función para actualizar la actividad reciente
    function actualizarActividadReciente(actividades) {
        const activityList = document.querySelector('.activity-list');
        if (!activityList) return;

        if (actividades.length === 0) {
            activityList.innerHTML = `
                <li class="activity-item">
                    <span class="activity-icon">ℹ️</span>
                    <div class="activity-content">
                        <p class="activity-message">No hay actividad reciente para mostrar</p>
                        <p class="activity-time">-</p>
                    </div>
                </li>
            `;
            return;
        }

        activityList.innerHTML = actividades.map(actividad => `
            <li class="activity-item">
                <span class="activity-icon">${obtenerIconoActividad(actividad.tipo)}</span>
                <div class="activity-content">
                    <p class="activity-message">${actividad.mensaje}</p>
                    <p class="activity-time">${formatearTiempo(actividad.fecha)}</p>
                </div>
            </li>
        `).join('');
    }

    // Función para obtener el icono según el tipo de actividad
    function obtenerIconoActividad(tipo) {
        const iconos = {
            'usuario_nuevo': '👤',
            'servicio_actualizado': '✂️',
            'permiso_modificado': '🔒',
            'producto_agregado': '💄',
            'turno_creado': '📅',
            'pago_recibido': '💰',
            'turno_cancelado': '❌',
            'cliente_actualizado': '📝',
            'backup_realizado': '💾',
            'login_usuario': '🔑'
        };
        return iconos[tipo] || '📋';
    }

    // Función para formatear el tiempo transcurrido
    function formatearTiempo(fechaString) {
        const fecha = new Date(fechaString);
        const ahora = new Date();
        const diferencia = Math.floor((ahora - fecha) / 1000); // diferencia en segundos

        if (diferencia < 60) {
            return 'Hace menos de 1 minuto';
        } else if (diferencia < 3600) {
            const minutos = Math.floor(diferencia / 60);
            return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
        } else if (diferencia < 86400) {
            const horas = Math.floor(diferencia / 3600);
            return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
        } else if (diferencia < 604800) {
            const dias = Math.floor(diferencia / 86400);
            return `Hace ${dias} día${dias > 1 ? 's' : ''}`;
        } else {
            return fecha.toLocaleDateString('es-ES', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    // Función para actualizar automáticamente las estadísticas cada 5 minutos
    function iniciarActualizacionAutomatica() {
        // Cargar estadísticas al inicio
        cargarEstadisticas();
        
        // Configurar actualización automática cada 5 minutos
        setInterval(cargarEstadisticas, 300000); // 300000ms = 5 minutos
    }

    // Función para mostrar indicador de carga
    function mostrarIndicadorCarga() {
        const statsValues = document.querySelectorAll('.stat-value');
        statsValues.forEach(stat => {
            stat.innerHTML = '<span class="loading-spinner">⏳</span>';
        });
    }

    // Función para manejar errores de red
    function manejarErrorRed(error) {
        console.error('Error de red:', error);
        
        // Mostrar mensaje de error temporal
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.textContent = 'Error de conexión. Reintentando...';
        errorMessage.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff4444;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
        `;
        
        document.body.appendChild(errorMessage);
        
        // Remover mensaje después de 3 segundos
        setTimeout(() => {
            if (errorMessage.parentNode) {
                errorMessage.parentNode.removeChild(errorMessage);
            }
        }, 3000);
    }

    // Función mejorada para cargar estadísticas con manejo de errores
    function cargarEstadisticasMejorada() {
        mostrarIndicadorCarga();
        
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_dashboard_stats'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Actualizar estadísticas con animación
                actualizarEstadisticasConAnimacion(data.data);
                
                // Actualizar actividad reciente si hay datos
                if (data.data.actividad_reciente && data.data.actividad_reciente.length > 0) {
                    actualizarActividadReciente(data.data.actividad_reciente);
                }
            } else {
                console.error('Error al cargar estadísticas:', data.message);
                mostrarEstadisticasPorDefecto();
            }
        })
        .catch(error => {
            manejarErrorRed(error);
            mostrarEstadisticasPorDefecto();
        });
    }

    // Función para actualizar estadísticas con animación
    function actualizarEstadisticasConAnimacion(data) {
        const statsValues = document.querySelectorAll('.stat-value');
        if (statsValues.length >= 4) {
            const valores = [
                data.usuarios.toLocaleString(),
                data.servicios.toLocaleString(),
                data.turnos.toLocaleString(),
                `$${data.ingresos.toLocaleString()}`
            ];

            statsValues.forEach((stat, index) => {
                stat.style.opacity = '0.5';
                setTimeout(() => {
                    stat.textContent = valores[index];
                    stat.style.opacity = '1';
                }, 200);
            });
        }
    }

    // Función para refrescar manualmente las estadísticas
    function refrescarEstadisticas() {
        cargarEstadisticasMejorada();
    }

    // Agregar botón de refresh si existe
    const refreshButton = document.querySelector('.refresh-stats');
    if (refreshButton) {
        refreshButton.addEventListener('click', refrescarEstadisticas);
    }

    // Inicializar la aplicación
    iniciarActualizacionAutomatica();

    // Exponer función global para refresh manual
    window.refrescarDashboard = refrescarEstadisticas;
});