<?php

namespace App\Services\SMS;

use Twilio\Exceptions\RestException;

class TwilioSMS implements SendSMS
{
    private $sid;
    private $token;
    private $from;
    /*
     * @var \Aloha\Twilio\Twilio
     */
    private $twilio;

    public function __construct($sid, $token, $from)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;

        $this->twilio = new \Aloha\Twilio\Twilio($sid, $token, $from);
    }

    public function verificationSMS($phone)
    {
        $code = mt_rand(10000, 99999);
        $smsBody = "Your CooMo travel verification code is: $code";

        try {
            $this->twilio->message($phone, $smsBody);

            return [true, $code];
        } catch (RestException $e) {
            return [false, null];
        }
    }
}