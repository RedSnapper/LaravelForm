<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestPermission extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $table="permissions";



}