<?php

namespace App\States\Doing;

class Cancelled extends DoingState
{
    public static $name = 'cancelled';

    public function color(): string
    {
        return 'red';
    }
}
