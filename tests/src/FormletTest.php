<?php

use PHPUnit\Framework\TestCase;
use RS\Form\Formlet;

/** external requirements. */
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\ConnectionResolver;


class FormletTest extends TestCase
{
	public function testWith() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$stub->with('foo','bar');
		$this->assertTrue($stub->getData('foo') === 'bar');
		$stub->with(['fred'=>'ginger']);
		$this->assertTrue($stub->getData('fred') === 'ginger');
	}

	public function testGetData()  {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$stub->with('foo','bar');
		$this->assertTrue($stub->getData('foo') === 'bar');
	}

	public function testIsMultiple()  {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class); //Initialises to false.
		$this->assertFalse($stub->isMultiple());
	}

	public function testSetMultiple() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$stub->setMultiple();	//Defaults to true.
		$this->assertTrue($stub->isMultiple());
		$stub->setMultiple(false);
		$this->assertFalse($stub->isMultiple());
		$stub->setMultiple(true);
		$this->assertTrue($stub->isMultiple());
	}

	public function testGetKey() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$this->assertNull($stub->getKey()); //defaults to null.
	}

	public function testSetKey() {

		/** @var Formlet $formlet */
		$formlet = $this->getMockForAbstractClass(Formlet::class);

		$result = $formlet->setKey(34); //can be an integer.
		$this->assertEquals($formlet, $result);
		$this->assertEquals(34,$formlet->getKey());
		$formlet->setKey([33,12]); //can be an array.
		$this->assertEquals([33,12],$formlet->getKey());

		/** @var Model $coll */
		//$res = $this->getMockForAbstractClass(ConnectionResolver::class);
		//Model::setConnectionResolver($res);
		//$coll = $this->getMockForAbstractClass(Model::class);
		//$coll->__set('id',34);
		//$formlet->setModel($coll);
		//$result = $formlet->setKey(34); //can be an integer.
		//$this->assertSame($formlet, $result);


	}

	public function testGetName() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$this->assertEquals("",$stub->getName()); //defaults to empty.
	}

	public function testSetName() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$stub->setName("34:fred Pø😍"); //any string.
		$this->assertEquals("34:fred Pø😍",$stub->getName()); //defaults to null.
		$stub->setName("base"); //can be changed.
		$this->assertEquals("base",$stub->getName()); //defaults to null.
	}

	public function testRules() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$this->assertEquals([],$stub->rules()); //defaults to [].
	}

	public function testSetRequest() {
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$request = new Request();
		$response = $stub->setRequest($request);
		$this->assertEquals($response,$stub); //defaults to [].
	}

	public function testSetSessionStore() {
		/** @var Formlet $stub */
		$key = "test";
		$value = 543643;
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$session = new Store("test",new SessionHandler());
		$bag = [$key => $value];
		$session->put("_old_input",$bag);
		$stub->setSessionStore($session);
		$session_value = $stub->old($key,false);
		$this->assertEquals($value,$session_value);
	}

	public function testSetModel() {
		/** @var Model $formlet */
		$model = $this->getMockForAbstractClass(Model::class);
		/** @var Formlet $formlet */
		$formlet = $this->getMockForAbstractClass(Formlet::class);

		$response = $formlet->setModel($model);
		$this->assertEquals($response,$formlet);
	}

	public function testGetModel() {
		/** @var Model $model */
		$model = $this->getMockForAbstractClass(Model::class);
		/** @var Formlet $formlet */
		$formlet = $this->getMockForAbstractClass(Formlet::class);

		$formlet->setModel($model);
		$result = $formlet->getModel();
		$this->assertEquals($result,$model);
	}


}