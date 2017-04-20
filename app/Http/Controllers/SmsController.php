<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Twilio\Twiml;

class SmsController extends Controller
{
    protected $weather;

    public function __construct(WeatherService $weatherService)
    {
        $this->weather = $weatherService;
    }

    public function showWeather(Request $request)
    {
        $parts = $this->parseBody($request);

        switch ($parts['command']) {
            case 'zipcode':
                $zip = $parts['data'];

                $request->session()->put('zipcode', $zip);

                $response = $this->weather->getWeather($zip, 'Today', true);

                break;
            case 'day':
                $zip = $request->session()->get('zipcode');

                $response = $this->weather->getWeather($zip, $parts['data'], true);

                break;
            case 'credits':
                $response = new Twiml();

                $response->message($this->weather->getCredits());

                break;
            default:
                $response = new Twiml();

                $text = 'Type in a zipcode to get the current weather. ';
                $text .= 'After that, you can type the day of the week to get that weather.';

                $response->message($text);

                break;
        }

        return $response;
    }

    private function parseBody($request)
    {
        $ret = ['command' => ''];
        $body = trim($request->input('Body'));

        if (is_numeric($body) and strlen($body) == 5) {
            $ret['command'] = 'zipcode';
            $ret['data'] = $body;
        }

        if (in_array(ucfirst(strtolower($body)), $this->weather->daysOfWeek) !== false) {
            $ret['command'] = 'day';
            $ret['data'] = ucfirst(strtolower($body));
        }

        if (strtolower($body) == 'credits') {
            $ret['command'] = 'credits';
        }

        return $ret;
    }
}
