<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends BaseModel
{
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currencies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cbr_id',
        'name',
        'eng_name',
        'nominal',
        'parent_code',
        'iso_num_code',
        'iso_char_code',
    ];

    /**
     * @return HasMany
     */
    public function values()
    {
        return $this->hasMany(CurrencyValue::class, 'cbr_id', 'cbr_id');
    }

    /**
     * @param float $value
     * @param float $previousValue
     * @return array
     */
    public function getDynamic(float $value, float $previousValue) : array
    {
        $result = [];
        $result['diff'] = round($value - $previousValue, 2);
        $result['percents'] = $previousValue !== 0
            ? round((($value * 100 / $previousValue) - 100), 2)
            : null;

        return $result;
    }

    /**
     * @param float $currencyDynamic
     * @return string
     */
    public function getDynamicArrowClassName(float $currencyDynamic) : string
    {
        return $currencyDynamic >= 0
            ? 'fa-arrow-up text-success'
            : 'fa-arrow-down text-danger';
    }

    /**
     * @param string $dateString
     * @return string
     */
    public function getPreviousDateNotWeekend(string $dateString): string
    {
        $previousDate = Carbon::createFromDate($dateString);

        if ($previousDate->isWeekend()) {
            $previousDate = $previousDate->lastWeekDay;
        }

        return $previousDate->toDateString();
    }
}
