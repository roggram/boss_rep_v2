<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class User extends Model {
    protected $table = 'users';
    protected $fillable = ['line_id', 'user_name'];
    protected $dates = ['created_at', 'updated_at'];

    public function getAttribute($key) {
        return parent::getAttribute(Str::snake($key));
    }
    public function setAttribute($key, $value){
        return parent::setAttribute(Str::snake($key), $value);
    }
}
