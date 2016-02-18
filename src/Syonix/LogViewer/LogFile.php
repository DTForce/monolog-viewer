<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Dubture\Monolog\Parser\LineLogParser;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use Syonix\Util\StringUtil;


class LogFile
{

	const PATTERN_NAME = "LogFile::pattern";

	/**
	 * @var LogFileDefinition
	 */
	protected $logFileDefinition;

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

	/**
	 * @var LineLogParser
	 */
	private $logParser;

	/**
	 * @var bool
	 */
	private $reverse = false;

	private $entries;

	private $filterLogger = null;

	private $filterLevel = 0;

	private $filterText = null;


	public function __construct(array $lines, LogFileDefinition $logFileDefinition, LineLogParser $logParser)
	{
		$this->lines = $lines;
		$this->logFileDefinition = $logFileDefinition;
		$this->logParser = $logParser;
	}


	public static function getLevelName($level)
	{
		return Logger::getLevelName($level);
	}


	public static function getLevels()
	{
		return Logger::getLevels();
	}


	public function getLine($line)
	{
		return $this->lines[intval($line)];
	}


	public function setFilter($logger = null, $level = 0, $text = null)
	{
		$this->entries = null;
		$this->filterLogger = $logger;
		$this->filterLevel = $level;
		$this->filterText = $text;
	}


	public function setLimit($limit = null)
	{
		$this->entries = null;
		$this->limit = $limit;
	}


	public function setOffset($offset = 0)
	{
		$this->entries = null;
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
		$this->reverse = true;
	}


	public function countLines()
	{
		return count($this->lines);
	}


	public function getLines()
	{
		$lines = $this->lines;
		if ($this->entries === null) {
			$entries = [];
			$entryCount = 0;
			$totalLines = count($lines);
			$limit = $this->getLimit() === null ? $totalLines : $this->getLimit();
			for ($pos = 0; $entryCount < $limit && $pos < $totalLines; $pos++) {
				if ($this->getOffset() > $pos) {
					continue;
				}

				$line = $lines[$this->reverse ? count($lines) - $pos - 1 : $pos];
				$entry = $this->logParser->parse($line, 0, LogFile::PATTERN_NAME);
				if (
					count($entry) > 0 &&
					static::logLineHasLogger($this->filterLogger, $entry) &&
					static::logLineHasMinLevel($this->filterLevel, $entry) &&
					static::logLineHasText($this->filterText, $entry)
				) {
					$this->addLogger($entry['logger']);
					$entries[] = $entry;
					$entryCount++;
				}
			}
			$this->entries = $entries;
		}

		return $this->entries;
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


	public function getLoggers()
	{
		return array_keys($this->loggers);
	}


	public function hasLogger($logger)
	{
		return isset($this->loggers[$logger]);
	}


	private function addLogger($logger)
	{
		return $this->loggers[$logger];
	}

}
