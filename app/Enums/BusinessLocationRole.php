<?php

namespace App\Enums;

enum BusinessLocationRole: string
{
    case Branch = 'branch';
    case HeadOffice = 'head_office';
    case ServiceArea = 'service_area';
}