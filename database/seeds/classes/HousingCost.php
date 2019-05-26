<?php
namespace Seeds\Classes;
use GuzzleHttp\Client;
use Carbon\Carbon;

class HousingCost {
    public $client;
    private $auth;
    private $auth_time;

    public function __construct()
    {
        $this->setClient();
    }

    private function setClient()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.makcorps.com/free/',
            'headers' => ['Authorization' => 'JWT '. $this->getAuth()]
        ]);
    }

    private function getClient()
    {
        return $this->client;
    }

    private function setAuthTime(Carbon $time)
    {
        $this->auth_time = $time;
    }

    private function getAuthTime()
    {
        return $this->auth_time;
    }

    private function setAuth(String $auth)
    {
        $this->auth = $auth;
    }

    private function getAuth()
    {   
        if ( !$this->validateAuthTime() ) $this->generateAuth();
        
        return $this->auth;
    }

    private function generateAuth()
    {           
        $auth_client =  new Client([
            'base_uri' => 'https://api.makcorps.com/',
            'headers' => ['Content-Type: application/json']
        ]);

        $res = $auth_client->post( '/auth', ['json' => [
            'username' => 'PauloArthur',
            'password' => '123456'
        ]]);

        $auth = json_decode( $res->getBody()->getContents() )->access_token;

        $this->setAuthTime( new Carbon() );

        $this->setAuth( $auth );
    }

    private function validateAuthTime()
    {
        $create_time = $this->getAuthTime();

        if ( !$create_time ) return false;

        $aux_time = new Carbon($create_time);
        $aux_time->addMinutes(29);

        $current = new Carbon();

        return ( $current < $aux_time );
    }

    private function getHotels(String $city)
    {   
        $city = str_replace(" ", "-", strtolower( $city ));

        if ( !$this->validateAuthTime() ) $this->setClient();

        $res = $this->getClient()->get( $city );

        return json_decode( $res->getBody()->getContents() )->comparison;
    }

    public function getCityHousing(String $city)
    {
        $hotels = $this->getHotels($city);

        $costs = array_map(function ($hotel) {
            return $hotel;
        }, $hotels);

        dd($costs);

        return false;
    }
}