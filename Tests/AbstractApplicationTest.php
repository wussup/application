<?php
/**
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\AbstractApplication.
 */
class AbstractApplicationTest extends TestCase
{
	/**
	 * @testdox  Tests the constructor creates default object instances
	 *
	 * @covers  Joomla\Application\AbstractApplication::__construct
	 */
	public function test__constructDefaultBehaviour()
	{
		$startTime      = time();
		$startMicrotime = microtime(true);

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');

		$this->assertAttributeInstanceOf('Joomla\Input\Input', 'input', $object);
		$this->assertAttributeInstanceOf('Joomla\Registry\Registry', 'config', $object);

		// Validate default configuration data is written
		$executionDateTime = new \DateTime($object->get('execution.datetime'));

		$this->assertSame(date('Y'), $executionDateTime->format('Y'));
		$this->assertGreaterThanOrEqual($startTime, $object->get('execution.timestamp'));
		$this->assertGreaterThanOrEqual($startMicrotime, $object->get('execution.microtimestamp'));
	}

	/**
	 * @testdox  Tests the correct objects are stored when injected
	 *
	 * @covers  Joomla\Application\AbstractApplication::__construct
	 */
	public function test__constructDependencyInjection()
	{
		$mockInput  = $this->createMock('Joomla\Input\Input');
		$mockConfig = $this->createMock('Joomla\Registry\Registry');
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array($mockInput, $mockConfig));

		$this->assertAttributeSame($mockInput, 'input', $object);
		$this->assertAttributeSame($mockConfig, 'config', $object);
	}

	/**
	 * @testdox  Tests that close() exits the application with the given code
	 *
	 * @covers  Joomla\Application\AbstractApplication::close
	 */
	public function testClose()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array(), '', false, true, true, array('close'));
		$object->expects($this->any())
			->method('close')
			->willReturnArgument(0);

		$this->assertSame(3, $object->close(3));
	}

	/**
	 * @testdox  Tests that the application is executed successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::execute
	 */
	public function testExecute()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$object->expects($this->once())
			->method('doExecute');

		// execute() has no return, with our mock nothing should happen but ensuring that the mock's doExecute() stub is triggered
		$this->assertNull($object->execute());
	}

	/**
	 * @testdox  Tests that the application is executed successfully when an event dispatcher is registered.
	 *
	 * @covers  Joomla\Application\AbstractApplication::execute
	 */
	public function testExecuteWithEvents()
	{
		$dispatcher = $this->getMockBuilder('Joomla\Event\DispatcherInterface')->getMock();
		$dispatcher->expects($this->exactly(2))
			->method('dispatch');

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$object->expects($this->once())
			->method('doExecute');

		$object->setDispatcher($dispatcher);

		// execute() has no return, with our mock nothing should happen but ensuring that the mock's doExecute() stub is triggered
		$this->assertNull($object->execute());
	}

	/**
	 * @testdox  Tests that data is read from the application configuration successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::get
	 */
	public function testGet()
	{
		$mockInput = $this->createMock('Joomla\Input\Input');

		$mockConfig = $this->createMock('Joomla\Registry\Registry');

		// The first three calls are for constructor internals, we don't care about those values here but have to fake them anyway
		$mockConfig->expects($this->exactly(5))
			->method('get')
			->willReturnOnConsecutiveCalls(null, null, null, 'bar', 'car');

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', [$mockInput, $mockConfig]);

		$this->assertSame('bar', $object->get('foo', 'car'), 'Checks a known configuration setting is returned.');
		$this->assertSame('car', $object->get('goo', 'car'), 'Checks an unknown configuration setting returns the default.');
	}

	/**
	 * @testdox  Tests that a default LoggerInterface object is returned.
	 *
	 * @covers  Joomla\Application\AbstractApplication::getLogger
	 */
	public function testGetLogger()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');

		$this->assertInstanceOf('Psr\\Log\\NullLogger', $object->getLogger());
	}

	/**
	 * @testdox  Tests that data is set to the application configuration successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::set
	 * @uses    Joomla\Application\AbstractApplication::get
	 */
	public function testSet()
	{
		$mockInput = $this->createMock('Joomla\Input\Input');

		$mockConfig = $this->createMock('Joomla\Registry\Registry');

		// The first three calls are for constructor internals, we don't care about those values here but have to fake them anyway
		$mockConfig->expects($this->exactly(5))
			->method('get')
			->willReturnOnConsecutiveCalls(null, null, null, null, 'car');

		$mockConfig->expects($this->exactly(4))
			->method('set')
			->willReturnOnConsecutiveCalls(null, null, null, null);

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', [$mockInput, $mockConfig]);

		$this->assertNull($object->set('foo', 'car'), 'Checks set returns the previous value.');
		$this->assertEquals('car', $object->get('foo'), 'Checks the new value has been set.');
	}

	/**
	 * @testdox  Tests that the application configuration is overwritten successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::setConfiguration
	 */
	public function testSetConfiguration()
	{
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$mockConfig = $this->createMock('Joomla\Registry\Registry');

		// First validate the two objects are different
		$this->assertAttributeNotSame($mockConfig, 'config', $object);

		// Now inject the config
		$object->setConfiguration($mockConfig);

		// Now the config objects should match
		$this->assertAttributeSame($mockConfig, 'config', $object);
	}

	/**
	 * @testdox  Tests that a LoggerInterface object is correctly set to the application.
	 *
	 * @covers  Joomla\Application\AbstractApplication::setLogger
	 */
	public function testSetLogger()
	{
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$mockLogger = $this->getMockForAbstractClass('Psr\Log\AbstractLogger');

		$object->setLogger($mockLogger);

		$this->assertAttributeSame($mockLogger, 'logger', $object);
	}
}
