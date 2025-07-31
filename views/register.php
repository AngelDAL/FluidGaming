<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Puntos</title>
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
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .register-header p {
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

        .form-group input.error {
            border-color: #c33;
        }

        .form-group input.success {
            border-color: #3c3;
        }

        .form-group .help-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .form-group .error-text {
            font-size: 0.8rem;
            color: #c33;
            margin-top: 0.25rem;
        }

        .image-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid #e1e5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            overflow: hidden;
            background: #f8f9fa;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview .placeholder {
            color: #666;
            font-size: 0.8rem;
            text-align: center;
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

        .btn-secondary {
            background: #6c757d;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }

        .error-message ul {
            margin: 0;
            padding-left: 1rem;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
        }

        .login-link a:hover {
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

        .predefined-images {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .predefined-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid #e1e5e9;
            cursor: pointer;
            transition: border-color 0.3s;
            object-fit: cover;
        }

        .predefined-image:hover,
        .predefined-image.selected {
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Crear Cuenta</h1>
            <p>Únete para participar en torneos y ganar puntos</p>
        </div>

        <div id="message-container"></div>

        <form id="registerForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nickname">Nickname:</label>
                <input type="text" id="nickname" name="nickname" required>
                <div class="help-text">Entre 3 y 50 caracteres. Solo letras, números y guiones bajos.</div>
                <div id="nickname-error" class="error-text"></div>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <div id="email-error" class="error-text"></div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
                <div class="help-text">Mínimo 6 caracteres.</div>
                <div id="password-error" class="error-text"></div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirmar Contraseña:</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
                <div id="confirm-password-error" class="error-text"></div>
            </div>

            <div class="form-group">
                <label>Imagen de Perfil (Opcional):</label>
                <div class="image-preview" id="imagePreview">
                    <div class="placeholder">Sin imagen</div>
                </div>
                
                <input type="file" id="profile-image" name="profile_image" accept="image/*" style="display: none;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('profile-image').click()">
                    Subir Imagen
                </button>
                
                <div class="help-text">O selecciona una imagen predefinida:</div>
                <div class="predefined-images">
                    <img src="https://via.placeholder.com/60/FF6B6B/FFFFFF?text=1" class="predefined-image" data-image="1">
                    <img src="https://via.placeholder.com/60/4ECDC4/FFFFFF?text=2" class="predefined-image" data-image="2">
                    <img src="https://via.placeholder.com/60/45B7D1/FFFFFF?text=3" class="predefined-image" data-image="3">
                    <img src="https://via.placeholder.com/60/96CEB4/FFFFFF?text=4" class="predefined-image" data-image="4">
                    <img src="https://via.placeholder.com/60/FFEAA7/333333?text=5" class="predefined-image" data-image="5">
                    <img src="https://via.placeholder.com/60/DDA0DD/FFFFFF?text=6" class="predefined-image" data-image="6">
                    <img src="https://via.placeholder.com/60/98D8C8/FFFFFF?text=7" class="predefined-image" data-image="7">
                    <img src="https://via.placeholder.com/60/F7DC6F/333333?text=8" class="predefined-image" data-image="8">
                </div>
                <input type="hidden" id="selected-predefined" name="predefined_image">
            </div>

            <button type="submit" class="btn" id="registerBtn">
                Crear Cuenta
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                Creando cuenta...
            </div>
        </form>

        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="index.php?page=login">Inicia sesión aquí</a></p>
        </div>
    </div>

    <script>
        // Form validation
        const validators = {
            nickname: (value) => {
                if (value.length < 3) return 'El nickname debe tener al menos 3 caracteres';
                if (value.length > 50) return 'El nickname no puede tener más de 50 caracteres';
                if (!/^[a-zA-Z0-9_]+$/.test(value)) return 'Solo se permiten letras, números y guiones bajos';
                return '';
            },
            email: (value) => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) return 'Formato de email inválido';
                return '';
            },
            password: (value) => {
                if (value.length < 6) return 'La contraseña debe tener al menos 6 caracteres';
                return '';
            },
            confirmPassword: (value, password) => {
                if (value !== password) return 'Las contraseñas no coinciden';
                return '';
            }
        };

        // Real-time validation
        document.getElementById('nickname').addEventListener('blur', function() {
            const error = validators.nickname(this.value);
            showFieldError('nickname', error);
        });

        document.getElementById('email').addEventListener('blur', function() {
            const error = validators.email(this.value);
            showFieldError('email', error);
        });

        document.getElementById('password').addEventListener('blur', function() {
            const error = validators.password(this.value);
            showFieldError('password', error);
        });

        document.getElementById('confirm-password').addEventListener('blur', function() {
            const password = document.getElementById('password').value;
            const error = validators.confirmPassword(this.value, password);
            showFieldError('confirm-password', error);
        });

        function showFieldError(fieldName, error) {
            const field = document.getElementById(fieldName);
            const errorElement = document.getElementById(fieldName + '-error');
            
            if (error) {
                field.classList.add('error');
                field.classList.remove('success');
                errorElement.textContent = error;
            } else {
                field.classList.remove('error');
                field.classList.add('success');
                errorElement.textContent = '';
            }
        }

        // Image handling
        document.getElementById('profile-image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
                
                // Clear predefined selection
                document.querySelectorAll('.predefined-image').forEach(img => {
                    img.classList.remove('selected');
                });
                document.getElementById('selected-predefined').value = '';
            }
        });

        // Predefined images
        document.querySelectorAll('.predefined-image').forEach(img => {
            img.addEventListener('click', function() {
                // Clear file input
                document.getElementById('profile-image').value = '';
                
                // Update preview
                const preview = document.getElementById('imagePreview');
                preview.innerHTML = `<img src="${this.src}" alt="Preview">`;
                
                // Update selection
                document.querySelectorAll('.predefined-image').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selected-predefined').value = this.dataset.image;
            });
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const registerBtn = document.getElementById('registerBtn');
            const loading = document.getElementById('loading');
            const messageContainer = document.getElementById('message-container');
            
            // Validate form
            const nickname = document.getElementById('nickname').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            let hasErrors = false;
            
            ['nickname', 'email', 'password'].forEach(field => {
                const value = document.getElementById(field).value;
                const error = validators[field](value);
                if (error) {
                    showFieldError(field, error);
                    hasErrors = true;
                }
            });
            
            const confirmError = validators.confirmPassword(confirmPassword, password);
            if (confirmError) {
                showFieldError('confirm-password', confirmError);
                hasErrors = true;
            }
            
            if (hasErrors) return;
            
            // Clear previous messages
            messageContainer.innerHTML = '';
            
            // Show loading state
            registerBtn.disabled = true;
            loading.style.display = 'block';
            
            try {
                const response = await fetch('api/users.php?action=register', {
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
                    
                    // Redirect to login after short delay
                    setTimeout(() => {
                        window.location.href = 'index.php?page=login';
                    }, 2000);
                } else {
                    // Show error messages
                    let errorHtml = '<div class="error-message">';
                    if (Array.isArray(result.errors)) {
                        errorHtml += '<ul>';
                        result.errors.forEach(error => {
                            errorHtml += `<li>${error}</li>`;
                        });
                        errorHtml += '</ul>';
                    } else {
                        errorHtml += result.error || 'Error al crear la cuenta';
                    }
                    errorHtml += '</div>';
                    messageContainer.innerHTML = errorHtml;
                }
            } catch (error) {
                messageContainer.innerHTML = `
                    <div class="error-message">
                        Error de conexión. Por favor, intenta de nuevo.
                    </div>
                `;
            } finally {
                // Hide loading state
                registerBtn.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>