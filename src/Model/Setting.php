<?php

namespace App\Model;

class Setting extends Elegant
{
    protected $table = 'template_settings';

    protected $fillable = array('shop_id', 'template_id');

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
