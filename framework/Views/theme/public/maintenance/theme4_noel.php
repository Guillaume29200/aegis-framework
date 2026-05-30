<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎄 Joyeuses Fêtes !</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Georgia', serif;
            background: linear-gradient(180deg, #0f4d2e 0%, #1a1a2e 100%);
            padding: 40px 20px;
            position: relative;
            min-height: 100vh;
        }
        
        /* Flocons de neige */
        .snowflake {
            position: fixed;
            top: -10px;
            color: #fff;
            font-size: 1.2em;
            animation: fall linear infinite;
            opacity: 0.7;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes fall {
            to { transform: translateY(105vh); }
        }
        
        .container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 50px 45px;
            text-align: center;
            position: relative;
            z-index: 10;
            border: 8px solid;
            border-image: linear-gradient(45deg, #c41e3a, #165b33, #ffd700, #c41e3a) 1;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .icon {
            font-size: 90px;
            margin-bottom: 15px;
            display: inline-block;
            animation: treeGlow 2s ease-in-out infinite;
            filter: drop-shadow(0 0 15px rgba(22, 91, 51, 0.5));
        }
        
        @keyframes treeGlow {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 15px rgba(22, 91, 51, 0.5)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 25px rgba(196, 30, 58, 0.7)); }
        }
        
        h1 {
            font-size: 40px;
            color: #c41e3a;
            margin-bottom: 10px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .subtitle {
            font-size: 18px;
            color: #165b33;
            font-style: italic;
        }
        
        .stars {
            font-size: 24px;
            margin: 20px 0;
        }
        
        .message {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 5px solid #c41e3a;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .message strong {
            color: #c41e3a;
            font-size: 18px;
            display: block;
            margin-bottom: 12px;
        }
        
        .message p {
            color: #495057;
            line-height: 1.7;
            font-size: 15px;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #c41e3a, #165b33, #ffd700, transparent);
            margin: 30px 0;
        }
        
        .admin-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border: 3px solid #165b33;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-section h2 {
            color: #165b33;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            color: #495057;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c41e3a;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c41e3a 0%, #165b33 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(196, 30, 58, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(196, 30, 58, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            border: 2px solid #c41e3a;
            color: #c41e3a;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }
        
        .error.show { display: block; }
        
        .footer {
            margin-top: 30px;
            color: #6c757d;
            font-size: 13px;
            font-style: italic;
        }
        
        /* Décorations */
        .candy-cane {
            position: absolute;
            font-size: 50px;
            opacity: 0.3;
        }
        
        .candy-cane.left { left: -25px; top: 40%; transform: rotate(-20deg); }
        .candy-cane.right { right: -25px; top: 40%; transform: rotate(20deg); }
    </style>
</head>
<body>
    <!-- Flocons de neige -->
    <script>
        for (let i = 0; i < 40; i++) {
            const snowflake = document.createElement('div');
            snowflake.className = 'snowflake';
            snowflake.textContent = '❄';
            snowflake.style.left = Math.random() * 100 + '%';
            snowflake.style.animationDuration = (Math.random() * 3 + 3) + 's';
            snowflake.style.animationDelay = Math.random() * 5 + 's';
            snowflake.style.fontSize = (Math.random() * 0.8 + 0.5) + 'em';
            document.body.appendChild(snowflake);
        }
    </script>
    
    <div class="container">
        <div class="candy-cane left">🍬</div>
        <div class="candy-cane right">🍬</div>
        
        <div class="header">
            <div class="icon">🎄</div>
            <h1>Joyeuses Fêtes !</h1>
            <p class="subtitle">Maintenance en cours...</p>
            <div class="stars">⭐ ✨ ⭐</div>
        </div>
        
        <div class="message">
            <strong>🎁 Préparatifs en cours</strong>
            <p>
                Notre équipe prépare de belles surprises pour vous !<br>
                Le site sera de retour très bientôt, encore plus festif.<br>
                Nous vous souhaitons de merveilleuses fêtes de fin d'année ! ⛄
            </p>
        </div>
        
        <div class="divider"></div>
        
        <div class="admin-section">
            <h2>🔐 Accès Administrateur</h2>
            <div id="error-message" class="error"></div>
            
            <form id="admin-login-form" method="POST" action="<?= htmlspecialchars($loginAction ?? u('/auth/login')) ?>">
                <input type="hidden" name="maintenance_bypass" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="form-group">
                    <label>👤 Identifiant</label>
                    <input type="text" name="identifier" required autocomplete="username" placeholder="Nom d'utilisateur ou email">
                </div>
                
                <div class="form-group">
                    <label>🔑 Mot de passe</label>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn">🎄 Se connecter</button>
            </form>
        </div>
        
        <div class="footer">
            🎅 eSport-CMS V4 | Joyeuses Fêtes <?= date('Y') ?> 🎁
        </div>
    </div>
    
    <script>
        const form = document.getElementById('admin-login-form');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = form.querySelector('.btn');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorDiv.classList.remove('show');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Connexion...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    errorDiv.textContent = '✅ ' + data.message;
                    errorDiv.style.background = '#d4edda';
                    errorDiv.style.borderColor = '#165b33';
                    errorDiv.style.color = '#165b33';
                    errorDiv.classList.add('show');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    errorDiv.textContent = '❌ ' + data.error;
                    errorDiv.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🎄 Se connecter';
                }
            } catch (error) {
                errorDiv.textContent = '❌ Une erreur est survenue';
                errorDiv.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = '🎄 Se connecter';
            }
        });
    </script>
</body>
</html>
