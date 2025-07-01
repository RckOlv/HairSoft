document.addEventListener('DOMContentLoaded', () => {
    const userRegistrationForm = document.getElementById('userRegistrationForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');

    userRegistrationForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evitar el envío por defecto del formulario

        // Validar que las contraseñas coincidan
        if (passwordInput.value !== confirmPasswordInput.value) {
            alert('Las contraseñas no coinciden. Por favor, verifíquelas.');
            return; // Detener el envío si no coinciden
        }
        // Validación básica de longitud de contraseña
        if (passwordInput.value.length < 6) {
            alert('La contraseña debe tener al menos 6 caracteres.');
            return;
        }

        // Recopilar los datos del formulario
        const userData = {
            action: 'register_client_user', // Acción para ajax_handler.php
            nombre: document.getElementById('first-name').value.trim(),
            apellido: document.getElementById('last-name').value.trim(),
            tipo_documento: document.getElementById('document-type').value,
            documento: document.getElementById('document-number').value.trim(),
            email: document.getElementById('email').value.trim(),
            telefono: document.getElementById('phone').value.trim(),
            password: passwordInput.value
        };

        try {
            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                userRegistrationForm.reset(); // Limpiar el formulario si fue exitoso
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
