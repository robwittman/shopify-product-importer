<?php

namespace App\Model;

class Queue extends Elegant
{
    const PENDING = 'pending';
    const FAILED = 'failed';
    const FINISHED = 'finished';

    protected $table = 'queue';
    public $timestamps = false;

    public function fail($reason = null)
    {
        $this->status = self::FAILED;
        $this->error = $reason;
        $this->save();
    }

    public function finish()
    {
        $this->finished_at = date("Y-m-d H:i:s");
        $this->status = self::FINISHED;
        $this->save();
    }

    public function start()
    {
        $this->status = self::STARTED;
        $this->started_at = date("Y-m-d H:i:s");
        $this->save();
    }
}
