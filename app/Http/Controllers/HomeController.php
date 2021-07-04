<?php

namespace App\Http\Controllers;

use App\Repositories\CurrencyRepository;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $currencyRepository = new CurrencyRepository();

        $test = $currencyRepository->getCurrenciesValues(Carbon::now()->format('d/m/Y'));

        dd($test);

//        return view('home.index');
    }
}
