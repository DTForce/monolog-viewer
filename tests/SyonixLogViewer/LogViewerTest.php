<?php

use League\Flysystem\Adapter\NullAdapter;
use Syonix\LogViewer\LogFileDefinition;
use Syonix\LogViewer\LogFileFactory;
use Syonix\LogViewer\LogFile;


class LogViewerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syonix\LogViewer\LogViewer
     */
	protected $logViewer;

    /**
     * @var LogFileFactory
     */
    protected $cache;

	public function setUp(){
        $config = array(
            'Client1' => array(
                'Log1' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                ),
                'Log2' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                )
            ),
            'Client2' => array(
                'Log3' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                )
            )
        );
	    $this->logViewer = new Syonix\LogViewer\LogViewer($config);
	}
	
	public function tearDown(){ }
    
    public function testInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\LogViewer', $this->logViewer, "LogViewer is not instance of LogViewer");
    }
    
    /**
     * @depends testInit
     */
    public function testClientsInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\Client', $this->logViewer->getFirstClient());
        $this->assertEquals('Client1', $this->logViewer->getFirstClient()->getName());
    }
    
    /**
     * @depends testClientsInit
     */
    public function testGetLogs()
    {
        $this->assertEquals(2, count($this->logViewer->getFirstClient()->getLogs()));
    }

    /**
     * @depends testGetLogs
     */
    public function testGetLog()
    {
        $log = $this->logViewer->getFirstClient()->getFirstLog();
        $this->assertInstanceOf(LogFileDefinition::class, $log);
        $this->assertEquals('Log1', $log->getName());
        return $log;
    }


    /**
     * @param LogFile $log
     *
     * @depends testGetLog
     */
    public function testGetLogLines(LogFileDefinition $log)
    {
        $adapter = new NullAdapter();
        $this->cache = new LogFileFactory($adapter,300, false);
        $log = $this->cache->createLogFile($log);
        $lines = $log->getLines();
        $this->assertInstanceOf('DateTime', $lines[0]['date']);
        $this->assertEquals('debug', $lines[0]['logger']);
        $this->assertEquals('DEBUG', $lines[0]['level']);
        $this->assertEquals('Random debug message', $lines[0]['message']);
        $this->assertEquals('Context1', $lines[0]['context']['c1']);
        $this->assertTrue(is_array($lines[0]['extra']));
    }


    /**
     * @param LogFile $log
     *
     * @depends testGetLog
     */
    public function testLimit(LogFileDefinition $log)
    {
        $adapter = new NullAdapter();
        $this->cache = new LogFileFactory($adapter,300, false);
        $log = $this->cache->createLogFile($log);
        $line0 = $log->getLines()[0];
        $this->assertCount(8, $log->getLines());
        $log->setLimit(4);
        $this->assertCount(4, $log->getLines());
        $this->assertEquals($line0, $log->getLines()[0]);
        $log->setLimit(8);
        $this->assertCount(8, $log->getLines());
        $this->assertEquals($line0, $log->getLines()[0]);
    }


    /**
     * @param LogFile $log
     *
     * @depends testGetLog
     */
    public function testOffset(LogFileDefinition $log)
    {
        $adapter = new NullAdapter();
        $this->cache = new LogFileFactory($adapter,300, false);
        $log = $this->cache->createLogFile($log);
        $line0 = $log->getLines()[0];
        $this->assertCount(8, $log->getLines());
        $log->setOffset(4);
        $this->assertCount(4, $log->getLines());
        $this->assertNotEquals($line0, $log->getLines()[0]);
        $log->setOffset(0);
        $this->assertCount(8, $log->getLines());
        $this->assertEquals($line0, $log->getLines()[0]);
    }


    /**
     * @param LogFileDefinition $log
     *
     * @depends testGetLog
     */
    public function testFilter(LogFileDefinition $log)
    {
        $adapter = new NullAdapter();
        $this->cache = new LogFileFactory($adapter,300, false);
        $log = $this->cache->createLogFile($log);
        $log->setFilter(null, 0, 'Random debug message');
        $line0 = $log->getLines()[0];
        $this->assertCount(1, $log->getLines());
        $this->assertInstanceOf('DateTime', $line0['date']);
        $this->assertEquals('debug', $line0['logger']);
        $this->assertEquals('DEBUG', $line0['level']);
        $this->assertEquals('Random debug message', $line0['message']);
        $this->assertEquals('Context1', $line0['context']['c1']);
        $this->assertTrue(is_array($line0['extra']));
    }

}
