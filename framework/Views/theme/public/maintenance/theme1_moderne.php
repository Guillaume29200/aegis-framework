<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Maintenance - eSport-CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .maintenance-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .maintenance-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: rotate 2s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
            border-radius: 8px;
        }
        
        .info-box strong {
            color: #667eea;
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box p {
            color: #555;
            line-height: 1.6;
            margin: 0;
        }
        
        .admin-login {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #e9ecef;
        }
        
        .admin-login h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }
        
        .error-message.show { display: block; }
        
        .footer {
            margin-top: 40px;
            color: #999;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .maintenance-container { padding: 40px 25px; }
            h1 { font-size: 28px; }
            .maintenance-icon { font-size: 80px; }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Maintenance en cours</h1>
        <p class="subtitle">
            Notre site est actuellement en maintenance pour des améliorations.<br>
            Nous serons de retour très bientôt !
        </p>
        
        <div class="info-box">
            <strong>🕐 Pourquoi cette maintenance ?</strong>
            <p>
                Nous effectuons des mises à jour importantes pour améliorer votre expérience.
                Le site sera de nouveau accessible dans quelques instants.
            </p>
        </div>
        
        <div class="admin-login">
            <h2>🔐 Accès administrateur</h2>
            <div id="error-message" class="error-message"></div>
            
            <form id="admin-login-form" method="POST" action="<?= htmlspecialchars($loginAction ?? u('/auth/login')) ?>">
                <input type="hidden" name="maintenance_bypass" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="form-group">
                    <label for="identifier">Nom d'utilisateur ou Email</label>
                    <input type="text" id="identifier" name="identifier" required 
                           placeholder="admin" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="••••••••" autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn-login">Se connecter</button>
            </form>
        </div>
        
        <div class="footer">
            <p>eSport-CMS V4 &copy; <?= date('Y') ?></p>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('admin-login-form');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = form.querySelector('.btn-login');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorDiv.classList.remove('show');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Connexion en cours...';
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    errorDiv.textContent = '✅ ' + data.message;
                    errorDiv.style.background = '#d4edda';
                    errorDiv.style.color = '#155724';
                    errorDiv.classList.add('show');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    errorDiv.textContent = '❌ ' + data.error;
                    errorDiv.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                }
            } catch (error) {
                errorDiv.textContent = '❌ Une erreur est survenue';
                errorDiv.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Se connecter';
            }
        });
    </script>
</body>
</html>
