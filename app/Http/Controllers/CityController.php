<?php

namespace App\Http\Controllers;

use App\City;
use Illuminate\Http\Request;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CityController extends Controller
{
    public function index()
    {
        return City::all();
    }
 
    public function show(City $city)
    {
        return City::find($city);
    }

    public function autocomplete(String $search)
    {
        $search = str_replace("+", "-", $search );

        return City::where('city_name', 'like', $search.'%')
            ->select('id', 'city_name', 'country_code')
            ->orderBy('city_name', 'asc')
            ->limit(15)
            ->get();
    }

    public function cheapest(Request $request)
    {
        $data = json_decode( $request->getContent() );
       
        $input_cities = $data->inputCities;
        
        /* 
            Total number of employees
        */
        $sum_employees = array_reduce ( $input_cities, function ($sum, $city) {
            $employees = is_numeric((float)$city->employees) ? (float)$city->employees : 0;
            return $sum + $employees;
        }, 0 );

        /* 
            Sort input cities in Desc order of employees number
        */
        usort($input_cities, function ($city1, $city2) {
            return $city1->employees < $city2->employees;
        });
        
        $url_date = "$data->outboundDate?inboundpartialdate=$data->inboundDate";
        $outbound_date = Carbon::createMidnightDate($data->outboundDate);
        $inbound_date = Carbon::createMidnightDate($data->inboundDate);

        $travel_duration_in_days = $outbound_date->diffInDays($inbound_date);

        $client = $this->getSkyscannerClient();

        $cheapest_meeting = [
            'sum_total_cost' => INF,
            'destination_city' => null,
            'separate_costs' => []
        ];

        foreach ( $input_cities as $destination ) {
            $destination_city = City::find($destination->id);
            
            $meals_daily_cost = 3 * $destination_city->meal_cost * $sum_employees;

            $housing_employees = $sum_employees - $destination->employees;
            $housing_daily_cost = $destination_city->housing_cost * $housing_employees;

            $meals_total_cost = $meals_daily_cost * $travel_duration_in_days;
            $housing_total_cost = $housing_daily_cost * $travel_duration_in_days;

            $travel_total_cost = 0;
            $has_no_quote = false;

            foreach ( $input_cities as $origin ) {
                if ( $destination->id === $origin->id ) continue;

                $origin_city = City::find($origin->id);

                $quotes = $this->getQuotes($client, $origin_city, $destination_city, $url_date);
                $has_no_quote = !$quotes;

                if ( $has_no_quote ) continue;
                
                $travel_price = $this->getCheapestQuote( $quotes );

                $travel_total_cost = $travel_total_cost + ($travel_price * $origin->employees);
            }   

            if ( $has_no_quote ) continue;

            $sum_total_cost = $meals_total_cost + $housing_total_cost + $travel_total_cost;
            
            if ( $sum_total_cost < $cheapest_meeting['sum_total_cost'] ) {
                $separate_info = compact("meals_total_cost", "housing_total_cost", "travel_total_cost", "travel_duration_in_days");
                $cheapest_meeting = compact("sum_total_cost", "destination_city", "separate_info");
            }
        }

        return compact("cheapest_meeting");
    }

    private function getSkyscannerClient() 
    {
        return new Client([
            'base_uri' => 'https://skyscanner-skyscanner-flight-search-v1.p.rapidapi.com/apiservices/browseroutes/v1.0/',
            'headers' => [
                'X-RapidAPI-Key' => '430f75ded0msh49407f569b7e7acp13e9c2jsnfc1f51ff018b',
                'X-RapidAPI-Host' => 'skyscanner-skyscanner-flight-search-v1.p.rapidapi.com',
            ]
        ]);
    }

    private function getQuotes(Client $client, City $origin, City $destination, String $url_date) 
    {
        $url = "US/USD/en-US/$origin->city_id/$destination->city_id/$url_date";

        return $client->requestAsync( 'GET', $url )->then(
            function (ResponseInterface $res) {
                return json_decode( $res->getBody()->getContents() )->Quotes;
            },
            function (RequestException $e) {
                return false;
            }
        )->wait();
    }

    private function getCheapestQuote(Array $quotes)
    {
        /* 
            Sort quotes in Asc order by quote Min price
        */
        usort($quotes, function ($quote1, $quote2) {
            return $quote1->MinPrice > $quote2->MinPrice;
        });

        return reset($quotes)->MinPrice;
    }
}
