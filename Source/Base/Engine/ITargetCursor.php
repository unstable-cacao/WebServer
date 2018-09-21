<?php
namespace WebServer\Base\Engine;


interface ITargetCursor
{
	public function setAction($action): void;
	public function setController(string $controller): void;
	
	public function getRoutePath(): string;
	public function hasRouteParams(): bool;
	public function getRouteParams(): array;
	
	public function setRoutePath(string $path): void;
	public function addRouteParam(string $key, string $value): void;
	public function setRouteParams(array $params): void;
}