<?php

namespace App\Services\SMS;

interface SendSMS {
    public function verificationSMS($phone);
}