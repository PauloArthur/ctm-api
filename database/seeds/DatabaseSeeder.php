<?php

use App\City;
use GuzzleHttp\Client;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        $this->call('CityTableSeeder');

        $this->command->info('City table seeded!');
    }
}

class CityTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cities')->delete();
        
        $sky_client = $this->createSkyscannerClient();

        $col_client = $this->createCostOfLivingClient();

        $meals = [
            "Meal, Inexpensive Restaurant" => 1,
            "Meal for 2 People, Mid-range Restaurant, Three-course" => 1,
            "Water (12 oz small bottle)" => 6
        ];

        $cities_places = [];

        $cities = $this->getIATACities();
        $this->command->info('IATA Cities loaded!');

        $cities_length = count($cities);
        $count = 1;
        $count_save = 1;

        foreach ($cities as $city) {
            $place = $this->getCityPlace($sky_client, $city->country_code, $city->name);
            
            $this->command->info('City ' . $count .' of '. $cities_length. '...');
            $count++;

            if ($place) {
                $meals_avg = $this->getCityMeal($col_client, $city->name, $meals);
                if ($meals_avg) {
                    $place["meals_cost"] = $meals_avg;
                    $cities_places[] = $place;
                    $this->command->info('City ' . $count_save . ' saved...');
                    $count_save++;
                }
            }
        }
        
        dd($cities_places);

        City::create([
            'name' => 'Fortaleza',
            'city_id' => 'FOR-sky',
        ]);
    }

    private function getIATACities()
    {
        $client = new Client([ 'base_uri' => 'https://iatacodes.org/api/v6/' ]);
                
        $res = $client->get('cities?api_key=140e40e1-44ce-4a5d-8412-bf9b0f6dd7a5', ['verify' => false]);

        return json_decode( $res->getBody()->getContents() )->response;
    }

    private function createCostOfLivingClient()
    {
        return new Client([
            'base_uri' => 'https://cost-of-living-api-lqvgibwbps.now.sh/',
        ]);
    }

    private function createSkyscannerClient()
    {
        $headers = [
            'X-RapidAPI-Key' => '430f75ded0msh49407f569b7e7acp13e9c2jsnfc1f51ff018b',
            'X-RapidAPI-Host' => 'skyscanner-skyscanner-flight-search-v1.p.rapidapi.com',
        ];  

        return new Client([
            'base_uri' => 'https://skyscanner-skyscanner-flight-search-v1.p.rapidapi.com/apiservices/autosuggest/v1.0/',
            'headers' => $headers
        ]);
    }

    private function getSkyscannerPlaces(Client $client, String $country, String $city)
    {   
        $city = str_replace(" ", "+", ucwords( $city ));

        $res = $client->get( $country.'/USD/en-US/?query='.$city );

        return json_decode( $res->getBody()->getContents() )->Places;
    }

    private function getCostOfLiving(Client $client, String $city)
    {   
        $city = str_replace(" ", "-", ucwords( $city ));

        $res = $client->get( $city . '?currency=USD' );

        return json_decode( $res->getBody()->getContents() )->costs;
    }

    private function getCityPlace(Client $client, String $country, String $city)
    {
        $places = $this->getSkyscannerPlaces($client, $country, $city);
        
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

    private function getCityMeal(Client $client, String $city, Array $meals)
    {
        $costs = $this->getCostOfLiving($client, $city);

        $meals_keys = array_keys( $meals );
        
        $filtered_costs = array_filter($costs, function ($cost) use ($meals_keys) {
            return in_array($cost->item, $meals_keys);
        });

        $sum_costs = array_reduce ( $filtered_costs, function ($sum, $cost) use ($meals) {
            $weight = $meals[$cost->item];
            return $sum + ($cost->cost * $weight);
        }, 0 ); 

        $count_costs = count($filtered_costs);

        $avg_cost = $count_costs ? $sum_costs/$count_costs : false;

        return $avg_cost;
    }
}
