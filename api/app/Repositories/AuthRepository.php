<?php

namespace App\Repositories;

use App\Interfaces\AuthRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(
        array $data
    ) {
        DB::beginTransaction();

        try {
            if (!Auth::guard('web')->attempt($data)) {
                // Log login gagal
                ActivityLogService::logAuth(
                    'login_failed',
                    "Login gagal untuk email: {$data['email']}",
                    null,
                    ['email' => $data['email']],
                );

                throw new \Exception('Unauthorized', 401);
            }

            $user = Auth::user()->load('roles');

            // Update last login timestamp
            $user->last_login_at = now();
            $user->save();

            $user->token = $user->createToken('auth_token')->plainTextToken;

            // Log login berhasil
            ActivityLogService::logAuth(
                'login',
                "{$user->name} berhasil login",
                $user->id,
            );

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    public function me()
    {
        DB::beginTransaction();

        try {
            if (!Auth::check()) {
                throw new \Exception('Unauthorized');
            }

            $user = Auth::user()->load(['roles', 'permissions', 'branch']);

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function logout()
    {
        DB::beginTransaction();

        try {
            if (!Auth::check()) {
                throw new \Exception('Unauthorized');
            }

            $user = Auth::user();

            // Log logout sebelum token dihapus
            ActivityLogService::logAuth(
                'logout',
                "{$user->name} berhasil logout",
                $user->id,
            );

            $user->tokens()->delete();

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception($e->getMessage());
        }
    }

    public function updateProfile(array $data)
    {
        DB::beginTransaction();

        try {
            if (!Auth::check()) {
                throw new \Exception('Unauthorized', 401);
            }

            $user = Auth::user();

            if (isset($data['name'])) {
                $user->name = $data['name'];
            }

            if (isset($data['phone_number'])) {
                $user->phone_number = $data['phone_number'];
            }

            // Allow user to manually set/update their telegram_chat_id
            if (array_key_exists('telegram_chat_id', $data)) {
                $user->telegram_chat_id = $data['telegram_chat_id'];
                // Update linked_at timestamp when manually setting telegram ID
                if (!empty($data['telegram_chat_id']) && empty($user->telegram_linked_at)) {
                    $user->telegram_linked_at = now();
                } elseif (empty($data['telegram_chat_id'])) {
                    $user->telegram_linked_at = null;
                }
            }

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            // Email is intentionally not updatable
            $user->save();

            DB::commit();

            return $user->fresh()->load(['roles', 'permissions', 'branch']);
        } catch (\Exception $e) {
            DB::rollBack();
            $code = is_int($e->getCode()) ? $e->getCode() : 500;
            throw new \Exception($e->getMessage(), $code ?: 500);
        }
    }
}
