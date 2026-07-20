@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Usuarios, Roles y Tecnicos</h1>
        <div class="page-title-sub">Separado por tipo de acceso dentro del sistema</div>
    </div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">+ Nuevo usuario</a>
</div>

@php
    $rolLabels = [
        'superusuario' => 'Super Usuario',
        'capturista' => 'Capturista',
        'vendedor' => 'Vendedor',
        'tecnico' => 'Tecnico',
        'usuario' => 'Usuario',
    ];

    $rolColors = [
        'superusuario' => '#dbeafe;color:#1e40af',
        'capturista' => '#fef9c3;color:#854d0e',
        'vendedor' => '#ede9fe;color:#5b21b6',
        'tecnico' => '#dcfce7;color:#166534',
        'usuario' => '#f1f5f9;color:#475569',
    ];
@endphp

@foreach([
    'Roles del sistema' => $rolesSistema,
    'Tecnicos' => $tecnicos,
    'Usuarios' => $usuariosGenerales,
] as $titulo => $lista)
    <div style="margin-bottom:1.75rem;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:.75rem;">
            <h2 style="font-size:16px;color:#0f1f3d;margin:0;">{{ $titulo }}</h2>
            <span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                {{ $lista->count() }}
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Sucursal</th>
                    <th>Registrado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lista as $usuario)
                <tr>
                    <td><strong>{{ $usuario->name }}</strong></td>
                    <td>{{ $usuario->telefono ?? '-' }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>
                        <span style="background:{{ $rolColors[$usuario->rol] ?? $rolColors['usuario'] }};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                            {{ $rolLabels[$usuario->rol] ?? 'Usuario' }}
                        </span>
                    </td>
                    <td>
                        {{-- Muestra la sucursal conectada por users.sucursal_id; si es antiguo, queda pendiente de asignar. --}}
                        {{ $usuario->sucursal->nombre ?? 'Sin sucursal' }}
                    </td>
                    <td>{{ $usuario->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn">Editar</a>
                            <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}"
                                onsubmit="return confirmarEliminacionSistema(event, 'el usuario', '{{ addslashes($usuario->name) }}');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    {{-- colspan="7" cubre Nombre, Telefono, Email, Rol, Sucursal, Registrado y Acciones. --}}
                    <td colspan="7" style="text-align:center;color:#888;padding:2rem">No hay registros en esta seccion</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endforeach
@endsection
