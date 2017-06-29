<?php
/**
 * Part of form
 * User: ben ©2017 Red Snapper Ltd.
 * Date: 28/06/2017 08:01
 */
use PHPUnit\Framework\TestCase;
use RS\Form\Formlet;

/** external requirements. */
use Illuminate\Http\Request;


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
		/** @var Formlet $stub */
		$stub = $this->getMockForAbstractClass(Formlet::class);
		$stub->setKey(34); //can be an integer.
		$this->assertEquals(34,$stub->getKey());
		$stub->setKey([33,12]); //can be an array.
		$this->assertEquals([33,12],$stub->getKey());
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

}