<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 Server Maintenance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Orbitron', 'Arial Black', sans-serif;
            background: #0a0e27;
            color: #00ff41;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 255, 65, 0.03) 2px, rgba(0, 255, 65, 0.03) 4px);
            pointer-events: none;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(10, 14, 39, 0.9);
            border: 2px solid #00ff41;
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.3), inset 0 0 30px rgba(0, 255, 65, 0.1);
            padding: 50px 40px;
            position: relative;
            animation: glowPulse 2s ease-in-out infinite;
        }
        
        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 255, 65, 0.3), inset 0 0 30px rgba(0, 255, 65, 0.1); }
            50% { box-shadow: 0 0 50px rgba(0, 255, 65, 0.5), inset 0 0 50px rgba(0, 255, 65, 0.2); }
        }
        
        .corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid #ff0080;
        }
        
        .corner.tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .corner.tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .corner.bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .corner.br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
        
        .icon {
            font-size: 80px;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 0 20px #00ff41;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            font-size: 36px;
            text-align: center;
            margin-bottom: 10px;
            text-shadow: 0 0 10px #00ff41, 0 0 20px #00ff41;
            letter-spacing: 3px;
        }
        
        .status {
            text-align: center;
            color: #ff0080;
            font-size: 14px;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #ff0080;
        }
        
        .message {
            text-align: center;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 14px;
            color: #00d4aa;
        }
        
        .scanline {
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff41, transparent);
            margin: 30px 0;
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .admin-section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            color: #ff0080;
            text-shadow: 0 0 10px #ff0080;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            color: #00d4aa;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 20, 40, 0.8);
            border: 1px solid #00ff41;
            color: #00ff41;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff0080;
            box-shadow: 0 0 15px rgba(255, 0, 128, 0.5);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #00ff41, #00d4aa);
            color: #0a0e27;
            border: none;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .btn:hover {
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.8);
            transform: translateY(-2px);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .error {
            background: rgba(255, 0, 128, 0.2);
            border: 1px solid #ff0080;
            color: #ff0080;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 12px;
            display: none;
        }
        
        .error.show { display: block; }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #00d4aa;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="corner tl"></div>
        <div class="corner tr"></div>
        <div class="corner bl"></div>
        <div class="corner br"></div>
        
        <div class="icon">🎮</div>
        <h1>SERVER OFFLINE</h1>
        <div class="status">[ MAINTENANCE MODE ACTIVE ]</div>
        
        <div class="message">
            System update in progress...<br>
            We're upgrading the experience.<br>
            Please stand by for reconnection.
        </div>
        
        <div class="scanline"></div>
        
        <div class="admin-section">
            <h2>[ ADMIN OVERRIDE ]</h2>
            <div id="error-message" class="error"></div>
            
            <form id="admin-login-form" method="POST" action="<?= htmlspecialchars($loginAction ?? u('/auth/login')) ?>">
                <input type="hidden" name="maintenance_bypass" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="form-group">
                    <label>► USERNAME / EMAIL</label>
                    <input type="text" name="identifier" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label>► PASSWORD</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn">ACCESS SYSTEM</button>
            </form>
        </div>
        
        <div class="footer">Aegis Framework V4 | CYBERSPACE <?= date('Y') ?></div>
    </div>
    
    <script>
        const form = document.getElementById('admin-login-form');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = form.querySelector('.btn');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorDiv.classList.remove('show');
            submitBtn.disabled = true;
            submitBtn.textContent = 'CONNECTING...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    errorDiv.textContent = '[ ACCESS GRANTED ] - ' + data.message;
                    errorDiv.style.background = 'rgba(0, 255, 65, 0.2)';
                    errorDiv.style.borderColor = '#00ff41';
                    errorDiv.style.color = '#00ff41';
                    errorDiv.classList.add('show');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    errorDiv.textContent = '[ ACCESS DENIED ] - ' + data.error;
                    errorDiv.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'ACCESS SYSTEM';
                }
            } catch (error) {
                errorDiv.textContent = '[ CONNECTION ERROR ] - System failure';
                errorDiv.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = 'ACCESS SYSTEM';
            }
        });
    </script>
</body>
</html>
