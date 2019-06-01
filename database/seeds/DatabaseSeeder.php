<?php

use App\City;
use Seeds\Classes\Skyscanner;
use Seeds\Classes\LivingCost;
use Seeds\Classes\HousingCost;

use Carbon\Carbon;
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

        // $housing_cost = new HousingCost();

        $meals = [
            "Meal, Inexpensive Restaurant" => 1,
            "Water (12 oz small bottle)" => 2
        ];

        $cities_places = [];

        $cities = $this->getIATACities();

        $this->command->info('IATA Cities loaded!');

        $cities_length = count($cities);
        $count = 0;
        $count_save = 0;

        $interval_time = new Carbon();

        $this->command->line("Initial Time: ". $interval_time);
        foreach ($cities as $city) {
            $now = new Carbon();
            if ( $now->isAfter($interval_time->copy()->addHours(1)) ){
                $interval_time = $now;
                $this->command->line("Time: ". $interval_time);
            }

            $place = $skyscanner->getCityPlace($city->country_code, $city->name);
            $count++;

            if ( !$place ) continue;

            $meals_avg = $living_cost->getCityMeal($city->name, $meals);
            $place["meal_cost"] = $meals_avg;

            if ( !$meals_avg ) continue;
            /*$housing_avg = $housing_cost->getCityHousing($city->name);
            if ( !$housing_avg ) continue; */
            
            $place["housing_cost"] = float_rand(25.0, 150.0, 2);
            
            $cities_places[] = $place;
            City::create($place);
            $count_save++;
            
            $this->command->info('City '.$count_save.' saved, '.$count.' of '.$cities_length.'...');
        }
        $this->command->line("Finish Time: ". $interval_time);
        
        dd($cities_places);

    }

    private function getIATACities()
    {
        $client = new Client([ 'base_uri' => 'https://iatacodes.org/api/v6/' ]);
                
        $res = $client->get('cities?api_key=140e40e1-44ce-4a5d-8412-bf9b0f6dd7a5', ['verify' => false]);

        return json_decode( $res->getBody()->getContents() )->response;
    }
}


/**
 * Generate Float Random Number
 *
 * @param float $Min Minimal value
 * @param float $Max Maximal value
 * @param int $round The optional number of decimal digits to round to. default 0 means not round
 * @return float Random float value
 */
function float_rand($Min, $Max, $round=0){
    //validate input
    if ($Min > $Max) { $min=$Max; $max=$Min; }
    else { $min=$Min; $max=$Max; }

    $randomfloat = $min + mt_rand() / mt_getrandmax() * ($max - $min);

    if($round > 0)
        $randomfloat = round($randomfloat, $round);

    return $randomfloat;
}