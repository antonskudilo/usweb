<?php

namespace App\Models;

class  CurrencyValue extends BaseModel
{
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency_values';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cbr_id',
        'value',
        'date',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'cbr_id', 'cbr_id');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }
}
