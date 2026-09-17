<?php

namespace App\Models;

use App\Services\NotificationCampaignService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; // <-- เพิ่ม Str facade
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    // เพิ่ม google_id และ avatar ใน $fillable
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'profile_image',
        'date_of_birth',
        'sex',
        'role',
        'status',
        'login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'google_id',  // <-- เพิ่มตรงนี้
        'avatar',     // <-- เพิ่มตรงนี้ (เผื่อเก็บรูปจาก Google)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Normalize PostgreSQL bpchar padding (for example, "Admin     "). */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : trim($value),
        );
    }

    // (แนะนำ) สร้าง UUID อัตโนมัติเมื่อสร้าง User ใหม่ ถ้าไม่ได้ส่ง user_id มา
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
        static::created(function (User $user) {
            app(NotificationCampaignService::class)->deliverPersistentTo($user);
        });
    }
}
