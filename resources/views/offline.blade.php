<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MovilPhone · Sin conexion</title>
    <link rel="stylesheet" href="/css/movilphone-ui.css">
</head>
<body class="offline-page">
    {{-- Estado offline: lo entrega el service worker cuando el servidor no esta disponible. --}}
    <main class="offline-panel">
        <span class="offline-icon">MP</span>
        <h1>Sin conexion con MovilPhone</h1>
        <p>Revisa la red o confirma que Laragon este encendido. Tus datos guardados permanecen en el servidor.</p>
        <button type="button" class="btn btn-primary" onclick="location.reload()">Intentar nuevamente</button>
    </main>
</body>
</html>
