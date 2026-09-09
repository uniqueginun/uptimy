<?php

namespace App\Enums;

enum CheckStatus: string
{
    case Up = 'up';
    case Down = 'down';
    case Error = 'error';
}
