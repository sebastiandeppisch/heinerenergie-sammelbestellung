<?php

namespace App\Mail;

use App\Models\Advice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderLink extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;

    public string $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Advice $advice)
    {
        $data = [
            'advisorEmail' => $advice->advisor->email,
            'firstName' => $advice->firstName,
            'lastName' => $advice->lastName,
            'street' => $advice->street,
            'streetNumber' => $advice->streetNumber,
            'zip' => $advice->zip,
            'city' => $advice->city,
            'email' => $advice->email,
            'phone' => $advice->phone,
            'email_confirmation' => $advice->email,
        ];
        $this->url = url('/sammelbestellung?formdata=' . json_encode($data));
        $this->password = Setting::get('orderFormPassword');
    }
    

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.advices.orderlink')
            ->subject('heiner*energie Sammelbestellung');
    }
}
