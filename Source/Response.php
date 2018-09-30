<?php
namespace WebServer;


use Traitor\TStaticClass;

use WebCore\Cookie;
use WebCore\IWebResponse;
use WebCore\HTTP\Responses\StandardWebResponse;


class Response
{
	use TStaticClass;
	
	
	/**
	 * @param int $code
	 * @param array $headers
	 * @param null|string $body
	 * @param Cookie[] $cookies
	 * @return IWebResponse
	 */
	public static function with(int $code = 200, array $headers = [], ?string $body = null, array $cookies = []): IWebResponse
	{
		$response = new StandardWebResponse();
		
		//$response->
		$response->setCookies($cookies);
		$response->setHeaders($headers);
		$response->setBody($body);
		
		return $response;
	}
	
	
	public static function OK(): IWebResponse
	{
		return self::with();
	}
	
	/**
	 * @param string $to
	 * @param bool|int $isTemporary If int, will be used as the code.
	 * @return IWebResponse
	 */
	public static function redirect(string $to, $isTemporary): IWebResponse
	{
		if (is_bool($isTemporary))
		{
			$code = $isTemporary ? 307 : 301;
		}
		else
		{
			$code = $isTemporary;
		}
		
		return self::with(
			$code,
			['Location' => $to]
		);
	}
	
	public static function temporaryRedirect(string $to): IWebResponse
	{
		return self::redirect($to, true);
	}
	
	public static function permanentlyRedirect(string $to): IWebResponse
	{
		return self::redirect($to, false);
	}
	
	public static function withBody(string $body, int $code = 200): IWebResponse
	{
		return self::with($code, [], $body, []);
	}
	
	/**
	 * @param Cookie[] $cookies
	 * @param int $code
	 * @return IWebResponse
	 */
	public static function withCookies(array $cookies, int $code = 200): IWebResponse
	{
		return self::with($code, [], null, $cookies);
	}
	
	/**
	 * @param string|Cookie $name
	 * @param null|string $value
	 * @param int $expire
	 * @param null|string $path
	 * @param null|string $domain
	 * @param bool $secure
	 * @param bool $serverOnly
	 * @return IWebResponse
	 */
	public static function withCookie(
		$name, 
		?string $value = null, 
		$expire = 0, 
		?string $path = null, 
		?string $domain = null, 
		bool $secure = false, 
		bool $serverOnly = false): IWebResponse
	{
		$cookie = (is_string($name) ?
			Cookie::create($name, $value, $expire, $path, $domain, $secure, $serverOnly) :
			$name);
		
		return self::withCookies([$cookie]);
	}
}