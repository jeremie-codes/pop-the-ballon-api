<?php

namespace App\Enums;

enum PopChoiceCategory: string
{
    case PERSONALITY = 'personality';
    case COMMUNICATION = 'communication';
    case ROMANCE = 'romance';
    case RELATIONSHIP = 'relationship';
    case LIFESTYLE = 'lifestyle';
    case VALUES = 'values';
    case FUN = 'fun';
}
