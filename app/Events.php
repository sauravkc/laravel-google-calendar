<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $fillable = [
        'client_id',
        'client_email',
        'client_name',
        'event_id',
        'kind',
        'htmlLink',
        'summary',
        'start_date',
        'start_date_time',
        'start_time_zone',
        'end_date',
        'end_date_time',
        'end_time_zone',
        'recurrence'
    ];
}
