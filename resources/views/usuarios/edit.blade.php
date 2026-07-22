@extends('layout')

@section('content')

<style>
    /* Panel informativo de accesos: aparece cuando el rol seleccionado es "Usuario". */
    .usuario-rol-info {
        display: none;
        background: #eff6ff;
        border: 2px solid #bfdbfe;
        border-radius: 10px;
        padding: 12px 14px;
        margin-top: 0.5rem;
        font-size: 13px;
        color: #1e3a8a;
        grid-column: 1 / -1;
    }

    .usuario-rol-info.visible { display: block; }

    .usuario-rol-info strong {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .usuario-rol-info ul {
        margin: 0;
        padding-left: 18px;
    }

    .usuario-rol-info li {
        margin-bottom: 2px;
    }

    .usuario-edit-hint {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 4px;
        display: block;
    }

    .usuario-show-pass {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
        margin-top: 6px;
        cursor: pointer;
        user-select: none;
    }

    /* Los campos de acceso solo aparecen para el rol Usuario y se conectan con UsuarioController::update. */
    .usuario-password-field { display: none; }
    .usuario-password-field.visible { display: block; }
</style>

<div class="page-header">
    <h1>Editar Usuario</h1>
    <a href="{{ route('usuarios.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="name" required value="{{ old('name', $usuario->name) }}"/>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="{{ old('email', $usuario->email) }}"/>
            </div>
            <div class="form-group">
                <label>Número telefónico</label>
                <input type="text" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" placeholder="Ej. 9991234567"/>
            </div>
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol" id="u_rol" required onchange="usuarioToggleRolInfo()">
                    <option value="usuario" {{ old('rol', $usuario->rol) == 'usuario' ? 'selected' : '' }}>Usuario</option>
                    <option value="superusuario" {{ old('rol', $usuario->rol) == 'superusuario' ? 'selected' : '' }}>Super Usuario</option>
                    <option value="capturista" {{ old('rol', $usuario->rol) == 'capturista' ? 'selected' : '' }}>Capturista</option>
                    <option value="vendedor" {{ old('rol', $usuario->rol) == 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                    <option value="tecnico" {{ old('rol', $usuario->rol) == 'tecnico' ? 'selected' : '' }}>Tecnico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                {{-- Se conecta con users.sucursal_id para cambiar la sucursal asignada al usuario. --}}
                <select name="sucursal_id" required>
                    <option value="">Selecciona una sucursal</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ old('sucursal_id', $usuario->sucursal_id) == $sucursal->id ? 'selected' : '' }}>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Panel informativo: se muestra solo cuando el rol elegido es "Usuario". --}}
            <div class="usuario-rol-info" id="usuario-rol-info">
                <strong>Este rol solo tendrá acceso a:</strong>
                <ul>
                    <li>Panel principal</li>
                    <li>Órdenes de servicio</li>
                    <li>Clientes</li>
                    <li>Inventario</li>
                    <li>Caja</li>
                    <li>Ventas</li>
                    <li>Categorías</li>
                </ul>
            </div>

            {{-- Contraseña opcional: por seguridad nunca se muestra la actual, solo se cambia si se escribe una nueva. --}}
            <div class="form-group usuario-password-field">
                <label>Nueva contraseña</label>
                <input type="password" name="password" id="u_password" autocomplete="new-password" minlength="6"/>
                <span class="usuario-edit-hint">Déjalo en blanco para conservar la contraseña actual.</span>
            </div>
            <div class="form-group usuario-password-field">
                <label>Confirmar nueva contraseña</label>
                <input type="password" name="password_confirmation" id="u_password_confirmation" autocomplete="new-password" minlength="6"/>
                <label class="usuario-show-pass">
                    <input type="checkbox" onclick="usuarioTogglePasswordVisibility()"> Mostrar contraseña
                </label>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem">
            <a href="{{ route('usuarios.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>

<script>
    /* Muestra u oculta el panel de accesos permitidos según el rol elegido. */
    function usuarioToggleRolInfo() {
        const rol = document.getElementById('u_rol').value;
        const info = document.getElementById('usuario-rol-info');
        info.classList.toggle('visible', rol === 'usuario');

        // Muestra las credenciales para Usuario y las exige al convertir un rol que antes no tenía acceso.
        const convirtiendoAUsuario = rol === 'usuario' && @json($usuario->rol !== 'usuario');
        document.querySelectorAll('.usuario-password-field').forEach(function (campo) {
            campo.classList.toggle('visible', rol === 'usuario');
        });
        document.getElementById('u_password').required = convirtiendoAUsuario;
        document.getElementById('u_password_confirmation').required = convirtiendoAUsuario;
    }

    /* Alterna visibilidad de los campos de nueva contraseña. */
    function usuarioTogglePasswordVisibility() {
        const type = document.getElementById('u_password').type === 'password' ? 'text' : 'password';
        document.getElementById('u_password').type = type;
        document.getElementById('u_password_confirmation').type = type;
    }

    usuarioToggleRolInfo();
</script>
@endsection
