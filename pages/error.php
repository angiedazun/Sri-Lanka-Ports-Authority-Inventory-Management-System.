<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/slpasystem');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - SLPA System</title>
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
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .error-code {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .debug-info {
            margin-top: 30px;
            padding: 20px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
        }
        
        .debug-info h3 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .debug-info h4 {
            color: #856404;
            margin: 15px 0 10px;
            font-size: 16px;
        }
        
        .debug-info p {
            margin: 5px 0;
            color: #856404;
            word-break: break-all;
        }
        
        .debug-info pre {
            background: white;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        
        .support-info {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .support-info a {
            color: #667eea;
            text-decoration: none;
        }
        
        .support-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠</div>
        
        <h1>Oops! Something went wrong</h1>
        
        <div class="error-code">
            Error Code: <?php echo http_response_code(); ?>
        </div>
        
        <div class="error-message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        
        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            <a href="<?php echo constant('BASE_URL'); ?>" class="btn btn-primary">Go to Home</a>
        </div>
        
        <?php if (!empty($details)): ?>
            <?php echo $details; ?>
        <?php endif; ?>
        
        <div class="support-info">
            If the problem persists, please contact <a href="mailto:support@slpa.gov.lk">support@slpa.gov.lk</a>
        </div>
    </div>
</body>
</html>
