<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'country_code',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function groups()
    {
        return $this->hasMany(ContactGroup::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function quotaPayments()
    {
        return $this->hasMany(QuotaPayment::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin || strcasecmp((string) $this->email, (string) config('services.send.admin_email')) === 0;
    }

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
            'is_admin' => 'boolean',
        ];
    }
}
