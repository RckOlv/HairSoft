 <script>
        // Animación del header al hacer scroll
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 10px 40px rgba(0, 0, 0, 0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.1)';
            }
        });

        //Función para seleccionar servicio

        function selectService(service, event) {
            console.log('Servicio seleccionado:', service);
            event.target.style.transform = 'scale(0.95)';
            setTimeout(() => {
                event.target.style.transform = '';
            }, 150);
        }

        // Efecto de click en la imagen de la peluquería
        document.getElementById('barbershopImage').addEventListener('click', () => {
            console.log('Imagen de peluquería clickeada - aquí puedes agregar navegación');
            // Efecto de pulso
            const img = document.getElementById('barbershopImage');
            img.style.transform = 'scale(1.02)';
            setTimeout(() => {
                img.style.transform = '';
            }, 200);
        });

        // Efecto parallax suave para elementos flotantes
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelectorAll('.floating-element');
            const speed = 0.5;

            parallax.forEach((element, index) => {
                const yPos = -(scrolled * speed * (index + 1) * 0.3);
                element.style.transform = `translate3d(0, ${yPos}px, 0)`;
            });
        });

        // Animación de entrada para las tarjetas de servicio
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplicar animación inicial y observador
        document.addEventListener('DOMContentLoaded', () => {
            const serviceCards = document.querySelectorAll('.service-card');
            serviceCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });