<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Alerta de seguridad</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:600px;margin:30px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <div style="padding:22px 28px;background:#0f1f3d;color:#fff;">
            <h1 style="margin:0;font-size:20px;">Alerta de seguridad</h1>
        </div>
        <div style="padding:28px;line-height:1.6;">
            <p>MovilPhone bloqueó temporalmente un intento de acceso después de recibir <strong>3 contraseñas incorrectas</strong>.</p>
            <table style="width:100%;border-collapse:collapse;margin:20px 0;">
                <tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;"><strong>Cuenta</strong></td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">{{ $loginEmail }}</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;"><strong>Fecha y hora</strong></td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">{{ $occurredAt->format('d/m/Y H:i:s') }}</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;"><strong>Dirección IP</strong></td><td style="padding:8px;border-bottom:1px solid #e2e8f0;">{{ $ipAddress }}</td></tr>
            </table>
            <p>La cuenta permanecerá bloqueada durante 5 minutos. Si reconoces el intento, espera ese tiempo antes de volver a ingresar.</p>
            <p><strong>Si no fuiste tú, cambia tu contraseña y comunícate con el administrador.</strong></p>
        </div>
    </div>
</body>
</html>
