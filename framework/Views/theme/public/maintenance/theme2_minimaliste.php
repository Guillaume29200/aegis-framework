<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site en maintenance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            border: 2px solid #fff;
            padding: 40px;
            animation: fadeIn 1s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-align: center;
            animation: blink 2s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .message {
            text-align: center;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .divider {
            border-top: 1px solid #fff;
            margin: 30px 0;
        }
        
        .admin-section h2 {
            font-size: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            background: #000;
            border: 1px solid #fff;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            background: #111;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #000;
            border: none;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #000;
            color: #fff;
            border: 1px solid #fff;
        }
        
        .error {
            background: #fff;
            color: #000;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 12px;
            display: none;
        }
        
        .error.show { display: block; }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>[503]</h1>
        <div class="message">
            > SYSTEM MAINTENANCE IN PROGRESS_<br>
            > PLEASE WAIT...<br>
            > ESTIMATED TIME: UNKNOWN
        </div>
        
        <div class="divider"></div>
        
        <div class="admin-section">
            <h2>[ ADMIN ACCESS ]</h2>
            <div id="error-message" class="error"></div>
            
            <form id="admin-login-form" method="POST" action="<?= htmlspecialchars($loginAction ?? u('/auth/login')) ?>">
                <input type="hidden" name="maintenance_bypass" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="form-group">
                    <label>> USERNAME OR EMAIL:</label>
                    <input type="text" name="identifier" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label>> PASSWORD:</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn">[ LOGIN ]</button>
            </form>
        </div>
        
        <div class="footer">eSport-CMS V4 | <?= date('Y') ?></div>
    </div>
    
    <script>
        const form = document.getElementById('admin-login-form');
        const errorDiv = document.getElementById('error-message');
        const submitBtn = form.querySelector('.btn');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorDiv.classList.remove('show');
            submitBtn.disabled = true;
            submitBtn.textContent = '[ LOADING... ]';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    errorDiv.textContent = '> ACCESS GRANTED';
                    errorDiv.classList.add('show');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    errorDiv.textContent = '> ERROR: ' + data.error.toUpperCase();
                    errorDiv.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '[ LOGIN ]';
                }
            } catch (error) {
                errorDiv.textContent = '> CONNECTION ERROR';
                errorDiv.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = '[ LOGIN ]';
            }
        });
    </script>
</body>
</html>
