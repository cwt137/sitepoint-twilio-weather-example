# Example Weather App Using Twilio

This is the completed example code that shows how to build a weather app that leverages Twilio.

## Install Instructions

* Clone repository

    ```bash
    git clone https://github.com/cwt137/sitepoint-twilio-weather-example Laravel
    ```

    ```bash
    cd Laravel
    ```


* Install Dependencies and Setup Database

    ```bash
    composer install
    php -r "copy('.env.example', '.env');"
    php artisan key:generate
    php artisan migrate
    ```

## Read The Article

To learn more about this code, read the article: