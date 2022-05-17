<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Uuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'username',
        'password',
        'date_of_birth',
        'gender',
        'phone',
        'photo',
        'address',
        'city',
        'state_id',
        'balance',
        'winning_amount',
        'deposited_balance',
        'cash_bonus',
        'phone_verified',
        'document_verified',
        'email_verified',
        'is_locked',
        'is_username_update',
        'can_played',
        'referral_code',
        'referral_amount',
        'referral_id',
        'is_deposit',
        'referral_pending_amount',
        'role',
        'remember_token',
        'verification_code',
        'bank_update_count',
        'level',
        'fcm_token',
        'is_sys_user',
        'promoter_type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified' => 'boolean',
        'document_verified' => 'boolean',
        'email_verified' => 'boolean',
        'is_sys_user' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function bank()
    {
        return $this->hasOne(BankAccount::class, 'user_id');
    }

    public function pan()
    {
        return $this->hasOne(PanCard::class, 'user_id');
    }

    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    public function referredby()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }
}
