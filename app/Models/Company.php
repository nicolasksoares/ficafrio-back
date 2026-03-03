<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Company extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'trade_name',
        'legal_name',
        'cnpj',
        'email',
        'password',
        'phone',
        'address_street',
        'address_number',
        'district',
        'city',
        'state',
        'zip_code',
        'type',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'type' => UserType::class,
    ];

    public function spaces()
    {
        return $this->hasMany(Space::class);
    }

    public function storageRequests()
    {
        return $this->hasMany(StorageRequest::class);
    }
}
