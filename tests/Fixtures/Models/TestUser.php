<?php

namespace RS\Form\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Builder;
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
        return $this->belongsToMany(TestPermission::class,'permission_user','user_id','permission_id')->withPivot('color','id');
    }

    public function assignProfile($attributes)
    {
        return $this->profile()->create($attributes);
    }

    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
      $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

}