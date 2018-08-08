<?php

namespace RS\Form\Tests\Middleware;

use Illuminate\Support\Facades\Route;
use RS\Form\Middleware\HoneyPot;
use RS\Form\Tests\TestCase;

class HoneyPotTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Route::post('/honey',function(){
            return "OK";
        })->middleware(HoneyPot::class);

        Route::put('/honey',function(){
            return "OK";
        })->middleware(HoneyPot::class);

        Route::get('/honey',function(){
            return "OK";
        })->middleware(HoneyPot::class);
    }

    /** @test */
    public function middleware_can_pass_if_honeypot_not_filled_in()
    {
        $this->post('/honey',['formlet-email'=>'','formlet-terms'=>false])->assertStatus(200);
        $this->put('/honey',['formlet-email'=>'','formlet-terms'=>false])->assertStatus(200);
        $this->get('/honey',['formlet-email'=>'foo','formlet-terms'=>true])->assertStatus(200);
    }

    /** @test */
    public function redirects_back_if_honeypot_fails()
    {
        $this->from('/home')->post('/honey',['formlet-email'=>'foo','formlet-terms'=>true])->assertStatus(302);
        $this->from('/home')->post('/honey',['formlet-email'=>'','formlet-terms'=>true])->assertStatus(302);
        $this->from('/home')->post('/honey',['formlet-email'=>'foo','formlet-terms'=>false])->assertStatus(302);
    }

}