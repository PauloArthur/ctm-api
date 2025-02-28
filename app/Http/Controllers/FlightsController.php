<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class FlightsController extends Controller
{
    private $client;
    private $requests_count;

    function __construct() {        
        $this->client = new Client([
            'base_uri' => 'https://skyscanner-skyscanner-flight-search-v1.p.rapidapi.com/apiservices/browseroutes/v1.0/',
            'headers' => [
                'X-RapidAPI-Key' => '430f75ded0msh49407f569b7e7acp13e9c2jsnfc1f51ff018b',
                'X-RapidAPI-Host' => 'skyscanner-skyscanner-flight-search-v1.p.rapidapi.com',
            ]
        ]);
        $this->requests_count = 0;
    }

    private function setRequestsCountZero()
    { 
        $this->requests_count = 0;
    }

    private function requestCount()
    { 
        $this->requests_count = $this->requests_count + 1;
        $has_timeout = $this->requests_count === 500;

        if ( $has_timeout ) {
            sleep(60);
            $this->setRequestsCountZero();
        }
    }

    private function getQuotes(Object $origin, Object $destination, String $url_date) 
    {
        $url = "US/USD/en-US/$origin->city_id/$destination->city_id/$url_date";

        return $this->client->requestAsync( 'GET', $url )->then(
            function (ResponseInterface $res) {
                return json_decode( $res->getBody()->getContents() )->Quotes;
            },
            function (RequestException $e) {
                return false;
            }
        )->wait();
    }

    public function getCheapestQuote(Object $origin, Object $destination, String $url_date)
    {
        $quotes = $this->getQuotes($origin, $destination, $url_date);

        if ( !$quotes ) return false;

        $quotes = $this->sortQuotesInAscByMinPrice( $quotes );

        return reset($quotes);
    }
    
    /* 
        Sort quotes in Asc order by min price
    */
    private function sortQuotesInAscByMinPrice(Array $quotes)
    {
        usort($quotes, function ($quote1, $quote2) {
            return $quote1->MinPrice > $quote2->MinPrice;
        });

        return $quotes;
    }
}
