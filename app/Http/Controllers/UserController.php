<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:users,correo',
            'contraseña' => 'required|string|min:6',
            'telefono' => 'required|string|max:20',
            'rol' => 'required|string'
        ]);

        return User::create($request->all());
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return $user;
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'correo' => 'sometimes|required|email|unique:users,correo,' . $user->id,
            'telefono' => 'sometimes|required|string|max:20',
            'rol' => 'sometimes|required|string'
        ]);

        $user->update($request->all());
        return $user;
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}


