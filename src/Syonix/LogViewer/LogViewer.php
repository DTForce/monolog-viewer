<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Syonix\LogViewer\Exceptions\NoLogsConfiguredException;

class LogViewer
{
    protected $clients;
    protected $cacheDir;

    public function __construct($logs)
    {
        setlocale(LC_ALL, 'en_US.UTF8');

        $this->clients = new ArrayCollection();

        if(count($logs) == 0) {
            throw new NoLogsConfiguredException();
        }
        foreach ($logs as $client_name => $client_logs) {
            if(count($client_logs) > 0) {
                $client = new Client($client_name);
                foreach ($client_logs as $log_name => $args) {
                    $client->addLog(new LogFileDefinition($log_name, $args));
                }
                $this->clients->add($client);
            }
        }
    }
    
    public function hasLogs()
    {
        return !$this->clients->isEmpty();
    }


    /**
     * @return Client[]
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param $slug
     * @return Client|null
     */
    public function getClient($slug)
    {
        foreach($this->clients as $client) {
            if($client->getSlug() == $slug) return $client;
        }
        return null;
    }


    /**
     * @return Client|null
     */
    public function getFirstClient()
    {
        return ($this->clients->count() > 0) ? $this->clients->first() : null;
    }
    
    public function clientExists($client)
    {
        return $this->clients->contains($client);
    }
}
