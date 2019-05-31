<?php

namespace App\Http\Controllers;

use App\City;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SearchCheapestController extends FlightsController
{
    private $input_cities;
    private $sum_employees;
    private $travel_duration;
    private $url_date;

    function __construct(Array $input_cities, String $outbound, String $inbound) {
        parent::__construct();

        $this->input_cities = $this->sortCitiesDescByEmployeesNumber( $input_cities );
        $this->sum_employees = $this->getTotalSumEmployees( $input_cities );

        $outbound_date = Carbon::createMidnightDate($outbound);
        $inbound_date = Carbon::createMidnightDate($inbound);

        $this->travel_duration = $outbound_date->diffInDays($inbound_date);

        $this->url_date = $outbound_date->toDateString()."?inboundpartialdate=".$inbound_date->toDateString();
    }

    /* 
        Transforms an array of objects to a matrix, array of arrays
    */
    private function transformArrayOfObjectsToMatrix(Array $array)
    {
        return array_map( function ($element) {
            return (array) $element;
        }, $array);
    }

    /* 
        Sorts input cities in Desc order by employees number
    */
    private function sortCitiesDescByEmployeesNumber(Array $input_cities)
    {
        $input_cities = $this->transformArrayOfObjectsToMatrix( $input_cities );
        
        usort($input_cities, function ($city1, $city2) {
            return $city1["employees"] < $city2["employees"];
        });

        return $input_cities;
    }

    /* 
        Total number of employees
    */
    private function getTotalSumEmployees(Array $input_cities)
    {
        return array_reduce ( $input_cities, function ($sum, $city) {
            $employees = is_numeric((float)$city->employees) ? (float)$city->employees : 0;
            return $sum + $employees;
        }, 0 );
    }

    /* 
        Gets the cheapest city from destinations array and the old cheapest city values

        When destinations array is empty, searches using own input cities as destinations

        When old cheapest city is empty, assumes is the first iteration
    */
    public function getCheapestMeetingCity(Array $destinations = [], Array $old_cheapest = [] )
    {
        $origins = $this->input_cities;
        $destinations = !$destinations ? $this->input_cities : $destinations;
        
        $old_cheapest = !$old_cheapest ? [
            'sum_total_cost' => INF,
            'destination_city' => null,
            'separate_costs' => []
        ] : $old_cheapest ;

        foreach ( $destinations as $destination ) {
            $destination_city = City::find($destination['id']);
            
            $has_employees = isset($destination["employees"]);
            $employees = $has_employees ? $destination['employees'] : 0;
            
            $meals_daily_cost = 3 * $destination_city->meal_cost * $this->sum_employees;
            
            $housing_employees = $this->sum_employees - $employees;
            $housing_daily_cost = $destination_city->housing_cost * $housing_employees;

            $meals_total_cost = $meals_daily_cost * $this->travel_duration;
            $housing_total_cost = $housing_daily_cost * $this->travel_duration;

            $living_cost = $meals_total_cost + $housing_total_cost;

            /* 
                Jumps iteration when living cost is already bigger than cheapest city total cost
            */
            if ( $old_cheapest && $living_cost > $old_cheapest['sum_total_cost'] ){
                continue;
            }

            $travel_total_cost = 0;
            $travel_costs = [];
            $has_no_quote = false;

            foreach ( $origins as $origin ) {
                /* 
                    Jumps iteration when origin is the same as destination
                */
                if ( $destination['id'] === $origin['id'] ){
                    continue;
                }

                $origin_city = City::find($origin['id']);

                $quote = parent::getCheapestQuote($origin_city, $destination_city, $this->url_date);
                
                $travel_costs[] = compact("quote", "origin_city", "destination_city");
                $has_no_quote = !$quote;
                $travel_price = $has_no_quote ? 0 : $quote->MinPrice;
                
                /* 
                    Jumps outside iteration if no quote is found from this origin
                */
                if ( $has_no_quote ){
                    continue 2;
                }
                
                $travel_total_cost = $travel_total_cost + ($travel_price * $origin['employees']);
            }   

            $sum_total_cost = $living_cost + $travel_total_cost;
            
            if ( $sum_total_cost < $old_cheapest['sum_total_cost'] ) {
                $separate_info = compact("meals_total_cost", "housing_total_cost", "travel_total_cost", "travel_duration", "travel_costs");
                $old_cheapest = compact("sum_total_cost", "destination_city", "separate_info");
            }
        }

        return $old_cheapest;
    }
}
