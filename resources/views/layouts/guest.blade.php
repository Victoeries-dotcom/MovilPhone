<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Acceso | MovilPhone</title>

    {{-- Figtree conserva la tipografía profesional del sistema y Vite carga los componentes de Laravel Breeze. --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    {{-- La composición divide identidad y acceso; el slot se conecta con login y las demás vistas públicas de Breeze. --}}
    <main class="auth-page">
        <section class="auth-brand" aria-label="Presentación de MovilPhone">
            <div class="auth-brand-content">
                {{-- El logotipo proviene del archivo de referencia y no participa en el proceso de autenticación. --}}
                <div class="auth-logo-lockup">
                    <img src="{{ asset('images/movilphone-logo-final.png') }}" alt="The Movil Phone Company">
                </div>

                <span>Sistema de taller</span>
                <h1>Tu taller,<br>organizado en un solo lugar.</h1>
                <p>Administra reparaciones, clientes y movimientos de caja de forma rápida y segura.</p>
            </div>

            <div class="auth-brand-footer">
                <i aria-hidden="true"></i>
                <span>Control · Orden · Confianza</span>
            </div>
        </section>

        <section class="auth-access" aria-label="Acceso al sistema">
            {{-- En móviles sustituye el panel lateral para mantener visible la marca sin reducir el formulario. --}}
            <div class="auth-mobile-brand">
                <img src="{{ asset('images/movilphone-logo-final.png') }}" alt="MovilPhone Company">
            </div>

            <div class="auth-card">{{ $slot }}</div>
            <p class="auth-copy">© {{ date('Y') }} MovilPhone · Sistema de administración del taller</p>
        </section>
    </main>

    <style>
        /*
         * Interfaz pública: se conecta con todas las vistas que usan x-guest-layout.
         * Solo modifica la presentación; los controladores, sesiones y credenciales permanecen intactos.
         */
        * { box-sizing: border-box; }
        body { margin: 0; }
        .auth-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(330px, 44%) 1fr;
            background: #f4f6f8;
            color: #111827;
        }
        .auth-brand {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(2rem, 5vw, 5rem);
            background: linear-gradient(145deg, #050505, #171717);
            color: #fff;
        }
        .auth-brand::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            top: -120px;
            right: -180px;
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 50%;
            box-shadow:
                0 0 0 70px rgba(255, 255, 255, .025),
                0 0 0 140px rgba(255, 255, 255, .018);
        }
        .auth-brand::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #dc2626 0 40%, #111 40% 72%, #16a34a 72%);
        }
        .auth-brand-content {
            position: relative;
            z-index: 1;
            max-width: 520px;
        }
        .auth-logo-lockup {
            position: relative;
            width: 190px;
            height: 94px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1.15rem;
            padding: 15px 17px 18px;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 13px;
            background: linear-gradient(145deg, #fff, #e9eaec);
            box-shadow: 0 12px 28px rgba(0, 0, 0, .3), inset 0 1px 0 #fff;
        }
        .auth-logo-lockup::after {
            content: "";
            position: absolute;
            right: 17px;
            bottom: 9px;
            left: 17px;
            height: 2px;
            border-radius: 99px;
            background: linear-gradient(90deg, #dc2626 0 42%, #171717 42% 72%, #16a34a 72%);
        }
        .auth-logo-lockup img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            transform: scale(2.35);
        }
        .auth-brand-content > span {
            color: #a1a1aa;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .2em;
            text-transform: uppercase;
        }
        .auth-brand h1 {
            margin: 1.3rem 0;
            font-size: clamp(34px, 4vw, 58px);
            font-weight: 500;
            line-height: 1.05;
            letter-spacing: 0;
        }
        .auth-brand p {
            max-width: 450px;
            color: #b8b8bd;
            font-size: 15px;
            line-height: 1.7;
        }
        .auth-brand-footer {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #a1a1aa;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .auth-brand-footer i {
            display: block;
            width: 36px;
            height: 2px;
            background: #dc2626;
        }
        .auth-access {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .auth-card {
            width: 100%;
            max-width: 480px;
            padding: clamp(1.5rem, 4vw, 2.5rem);
            border: 1px solid #e3e7ec;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 55px rgba(15, 23, 42, .09);
        }
        .auth-mobile-brand { display: none; }
        .auth-copy {
            position: absolute;
            bottom: 1.25rem;
            color: #94a3b8;
            font-size: 11px;
        }

        /* La respuesta móvil conserva formulario, marca y controles táctiles sin desplazamiento horizontal. */
        @media (max-width: 800px) {
            .auth-page { display: block; }
            .auth-brand { display: none; }
            .auth-access {
                min-height: 100vh;
                padding: 1.25rem;
            }
            .auth-mobile-brand {
                display: block;
                margin-bottom: 1rem;
            }
            .auth-mobile-brand img {
                width: 150px;
                height: 75px;
                object-fit: contain;
            }
            .auth-copy {
                position: static;
                margin-top: 1.25rem;
                text-align: center;
            }
        }
    </style>
</body>
</html>
