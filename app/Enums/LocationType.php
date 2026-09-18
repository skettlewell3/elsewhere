<?php

namespace App\Enums;

enum LocationType: string
{
    case Nation = 'nation';

    case Region = 'region';
    case County = 'county';
    case Province = 'province';
    case State = 'state';
    case AutonomousCommunity = 'autonomous_community';

    case City = 'city';
    case Town = 'town';
    case Village = 'village';
    case District = 'district';
    case Borough = 'borough';

    public function filterGroup(): string
    {
        return match ($this) {
            self::Nation => 'country',

            self::Region,
            self::County,
            self::Province,
            self::State,
            self::AutonomousCommunity => 'area',

            self::City,
            self::Town,
            self::Village,
            self::District,
            self::Borough => 'locality',
        };
    }
}