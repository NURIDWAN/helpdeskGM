<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Guard name for Spatie Permission.
     * Must match the guard used when creating permissions/roles.
     */
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'position',
        'identity_number',
        'phone_number',
        'type',
        'last_login_at',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get activity status based on last login
     */
    public function getActivityStatusAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'never';
        }

        $daysSinceLogin = $this->last_login_at->diffInDays(now());

        if ($daysSinceLogin <= 7) {
            return 'active';
        } elseif ($daysSinceLogin <= 30) {
            return 'rarely';
        } else {
            return 'inactive';
        }
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->orWhere('position', 'like', '%' . $search . '%')
            ->orWhere('identity_number', 'like', '%' . $search . '%')
            ->orWhere('phone_number', 'like', '%' . $search . '%')
            ->orWhereHas('branch', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('roles', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('permissions', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_staff', 'user_id', 'ticket_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    public function workReports()
    {
        return $this->hasMany(WorkReport::class);
    }

    public function dailyRecords()
    {
        return $this->hasMany(DailyRecord::class);
    }
}
