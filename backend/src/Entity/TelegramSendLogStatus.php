<?php

declare(strict_types=1);

namespace App\Entity;

enum TelegramSendLogStatus: string
{
    case SENT = 'SENT';
    case FAILED = 'FAILED';
}
