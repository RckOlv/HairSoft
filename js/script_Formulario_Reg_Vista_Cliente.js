document.addEventListener('DOMContentLoaded', () => {
    const userRegistrationForm = document.getElementById('userRegistrationForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');

    userRegistrationForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Recopilar valores para validación
        const nombre = document.getElementById('first-name').value.trim();
        const apellido = document.getElementById('last-name').value.trim();
        const tipoDocumento = document.getElementById('document-type').value;
        const documento = document.getElementById('document-number').value.trim();
        const email = document.getElementById('email').value.trim();
        const telefono = document.getElementById('phone').value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Validaciones

        // Nombre y apellido: obligatorio, solo letras y espacios, 2-50 caracteres
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/;
        if (!nameRegex.test(nombre)) {
            alert('Ingrese un nombre válido (solo letras, 2-50 caracteres).');
            return;
        }
        if (!nameRegex.test(apellido)) {
            alert('Ingrese un apellido válido (solo letras, 2-50 caracteres).');
            return;
        }

        // Tipo de documento: obligatorio (no vacío)
        if (!tipoDocumento) {
            alert('Seleccione un tipo de documento.');
            return;
        }

        // Documento: obligatorio, solo números, entre 6 y 15 dígitos (ajustar según necesidad)
        const documentoRegex = /^\d{6,15}$/;
        if (!documentoRegex.test(documento)) {
            alert('Ingrese un número de documento válido (solo números, 6-15 dígitos).');
            return;
        }

        // Email: obligatorio y formato válido básico
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Ingrese un correo electrónico válido.');
            return;
        }

        // Teléfono: opcional, pero si se ingresa debe ser números entre 7 y 15 dígitos
        if (telefono) {
            const telefonoRegex = /^\d{7,15}$/;
            if (!telefonoRegex.test(telefono)) {
                alert('Ingrese un número de teléfono válido (solo números, 7-15 dígitos).');
                return;
            }
        }

        // Contraseña: mínimo 6 caracteres
        if (password.length < 6) {
            alert('La contraseña debe tener al menos 6 caracteres.');
            return;
        }

        // Confirmar contraseña igual a contraseña
        if (password !== confirmPassword) {
            alert('Las contraseñas no coinciden. Por favor, verifíquelas.');
            return;
        }

        // Si pasa todas las validaciones, hacer fetch para enviar datos al backend
        const userData = {
            action: 'register_client_user',
            nombre,
            apellido,
            tipo_documento: tipoDocumento,
            documento,
            email,
            telefono,
            password
        };

        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                userRegistrationForm.reset();
                window.location.href = 'login.php';
            } else {
                alert(result.message || 'Error al registrar usuario');
            }
        } catch (error) {
            alert('Error de conexión. Intente nuevamente más tarde.');
            console.error('Error al registrar usuario:', error);
        }
    });
});
