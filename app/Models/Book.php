<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_on', 'updated_on'];

    public function author():BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function genres():BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withPivot('created_by', 'updated_by', 'created_at', 'updated_at');
    }
}
