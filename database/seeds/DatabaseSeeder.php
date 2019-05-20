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

        $cities = $this->getIATACities();
        
        $sky_client = $this->createSkyscannerClient();
        
        $places = $this->getCityPlace($sky_client, $cities[0]->country_code, $cities[0]->name);
        
        dd($places);

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

    private function getCityPlace(Client $client, String $country, String $city)
    {
        $places = $this->getSkyscannerPlaces($client, $country, $city);
        
        $filtered_places = array_filter($places, function ($place) {
            return ($place->PlaceName === $city);
        });

        $place = reset( $filtered_places );

        return $place ? [
            "city" => $city,
            "city_id" => $place->CityId,
            "country_code" => $country        
        ] : false;
    }
}
