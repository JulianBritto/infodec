<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryExecutionLog extends Model
{
    protected $table = 'query_execution_logs';

    protected $fillable = [
        'executed_at',
        'connection',
        'method',
        'path',
        'url',
        'section',
        'group',
        'time_ms',
        'sql',
        'bindings',
        'ip',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'bindings' => 'array',
        'time_ms' => 'integer',
    ];
}
