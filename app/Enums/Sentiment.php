<?php

namespace App\Enums;

enum Sentiment: string
{
    case Preferred = 'preferred';
    case Liked = 'liked';
    case Disliked = 'disliked';
}
