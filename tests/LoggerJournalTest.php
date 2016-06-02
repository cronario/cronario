<?php


namespace Cronario\Test;


class LoggerJournalTest extends \PHPUnit_Framework_TestCase
{

    public function testCreating()
    {
        $journal = new \Cronario\Logger\Journal();

        $this->assertInstanceOf('\\Cronario\\Logger\\Journal', $journal);
    }


    public function testLogging()
    {

        $journal = new \Cronario\Logger\Journal();

        $journal->debug('-debug' , [ __NAMESPACE__]);
        $journal->info('-info',[ __NAMESPACE__]);
        $journal->notice('-notice',[ __NAMESPACE__]);
        $journal->warning('-warning',[ __NAMESPACE__]);
        $journal->error('-error',[ __NAMESPACE__]);
        $journal->critical('-critical',[ __NAMESPACE__]);
        $journal->alert('-alert',[ __NAMESPACE__]);
        $journal->emergency('-emergency',[ __NAMESPACE__]);

        $this->assertInstanceOf('\\Psr\\Log\\LoggerInterface', $journal);

    }

}
