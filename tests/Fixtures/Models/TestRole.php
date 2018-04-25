<?php

namespace RS\Form\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestRole extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $table="roles";


}