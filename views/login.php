<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FluidGaming</title>
    <link rel="stylesheet" href="views/stylesLogin.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Video Background -->
    <video class="video-background" autoplay muted loop>
        <source src="https://cdn.pixabay.com/vimeo/465808805/neon-glow-tunnel-27832.mp4?width=1920&hash=b0e6e75b9c1e7e6ce8bb9de9d1f28b7bf85b8b4e" type="video/mp4">
        <!-- Fallback video URL from Pexels -->
        <source src="https://player.vimeo.com/external/415232930.sd.mp4?s=38e9e8d1c94d8b3a8b4c0e5a3d5d9e4d" type="video/mp4">
    </video>
    
    <!-- Video Overlay for better contrast -->
    <div class="video-overlay"></div>

    <!-- Particle Effect Container -->
    <div class="particles" id="particles"></div>
    <div class="login-container">
        <div class="login-header">
            <h1>FluidGaming</h1>
            <p>Accede a tu cuenta para dominar los torneos</p>
        </div>

        <div id="message-container"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="jugador@fluidgaming.com" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                Iniciar Sesión
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                Conectando al servidor...
            </div>
        </form>

        <div class="register-link">
            <p>¿No tienes cuenta? <a href="index.php?page=register">Únete a la elite gamer</a></p>
        </div>
    </div>

    <script>
        // Create particle effect
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                
                // Random colors for particles
                const colors = ['#00ffff', '#ff0080', '#8000ff', '#00ff00'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.boxShadow = `0 0 6px ${particle.style.background}`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize particles when page loads
        window.addEventListener('load', createParticles);
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            const messageContainer = document.getElementById('message-container');
            
            // Clear previous messages
            messageContainer.innerHTML = '';
            
            // Show loading state
            loginBtn.disabled = true;
            loading.style.display = 'block';
            
            try {
                const response = await fetch('api/users.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    messageContainer.innerHTML = `
                        <div class="success-message">
                            ${result.message}
                        </div>
                    `;
                    
                    // Redirect to dashboard after short delay
                    setTimeout(() => {
                        window.location.href = 'index.php?page=dashboard';
                    }, 1500);
                } else {
                    // Show error message
                    messageContainer.innerHTML = `
                        <div class="error-message">
                            ${result.error || 'Error al iniciar sesión'}
                        </div>
                    `;
                }
            } catch (error) {
                messageContainer.innerHTML = `
                    <div class="error-message">
                        Error de conexión. Por favor, intenta de nuevo.
                    </div>
                `;
            } finally {
                // Hide loading state
                loginBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        // Client-side validation with gaming theme
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#ff6b6b';
                this.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.3)';
            } else {
                this.style.borderColor = 'rgba(0, 255, 255, 0.3)';
                this.style.boxShadow = 'inset 0 2px 4px rgba(0, 0, 0, 0.3)';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0 && password.length < 6) {
                this.style.borderColor = '#ff6b6b';
                this.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.3)';
            } else {
                this.style.borderColor = 'rgba(0, 255, 255, 0.3)';
                this.style.boxShadow = 'inset 0 2px 4px rgba(0, 0, 0, 0.3)';
            }
        });
    </script>
</body>
</html>