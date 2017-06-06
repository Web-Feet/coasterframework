<?php namespace CoasterCms\Libraries\Builder\FormMessage;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Support\ViewErrorBag;

class FormMessageInstance
{

    /**
     * @var ViewErrorBag
     */
    protected $_errorBag;

    /**
     * FormMessage constructor.
     * @param Request $request
     * @param string  $defaultBag
     * @param string $errorClass
     */
    public function __construct($request, $defaultBag, $errorClass)
    {
        if (!$request->session()->get('errors')) {
            $request->session()->put('errors', new ViewErrorBag);
        }
        $this->_errorBag = $request->session()->get('errors');
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
     */
    public function set($messages)
    {
        $this->defaultBag(null, $messages);
    }

    /**
     * @param string $key
     * @param string $message
     */
    public function add($key, $message)
    {
        $this->defaultBag()->add($key, $message);
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