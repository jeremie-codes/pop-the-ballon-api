<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion Google</title>
</head>

<body>

    <div style="
        display:flex;
        align-items:center;
        justify-content:center;
        height:100vh;
        font-family:Arial,sans-serif;
    ">
        <p>Connexion en cours...</p>
    </div>

    <script>
        (function () {
            const result = {
                success: @json($success),
                code: @json($code),
                next: @json($next),
            };

            console.log('🔥 GOOGLE MOBILE RESULT:', result);

            if (
                window.ReactNativeWebView &&
                typeof window.ReactNativeWebView.postMessage === 'function'
            ) {
                window.ReactNativeWebView.postMessage(
                    JSON.stringify(result)
                );
            }
        })();
    </script>

</body>

</html>
