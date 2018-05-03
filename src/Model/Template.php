<?php

namespace App\Model;

class Template extends Elegant
{
    protected $table = 'templates';

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function sub_templates()
    {
        return $this->hasMany(SubTemplate::class);
    }

    public function showcase_colors()
    {
        return $this->hasMany(ShowcaseColor::class);
    }

    public function showcase_products()
    {
        return $this->hasMany(ShowcaseProduct::class);
    }
}
