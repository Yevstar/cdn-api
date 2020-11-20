<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\City;

class CityController extends Controller
{
    public function citiesForState($state) {
    	$cities = City::where('state', $state)->get();

    	return response()->json($cities);
    }
}
