<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            {{-- El contenedor conecta el botón visual con el campo password sin modificar las credenciales enviadas a LoginRequest. --}}
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-12"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <button type="button"
                        id="toggle-password"
                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-gray-500 transition hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                        title="Mostrar contraseña">
                    <i data-lucide="eye" class="h-5 w-5" aria-hidden="true"></i>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    {{-- Lucide dibuja el icono del botón y el script alterna de forma local entre contraseña oculta y visible. --}}
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const password = document.getElementById('password');
            const toggle = document.getElementById('toggle-password');

            function renderPasswordToggle(visible) {
                // Actualiza icono, ayuda emergente y estado accesible sin cambiar el valor escrito.
                toggle.innerHTML = `<i data-lucide="${visible ? 'eye-off' : 'eye'}" class="h-5 w-5" aria-hidden="true"></i>`;
                toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
                toggle.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
                window.lucide?.createIcons();
            }

            toggle.addEventListener('click', function () {
                const visible = password.type === 'password';
                password.type = visible ? 'text' : 'password';
                renderPasswordToggle(visible);
                password.focus();
            });

            renderPasswordToggle(false);
        });
    </script>
</x-guest-layout>
