<?php

namespace App\Enums;

enum ProcessType: string
{
    case Booking = 'booking';
    case Reservation = 'reservation';
    case Sale = 'sale';
}
