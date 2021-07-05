<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
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


}
