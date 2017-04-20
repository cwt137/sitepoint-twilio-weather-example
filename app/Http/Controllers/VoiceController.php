<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Twilio\Twiml;

class VoiceController extends Controller
{
    protected $weather;

    public function __construct(WeatherService $weatherService)
    {
        $this->weather = $weatherService;
    }

    public function showEnterZipcode()
    {
        $response = new Twiml();

        $gather = $response->gather(
            [
                'numDigits' => 5,
                'action' => route('zip-weather', [], false)
            ]
        );

        $gather->say('Enter the zipcode for the weather you want');

        return $response;
    }

    public function showZipcodeWeather(Request $request)
    {
        $zip = $request->input('Digits');

        $request->session()->put('zipcode', $zip);

        return $this->weather->getWeather($zip, 'Today');
    }

    public function showDayWeather(Request $request)
    {
        $digit = $request->input('Digits', '0');

        switch ($digit) {
            case '8':
                $response = new Twiml();
                $response->redirect(route('credits', [], false));
                break;
            case '9':
                $response = new Twiml();
                $response->redirect(route('enter-zip', [], false));
                break;
            case '0':
                $response = new Twiml();
                $response->hangup();
                break;
            default:
                $zip = $request->session()->get('zipcode');
                $day = $this->weather->daysOfWeek[$digit];
                $response = $this->weather->getWeather($zip, $day);
                break;
        }

        return $response;
    }

    public function showCredits()
    {
        $response = new Twiml();
        $credits = $this->weather->getCredits();

        $response->say($credits);
        $response->hangup();

        return $response;
    }
}
