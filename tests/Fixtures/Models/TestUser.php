<?php

namespace RS\Form\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{

    protected $table = "users";
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function profile()
    {
        return $this->hasOne(TestProfile::class,'user_id');
    }

    public function posts()
    {
        return $this->hasMany(TestPost::class,'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(TestRole::class,'role_user','user_id','role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(TestPermission::class,'permission_user','user_id','permission_id')->withPivot('color');
    }

    public function assignProfile($attributes)
    {
        return $this->profile()->create($attributes);
    }

}