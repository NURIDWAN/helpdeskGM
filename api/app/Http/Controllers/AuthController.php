<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\LoginStoreRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\AuthRepositoryInterface;
use App\Services\FileCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user and return token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/User")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function login(LoginStoreRequest $request)
    {
        $request = $request->validated();

        try {
            $user = $this->authRepository->login($request);

            return ResponseHelper::jsonResponse(true, 'Login Berhasil', new UserResource($user), 200);
        } catch (\Exception $e) {
            // Ensure status code is valid HTTP code, default to 401 for auth errors
            $statusCode = $e->getCode();
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 401;
            }
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, $statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user",
     *     description="Get authenticated user details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/User")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function me()
    {
        try {
            $user = $this->authRepository->me();

            return ResponseHelper::jsonResponse(true, 'Profile berhasil diambil', new UserResource($user), 200);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Invalidate user token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function logout()
    {
        try {
            $user = $this->authRepository->logout();

            return ResponseHelper::jsonResponse(true, 'Logout berhasil', new UserResource($user), 200);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="Update user profile",
     *     description="Update authenticated user profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "phone_number"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="phone_number", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/User")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'phone_number' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string|max:50',
        ]);

        try {
            $user = $this->authRepository->updateProfile([
                'name' => $request->input('name'),
                'password' => $request->input('password'),
                'phone_number' => $request->input('phone_number'),
                'telegram_chat_id' => $request->input('telegram_chat_id'),
            ]);

            return ResponseHelper::jsonResponse(true, 'Profil berhasil diperbarui', new UserResource($user), 200);
        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, $status);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/me/photo",
     *     tags={"Authentication"},
     *     summary="Upload profile photo",
     *     description="Upload or update authenticated user's profile photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="photo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile photo uploaded successfully"
     *     )
     * )
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        try {
            $user = $request->user();

            // Delete old photo if exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $file = $request->file('photo');
            $fileName = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            $compressionService = new FileCompressionService();
            $path = $compressionService->compressAndStore($file, 'profile-photos', $fileName, 80, 512);

            $user->profile_photo = $path;
            $user->save();

            $user->load(['roles', 'permissions', 'branch']);

            return ResponseHelper::jsonResponse(true, 'Foto profil berhasil diperbarui', new UserResource($user), 200);
        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, $status);
        }
    }

    /**
     * @OA\Delete(
     *     path="/auth/me/photo",
     *     tags={"Authentication"},
     *     summary="Delete profile photo",
     *     description="Remove authenticated user's profile photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile photo deleted successfully"
     *     )
     * )
     */
    public function deletePhoto(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
                $user->profile_photo = null;
                $user->save();
            }

            $user->load(['roles', 'permissions', 'branch']);

            return ResponseHelper::jsonResponse(true, 'Foto profil berhasil dihapus', new UserResource($user), 200);
        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, $status);
        }
    }
}
