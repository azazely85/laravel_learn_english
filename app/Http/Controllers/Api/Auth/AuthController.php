<?php

namespace App\Http\Controllers\Api\Auth;

use App\Jobs\ResetPasswordLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    /** Login action
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->get('remember_me'))){
            $authUser = Auth::user();
            $token = $authUser->createToken('user-token')->plainTextToken;

            return $this->return_success(['token' => $token, 'user' => auth()->user()]);
        } else {
            return $this->return_error('Incorrect password or email', 200);
        }
    }

    /** Login action
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            auth()->user()->tokens()->delete();
            return $this->return_success('OK');
        } catch (\Exception $e) {
            return $this->return_error($e->getMessage());
        }

    }

    /** Register Action
     * @param RegisterUserRequest $request
     * @return JsonResponse
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('user-token')->plainTextToken;
        $success['name'] = $user->name;

        return response()->json([$success]);
    }

    /** Send Password Reset Link Action
     * @param Request $request
     * @return JsonResponse
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $job = new ResetPasswordLink($request->only('email'));
        $this->dispatch($job);

        return $this->return_success('Check your email!');
    }

    /** Reset Password Action
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(Str::random(60));

                $user->first_login = 0;
                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->return_success('Password successfully updated!')
            : $this->return_error([$status]);
    }
}
