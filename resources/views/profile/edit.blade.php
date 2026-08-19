@extends('layout')

@section('content')
@php
    /* Presenta el rol real con una etiqueta legible sin alterar sus permisos ni su valor en la base de datos. */
    $rolPerfil = match ($user->rol) {
        'superusuario' => 'Super Usuario',
        'capturista' => 'Capturista',
        'vendedor' => 'Vendedor',
        'tecnico' => 'Técnico',
        default => 'Usuario',
    };
    $inicialesPerfil = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
        ->implode('');
@endphp

<div class="profile-page">
    {{-- Cabecera: conecta esta cuenta con el mismo lenguaje visual y navegación del resto del sistema. --}}
    <div class="page-header profile-page-header">
        <div>
            <span class="profile-page-eyebrow">CUENTA PERSONAL</span>
            <h1>Mi perfil</h1>
            <p class="page-title-sub">Administra tus datos personales y la seguridad de tu acceso.</p>
        </div>
        <a href="{{ route('home') }}" class="btn"><i data-lucide="arrow-left"></i><span>Volver al panel</span></a>
    </div>

    {{-- Resumen: confirma visualmente qué cuenta y sucursal se están editando. --}}
    <section class="profile-overview" aria-label="Resumen de la cuenta">
        <span class="profile-overview-avatar" aria-hidden="true">{{ $inicialesPerfil ?: 'MP' }}</span>
        <div class="profile-overview-copy">
            <span>PERFIL ACTIVO</span>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
        </div>
        <div class="profile-overview-meta">
            <span><i data-lucide="shield-check"></i>{{ $rolPerfil }}</span>
            <span><i data-lucide="map-pin"></i>{{ session('sucursal_nombre') ?? $user->sucursal?->nombre ?? 'Sin sucursal' }}</span>
        </div>
    </section>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success" role="status"><i data-lucide="circle-check"></i><span>Los datos del perfil se actualizaron correctamente.</span></div>
    @elseif(session('status') === 'password-updated')
        <div class="alert alert-success" role="status"><i data-lucide="shield-check"></i><span>La contraseña se actualizó correctamente.</span></div>
    @endif

    <div class="profile-settings-grid">
        {{-- Datos personales: conserva ProfileUpdateRequest, validaciones y verificación de correo existentes. --}}
        <section class="profile-settings-card">
            <header class="profile-settings-header">
                <span class="profile-settings-icon"><i data-lucide="contact-round"></i></span>
                <div><span>INFORMACIÓN</span><h2>Datos personales</h2><p>Nombre y correo utilizados para identificar tu cuenta.</p></div>
            </header>
            <form method="POST" action="{{ route('profile.update') }}" class="profile-settings-form">
                @csrf
                @method('patch')

                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')<span class="profile-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email')<span class="profile-field-error">{{ $message }}</span>@enderror
                </div>

                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                    <div class="profile-verification-note">
                        <i data-lucide="mail-warning"></i>
                        <div><strong>Correo pendiente de verificación</strong><p>Solicita un enlace nuevo para confirmar esta dirección.</p></div>
                        <button type="submit" form="profile-verification-form" class="btn btn-sm">Reenviar enlace</button>
                    </div>
                @endif

                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i><span>Guardar cambios</span></button>
                </div>
            </form>
            <form id="profile-verification-form" method="POST" action="{{ route('verification.send') }}">@csrf</form>
        </section>

        {{-- Seguridad: usa la ruta password.update y mantiene los errores en su bolsa independiente. --}}
        <section class="profile-settings-card">
            <header class="profile-settings-header">
                <span class="profile-settings-icon"><i data-lucide="key-round"></i></span>
                <div><span>SEGURIDAD</span><h2>Cambiar contraseña</h2><p>Utiliza una contraseña privada, extensa y difícil de adivinar.</p></div>
            </header>
            <form method="POST" action="{{ route('password.update') }}" class="profile-settings-form">
                @csrf
                @method('put')

                <div class="form-group">
                    <label for="update_password_current_password">Contraseña actual</label>
                    <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
                    @error('current_password', 'updatePassword')<span class="profile-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="update_password_password">Nueva contraseña</label>
                    <input id="update_password_password" name="password" type="password" autocomplete="new-password">
                    @error('password', 'updatePassword')<span class="profile-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="update_password_password_confirmation">Confirmar nueva contraseña</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword')<span class="profile-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary"><i data-lucide="shield-check"></i><span>Actualizar contraseña</span></button>
                </div>
            </form>
        </section>
    </div>

    {{-- Zona sensible: solicita la contraseña y mantiene la doble confirmación global antes de eliminar. --}}
    <section class="profile-danger-card">
        <div class="profile-danger-copy">
            <span class="profile-danger-icon"><i data-lucide="triangle-alert"></i></span>
            <div><span>ZONA SENSIBLE</span><h2>Eliminar mi cuenta</h2><p>Esta acción elimina permanentemente la cuenta y no se puede deshacer.</p></div>
        </div>
        <form method="POST" action="{{ route('profile.destroy') }}" class="profile-danger-form"
            onsubmit="return confirmarEliminacionSistema(event, 'la cuenta', '{{ addslashes($user->name) }}', 'se cerrará la sesión y la cuenta dejará de existir');">
            @csrf
            @method('delete')
            <div class="form-group">
                <label for="delete_profile_password">Confirma con tu contraseña</label>
                <input id="delete_profile_password" name="password" type="password" autocomplete="current-password" required placeholder="Escribe tu contraseña actual">
                @error('password', 'userDeletion')<span class="profile-field-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-danger"><i data-lucide="trash-2"></i><span>Eliminar cuenta</span></button>
        </form>
    </section>
</div>
@endsection
