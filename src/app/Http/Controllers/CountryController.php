<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Language;
use App\Services\Country\CountryFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CountryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = CountryFilters::fromRequest($request);

        $countries = Country::query()
            ->with('capitals')
            ->filter($filters)
            ->orderBy('name_common')
            ->paginate(21)
            ->withQueryString();

        $regions = Country::query()
            ->whereNotNull('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        $subregions = Country::query()
            ->whereNotNull('subregion')
            ->distinct()
            ->orderBy('region')
            ->orderBy('subregion')
            ->get(['region', 'subregion'])
            ->groupBy('region');

        $languages = Language::query()
            ->has('countries')
            ->withCount('countries')
            ->orderByDesc('countries_count')
            ->orderBy('name')
            ->get();

        $view = view('countries.index', compact(
            'countries',
            'regions',
            'subregions',
            'languages',
            'filters'
        ));

        return response($request->ajax() ? $view->fragment('results') : $view)
            ->header('Vary', 'X-Requested-With');
    }
}
