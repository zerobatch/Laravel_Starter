<?php

namespace App\Http\Controllers\Api;

use App\{User, Role};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{

    public function createUser(CreateUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
            ]);

            $roles = array_map(function ($role) {
                return Role::where('hash', $role['value'])->first()->id;
            }, $request->roles);

            $user->roles()->attach($roles);


            DB::commit();

            return response()->json(['success' => true, 'message' => 'Usuario creado con éxito', 'user' => $user->fresh()->toArray()], 201);
        } catch (\Exception $error) {
            DB::rollBack();
            \Log::error("AuthController::createUser, ha sucedido un error grave --> " . $error->getMessage());
            \Log::error($error->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'Ha sucedido un error inesperado, inténtelo de nuevo'
            ], 501);
        }
    }

    public function retrieveRoles(Request $request)
    {
        if ($request->isMethod('GET')) {
            $roles = Role::all()->reject(function ($role) {
                return $role->name === 'superadmin';
            });

            return response()->json(['success' => true, 'roles' => $roles], 201);
        }

        return response()->json(['success' => false, 'error' => 'Unauthorized operation, this attempt was register in the system'], 401);
    }
}
