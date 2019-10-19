<?php

namespace App;

use App\Notifications\MailResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['role_names', 'is_online', 'last_activity_diff'];

    public static $rules = [
        'name' => 'required|string|max:75',
        'email' => 'required|email',
        'password' => 'required|string',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->password = bcrypt($user->password);

            if (empty($user->roles)) {
                $default_role = Role::where('name', '=', 'admin')->first();
                if (isset($default_role)) {
                    $user->roles()->attach($default_role->id);
                }
            }
            return $user;
        });
    }


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MailResetPasswordNotification($token));
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        $roles = $this->role_names;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'last_activity' => $this->last_activity_diff
        ];
    }

    public function getRoleNamesAttribute()
    {
        $names_as_array = $this->roles->map(function ($role) {
            return $role->name;
        })->toArray();

        $role_names = implode(",", $names_as_array);

        return $role_names;
    }

    public function getIsOnlineAttribute()
    {
        $isOnline = $this->last_activity < now()->subMinutes(5)->format('Y-m-d H:i:s');

        return $isOnline;
    }

    public function getLastActivityDiffAttribute()
    {
        return now()->diffForHumans($this->last_activity);
    }
}
