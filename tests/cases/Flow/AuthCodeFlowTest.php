<?php declare(strict_types = 1);

namespace Tests\Cases\Flow;

use Contributte\OAuth2Client\Exception\Logical\InvalidArgumentException;
use Contributte\OAuth2Client\Exception\Runtime\PossibleCsrfAttackException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mockery;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Flow\TestAuthCodeFlow;
use Tests\Fixtures\Provider\TestProvider;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$session = Mockery::mock(Session::class);
		$flow = new TestAuthCodeFlow(new TestProvider(), $session);

		$flow->getAccessToken([]);
	}, InvalidArgumentException::class, 'Missing "code" parameter');
});

Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$session = Mockery::mock(Session::class);
		$flow = new TestAuthCodeFlow(new TestProvider(), $session);

		$flow->getAccessToken(['code' => 'foo']);
		$flow->getAccessToken([]);
	}, InvalidArgumentException::class, 'Missing "state" parameter');
});

Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$sessionSection = Mockery::mock(SessionSection::class);
		$sessionSection->shouldReceive('offsetExists')
			->andReturn(true);
		$sessionSection->shouldReceive('offsetGet')
			->andReturn('baz');
		$sessionSection->shouldReceive('offsetUnset');

		$session = Mockery::mock(Session::class);
		$session->shouldReceive('getSection')
			->andReturn($sessionSection);

		$flow = new TestAuthCodeFlow(new TestProvider(), $session);

		$flow->getAccessToken(['code' => 'foo', 'state' => 'bar']);
	}, PossibleCsrfAttackException::class);
});

Toolkit::test(function (): void {
	$token = Mockery::mock(AccessTokenInterface::class);

	$provider = Mockery::mock(AbstractProvider::class);
	$provider->shouldReceive('getAccessToken')
		->andReturn($token);

	$sessionSection = Mockery::mock(SessionSection::class);
	$sessionSection->shouldReceive('offsetExists')
		->andReturn(false);

	$session = Mockery::mock(Session::class);
	$session->shouldReceive('getSection')
		->andReturn($sessionSection);

	$flow = new TestAuthCodeFlow($provider, $session);

	Assert::same($token, $flow->getAccessToken(['code' => 'foo', 'state' => 'bar']));
});

Toolkit::test(function (): void {
	$provider = Mockery::mock(AbstractProvider::class);
	$provider->shouldReceive('getAuthorizationUrl')
		->andReturn('foo');
	$provider->shouldReceive('getState');

	$sessionSection = Mockery::mock(SessionSection::class);
	$sessionSection->shouldReceive('offsetSet');

	$session = Mockery::mock(Session::class);
	$session->shouldReceive('getSection')
		->andReturn($sessionSection);

	$flow = new TestAuthCodeFlow($provider, $session);

	Assert::same('foo', $flow->getAuthorizationUrl());
});
