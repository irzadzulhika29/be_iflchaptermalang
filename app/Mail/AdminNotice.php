<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotice extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $notifMessage;
    public $errorMessage;
    public $userEmail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $notifMessage, $errorMessage, $userEmail)
    {
      $this->subject = $subject;
      $this->notifMessage = $notifMessage;
      $this->errorMessage = $errorMessage;
      $this->userEmail = $userEmail;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: "$this->subject",
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mail.admin_notice',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
