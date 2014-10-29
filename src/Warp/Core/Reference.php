<?php

/**
 * Reference class
 * @author Jake Josol
 * @description File that manages all references
 */

namespace Warp\Core;

class Reference
{
	protected static $directory;

	private static function fileExists($directory, $name)
	{
		return file_exists(static::$directory[$directory]."{$name}.php");
	}
	
	public static function Import($reference, $name)
	{
		require_once static::$directory[$reference]."{$name}.php";
	}

	public static function Path($key, $directory)
	{
		static::$directory[$key] = $directory;
	}	
	
	public static function Autoload($name)
	{
		$classExists 			= static::fileExists("class", $name);
		$controlExists 			= static::fileExists("control", $name);;
		$modelExists 			= strpos($name,"Model") > 0 && static::fileExists("model", $name);
		$controllerExists 		= strpos($name,"Controller") > 0 && static::fileExists("controller", $name);
		$viewExists 			= strpos($name,"View") > 0 && static::fileExists("view", $name);
		$configurationExists 	= strpos($name,"Config") > 0 && static::fileExists("configuration", $name);
		$migrationExists 		= strncmp("migration", $name, strlen("migration")) == 0 && static::fileExists("migration", $name);
		
		if($classExists) 				static::Import("class", $name);
		else if($controlExists) 		static::Import("control", $name);
		else if($modelExists) 			static::Import("model", $name);
		else if($controllerExists) 		static::Import("controller", $name);
		else if($viewExists) 			static::Import("view", $name);
		else if($configurationExists) 	static::Import("configuration", $name);
		else if($migrationExists) 		static::Import("migration", $name);
	}
	
	public static function Vendor($name)
	{
		static::Import("vendor", $name);
	}
	
	public static function Register()
	{
		spl_autoload_register("Reference::Autoload");
	}
}  

?>