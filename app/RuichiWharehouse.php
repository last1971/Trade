<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuichiWharehouse extends Model
{
    use HasFactory;

    protected $connection = 'ruichi';

    protected $table = 'maingrey';
}
