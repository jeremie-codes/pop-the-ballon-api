<?php

namespace App\Enums;

enum MessageType: string
{

    case TEXT = 'text';
    case VOICE = 'voice';
    // On prépare déjà le futur
    case IMAGE = 'image';
    case VIDEO = 'video';

}
