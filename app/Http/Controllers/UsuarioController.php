<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\AdminActivityLogger;

class UsuarioController extends Controller
{
    public function index()
    {
        // Usa la sucursal activa del menú lateral para mostrar solo usuarios de esa sucursal.
        $sucursalId = session('sucursal_id');

        $usuarios = User::with('sucursal')
            // La lista se conecta estrictamente con users.sucursal_id para no mezclar personal.
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when(!$sucursalId, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->get();

        $rolesSistema = $usuarios->whereIn('rol', ['superusuario', 'capturista', 'vendedor']);
        $tecnicos = $usuarios->where('rol', 'tecnico');
        $usuariosGenerales = $usuarios->whereNotIn('rol', ['superusuario', 'capturista', 'vendedor', 'tecnico']);

        return view('usuarios.index', compact('usuariosGenerales', 'rolesSistema', 'tecnicos'));
    }

    public function create()
    {
        // Carga sucursales para que el formulario pueda asignar de dónde es el usuario.
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('usuarios.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email'    => 'required|email|unique:users,email',
            'rol'      => 'required|in:superusuario,capturista,vendedor,tecnico,usuario',
            'sucursal_id' => 'required|exists:sucursales,id',
            // Solo el rol "usuario" tiene login real, por eso la contraseña se exige nada más en ese caso.
            'password' => 'required_if:rol,usuario|nullable|string|min:6|confirmed',
        ]);

        $usuario = User::create([
            'name'     => $request->name,
            'telefono' => $request->telefono,
            'email'    => $request->email,
            // Si el rol es "usuario" se guarda la contraseña que definió el admin en el wizard.
            // Para los demás roles (sin acceso al sistema) se genera una aleatoria como antes.
            'password' => $request->rol === 'usuario'
                ? Hash::make($request->password)
                : Hash::make(Str::random(32)),
            'rol'      => $request->rol,
            'sucursal_id' => $request->sucursal_id,
        ]);

        // Auditoria: informa al admin del alta y se conecta con la sucursal asignada.
        AdminActivityLogger::registrar('USUARIOS', 'CREAR', 'Usuario '.$usuario->name.' creado con rol '.$usuario->rol.'.', $usuario->sucursal_id, $usuario);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        // Carga sucursales para poder cambiar la sucursal conectada al usuario.
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('usuarios.edit', compact('usuario', 'sucursales'));
    }

    public function update(Request $request, User $usuario)
    {
        // Al convertir otro rol en Usuario exige una contraseña inicial; se conecta con el acceso de inicio de sesión.
        $reglaPassword = $request->rol === 'usuario' && $usuario->rol !== 'usuario'
            ? 'required|string|min:6|confirmed'
            : 'nullable|string|min:6|confirmed';

        $request->validate([
            'name'     => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email'    => 'required|email|unique:users,email,'.$usuario->id,
            'rol'      => 'required|in:superusuario,capturista,vendedor,tecnico,usuario',
            'sucursal_id' => 'required|exists:sucursales,id',
            // Para un Usuario existente es opcional; si queda en blanco conserva su contraseña actual.
            'password' => $reglaPassword,
        ]);

        $data = [
            'name'     => $request->name,
            'telefono' => $request->telefono,
            'email'    => $request->email,
            'rol'      => $request->rol,
            'sucursal_id' => $request->sucursal_id,
        ];

        // Solo se toca el password si el admin escribió una nueva; si no, se deja la que ya tenía.
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        AdminActivityLogger::registrar('USUARIOS', 'EDITAR', 'Usuario '.$usuario->name.' actualizado.', $usuario->sucursal_id, $usuario);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        abort_if($usuario->is(auth()->user()), 422, 'No puedes eliminar tu propia sesion administrativa.');
        AdminActivityLogger::registrar('USUARIOS', 'ELIMINAR', 'Usuario '.$usuario->name.' eliminado.', $usuario->sucursal_id, $usuario);
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado.');
    }

    public function show(User $usuario) {}
}
