<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;
use App\Country;
use App\State;
use App\City;

class Profile extends Model
{
	use SoftDeletes;
	protected $dates = ['deleted_at'];
    protected $guarded = [];
    //
    public function users()
    {
        return $this->belongsToMany('App\User');
    }
    public function country()
    {
        return $this->belongsTo('App\Country');
    }
    public function state()
    {
        return $this->belongsTo('App\State');
    }
    public function city()
    {
        return $this->belongsTo('App\City');
    }
}
