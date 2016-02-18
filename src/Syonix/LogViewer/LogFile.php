<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use Syonix\Util\StringUtil;


class LogFile
{

	protected $name;

	protected $slug;

	protected $clientSlug;

	protected $args;

	protected $lines;

	protected $loggers = [];

	/**
	 * @var array
	 */
	private $filter;

	/**
	 * @var int
	 */
	private $offset = 0;

	/**
	 * @var int|null
	 */
	private $limit = null;


	public function __construct($name, $clientSlug, $args)
	{
		$this->name = $name;
		$this->slug = StringUtil::toAscii($name);
		$this->clientSlug = StringUtil::toAscii($clientSlug);
		$this->args = $args;
		$this->lines = new ArrayCollection();
	}


	public static function getLevelName($level)
	{
		return Logger::getLevelName($level);
	}


	public static function getLevels()
	{
		return Logger::getLevels();
	}


	public function addLine($line)
	{
		return $this->lines->add($line);
	}


	public function getLine($line)
	{
		return $this->lines[intval($line)];
	}


	public function getArgs()
	{
		return $this->args;
	}


	public function setFilter($logger = null, $level = 0, $text = null)
	{
		$this->filter['logger'] = $logger;
		$this->filter['level'] = $level;
		$this->filter['text'] = $text;
	}


	public function setLimit($limit = null)
	{
		$this->limit = $limit;
	}


	public function setOffset($offset = 0)
	{
		$this->offset = $offset;
	}


	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}


	/**
	 * @return int|null
	 */
	public function getLimit()
	{
		return $this->limit;
	}


	public function reverseLines()
	{
		$this->lines = new ArrayCollection(array_reverse($this->lines->toArray(), false));
	}


	public function countLines()
	{
		return count($this->getLines());
	}


	public function getLines()
	{
		$lines = $this->lines;
		$filter = $this->filter;
		$logger = isset($filter['logger']) ? $filter['logger'] : null;
		$minLevel = isset($filter['level']) ? $filter['level'] : 0;
		$text = (isset($filter['text']) && $filter['text'] != '') ? $filter['text'] : null;

		foreach ($lines as $line) {
			if (
				! static::logLineHasLogger($logger, $line)
				|| ! static::logLineHasMinLevel($minLevel, $line)
				|| ! static::logLineHasText($text, $line)
			) {
				$lines->removeElement($line);
			}
		}

		if (null !== $this->limit) {
			return array_values($lines->slice($this->offset, $this->limit));
		}
		return array_values($lines->toArray());
	}


	private static function logLineHasLogger($logger, $line)
	{
		if ($logger === null) {
			return true;
		}

		return (array_key_exists('logger', $line) && $line['logger'] == $logger);
	}


	private static function logLineHasMinLevel($minLevel, $line)
	{
		if ($minLevel == 0) {
			return true;
		}

		return (array_key_exists('level', $line) && static::getLevelNumber($line['level']) >= $minLevel);
	}


	public static function getLevelNumber($level)
	{
		$levels = Logger::getLevels();

		if ( ! isset($levels[$level])) {
			throw new InvalidArgumentException(
				'Level "' . $level . '" is not defined, use one of: ' . implode(', ', $levels)
			);
		}

		return $levels[$level];
	}


	private static function logLineHasText($keyword, $line, $searchContextExtra = false)
	{
		if ($keyword === null) {
			return true;
		}
		if (array_key_exists('message', $line)
			&& strpos(strtolower($line['message']), strtolower($keyword)) !== false
		) {
			return true;
		}
		if (array_key_exists('date', $line) && strpos(strtolower($line['date']), strtolower($keyword)) !== false) {
			return true;
		}
		if ($searchContextExtra) {
			if (array_key_exists('context', $line)) {
				$context = $line['context'];
				if (array_key_exists(strtolower($keyword), $context)) {
					return true;
				}
				foreach ($context as $content) {
					if (strpos(strtolower($content), strtolower($keyword)) !== false) {
						return true;
					}
				}
			}
			if (array_key_exists('extra', $line)) {
				$extra = $line['extra'];
				if (array_key_exists($keyword, $extra)) {
					return true;
				}
				foreach ($extra as $content) {
					if (strpos(strtolower($content), strtolower($keyword)) !== false) {
						return true;
					}
				}
			}
		}

		return false;
	}


	public function getName()
	{
		return $this->name;
	}


	public function getSlug()
	{
		return $this->slug;
	}


	public function getLoggers()
	{
		return array_keys($this->loggers);
	}


	public function hasLogger($logger)
	{
		return isset($this->loggers[$logger]);
	}


	public function addLogger($logger)
	{
		return $this->loggers[$logger];
	}


	public function getClientSlug()
	{
		return $this->clientSlug;
	}


	public function getIdentifier()
	{
		return $this->clientSlug . '/' . $this->slug;
	}

}
