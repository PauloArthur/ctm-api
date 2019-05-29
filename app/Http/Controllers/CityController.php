<?php

namespace App\Http\Controllers;

use App\City;
use Illuminate\Http\Request;

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

    public function autocomplete($search)
    {
        $search = str_replace("+", "-", $search );

        $query = City::where('city_name', 'like', $search.'%')->select('id', 'city_name', 'country_code');
        
        return $query->orderBy('city_name', 'asc')->limit(15)->get();
    }

    public function cheapest(Request $request)
    {
        return $request;
    }
}
