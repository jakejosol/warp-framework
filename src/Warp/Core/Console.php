<?php

/**
 * Console class
 * @author Jake Josol
 * @description Class that is responsible for the command line functions
 */

namespace Warp\Core;

use Warp\Data\Migration;
use Warp\Foundation\Model;
use Warp\Foundation\FoundationFactory;
use Warp\Utils\FileHandle;
use Warp\Utils\Enumerations\SystemField;

class Console
{
	protected $functions = array();

	public function __construct() 
	{
		$this->Register("foundation:make", function($parameters)
		{
			return FoundationFactory::Generate($parameters);
		});

		$this->Register("migrate:install", function($parameters)
		{
			return Migration::Install();
		});

		$this->Register("migrate:base", function($parameters)
		{
			return Migration::Base();
		});	

		$this->Register("migrate:make", function($parameters)
		{
			return Migration::Make($parameters);
		});	

		$this->Register("migrate:destroy", function($parameters)
		{
			return Migration::Destroy($parameters);
		});

		$this->Register("migrate:commit", function()
		{
			return Migration::Commit();
		});

		$this->Register("migrate:revert", function()
		{
			return Migration::Revert();
		});

		$this->Register("migrate:reset", function()
		{
			return Migration::Reset();
		});

		$this->Register("job:start", function($parameters)
		{
			return Job::Install();
		});

		$this->Register("deploy", function($parameters)
		{
			// TO-DO Deployment
		});
	}

	// Start the Console
	public static function Start()
	{
		// Retrieve the global variables
		if(!$argv) $argv = $_SERVER["argv"];

		// Get the console variables
		$functionName = $argv[1];
		$rows = explode(",", $argv[2]);
		$vars = array();
		foreach($rows as $row)
		{
			$parts = explode("=", $row);
			$vars[$parts[0]] = $parts[1];
		}

		// Prepare the console
		$console = new Console;

		// Run the console
		try
		{
			return $console->Run($functionName, $vars);
		}
		catch(\Exception $ex)
		{
			return "\nError: " . $ex->getMessage() . "\n";
		}
	}

	// Generic function caller
	public function Run($functionName, $parameters)
	{
		if(!array_key_exists($functionName, $this->functions))
			return "\nError: The command '{$functionName}' does not exist\n";

		$response = $this->functions[$functionName]($parameters);
		
		return "\n{$response}\n";
	}

	// Function registry
	public function Register($functionName, $function)
	{
		$this->functions[$functionName] = $function;
	}
}