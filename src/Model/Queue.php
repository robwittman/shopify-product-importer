<?php

namespace App\Model;

class Queue extends Elegant
{
    const PENDING = 'pending';
    const FAILED = 'failed';
    const FINISHED = 'finished';
    const STARTED = 'started';

    protected $table = 'queue';
    public $timestamps = false;

    protected $casts = array(
        'data' => 'json'
    );

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'handle');
    }

    public function sub_template()
    {
        return $this->belongsTo(SubTemplate::class, 'sub_template_id', 'value');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function fail($reason = null)
    {
        $this->status = self::FAILED;
        $this->error = $reason;
        $this->save();
    }

    public function finish($data)
    {
        $this->finished_at = date("Y-m-d H:i:s");
        $this->status = self::FINISHED;
        $this->product_id = $data;
        $this->save();
    }

    public function getProductIdAttribute()
    {
        return json_decode($this->attributes['product_id']);
    }

    public function setProductIdAttribute($data)
    {
        $this->attributes['product_id'] = json_encode($data);
    }

    public function start()
    {
        $this->status = self::STARTED;
        $this->started_at = date("Y-m-d H:i:s");
        $this->save();
    }
}
