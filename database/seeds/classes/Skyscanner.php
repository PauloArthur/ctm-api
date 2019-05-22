<?php
namespace Seeds\Classes;
use GuzzleHttp\Client;

class Skyscanner {
    public $client;

    public function __construct()
    { 
        $this->client = new Client([
            'base_uri' => 'https://skyscanner-skyscanner-flight-search-v1.p.rapidapi.com/apiservices/autosuggest/v1.0/',
            'headers' => [
                'X-RapidAPI-Key' => '430f75ded0msh49407f569b7e7acp13e9c2jsnfc1f51ff018b',
                'X-RapidAPI-Host' => 'skyscanner-skyscanner-flight-search-v1.p.rapidapi.com',
            ]
        ]);
    }

    private function getSkyscannerPlaces(String $country, String $city)
    {   
        $city = str_replace(" ", "+", ucwords( $city ));

        $res = $this->client->get( $country.'/USD/en-US/?query='.$city );

        return json_decode( $res->getBody()->getContents() )->Places;
    }

    public function getCityPlace(String $country, String $city)
    {
        $places = $this->getSkyscannerPlaces($country, $city);

        if (!$places) return false;
        
        $filtered_places = array_filter($places, function ($place) use ($city) {
            return $place->PlaceName === $city;
        });

        $place = reset( $filtered_places );

        return $place ? [
            "city" => $city,
            "city_id" => $place->CityId,
            "country_code" => $country        
        ] : false;
    }
}