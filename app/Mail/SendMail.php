<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $subject;
    public $view;
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject = 'Send Mail Test', $view = 'test', $data = [])
    {
        $this->subject  = $subject;
        $this->view     = $view;
        $this->data     = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
            ->view('email.' . $this->view, $this->data);
    }
}
