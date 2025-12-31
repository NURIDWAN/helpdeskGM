<?php

namespace App\Repositories;

use App\Interfaces\AuthRepositoryInterface;
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
                throw new \Exception('Unauthorized', 401);
            }

            $user = Auth::user()->load('roles');

            // Update last login timestamp
            $user->last_login_at = now();
            $user->save();

            $user->token = $user->createToken('auth_token')->plainTextToken;

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

            return response()->json(['message' => $e->getMessage()], 500);
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


            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            // Email is intentionally not updatable
            $user->save();

            DB::commit();

            return $user->fresh()->load(['roles', 'permissions', 'branch']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
