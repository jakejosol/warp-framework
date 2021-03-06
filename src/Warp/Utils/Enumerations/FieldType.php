<?php

/*
 * Field Type Enumeration 
 * @author Jake Josol
 * @description Enumeration for field types
 */

namespace Warp\Utils\Enumerations;
  
class FieldType
{
	const Boolean = "bit";
	const String = "string";
	const Text = "text";
	const Float = "float";
	const Decimal = "decimal";
	const Integer = "integer";
	const Timestamp = "timestamp";
	const Date = "date";
	const DateTime = "datetime";
	const Password = "password";
	const Pointer = "pointer";
	const Relation = "relation";
}
 
?>