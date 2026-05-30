<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscriptions fermées - eSport-CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            padding: 50px 40px;
            text-align: center;
        }
        
        .icon {
            font-size: 100px;
            margin-bottom: 25px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .info-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 5px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .info-box h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-box p {
            color: #555;
            line-height: 1.7;
            font-size: 15px;
        }
        
        .info-box ul {
            margin-top: 15px;
            padding-left: 20px;
        }
        
        .info-box li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 8px;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 30px 0;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 13px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .icon {
                font-size: 80px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        
        <h1>Inscriptions actuellement fermées</h1>
        
        <p class="subtitle">
            Nous n'acceptons pas de nouvelles inscriptions pour le moment.
        </p>
        
        <div class="info-box">
            <h3>📋 Pourquoi les inscriptions sont-elles fermées ?</h3>
            <p>
                Les inscriptions peuvent être temporairement fermées pour plusieurs raisons :
            </p>
            <ul>
                <li>Maintenance de la plateforme</li>
                <li>Période de mise à jour du système</li>
                <li>Gestion de la capacité d'accueil</li>
                <li>Validation des comptes existants</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>💡 Que puis-je faire ?</h3>
            <p>
                Si vous avez déjà un compte, vous pouvez vous connecter normalement.
                Pour toute question, n'hésitez pas à contacter l'équipe d'administration.
            </p>
        </div>
        
        <div class="divider"></div>
        
        <div class="actions">
            <a href="login" class="btn btn-primary">
                🔑 Se connecter
            </a>
            <a href="/" class="btn btn-secondary">
                🏠 Retour à l'accueil
            </a>
        </div>
        
        <div class="footer">
            Les inscriptions seront rouvertes prochainement
        </div>
    </div>
<?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>
