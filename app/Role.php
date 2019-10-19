<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = ['name', 'description'];
    protected $hidden = ['id'];

    protected $appends = ['name_translated'];

    public $timestamps = false;


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            $role->hash = \Hash::make($role->name);

            return $role;
        });
    }

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function getNameTranslatedAttribute()
    {
        $translations = [
            'superadmin' => 'Super Administrador',
            'admin' => 'Administrador',
        ];

        return $translations[$this->name];
    }
}
