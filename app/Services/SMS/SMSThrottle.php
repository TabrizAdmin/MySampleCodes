<?php

namespace App\Services\SMS;

interface SMSThrottle
{
    public function shouldThrottle(): bool;
}