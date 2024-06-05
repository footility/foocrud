<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {{ $entityName }} extends Model
{
use HasFactory;

protected $fillable = [{{ implode(', ', $fields) }}];
}
