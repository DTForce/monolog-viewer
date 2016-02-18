<?php
namespace Syonix\LogViewer;

use Dubture\Monolog\Parser\LineLogParser;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

class Cache
{
    private $cache;
    private $expire;
    private $reverse;

    public function __construct(AdapterInterface $adapter, $expire = 300, $reverse = true)
    {
        $this->cache = new Filesystem($adapter);
        $this->expire = $expire;
        $this->reverse = $reverse;
    }

    public function get(LogFile $logFile)
    {
        return $this->loadSource($logFile);
    }

    private function getFilename(LogFile $logFile)
    {
        return base64_encode($logFile->getIdentifier() . '_of_' . $logFile->getOffset() . '_lim_' . $logFile->getLimit());
    }

    public function emptyCache()
    {
        $cache = $this->cache->get('/')->getContents();
        foreach ($cache as $file) {
            if($file['type'] == 'file') $this->cache->delete($file['path']);
        }
    }

    private function loadSource(LogFile $logFile)
    {
        $args = $logFile->getArgs();

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
                throw new \InvalidArgumentException("Invalid log file type: \"" . $args['type']."\"");
        }

        $file = $filesystem->read($args['path']);
        $lines = explode("\n", $file);

        unset($file); // deallocate memory

        $parser = new LineLogParser();
        $pattern = 'default';
        if(isset($args['pattern'])) {
            $pattern = 'custom';
            $parser->registerPattern('custom', $args['pattern']);
        }

        for ($pos = 0; $pos < ($logFile->getOffset() + $logFile->getLimit()); $pos++) {
            if ($logFile->getOffset() > $pos) {
                unset($lines[$pos]); // deallocate memory
                continue;
            }

            $line = $lines[$pos];
            $entry = $parser->parse($line, 0, $pattern);
            if (count($entry) > 0) {
                if(!$logFile->hasLogger($entry['logger'])) {
                    $logFile->addLogger($entry['logger']);
                }
                $logFile->addLine($entry);
            }
        }

        if ($this->reverse) {
            $logFile->reverseLines();
        }

        return $logFile;
    }
}
