<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\UserPasswordResets;
use App\Models\PasswordResets;
use Bus;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mail;
use Nette\Utils\JsonException;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Domain\ProcessPasswordResets;
use Tickets\User\Account\Dto\AccountDto;

class AuthController extends Controller
{
    public function __construct(
        private AccountApplication $accountApplication,
        private Bus $bus
    ) {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'register',
                'forgotPassword',
                'resetPassword'
            ]
        ]);
    }

    /**
     * @throws JsonException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            '*.required' => 'Поле обязательно для ввода',
            '*.email' => 'E-mail не прошел проверку!'
        ]);
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials, true)) {
            return response()->json(['message' => 'Твой пароль неверный, попробуй еще раз!'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @throws JsonException
     * @throws \Throwable
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|confirmed|string|min:6',
        ], [
            '*.required' => 'Поле обязательно для ввода',
            '*.email' => 'Поле должно быть email',
            '*.unique' => 'Такой пользователь уже зарегистрирован в системе',
            '*.confirmed' => 'Пароль не совпадает',
            '*.min' => 'Минимальное количество символов 6-ть',
        ]);

        $this->accountApplication->createNewAccount(
            AccountDto::fromState($request->toArray()),
            $request->password
        );
        $credentials = $request->only('email', 'password');
        $token = auth()->attempt($credentials, true);
        if (!$token = auth()->attempt($credentials, true)) {
            return response()->json(['message' => 'Логин и пароль указан не верно'], 401);
        }
        return $this->respondWithToken($token);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @return JsonResponse
     * @throws JsonException
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
     * @throws JsonException
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
            throw new DomainException('Пользователь не найден');
        }

        $isCorrect = true;
        foreach ($roles as $role) {
            if (!$userInfoDto->isRole($role)) {
                $isCorrect = false;
                break;
            }
        }

        if (!$isCorrect) {
            return response()
                ->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [
            '*.required' => 'Поле обязательно для ввода',
            '*.email' => 'Поле должно быть email'
        ]);
        $validate->validate();
        $email = $request->get('email');
        if ($user = User::where('email', '=', $email)->first()) {
            $this->bus::chain([new ProcessPasswordResets($user)])->dispatch();

            return response()->json([
                'message' => 'На указанный е-мейл отправлена ссылка для восстановления пароля'
            ]);
        }
        return response()->json([
            'errors' => [
                'email' => 'Такой e-mail не зарегистрирован в системе!'
            ]
        ], 422);
    }

    /**
     * @throws ValidationException
     */
    public function editPassword(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'password' => 'required|confirmed|min:6',
        ], [
            '*.required' => 'Поле обязательно для ввода',
            '*.min' => 'Минимальное количество символов 6-ть',
            '*.confirmed' => 'Пароль не совпадает'
        ])->validate();
        /** @var User $user */
        $user = auth()->user();
        $user->password = Hash::make($request->get('password'));
        $user->save();

        return response()->json([
            'message' => 'Пароль сменён'
        ]);
    }

    /**
     * @throws ValidationException
     * @throws JsonException
     */
    public function resetPassword(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'password' => 'required|confirmed|min:6',
            'token' => 'required'
        ], [
            '*.required' => 'Поле обязательно для ввода',
            '*.min' => 'Минимальное количество символов 6-ть',
            '*.confirmed' => 'Пароль не совпадает'
        ])->validate();

        $userPasswordResets = PasswordResets::whereToken($request->get('token'))->first();
        if (is_null($userPasswordResets)) {
            return response()->json([
                'errors' => [
                    'email' => ['Не верная ссылка']
                ]
            ], 422);
        }

        $user = User::whereEmail($userPasswordResets->email)->first();
        if (is_null($user)) {
            throw new DomainException('Не найден пользователь');
        }

        $user->password = Hash::make($request->get('password'));
        $user->save();
        $token = Auth::login($user);

        return $this->respondWithToken($token);
    }

    public function editProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->name = $request->get('name');
        $user->phone = $request->get('phone');
        $user->city = $request->get('city');
        $user->save();

        return response()->json([
            'message' => 'Данные пользователя изменены'
        ]);
    }

    public function findUserByEmail(string $email): JsonResponse
    {
        $userInfo = $this->accountApplication->getUserByEmail($email);

        return response()->json([
            'success' => !is_null($userInfo)
        ]);
    }
}
