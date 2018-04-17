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
}
