<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsersModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $incrementing = false;
    protected $fillable = [
        'id_user',
        'nama',
        'username',
        'password',
        'role',
        'id_cabang',
    ];

    protected $hidden = [
        'password',
    ];

    public function cabang()
    {
        return $this->belongsTo('App\Models\CabangModel', 'id_cabang');
    }
}
