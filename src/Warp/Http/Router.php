<?php

/**
 * Router class
 * @author Jake Josol
 * @description File that routes all URL requests
 */

namespace Warp\Http;

use Warp\Utils\PatternList;
use Warp\Http\Response;

class Router
{
	protected static $path;
	protected static $prefix;
	protected static $patterns;
	protected static $elementDelimiter = "/";
	protected static $home;
	const ROUTE_DIRECTORY = "application/build/routes/";
	const REGEX_ANY = "([^/]+?)";
	const REGEX_INT = "([0-9]+?)";
	const REGEX_ALPHA = "([a-zA-Z_-]+?)";
	const REGEX_ALPHANUMERIC = "([0-9a-zA-Z_-]+?)";
	const REGEX_STATIC = "%s";
	
	public static function GetServer()
	{
		return $_SERVER['SERVER_NAME'];
	}
	
	public static function GetURL()
	{
		$URL = $_SERVER['REQUEST_URI'];
		if(static::$path) $URL = str_replace(static::$path."/", "", $_SERVER['REQUEST_URI']);
		
		return $URL;
	}
	
	public static function GetVerb()
	{
		return $_SERVER['REQUEST_METHOD']; 
	}
	
	public static function SetPath($path)
	{
		static::$path = preg_quote($path, "@");
	}
	
	public static function GetPath()
	{
		return static::$path;
	}

	protected static function parseRoute($route)
	{
		// Define the root path
		//$baseURL = static::$path;
		$baseURL = "";

		$route = static::$prefix . $route;

		// Return the root directory
		if($route == static::$elementDelimiter) 
			return "@^{$baseURL}/$@";

		// Retrieve the elements
		$elements = explode(static::$elementDelimiter, $route);

		// Initialize the regular expression
		$regex = "@^{$baseURL}";

		// Remove extra forward slash
		if($route[0] == static::$elementDelimiter) array_shift($elements);

		foreach($elements as $element)
		{
			// Add a delimiter to the regular expression
			$regex .= static::$elementDelimiter;

			// Examine type:name strings
			$args = explode(":", $element);

			// If there is only one item, it is a static string
			if(sizeof($args) == 1)
			{
				$regex .= sprintf(self::REGEX_STATIC,
					preg_quote(array_shift($args), "@"));

				continue;
			}
			else if($args[0] == "")
			{
				// If no type is specified (first item), discard
				array_shift($args);
				$type = false;
			}
			else
			{
				// Otherwise, we have a valid type/key pair
				$type = array_shift($args);
			}

			// Retrieve the key
			$key = array_shift($args);

			// If it's a regular expression, add it to the expression
			if($type == "regex")
			{
				$regex .= $key;
				continue;
			}

			// Remove any characters that are not allowed in the sub-pattern names
			$key = preg_replace("/[^a-zA-Z0-9]/", "", $key);

			// Start creating the named sub-pattern
			$regex .= "(?P<" . $key . ">";

			// Add the actual pattern
			switch(strtolower($type))
			{
				case "int":
				case "integer":
					$regex .= self::REGEX_INT;
					break;
				case "alpha":
					$regex .= self::REGEX_ALPHA;
					break;
				case "alphanumeric":
				case "alphanum":
				case "alnum":
					$regex .= self::REGEX_ALPHANUMERIC;
					break;
				default:
					$regex .= self::REGEX_ANY;
					break;
			}

			// Close the named sub-pattern
			$regex .= ")";
		}

		// Match the end of the URL with Unicode awareness
		$regex .= "$@u";

		return $regex;
	}
	
	public static function Add($route, $action, $options=null)
	{
		if(!static::$patterns) static::$patterns = new PatternList();
		
		// Retrieve the pattern
		$pattern = static::parseRoute($route);
		
		if(is_string($action))
		{
			static::$patterns->AddPattern($pattern, function($parameters) use ($action)
			{
				$actionItems = explode("@", $action);

				$name = $actionItems[0];
				$handler = $actionItems[1] . "Action";
				$page = new $name();

				if($actionItems[1])
					return $page->$handler($parameters);
				else
					return $page->IndexAction($parameters);
			}, $options);
		}
		else
			static::$patterns->AddPattern($pattern, $action, $options);
	}

	public static function Any($route, $action)
	{
		static::Add($route, $action);
	}

	public static function Get($route, $action)
	{
		static::Add($route, $action, array("type" => "GET"));
	}

	public static function Post($route, $action)
	{
		static::Add($route, $action, array("type" => "POST"));
	}

	public static function None($class)
	{
		if(is_string($class))
		{
			static::$patterns->SetDefault(function() use ($class)
			{
				$name = $class;
				$page = new $name();
				return $page->IndexAction();
			});
		}
		else
			static::$patterns->SetDefault($class);
	}
	
	public static function Home($class)
	{
		static::Add("/", $class);
	}

	public static function Group($route, $subroutes)
	{
		static::$prefix = $route;
		$subroutes();
		static::$prefix = null;
	}

	public static function Crud($base, $controller)
	{
		Router::Group("{$base}/", function() use ($controller)
		{
			Router::Any("add", 				"{$controller}Controller@Create");
			Router::Any("view/int:id", 		"{$controller}Controller@Read");
			Router::Any("edit/int:id", 		"{$controller}Controller@Update");
			Router::Any("delete/int:id",	"{$controller}Controller@Destroy");
		});
	}

	public static function Fetch()
	{
		
		
		if(!static::$patterns) static::$patterns = new PatternList();
	
		// If no default route is set, add the pre-built 404 response
		static::$patterns
			->SetDefault(function()
			{
				return Response::Make("404","Not Found","The specified page does not exist")->ToJSON();
			});
			
		return static::$patterns->FindMatch(static::GetURL(), function($pattern)
		{
			return ($pattern["options"] == null) ?
			 true : $pattern["options"]["type"] == static::GetVerb();
		})
		->Execute();
	}

	public static function Import($routeFile)
	{
		require_once self::ROUTE_DIRECTORY . "routes_{$routeFile}.php";
	}
}
 
?>