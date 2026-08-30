<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('รหัส OTP สำหรับยืนยันการสมัครสมาชิก | Checkup')
            ->view('emails.registration-otp', [
                'firstName' => $notifiable->first_name,
                'otp' => $this->otp,
            ]);
    }
}
