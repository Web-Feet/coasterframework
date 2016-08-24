<?php namespace CoasterCms\Events\Cms;

class LoadAuth
{
    /**
     * @var string
     */
    public $authGuard;

    /**
     * @var string
     */
    public $authUserProvider;

    /**
     * LoadAuth constructor.
     * @param string $authGuard
     * @param string $authUserProvider
     */
    public function __construct(&$authGuard, &$authUserProvider)
    {
        $this->authGuard = &$authGuard;
        $this->authUserProvider = &$authUserProvider;
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
