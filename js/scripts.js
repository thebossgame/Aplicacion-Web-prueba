
// CARRUSEL - Solo para index.html
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en index.html (busca el carrusel)
    if (document.querySelector('.carousel')) {
        let currentSlide = 0;
        const slidesContainer = document.getElementById('slides');
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;

        function showSlide(index) {
            slidesContainer.style.transform = `translateX(-${index * 100}%)`;
            document.querySelectorAll('.dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }

        window.changeSlide = function(direction) {
            currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Inicializar dots
        const dotsContainer = document.getElementById('dots');
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('span');
            dot.classList.add('dot');
            dot.onclick = () => showSlide(i);
            dotsContainer.appendChild(dot);
        }
        showSlide(0);

        // Auto-slide cada 5 segundos
        setInterval(() => changeSlide(1), 5000);
    }

    // FORMULARIOS - Para páginas de registro/login
    const esRegistro = document.getElementById('registroForm') !== null;
    if (esRegistro) {
        const form = document.getElementById('registroForm');
        const mensaje = document.getElementById('mensaje');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const datos = {
                nombre: document.getElementById('nombre').value,
                apellido: document.getElementById('apellido').value,
                cedula: document.getElementById('cedula').value
            };
            
            localStorage.setItem('usuarios', JSON.stringify([...(JSON.parse(localStorage.getItem('usuarios') || '[]')), datos]));
            
            mensaje.textContent = `¡Usuario registrado: ${datos.nombre} ${datos.apellido}!`;
            mensaje.className = 'exito';
            mensaje.style.display = 'block';
            form.reset();
            
            setTimeout(() => { window.location.href = '../index.html'; }, 2000);
        });
    }
    
    const esLogin = document.getElementById('loginForm') !== null;
    if (esLogin) {
        const form = document.getElementById('loginForm');
        const mensaje = document.getElementById('mensaje');

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const cedula = document.getElementById('cedulaLogin').value;
            const usuarios = JSON.parse(localStorage.getItem('usuarios') || '[]');
            const usuario = usuarios.find(u => u.cedula === cedula);

            if (usuario) {
                localStorage.setItem('usuarioActual', JSON.stringify(usuario));
                mensaje.textContent = `¡Bienvenido, ${usuario.nombre} ${usuario.apellido}!`;
                mensaje.className = 'exito';
                mensaje.style.display = 'block';
                setTimeout(() => { window.location.href = '../index.html'; }, 2000);
            } else {
                mensaje.textContent = 'Usuario no encontrado.';
                mensaje.className = 'error';
                mensaje.style.display = 'block';
            }
        });
    }
});
