<?php
namespace Seeds\Classes;
use GuzzleHttp\Client;

class LivingCost {
    public $client;

    public function __construct()
    {
        $this->client =  new Client([
            'base_uri' => 'https://cost-of-living-api-lqvgibwbps.now.sh/',
        ]);
    }

    private function getLivingCost(String $city)
    {   
        $city = str_replace(" ", "-", ucwords( $city ));

        $res = $this->client->get( $city . '?currency=USD' );

        return json_decode( $res->getBody()->getContents() )->costs;
    }

    public function getCityMeal(String $city, Array $meals)
    {
        $costs = $this->getLivingCost($city);

        if (!$costs) return false;

        $meals_keys = array_keys( $meals );

        $filtered_costs = array_filter($costs, function ($cost) use ($meals_keys) {
            return in_array($cost->item, $meals_keys);
        });

        if (!$filtered_costs) return false;

        $count_costs = count($filtered_costs);

        $sum_costs = array_reduce ( $filtered_costs, function ($sum, $cost) use ($meals) {
            $weight = $meals[$cost->item];
            return $sum + ($cost->cost * $weight);
        }, 0 ); 

        return $count_costs ? $sum_costs/$count_costs : false;
    }
}