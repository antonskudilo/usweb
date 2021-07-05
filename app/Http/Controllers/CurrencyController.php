<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{
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
