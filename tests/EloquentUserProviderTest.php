<?php
/**
 * Part of the Sentry Package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Sentry
 * @version    2.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Mockery as m;
use Cartalyst\Sentry\Users\Eloquent\Provider;

class EloquentUserProviderTest extends PHPUnit_Framework_TestCase {

	/**
	 * Close mockery.
	 * 
	 * @return void
	 */
	public function tearDown()
	{
		m::close();
	}

	public function testFindingById()
	{
		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');

		$query = m::mock('StdClass');
		$query->shouldReceive('newQuery')->andReturn($query);
		$query->shouldReceive('find')->with(1)->once()->andReturn('foo');

		$provider->shouldReceive('createModel')->once()->andReturn($query);

		$this->assertEquals('foo', $provider->findById(1));
	}

	/**
	 * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
	 */
	public function testFailedFindingByIdThrowsException()
	{
		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');

		$query = m::mock('StdClass');
		$query->shouldReceive('newQuery')->andReturn($query);
		$query->shouldReceive('find')->with(1)->once()->andReturn(null);

		$provider->shouldReceive('createModel')->once()->andReturn($query);

		$this->assertEquals('foo', $provider->findById(1));
	}

	public function testFindingByName()
	{
		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');

		$loginColumn = 'email';

		$query = m::mock('StdClass');
		$query->shouldReceive('getLoginName')->once()->andReturn($loginColumn);

		$query->shouldReceive('newQuery')->andReturn($query);
		$query->shouldReceive('where')->with($loginColumn, '=', 'foo@bar.com')->once()->andReturn($query);
		$query->shouldReceive('first')->andReturn('bar');

		$provider->shouldReceive('createModel')->once()->andReturn($query);

		$this->assertEquals('bar', $provider->findByLogin('foo@bar.com'));
	}

	/**
	 * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
	 */
	public function testFailedFindingByNameThrowsException()
	{
		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');

		$loginColumn = 'email';

		$query = m::mock('StdClass');
		$query->shouldReceive('getLoginName')->once()->andReturn($loginColumn);

		$query->shouldReceive('newQuery')->andReturn($query);
		$query->shouldReceive('where')->with($loginColumn, '=', 'foo@bar.com')->once()->andReturn($query);
		$query->shouldReceive('first')->andReturn(null);

		$provider->shouldReceive('createModel')->once()->andReturn($query);

		$this->assertEquals('bar', $provider->findByLogin('foo@bar.com'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFindingByCredentialsFailsWithoutLoginColumn()
	{
		$user = m::mock('Cartalyst\Sentry\Users\Eloquent\User');
		$user->shouldReceive('getLoginName')->once()->andReturn('foo');

		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');
		$provider->shouldReceive('createModel')->once()->andReturn($user);

		$provider->findByCredentials(array(
			'not_foo' => 'ff',
		));
	}

	/**
	 * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
	 */
	public function testFindingByCredentialsFailsWhenModelIsNull()
	{
		$query = m::mock('StdClass');
		$query->shouldReceive('where')->with('foo', '=', 'fooval')->once()->andReturn($query);
		$query->shouldReceive('where')->with('bar', '=', 'barval')->once()->andReturn($query);
		$query->shouldReceive('first')->andReturn(null);

		$user = m::mock('Cartalyst\Sentry\Users\Eloquent\User');
		$user->shouldReceive('getLoginName')->once()->andReturn('foo');
		$user->shouldReceive('newQuery')->andReturn($query);
		$user->shouldReceive('getHashableAttributes')->once()->andReturn(array('baz', 'bat'));

		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel,getHashableCredentials]');
		$provider->shouldReceive('createModel')->once()->andReturn($user);

		$result = $provider->findByCredentials(array(
			'foo' => 'fooval',
			'bar' => 'barval',
			'baz' => 'unhashed_baz',
			'bat' => 'unhashed_bat',
		));

		$this->assertNull($result);
	}

	public function testFindingByCredentials()
	{
		$actualUser = m::mock('Cartalyst\Sentry\Users\Eloquent\User');
		$actualUser->shouldReceive('getAttribute')->with('baz')->andReturn('hashed_baz');
		$actualUser->shouldReceive('getAttribute')->with('bat')->andReturn('hashed_bat');

		$hasher = m::mock('Cartalyst\Sentry\Hashing\HasherInterface');
		$hasher->shouldReceive('checkhash')->with('unhashed_baz', 'hashed_baz')->
		once()->andReturn(true);
		$hasher->shouldReceive('checkhash')->with('unhashed_bat', 'hashed_bat')->once()->andReturn(true);

		$query = m::mock('StdClass');
		$query->shouldReceive('where')->with('foo', '=', 'fooval')->once()->andReturn($query);
		$query->shouldReceive('where')->with('bar', '=', 'barval')->once()->andReturn($query);
		$query->shouldReceive('first')->andReturn($actualUser);

		$user = m::mock('Cartalyst\Sentry\Users\Eloquent\User');
		$user->shouldReceive('getLoginName')->once()->andReturn('foo');
		$user->shouldReceive('newQuery')->andReturn($query);
		$user->shouldReceive('getHashableAttributes')->once()->andReturn(array('baz', 'bat'));

		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel,getHashableCredentials]');
		$provider->__construct($hasher);

		$provider->shouldReceive('createModel')->once()->andReturn($user);

		$result = $provider->findByCredentials(array(
			'foo' => 'fooval',
			'bar' => 'barval',
			'baz' => 'unhashed_baz',
			'bat' => 'unhashed_bat',
		));

		$this->assertEquals($actualUser, $result);
	}

	public function testCreatingUser()
	{
		$attributes = array(
			'email'    => 'foo@bar.com',
			'password' => 'foo_bar_baz',
		);

		$user = m::mock('Cartalyst\Sentry\Users\EloquentUser');
		$user->shouldReceive('fill')->with($attributes)->once();
		$user->shouldReceive('save')->once();

		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');
		$provider->shouldReceive('createModel')->once()->andReturn($user);

		$this->assertEquals($user, $provider->create($attributes));
	}

	public function testGettingEmptyUserInterface()
	{
		$provider = m::mock('Cartalyst\Sentry\Users\Eloquent\Provider[createModel]');
		$provider->shouldReceive('createModel')->once()->andReturn('foo');

		$this->assertEquals('foo', $provider->getEmptyUser());
	}

}