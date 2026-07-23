<x-guest-layout>
    {{-- Encabezado del formulario: comunica acceso seguro sin intervenir en la autenticación. --}}
    <div class="login-heading">
        <span>Acceso seguro</span>
        <h2>Bienvenido de nuevo</h2>
        <p>Ingresa tus datos para abrir el panel de MovilPhone.</p>
    </div>

    {{-- Muestra mensajes de recuperación o sesión enviados por los controladores de Laravel Breeze. --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- El formulario conserva route('login'), CSRF y los nombres email/password usados por LoginRequest. --}}
    <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf

        <label for="email">
            <span>Correo electrónico</span>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                    <path d="m3 7 9 6 9-6"></path>
                </svg>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="nombre@correo.com"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </label>

        <label for="password">
            <span>Contraseña</span>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                </svg>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Escribe tu contraseña"
                    required
                    autocomplete="current-password"
                >

                {{-- Alterna la visibilidad localmente; nunca modifica ni almacena el valor de la contraseña. --}}
                <button
                    type="button"
                    class="password-toggle"
                    id="password-toggle"
                    aria-label="Mostrar contraseña"
                    aria-pressed="false"
                    title="Mostrar contraseña"
                >
                    <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.1 2.8M6.7 6.7C3.6 8.5 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.3-.9M10 10a3 3 0 0 0 4 4"></path>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </label>

        <div class="login-options">
            {{-- Remember conserva el comportamiento nativo de Auth::attempt definido en LoginRequest. --}}
            <label class="remember" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Recordar mi sesión</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit" class="login-button">
            <span>Iniciar sesión</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"></path>
            </svg>
        </button>
    </form>

    <div class="login-help">
        <span aria-hidden="true"></span>
        <p>Acceso exclusivo para personal autorizado</p>
        <span aria-hidden="true"></span>
    </div>

    <style>
        /*
         * Formulario de acceso: reproduce la referencia visual y se conecta con LoginRequest.
         * Los estilos no cambian usuarios, hashes de contraseña, sesiones ni permisos.
         */
        .login-heading > span {
            color: #16a34a;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .login-heading h2 {
            margin: .55rem 0;
            color: #111827;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: 0;
        }
        .login-heading p {
            margin: 0 0 1.7rem;
            color: #64748b;
            font-size: 13px;
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }
        .login-form > label > span {
            display: block;
            margin-bottom: .4rem;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }
        .input-wrap { position: relative; }
        .input-wrap > svg {
            position: absolute;
            top: 50%;
            left: 13px;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            fill: none;
            stroke: #64748b;
            stroke-width: 1.7;
        }
        .input-wrap input {
            width: 100%;
            height: 48px;
            padding: 0 1rem 0 2.65rem;
            border: 1px solid #d9e0e7;
            border-radius: 10px;
            outline: none;
            background: #f8fafc;
            color: #111827;
            font: inherit;
            font-size: 13px;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .input-wrap:has(.password-toggle) input { padding-right: 3.15rem; }
        .input-wrap input:focus {
            border-color: #18181b;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, .06);
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }
        .password-toggle:hover {
            background: #e9edf2;
            color: #111;
        }
        .password-toggle:focus-visible {
            outline: 2px solid #111;
            outline-offset: 1px;
        }
        .password-toggle svg {
            position: static;
            width: 19px;
            height: 19px;
            transform: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
        }
        .password-toggle .eye-closed { display: none; }
        .password-toggle.is-visible .eye-open { display: none; }
        .password-toggle.is-visible .eye-closed { display: block; }
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 11px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: .45rem;
            color: #64748b;
        }
        .remember input {
            border-color: #cbd5e1;
            border-radius: 5px;
            color: #111;
        }
        .remember input:focus { --tw-ring-color: #111; }
        .login-options > a {
            color: #334155;
            font-weight: 600;
            text-decoration: none;
        }
        .login-options > a:hover { text-decoration: underline; }
        .login-button {
            height: 49px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            border: 0;
            border-radius: 10px;
            background: #090909;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .login-button:hover {
            transform: translateY(-1px);
            background: #202020;
            box-shadow: 0 9px 20px rgba(0, 0, 0, .15);
        }
        .login-button svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }
        .login-help {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 9px;
            letter-spacing: .08em;
            text-align: center;
            text-transform: uppercase;
        }
        .login-help span {
            height: 1px;
            flex: 1;
            background: #e5e7eb;
        }
        .login-help p {
            margin: 0;
            white-space: nowrap;
        }

        @media (max-width: 500px) {
            .login-options {
                align-items: flex-start;
                flex-direction: column;
            }
            .login-heading h2 { font-size: 25px; }
            .login-help p { white-space: normal; }
        }
    </style>

    <script>
        /*
         * Alterna entre contraseña visible y oculta sin enviar datos adicionales.
         * Se conecta exclusivamente con el campo password que procesa LoginRequest.
         */
        (() => {
            const input = document.getElementById('password');
            const button = document.getElementById('password-toggle');

            if (!input || !button) {
                return;
            }

            button.addEventListener('click', () => {
                const shouldShow = input.type === 'password';
                input.type = shouldShow ? 'text' : 'password';
                button.classList.toggle('is-visible', shouldShow);
                button.setAttribute('aria-pressed', String(shouldShow));
                button.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
                button.title = shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña';
                input.focus({ preventScroll: true });
            });
        })();
    </script>
</x-guest-layout>
