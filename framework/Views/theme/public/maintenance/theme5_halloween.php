<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎃 Maintenance - Joyeux Halloween !</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Creepster', 'Courier New', monospace;
            background: linear-gradient(180deg, #1a0a00 0%, #0a0500 100%);
            padding: 40px 20px;
            position: relative;
            min-height: 100vh;
        }
        
        /* Chauves-souris volantes */
        .bat {
            position: fixed;
            font-size: 2em;
            animation: fly 15s linear infinite;
            opacity: 0.6;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes fly {
            0% { transform: translateX(-100px) translateY(0); }
            25% { transform: translateX(25vw) translateY(-50px); }
            50% { transform: translateX(50vw) translateY(0); }
            75% { transform: translateX(75vw) translateY(-30px); }
            100% { transform: translateX(calc(100vw + 100px)) translateY(0); }
        }
        
        /* Toiles d'araignée */
        .web {
            position: absolute;
            top: 0;
            opacity: 0.3;
        }
        
        .web.left { left: 0; font-size: 100px; }
        .web.right { right: 0; font-size: 100px; }
        
        .container {
            background: linear-gradient(135deg, #ff6b00 0%, #1a0a00 100%);
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(255, 107, 0, 0.5);
            max-width: 650px;
            width: 100%;
            margin: 0 auto;
            padding: 60px 50px;
            text-align: center;
            position: relative;
            z-index: 10;
            border: 5px solid #ff6b00;
            animation: spookyGlow 3s ease-in-out infinite;
        }
        
        @keyframes spookyGlow {
            0%, 100% { box-shadow: 0 30px 80px rgba(255, 107, 0, 0.3); }
            50% { box-shadow: 0 30px 80px rgba(255, 107, 0, 0.8); }
        }
        
        /* Citrouilles décoratives */
        .pumpkin {
            position: absolute;
            font-size: 40px;
            animation: float 4s ease-in-out infinite;
        }
        
        .pumpkin.left { left: -20px; top: 50%; }
        .pumpkin.right { right: -20px; top: 50%; animation-delay: 2s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }
        
        .icon {
            font-size: 120px;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px #ff6b00);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h1 {
            font-size: 48px;
            color: #ff6b00;
            margin-bottom: 15px;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8);
            font-weight: bold;
            letter-spacing: 3px;
        }
        
        .subtitle {
            font-size: 22px;
            color: #fff;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        
        .message {
            background: rgba(0, 0, 0, 0.7);
            border: 3px dashed #ff6b00;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            color: #fff;
            line-height: 1.8;
        }
        
        .message strong {
            color: #ff6b00;
            font-size: 20px;
            display: block;
            margin-bottom: 10px;
        }
        
        .divider {
            height: 3px;
            background: linear-gradient(90deg, transparent, #ff6b00, transparent);
            margin: 30px 0;
        }
        
        .admin-section {
            background: rgba(0, 0, 0, 0.8);
            border: 3px solid #ff6b00;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .admin-section h2 {
            color: #ff6b00;
            font-size: 24px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            color: #ff6b00;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ff6b00;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.7);
            box-shadow: 0 0 20px rgba(255, 107, 0, 0.5);
        }
        
        .form-group input::placeholder {
            color: #888;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b00 0%, #d45500 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            text-shadow: 0 0 5px #fff;
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(255, 107, 0, 0.6);
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff6b00;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }
        
        .error.show { display: block; }
        
        .footer {
            margin-top: 30px;
            color: #ff6b00;
            font-size: 14px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        
        /* Fantômes flottants */
        .ghost {
            position: absolute;
            font-size: 50px;
            opacity: 0.3;
            animation: ghostFloat 5s ease-in-out infinite;
        }
        
        .ghost.g1 { top: 10%; right: 10%; }
        .ghost.g2 { bottom: 20%; left: 5%; animation-delay: 2s; }
        
        @keyframes ghostFloat {
            0%, 100% { transform: translateY(0) rotate(5deg); }
            50% { transform: translateY(-30px) rotate(-5deg); }
        }
    </style>
</head>
<body>
    <!-- Chauves-souris -->
    <script>
        // Créer des chauves-souris
        for (let i = 0; i < 5; i++) {
            const bat = document.createElement('div');
            bat.className = 'bat';
            bat.textContent = '🦇';
            bat.style.top = Math.random() * 80 + '%';
            bat.style.animationDelay = Math.random() * 5 + 's';
            bat.style.animationDuration = (Math.random() * 10 + 10) + 's';
            document.body.appendChild(bat);
        }
    </script>
    
    <!-- Toiles d'araignée -->
    <div class="web left">🕸️</div>
    <div class="web right">🕸️</div>
    
    <!-- Fantômes -->
    <div class="ghost g1">👻</div>
    <div class="ghost g2">👻</div>
    
    <div class="container">
        <div class="pumpkin left">🎃</div>
        <div class="pumpkin right">🎃</div>
        
        <div class="icon">🎃</div>
        <h1>BOO!</h1>
        <div class="subtitle">👻 Site hanté... Maintenance en cours 🕷️</div>
        
        <div class="message">
            <strong>🎃 Travaux mystérieux en cours</strong>
            <p>
                Nos développeurs préparent quelque chose d'effrayant...<br>
                Le site ressuscitera très bientôt avec de nouvelles fonctionnalités !<br>
                En attendant, joyeux Halloween ! 👻🕷️🦇
            </p>
        </div>
        
        <div class="divider"></div>
        
        <div class="admin-section">
            <h2>🧙 Accès des Sorciers</h2>
            <div id="error-message" class="error"></div>
            
            <form id="admin-login-form" method="POST" action="<?= htmlspecialchars($loginAction ?? u('/auth/login')) ?>">
                <input type="hidden" name="maintenance_bypass" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="form-group">
                    <label>🦇 Identifiant Magique</label>
                    <input type="text" name="identifier" required autocomplete="username" placeholder="Nom d'utilisateur ou email">
                </div>
                
                <div class="form-group">
                    <label>🔮 Formule Secrète</label>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn">🎃 ENTRER</button>
            </form>
        </div>
        
        <div class="footer">
            🕷️ eSport-CMS V4 | Happy Halloween <?= date('Y') ?> 🦇
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
            submitBtn.textContent = '👻 Connexion...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    errorDiv.textContent = '🎃 ' + data.message;
                    errorDiv.style.background = 'rgba(255, 107, 0, 0.2)';
                    errorDiv.style.borderColor = '#ff6b00';
                    errorDiv.classList.add('show');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    errorDiv.textContent = '👻 ' + data.error;
                    errorDiv.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🎃 ENTRER';
                }
            } catch (error) {
                errorDiv.textContent = '💀 Une erreur terrifiante est survenue';
                errorDiv.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = '🎃 ENTRER';
            }
        });
    </script>
</body>
</html>
