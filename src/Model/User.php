<?php

namespace App\Model;

use App\Model\Shop;

class User extends Elegant
{
    protected $table = 'users';
    protected $hidden = array(
        'password'
    );

    protected $casts = array(
        'default_shops' => 'array'
    );

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    public function authenticate($password)
    {
        return password_verify($password, $this->attributes['password']);
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'users_shops', 'user_id', 'shop_id');
    }
}
