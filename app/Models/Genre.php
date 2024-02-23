<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel as Model;

class Genre extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_on', 'updated_on'];
}
