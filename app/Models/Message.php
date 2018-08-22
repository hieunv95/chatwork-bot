<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    const REMIND_LUNCH_TYPE = 1,
          REMIND_UNIPOS_TYPE = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'room_id',
        'type',
    ];
}
