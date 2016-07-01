<?php namespace CoasterCms\Events;

class DisplayPage
{
    public $t;

    /**
     * DisplayPage constructor.
     * @param $t
     */
    public function __construct($t)
    {
        $this->t = $t;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
