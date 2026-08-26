<?php

namespace App\Enums;

enum PhaseType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Mixed = 'mixed';
}
