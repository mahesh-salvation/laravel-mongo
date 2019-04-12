<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Report extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'reports';
    
    protected $fillable = [
        'content_id', 'user_id','time', 'clicks', 'content_type', 'created_at', 'updated_at'
    ];
}
