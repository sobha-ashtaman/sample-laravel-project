<?php

namespace App\Http\Controllers;

use App\Exceptions\DisabledUserException;
use App\Http\Requests\loginRequest;
use App\Http\Resources\LoginResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(loginRequest $request): LoginResource
    {
        $request->validated();
        $user = User::where('email', $request->email)->first();
        
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if($user->status == 0)
            throw new DisabledUserException('Your access to this system is suspended, please contact administrator for more details.');

        $new_token = $user->createToken('auth_token')->plainTextToken;
        $user->auth_token = $new_token;

        return new LoginResource($user);
    }

    public function getUser(Request $request){
        $user = auth()->user();
        return new LoginResource($user);
    }
}
