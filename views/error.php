<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка - CRM.PROFTRANSFER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="/css/theme.css" rel="stylesheet">
    <link href="/css/modern.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 2rem;
        }
        
        .error-card {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .error-icon {
            font-size: 5rem;
            color: var(--danger-color);
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .error-code {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 2rem;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card card">
            <div class="card-body p-5">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h1 class="error-title">Что-то пошло не так</h1>
                
                <p class="error-message">
                    Произошла непредвиденная ошибка. Наша команда уже уведомлена и работает над устранением проблемы.
                </p>
                
                <div class="error-actions">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        На главную
                    </a>
                    <button onclick="location.reload()" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>
                        Попробовать снова
                    </button>
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Назад
                    </button>
                </div>
                
                <div class="error-code">
                    Error ID: <?php echo uniqid('err_'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/js/theme.js"></script>
</body>
</html>
