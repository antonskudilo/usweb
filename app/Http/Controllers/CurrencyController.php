<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    /**
     * Вывод списка валют с показом курса ЦБ и динамики
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request)
    {
        if ($request->has('date')) {
            try {
                $currentDateString = Carbon::createFromFormat('d.m.Y', $request->input('date'))->toDateString();
            } catch (\Exception $exception) {
                return redirect()->route('home')
                    ->with('statusClass', 'warning')
                    ->withStatus('Указан неверный формат даты');
            }
        } else {
            $currentDateString = Carbon::now()->toDateString();
        }

        $currentDate = Carbon::parse($currentDateString)->format('d.m.Y');
        $previousDateString = getPreviousDateStringNotWeekend($currentDateString);
        $previousDate = Carbon::parse($previousDateString)->format('d.m.Y');

        try {
            if (!(new CurrencyRepository())->getCurrenciesValues($currentDateString)) {
                return redirect()->route('home')
                    ->with('statusClass', 'warning')
                    ->withStatus('Данные валют не получены');
            }
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());

            return redirect()->route('home')
                ->with('statusClass', 'warning')
                ->withStatus($e->getMessage());
        }

        $currencies = Currency::query()
            ->join('currency_values', function ($join) use ($currentDateString) {
                $join
                    ->on('currencies.cbr_id', '=', 'currency_values.cbr_id')
                    ->where(function ($query) use ($currentDateString) {
                        $query->where('currency_values.date', '=', $currentDateString);
                    });
            })
            ->join('currency_values as previous_currency_values', function ($join) use ($previousDateString) {
                $join
                    ->on('currencies.cbr_id', '=', 'previous_currency_values.cbr_id')
                    ->where(function ($query) use ($previousDateString) {
                        $query->where('previous_currency_values.date', '=', $previousDateString);
                    });
            })
            ->select(
                'currencies.*',
                'currency_values.value as value',
                'previous_currency_values.value as previousValue'
            )
            ->get();

        return view('currencies.index', compact('currencies', 'currentDate', 'previousDate'));
    }

    public function getCurrenciesValues()
    {

    }

    /**
     * Получение списка валют ЦБ
     *
     * @return mixed
     */
    public function getCurrenciesList()
    {
        $currencyRepository = new CurrencyRepository();
        $currenciesList = $currencyRepository->getCurrenciesList();

        if (empty($currenciesList)) {
            return redirect()->route('home')
                ->with('statusClass', 'warning')
                ->withStatus('Данные валют не получены');
        }

        foreach ($currenciesList as $cbrId => $currencyData) {
            try {
                Currency::updateOrCreate([
                    'cbr_id' => $cbrId,
                    'name' => trim($currencyData['Name']),
                    'eng_name' => trim($currencyData['EngName']),
                    'nominal' => (int)$currencyData['Nominal'],
                    'parent_code' => trim($currencyData['ParentCode']),
                    'iso_num_code' => !empty($currencyData['ISO_Num_Code']) ? (int)$currencyData['ISO_Num_Code'] : null,
                    'iso_char_code' => !empty($currencyData['ISO_Char_Code']) ? trim($currencyData['ISO_Char_Code']) : null,
                ]);
            } catch (\Exception $e) {
                Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());

                return redirect()->route('home')
                    ->with('statusClass', 'warning')
                    ->withStatus($e->getMessage());
            }
        }

        return redirect()->route('home')
            ->with('statusClass', 'success')
            ->withStatus('Данные валют успешно загружены');
    }
}
