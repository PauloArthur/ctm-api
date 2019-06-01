<?php

namespace App\Http\Controllers;

use App\City;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    public function cheapest_meeting(Request $request)
    {
        $data = json_decode( $request->getContent() );

        $search_cheapest = new SearchCheapestController( $data->inputCities, $data->outboundDate, $data->inboundDate );
        
        /* 
            The cheapest value using input cities as destination
        */
        $cheapest_meeting = $search_cheapest->getCheapestMeetingCity();

        $input_cities_ids = array_map(function ($city) {
            return $city->id;
        }, $data->inputCities);

        /* 
            Selects ids from the cities not in the input array, asc ordering it by meal cost and housing cost
         */
        $remainign_cities = City::whereNotIn('id', $input_cities_ids)->select('id')
            ->orderBy('meal_cost', 'ASC')
            ->orderBy('housing_cost', 'ASC')
            ->get()
            ->toArray();
            
        $cheapest_meeting = $search_cheapest->getCheapestMeetingCity($remainign_cities, $cheapest_meeting);

        return compact("cheapest_meeting");
    }
}
