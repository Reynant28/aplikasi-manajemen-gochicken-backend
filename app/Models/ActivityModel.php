<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityModel extends Model
{
    use HasFactory;
    protected $table = 'activities';
    protected $primaryKey = 'id_activity';
    public $incrementing = false;
    protected $fillable = [
        'type',
        'model_type',
        'id_model',
        'description',
        'id_user',
        'id_cabang',
        'old_data',
        'new_data'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(UsersModel::class);
    }

    public function model()
    {
        return $this->morphTo();
    }
}
