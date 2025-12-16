<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Trigger extends Model {
    use SoftDeletes;

    protected $table = 'triggers';
    protected $dates = ['created_at', 'updated_at'];

    const UPDATED_AT = null;


    public function getAttribute($key) {
        return parent::getAttribute(Str::snake($key));
    }
    public function setAttribute($key, $value){
        return parent::setAttribute(Str::snake($key), $value);
    }
}