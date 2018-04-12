<?php

namespace App\Model;

class Shop extends Elegant
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'users_shops', 'shop_id', 'user_id');
    }

    public function setDescriptionAttribute($html)
    {
        $this->attributes['description'] = utf8_encode($html);
        return $this;
    }

    public function getDescriptionAttribute($html)
    {
        return utf8_decode($html);
    }
}
