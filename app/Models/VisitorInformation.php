<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorInformation extends Model
{
    use HasFactory;

    public $fillable = [
        "vis_ip",
        "vis_city",
        "vis_country",
        "vis_countrycode",
        "vis_os",
        "vis_browser",
        "vis_latitude",
        "vis_longitude",
    ];

    public function visitorsType($type, bool $sortBigger = false, bool $json = true)
    {
        $countriesCode = [];
        foreach ([
            [
                "vis_ip" => "192.168.1.1",
                "vis_city" => "New York",
                "vis_country" => "United States",
                "vis_countrycode" => "US",
                "vis_os" => "Windows 10",
                "vis_browser" => "Chrome",
                "vis_latitude" => 40.7128,
                "vis_longitude" => -74.0060,
                "type" => 1
            ]

        ] as $visitor) {
            if (!isset($countriesCode[$visitor->$type])) {
                $countriesCode[$visitor->$type] = 1;
            } else {
                $countriesCode[$visitor->$type]++;
            }
        }
        if ($sortBigger) {
            arsort($countriesCode);
        }
        return $json ? response()->json($countriesCode) : $countriesCode;
    }

    /**
     * @return array
     */
    public static function visitorsMap(): array
    {
        $countriesCode = [];
        foreach (VisitorInformation::all() as $visitor) {
            if (!isset($countriesCode[$visitor->vis_countrycode])) {
                $countriesCode[$visitor->vis_countrycode] = 1;
            } else {
                $countriesCode[$visitor->vis_countrycode]++;
            }
        }
        return $countriesCode;
    }

    /**
     * @return \array[][]
     */
    public static function visitorsBrowser(): array
    {
        $countriesCode = [];
        foreach (VisitorInformation::all() as $visitor) {
            if (!isset($countriesCode[$visitor->vis_browser])) {
                $countriesCode[$visitor->vis_browser] = 1;
            } else {
                $countriesCode[$visitor->vis_browser]++;
            }
        }
        $countries = (new VisitorInformation)->visitorsType("vis_country", true);
        return [
            ["countries" => $countries],
            ["countriesCode" => $countriesCode],
        ];
    }
}
