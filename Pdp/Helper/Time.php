<?php
namespace Litvinenko\Combinatorics\Pdp\Helper;

class Time
{
    protected $start;

    public function start()
    {
        $this->start = microtime(true);
    }

    public function getTimeFromStart()
    {
        return microtime(true) - $this->start;
    }
}