<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BranchResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Expose PII when:
        // 1. Viewing own profile
        // 2. From auth/me endpoint
        // 3. Admin/superadmin managing users (has user-edit or user-list permission)
        $isOwnProfile = $request->user() && $request->user()->id === $this->id;
        $isAuthMe = $request->is('*/auth/me');
        $isAdmin = $request->user() && $request->user()->can('user-edit');
        $canViewPII = $isOwnProfile || $isAuthMe || $isAdmin;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'name' => $this->name,
            'email' => $this->email,
            'position' => $this->position,
            'identity_number' => $canViewPII ? $this->identity_number : null,
            'phone_number' => $canViewPII ? $this->phone_number : null,
            'profile_photo' => $this->profile_photo ? asset('storage/' . $this->profile_photo) : null,
            'telegram_chat_id' => $canViewPII ? $this->telegram_chat_id : null,
            'type' => $this->type,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            // Always load all permissions (from both direct assignment and roles)
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'token' => $this->when(isset($this->token), $this->token),
            'created_at' => $this->created_at,
        ];
    }
}
