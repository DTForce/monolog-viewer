<?php
namespace Syonix\LogViewer;

use Dubture\Monolog\Parser\LineLogParser;
use InvalidArgumentException;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class LogFileFactory
{
    private $defaultPattern = '/\[(?P<date>.*)\] (?P<logger>\w+).(?P<level>\w+): (?P<message>[^\[\{]+) (?P<context>[\[\{].*[\]\}]) (?P<extra>[\[\{].*[\]\}])/';

    /**
     * @var LineLogParser
     */
    private $logParser;

    public function __construct()
    {
        $this->logParser = new LineLogParser();
    }

    public function createLogFile(LogFileDefinition $logFileDefinition)
    {
        $args = $logFileDefinition->getArgs();
        switch($args['type']) {
            case 'ftp':
                $filesystem = new Filesystem(new Ftp(array(
                    'host' => $args['host'],
                    'username' => $args['username'],
                    'password' => $args['password'],
                    'passive' => true,
                    'ssl' => false,
                )));
                break;
            case 'local':
                $filesystem = new Filesystem(new Local(dirname($args['path'])));
                $args['path'] = basename($args['path']);
                break;
            default:
                throw new InvalidArgumentException("Invalid log file type: \"" . $args['type']."\"");
        }

        $file = $filesystem->read($args['path']);
        $lines = explode("\n", $file);

        if(isset($args['pattern'])) {
            $this->logParser->registerPattern(LogFile::PATTERN_NAME, $args['pattern']);
        } else {
            $this->logParser->registerPattern(LogFile::PATTERN_NAME, $this->defaultPattern);
        }

        return new LogFile($lines, $logFileDefinition, $this->logParser);
    }
}
