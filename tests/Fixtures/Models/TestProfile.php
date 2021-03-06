<?php

namespace RS\Form\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestProfile extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $table = "profiles";

    protected $primaryKey = "user_id";

    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
}