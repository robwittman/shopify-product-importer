<?php

namespace App\Model;

class Setting extends Elegant
{
    protected $table = 'template_settings';

    protected $fillable = array('shop_id', 'template_id');
}
