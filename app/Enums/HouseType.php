<?php

namespace App\Enums;

enum HouseType: int
{
    case SingleFamily = 0;
    case MultiFamily = 1;
    case Other = 2;
}
