<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(Request $request): View
    {
        $countries = Country::query()
            ->with('capitals')
            ->when($request->filled('region'), fn ($query) => $query->where('region', $request->string('region')))
            ->orderBy('name_common')
            ->paginate(21)
            ->withQueryString();

        $regions = Country::query()
            ->whereNotNull('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('countries.index', compact('countries', 'regions'));
    }
}
