<?php

namespace CoasterCms\Notifications;

use CoasterCms\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordReset extends Notification
{

    /**
     * @var User
     */
    protected $_user;

    /**
     * @var string
     */
    protected $_routeName;

    /**
     * PasswordReset constructor.
     * @param User $user
     * @param string $routeName
     */
    public function __construct($user, $routeName)
    {
        $this->_user = $user;
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
            ->subject(config('coaster::site.name') . ': Forgotten Password')
            ->from(config('coaster::site.email'))
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', url(route($this->_routeName, $this->_user->tmp_code)))
            ->line('If you did not request a password reset, no further action is required.');
    }

}