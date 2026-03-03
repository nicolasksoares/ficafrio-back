<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException; // <--- ADICIONADO: Importação necessária

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $company = Company::where('email', $request->email)->first();

        if (! $company || ! Hash::check($request->password, $company->password)) {
            return response()->json([
                'message' => 'Credenciais Inválidas!',
            ], 401);
        }

        // Token com expiração de 30 dias
        $expiresAt = now()->addDays(30);
        $token = $company->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'Login realizado',
            'token' => $token,
            'company_id' => $company->id,
            // Usando o Resource para garantir que venha formatado igual ao /me
            'user' => new CompanyResource($company), 
        ], 200);
    }

    public function logout(Request $request)
    {
    // Deleta apenas o token usado nesta requisição (mais seguro e preciso)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
        'message' => 'Logout realizado com sucesso',
        ], 200);
    }

    public function me(Request $request)
    {
        return new CompanyResource($request->user());
    }

    // --- RECUPERAÇÃO DE SENHA ---

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Atenção: Certifique-se que 'companies' está configurado em config/auth.php
        $status = Password::broker('companies')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['status' => __($status)]);
        }

        return response()->json(['email' => [__($status)]], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $status = Password::broker('companies')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // CORREÇÃO: Removemos o Hash::make() porque o Model Company já tem o cast 'hashed'
                $user->forceFill([
                    'password' => $password 
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)])
            : throw ValidationException::withMessages(['email' => [__($status)]]);
    }
}