<?php
namespace WebServer\Engine;


use Narrator\INarrator;

use WebServer\Base\ITargetAction;
use WebServer\Base\IActionResponse;
use WebServer\Exceptions\WebServerException;


class ActionExecutor
{
	private const HANDLERS_INIT			= 'init';
	private const HANDLERS_PRE_ACTION	= 'before';
	private const HANDLERS_POST_ACTION	= 'after';
	private const HANDLERS_ON_EXCEPTION	= 'onException';
	private const HANDLERS_COMPLETE		= 'complete';
	
	
	/** @var IActionResponse|null */
	private $response = null;
	
	/** @var INarrator */
	private $narrator;
	
	/** @var ITargetAction */
	private $target;
	
	
	private function invokeMethod(string $method): void
	{
		foreach ($this->target->getDecorators() as $object)
		{
			$this->narrator->invokeMethodIfExists($object, $method);
		}
		
		if ($this->target->hasController())
		{
			$this->narrator->invokeMethodIfExists($this->target->getController(), $method);
		}
	}
	
	private function invokeCallbackDecorators(): void
	{
		foreach ($this->target->getCallbackDecorators() as $callbackDecorator)
		{
			$this->narrator->invoke($callbackDecorator);
		}
	}
	
	private function invokeAction(): void
	{
		$result = $this->narrator->invoke($this->target->getAction());
		$this->response = new ActionResponse($result);
		
		$this->narrator->params()->byType(IActionResponse::class, function () { return $this->response; });
	}
	
	private function invokeMethodWithResponse(string $method, ?INarrator $narrator = null): bool
	{
		$exists = false;
		$narrator = $narrator ?: $this->narrator;
		$controller = $this->target->getController();
		$decorators = $this->target->getDecorators();
		
		foreach ($decorators as $decorator)
		{
			$exists = method_exists($decorator, $method) || $exists;
			$result = $narrator->invokeMethodIfExists($decorator, $method);
			
			if (!is_null($result))
			{
				$this->response = new ActionResponse($result);
			}
		}
		
		if ($controller)
		{
			$exists = method_exists($controller, $method) || $exists;
			$result = $narrator->invokeMethodIfExists($controller, $method);
			
			if (!is_null($result))
			{
				$this->response = new ActionResponse($result);
			}
		}
		
		return $exists;
	}
	
	private function handleException(\Throwable $t): void
	{
		if (!$this->response)
			$this->response = new ActionResponse();
		
		$narrator = clone $this->narrator;
		$narrator->params()->first($t);
		
		$handled = $this->invokeMethodWithResponse(self::HANDLERS_ON_EXCEPTION, $narrator);
		
		if (!$handled)
		{
			throw $t;
		}
	}
		
	
	public function __construct(INarrator $narrator)
	{
		$this->narrator = $narrator;
		
		$narrator->params()->byType(IActionResponse::class, [$this, 'getServerResponse']);
	}
	
	
	public function getServerResponse(): IActionResponse
	{
		if (!$this->response)
			throw new WebServerException(IActionResponse::class . ' is not available at this point');
		
		return $this->response;
	}
	
	public function initialize(ITargetAction $target): void
	{
		$this->target = $target;
	}
	
	
	public function executeAction(): IActionResponse
	{
		$this->invokeMethod(self::HANDLERS_INIT);
		
		try
		{
			$this->invokeMethod(self::HANDLERS_PRE_ACTION);
			$this->invokeCallbackDecorators();
			$this->invokeAction();
			$this->invokeMethodWithResponse(self::HANDLERS_POST_ACTION);
		}
		catch (\Throwable $t)
		{
			$this->handleException($t);
		}
		
		return $this->response;
	}
	
	public function executeComplete()
	{
		$this->invokeMethod(self::HANDLERS_COMPLETE);
	}
}