<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Merchant;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function me()
    {

        $user = auth()->user();
        return apiResponse(true, null, ['user' => $user]);
    }

    public function login(Request $request)
    {
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            //'pin' => 'required|in:1234'
            'pin' => 'required'
        ]);

        $user = Admin::query()->firstWhere([
            //'email' => 'admin@gmail.com'
            'email' => $request->email
        ]);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return apiResponse(false, 'Invalid email or password.');
        } else {

            $token = $user->createToken('TOKEN')->plainTextToken;
            $data = [
                'user' => $user,
                'token' => $token
            ];

            return apiResponse(true, 'Login successfully.', $data);
        }
    }

    public function logout()
    {
        if (auth()->check()) {
            auth()->user()->tokens()->delete();
            return apiResponse(true, 'Logged out successfully.');
        }

        return apiResponse(true, null);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('admins')->sendResetLink(
            $request->only('email')
        );

        return apiResponse(true, __($status));
    }

    public function showPasswordResetForm($token)
    {
        $this->middleware('guest:admin');
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:4|confirmed',
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(null);

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return apiResponse(true, __($status));
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:3|confirmed'
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $user = auth()->user();
        if (password_verify(\request('current_password'), $user->getAuthPassword())) {
            $user->password = Hash::make(request('password'));
            $user->save();
            return apiResponse(true, 'Password changed successfully.');
        } else {
            return apiResponse(false, 'Current password is wrong.');
        }
    }
}
