<?php

namespace App\Enums;

enum AdviceStatusResult: int
{
    case New = 0;
    case InProgress = 1;
    case Completed = 2;
    case Unsuccessfully = 3;
}
