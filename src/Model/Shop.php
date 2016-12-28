<?php

namespace App\Model;

class Shop extends Elegant
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_shops', 'shop_id', 'user_id');
    }
}
