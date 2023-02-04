<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Spatie\FlareClient\Http\Exceptions\NotFound;
use Tickets\Shared\Domain\Assert;
use Tickets\User\Account\Application\AccountApplication;

class AuthController extends Controller
{
    public function __construct(
        private AccountApplication $accountApplication
    ) {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials, true)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|confirmed|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'city' => $request->city,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return $this->respondWithToken($token);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json([
            'status' => 'success',
            'user' => $this->accountApplication->getUserByEmail($user->email)?->toArray(),
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
                'lifetime' => time() + (auth()->factory()->getTTL() * 60),
            ]
        ]);
    }

    public function isCorrectRole(Request $request): JsonResponse
    {
        $roles = $request->only('role')['role'];

        /** @var User $user */
        $user = auth()->user();
        if (is_null($userInfoDto = $this->accountApplication->getUserByEmail($user->email))) {
            throw new \DomainException('Пользователь не найден');
        }

        $isCorrect = true;
        foreach ($roles as $role) {
            if (!$userInfoDto->isRole($role)) {
                $isCorrect = false;
                break;
            }
        }

        if (!$isCorrect) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );
        $success = $status === Password::RESET_LINK_SENT;

        return response()->json([
            'success' => $success,
            'status' => $success
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)])
        ]);
    }
}
