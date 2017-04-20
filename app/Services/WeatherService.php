<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Twilio\Twiml;

class WeatherService
{
    public $daysOfWeek = [
        'Today',
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];

    public function getWeather($zip, $dayName, $forSms = false)
    {

        $point = $this->getPoint($zip);
        $tz = $this->getTimeZone($point);
        $forecast = $this->retrieveNwsData($zip);
        $ts = $this->getTimestamp($dayName, $zip);

        $tzObj = new \DateTimeZone($tz->timezoneId);

        $tsObj = new \DateTime(null, $tzObj);
        $tsObj->setTimestamp($ts);

        foreach ($forecast->properties->periods as $k => $period) {
            $startTs = strtotime($period->startTime);
            $endTs = strtotime($period->endTime);

            if ($ts > $startTs and $ts < $endTs) {
                $day = $period;
                break;
            }
        }

        $response = new Twiml();

        $weather = $day->name;
        $weather .= ' the ' . $tsObj->format('jS') . ': ';

        if ($forSms) {
            $remainingChars = 140 - strlen($weather);

            if (strlen($day->detailedForecast) > $remainingChars) {
                $weather .= $day->shortForecast;
                $weather .= '. High of ' . $day->temperature . '. ';
                $weather .= $day->windDirection;
                $weather .= ' winds of ' . $day->windSpeed;
            } else {
                $weather .= $day->detailedForecast;
            }

            $response->message($weather);
        } else {
            $weather .= $day->detailedForecast;

            $gather = $response->gather(
                [
                    'numDigits' => 1,
                    'action' => route('day-weather', [], false)
                ]
            );

            $menuText = ' ';
            $menuText .= "Press 1 for Sunday, 2 for Monday, 3 for Tuesday, ";
            $menuText .= "4 for Wednesday, 5 for Thursday, 6 for Friday, ";
            $menuText .= "7 for Saturday. Press 8 for the credits. ";
            $menuText .= "Press 9 to enter in a new zipcode. ";
            $menuText .= "Press 0 to hang up.";

            $gather->say($weather . $menuText);
        }

        return $response;
    }

    protected function retrieveNwsData($zip)
    {
        return Cache::remember('weather:' . $zip, 60, function () use ($zip) {
            $point = $this->getPoint($zip);

            $point = $point->lat . ',' . $point->lng;
            $url = 'https://api.weather.gov/points/' . $point . '/forecast';

            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/geo+json',
                ]
            ]);

            return json_decode((string)$response->getBody());
        });
    }

    protected function getPoint($zip)
    {
        return Cache::remember('latLng:' . $zip, 1440, function () use ($zip) {
            $client = new \GuzzleHttp\Client();
            $url = 'http://api.geonames.org/postalCodeSearchJSON';

            $response = $client->request('GET', $url, [
                'query' => [
                    'postalcode' => $zip,
                    'countryBias' => 'US',
                    'username' => env('GEONAMES_USERNAME')
                ]
            ]);

            $json = json_decode((string)$response->getBody());

            return $json->postalCodes[0];
        });
    }

    protected function getTimeZone($point)
    {
        $key = 'timezone:' . $point->lat . ',' . $point->lng;

        return Cache::remember($key, 1440, function () use ($point) {
            $client = new \GuzzleHttp\Client();
            $url = 'http://api.geonames.org/timezoneJSON';

            $response = $client->request('GET', $url, [
                'query' => [
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                    'username' => env('GEONAMES_USERNAME')
                ]
            ]);

            return json_decode((string) $response->getBody());
        });
    }

    protected function getTimestamp($day, $zip)
    {
        $point = $this->getPoint($zip);
        $tz = $this->getTimeZone($point);

        $tzObj = new \DateTimeZone($tz->timezoneId);

        $now = new \DateTime(null, $tzObj);

        $hourNow = $now->format('G');
        $dayNow = $now->format('l');

        if ($day == $dayNow and $hourNow >= 18) {
            $time = new \DateTime('next ' . $day . ' noon', $tzObj);
            $ts = $time->getTimestamp();
        } elseif (($day == 'Today' or $day == $dayNow) and $hourNow >= 6) {
            $ts = $now->getTimestamp();
        } else {
            $time = new \DateTime($day . ' noon', $tzObj);
            $ts = $time->getTimestamp();
        }

        return $ts;
    }

    public function getCredits()
    {
        $credits = "Weather data provided by the National Weather Service. ";
        $credits .= "Zipcode data provided by GeoNames.";

        return $credits;
    }
}
