<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Category;
class Product extends Model
{
	use SoftDeletes;
	protected $dates = ['deleted_at'];
    protected $guarded = [];
    //
    public function categories(){
        return $this->belongsToMany("App\Category");
    }
}
