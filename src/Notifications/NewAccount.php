<?php

namespace CoasterCms\Notifications;

use CoasterCms\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewAccount extends Notification
{

    /**
     * @var User
     */
    protected $_user;

    /**
     * @var string
     */
    protected $_password;

    /**
     * @var string
     */
    protected $_routeName;

    /**
     * PasswordReset constructor.
     * @param User $user
     * @param string $password
     * @param string $routeName
     */
    public function __construct($user, $password, $routeName)
    {
        $this->_user = $user;
        $this->_password = $password;
        $this->_routeName = $routeName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(config('coaster::site.name') . ': New Account Details')
            ->from(config('coaster::site.email'))
            ->line('You have been created a new account on '.url()->to('/').' please see details below:')
            ->line('Username: ' . $this->_user->email)
            ->line('Password: ' . $this->_password)
            ->action('Go to Login', url(route($this->_routeName)));
    }

}