@extends('layouts.app')

@section('content')

    <form class="row g-3 mb-3 justify-content-center">
        <div class="col-auto">
            <label class="col-form-label">{{ __('Выберите дату') }}</label>
        </div>
        <div class="col-auto">
            <input type="text"
                   name="date"
                   value="{{ request()->get('date') ?? $currentDate }}"
                   class="form-control datepicker"
                   autocomplete="off"
            >
        </div>
        <div class="col-auto">
            <input type="submit" value="{{ __('Выбрать') }}" class="btn btn-primary">
        </div>
    </form>

    <div class="text-center mb-3">
        <h2>{{ __("Котировки валют ЦБ РФ на {$currentDate}") }}</h2>
        <h4>{{ __("Динамика по отношению к {$previousDate}") }}</h4>
    </div>

    @isset($currencies)
        <table class="table table-hover text-sm">
            <thead>
                <tr>
                    <th scope="col">{{ __('Название валюты') }}</th>
                    <th scope="col">{{ __('Код ISO') }}</th>
                    <th scope="col">{{ __('Номинал') }}</th>
                    <th scope="col">{{ __('Курс') }}</th>
                    <th scope="col">{{ __('Динамика') }}</th>
                </tr>
            </thead>
            <tbody>

                @foreach($currencies as $currency)
                    <tr>
                        <th scope="row">{{ $currency->name }}</th>
                        <td>{{ $currency->iso_char_code }}</td>
                        <td>{{ $currency->nominal }}</td>
                        <td>{{ $currency->value }}</td>
                        <td>
                            @php($currencyDynamic = $currency->getDynamic($currency->value, $currency->previousValue))

                            <div class="d-flex flex-row align-items-baseline">
                                <i class="col-2 mr-2 fas {{ $currency->getDynamicArrowClassName($currencyDynamic['percents']) }}"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   title="{{ __("Курс на $previousDate $currency->previousValue") }}"
                                ></i>
                                <span class="col-5">{{ $currencyDynamic['percents'] . '%'}}</span>
                                <span class="col-5">{{ $currencyDynamic['diff'] }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    @endisset

@endsection

@push('js')
    <script src="{{ asset('js/currencies.index.js') }}?v={{ config('app.version') }}"></script>
@endpush
