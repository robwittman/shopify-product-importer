<?php

namespace App\Model;

class Shop extends Elegant
{
    protected $hidden = ['password', 'google_access_token', 'google_sheet_slug'];
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_shops', 'shop_id', 'user_id');
    }
}
