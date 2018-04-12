<?php

namespace App\Model;

class Batch extends Elegant
{
    protected $table = 'batches';

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

    public function setPostAttribute($data)
    {
        $this->attributes['post'] = json_encode($data);
        return $this;
    }

    public function getPostAttribute($json)
    {
        return json_decode($json, true);
    }
}
