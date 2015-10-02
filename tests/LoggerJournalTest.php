<?php


namespace Cronario\Test;


class LoggerJournalTest extends \PHPUnit_Framework_TestCase
{

    public function testCreating()
    {
        $journal = new \Cronario\Logger\Journal([
            \Cronario\Logger\Journal::P_CONSOLE_LEVEL  => 9,
            \Cronario\Logger\Journal::P_JOURNAL_LEVEL  => 9,
            \Cronario\Logger\Journal::P_JOURNAL_FOLDER => null,
        ]);

        $this->assertInstanceOf('\\Cronario\\Logger\\Journal', $journal);
    }


    public function testLogging()
    {

        $journal = new \Cronario\Logger\Journal([
            \Cronario\Logger\Journal::P_CONSOLE_LEVEL  => 9,
            \Cronario\Logger\Journal::P_JOURNAL_LEVEL  => 9,
            \Cronario\Logger\Journal::P_JOURNAL_FOLDER => '',
        ]);

        ob_start();
        $journal->debug('-debug');
        $journal->trace('-trace');
        $journal->info('-info');
        $journal->attempt('-attempt');
        $journal->warning('-warning');
        $journal->error('-error');
        $journal->exception(new \Exception('-exception ...'));
        $output = ob_get_flush();

        $this->assertContains('-debug' , $output);
        $this->assertContains('-trace' , $output);
        $this->assertContains('-info' , $output);
        $this->assertContains('-attempt' , $output);
        $this->assertContains('-error' , $output);
        $this->assertContains('-warning' , $output);
        $this->assertContains('-exception' , $output);

    }

}
