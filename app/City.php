<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    //
    protected $fillable = [ 'city_name', 'city_id', 'country_code', 'meal_cost', 'housing_cost' ];
}
