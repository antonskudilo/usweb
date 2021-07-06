<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function scopeWithAndWhereHas($query, $relation, $constraint) {
        return $query->whereHas($relation, $constraint)
            ->with([$relation => $constraint]);
    }
}
