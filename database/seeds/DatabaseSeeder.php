<?php

use App\City;
use Seeds\Classes\Skyscanner;
use Seeds\Classes\LivingCost;
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
        
        $skyscanner = new Skyscanner();

        $living_cost = new LivingCost();

        $meals = [
            "Meal, Inexpensive Restaurant" => 1,
            "Meal for 2 People, Mid-range Restaurant, Three-course" => 1,
            "Water (12 oz small bottle)" => 2
        ];

        $cities_places = [];

        $cities = $this->getIATACities();
        $this->command->info('IATA Cities loaded!');

        $cities_length = count($cities);
        $count = 0;
        $count_save = 0;

        foreach ($cities as $city) {
            $place = $skyscanner->getCityPlace($city->country_code, $city->name);
            $count++;

            if ( !$place ) continue;

            $meals_avg = $living_cost->getCityMeal($city->name, $meals);

            if ( !$meals_avg ) continue;
        
            $place["meals_cost"] = $meals_avg;
            $cities_places[] = $place;
            $count_save++;
            $this->command->info('City ' . $count_save . ' saved, of '. $count .' of '. $cities_length. '...');
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
}
