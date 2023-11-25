<?php

namespace App\Http\Controllers;

use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        // TASK: load the relationship average of team size
        $countries = Country::with(['teams'])->get()
            ->each(function ($items) {
                $items->teams_avg_size = $items->teams->avg('size');
            });
        return view('countries.index', compact('countries'));
    }
}
