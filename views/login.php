<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Puntos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Iniciar Sesión</h1>
            <p>Accede a tu cuenta para participar en torneos</p>
        </div>

        <div id="message-container"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                Iniciar Sesión
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                Iniciando sesión...
            </div>
        </form>

        <div class="register-link">
            <p>¿No tienes cuenta? <a href="index.php?page=register">Regístrate aquí</a></p>
        </div>
    </div>

    <script>
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

        // Client-side validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#c33';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0 && password.length < 6) {
                this.style.borderColor = '#c33';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>