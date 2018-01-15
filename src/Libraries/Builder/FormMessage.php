<?php namespace CoasterCms\Libraries\Builder;

use Illuminate\Session\Store as Session;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class FormMessage
{

    /**
     * @var ViewErrorBag
     */
    protected $_errorBag;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var string
     */
    protected $_defaultBag;

    /**
     * @var string
     */
    protected $_errorClass;

    /**
     * FormMessage constructor.
     * @param Session $session
     * @param string  $defaultBag
     * @param string $errorClass
     */
    public function __construct($session, $defaultBag, $errorClass)
    {
        $this->_session = $session;
        $this->_errorBag = View::shared('errors');
        $this->_defaultBag = $defaultBag;
        $this->_errorClass = $errorClass;
    }

    /**
     * @param string $bag
     * @param MessageBag|array $messages
     * @return MessageBag
     */
    public function defaultBag($bag = null, $messages = null)
    {
        $bag = isset($bag) ? $bag : $this->_defaultBag;
        if (is_array($messages)) {
            $messageBag = new MessageBag($messages);
        } elseif ($messages instanceof MessageBagContract) {
            $messageBag = $messages;
        } else {
            $messageBag = new MessageBag();
        }
        if (!$this->_errorBag->hasBag($bag)) {
            $this->_errorBag->put($bag, $messageBag);
        }
        return $this->_errorBag->getBag($bag);
    }

    /**
     * @param MessageBag|array $messages
     * @param bool $flash
     */
    public function set($messages, $flash = true)
    {
        $this->defaultBag(null, $messages);
        if ($flash) {
            $this->_session->put('_flash.old', 'errors');
        }
    }

    /**
     * @param string $key
     * @param string $message
     * @param bool $flash
     */
    public function add($key, $message, $flash = true)
    {
        $this->defaultBag()->add($key, $message);
        if ($flash) {
            $this->_session->put('_flash.old', 'errors');
        }
    }

    /**
     * @param string $errorClass
     */
    public function setErrorClass($errorClass)
    {
        $this->_errorClass = $errorClass;
    }

    /**
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        if ($this->defaultBag()->has($key)) {
            return $this->defaultBag()->first($key);
        } else {
            $dotKey = $this->_dotNotation($key);
            return $this->defaultBag()->has($dotKey) ? $this->defaultBag()->first($dotKey) : null;
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getClass($key)
    {
        return ($this->getErrorMessage($key) !== null) ? $this->_errorClass : '';
    }

    /**
     * @return MessageBag
     */
    public function getMessageBag()
    {
        return $this->defaultBag();
    }

    /**
     * @param string $key
     * @return string
     */
    protected function _dotNotation($key)
    {
        return str_replace(['[', ']'], ['.', ''], $key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getErrorMessage($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getErrorClass($key)
    {
        return $this->getClass($key);
    }

    /**
     * @param string $key
     * @return string
     * @deprecated
     */
    public function get_class($key)
    {
        return $this->getClass($key);
    }

    /**
     * @param string $key
     * @return string
     * @deprecated
     */
    public function get_message($key)
    {
        return $this->get($key);
    }

}