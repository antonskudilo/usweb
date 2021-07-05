<?php

namespace App\Http\Controllers;

use App\Repositories\CurrencyRepository;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $currencyRepository = new CurrencyRepository();

//        $test = $currencyRepository->getCurrenciesValues(Carbon::now()->format('d/m/Y'));

//        dd($test);

        $testCurrencyDynamicsParams = [
            'date_req1' => Carbon::now()->subWeek()->startOfWeek()->format('d/m/Y'),
            'date_req2' => Carbon::now()->format('d/m/Y'),
            'VAL_NM_RQ' => 'R01235'
        ];

        $testCurrencyDynamics = $currencyRepository->getCurrencyDynamics($testCurrencyDynamicsParams);

        dd($testCurrencyDynamics);

//        return view('home.index');
    }
}
