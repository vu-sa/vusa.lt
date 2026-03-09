<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prisijungimas...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #bd2835 0%, #8b1c28 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <p>Užbaigiamas prisijungimas...</p>
    </div>
    
    <script>
        (function() {
            const data = @json($data);
            
            if (window.opener) {
                // Send message to parent window
                window.opener.postMessage(data, window.location.origin);
                
                // Close this popup after a short delay
                setTimeout(function() {
                    window.close();
                }, 500);
            } else {
                // If no opener (popup was blocked or opened directly), redirect
                if (data.type === 'oauth-success') {
                    window.location.href = data.redirectUrl;
                } else {
                    window.location.href = '/login';
                }
            }
        })();
    </script>
</body>
</html>
