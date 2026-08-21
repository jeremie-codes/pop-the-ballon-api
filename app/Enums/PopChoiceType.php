<?php

namespace App\Enums;

enum PopChoiceType: string
{
    case DISCOVERY = 'discovery';
    case MATCH = 'match';
    case COMPATIBILITY = 'compatibility';
}
