<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Syonix\Util\StringUtil;

class Client {
    protected $name;
    protected $slug;
    protected $logs;

    public function __construct($name = null)
    {
        $this->logs = new ArrayCollection();
        if($name !== null) {
            $this->setName($name);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->slug = StringUtil::toAscii($name);
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function addLog(LogFileDefinition $log) {
        if(!$this->logs->containsKey($log)) {
            $this->logs->set($log->getSlug(), $log);
        }
        return $this;
    }


    /**
     * @return LogFileDefinition[]
     */
    public function getLogs() {
        return $this->logs->toArray();
    }

    /**
     * @param $slug
     * @return LogFileDefinition|null
     */
    public function getLog($slug)
    {
        if (!$this->logs->containsKey($slug)) {
            return null;
        }
        return $this->logs->get($slug);
    }


    /**
     * @return LogFileDefinition|null
     */
    public function getFirstLog()
    {
        return ($this->logs->count() > 0) ? $this->logs->first() : null;
    }

    public function logExists($log)
    {
        foreach($this->logs as $existing_log) {
            if($existing_log->getSlug() == $log) return true;
        }
        return false;
    }

    public function toArray()
    {
        $logs = [];
        foreach($this->logs as $log) {
            $logs[] = $log->toArray();
        }
        return array(
            'name' => $this->name,
            'slug' => $this->slug,
            'logs' => $logs
        );
    }
}
