<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function update(Request $request)
    {
        // Obtener el usuario autenticado
        $authenticatedUser = auth()->user();

        // Buscar el usuario por su ID
        $user = User::find($request->input('id'));

        // Verificar si el usuario existe
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Verificar que el usuario autenticado es el propietario del perfil
        if ($authenticatedUser->id !== $user->id) {
            return response()->json(['error' => 'No tienes permisos para actualizar este perfil'], 403);
        }

        // Valida los datos del formulario
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'password' => 'sometimes|confirmed',
        ]);

        // Actualiza los campos del usuario
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');

        // Si se proporciona una nueva contraseña, actualizarla
        if ($request->has('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Guarda los cambios en la base de datos
        $user->save();

        // Respuesta exitosa
        return response()->json(['message' => 'Usuario actualizado correctamente'], 200);
    }
}
