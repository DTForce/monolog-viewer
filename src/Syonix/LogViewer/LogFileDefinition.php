<?php

namespace Syonix\LogViewer;


use Syonix\Util\StringUtil;


final class LogFileDefinition
{

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $args;


	public function __construct($name, array $args)
	{
		$this->name = $name;
		$this->args = $args;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function getSlug()
	{
		return StringUtil::toAscii($this->name);
	}


	/**
	 * @return array
	 */
	public function getArgs()
	{
		return $this->args;
	}

}
