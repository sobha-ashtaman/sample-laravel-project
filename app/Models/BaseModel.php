<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Schema;

class BaseModel extends Model
{
    public static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if(Schema::hasColumn($model->getTableName(), 'created_by')) {
                if($user = auth()->user())
                    $model->created_by = $user->id;
            }
        });
        
        static::saving(function ($model) {
            if(Schema::hasColumn($model->getTableName(), 'updated_by')) {
                if($user = auth()->user())
                    $model->updated_by = $user->id;
            }
        });
    }
    
    public static function getTableName() {
        return with(new static)->getTable();
    }

    public function created_user() {
        if (isset($this->attributes['created_by'])) return $this->belongsTo(User::class, 'created_by');
        return null;
    }

    public function updated_user() {
        if (isset($this->attributes['updated_by'])) return $this->belongsTo(User::class, 'updated_by');
        return null;
    }
}
