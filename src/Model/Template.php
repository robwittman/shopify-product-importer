<?php

namespace App\Model;

class Template implements \JsonSerializable
{
    protected $name;
    protected $description;

    public function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function jsonSerialize()
    {
        return array(
            'name' => $this->name,
            'description' => $this->description
        );
    }
}
