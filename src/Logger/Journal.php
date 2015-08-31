<?php

namespace Cronario\Logger;

use Cronario\TraitOptions;

class Journal implements LoggerInterface
{
    use TraitOptions;

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    const P_CONSOLE_LEVEL = "consoleLevel";
    const P_JOURNAL_LEVEL = "journalLevel";
    const P_JOURNAL_FOLDER = "journalFolder";

    protected $consoleLevel = 0;
    protected $journalLevel = 0;
    protected $journalFolder;

    protected function setConsoleLevel($level)
    {
        $this->consoleLevel = $level;
    }

    protected function setJournalLevel($level)
    {
        $this->journalLevel = $level;
    }

    protected function setJournalFolder($folder)
    {
        if (!empty($folder)) {
            $this->journalFolder = rtrim($folder, '/') . '/';
        }
    }


    public function log($level, $msg)
    {
        return $this->save($level, $msg);
    }


    public function debug($msg)
    {
        return $this->save(self::LEVEL_DEBUG, $msg);
    }

    public function trace($msg)
    {
        return $this->save(self::LEVEL_TRACE, $msg);
    }

    public function info($msg)
    {
        return $this->save(self::LEVEL_INFO, $msg);
    }

    public function attempt($msg)
    {
        return $this->save(self::LEVEL_ATTEMPT, $msg);
    }

    public function warning($msg)
    {
        return $this->save(self::LEVEL_WARNING, $msg);
    }

    public function error($msg)
    {
        return self::save(self::LEVEL_ERROR, $msg);
    }

    public function exception(\Exception $e)
    {
        $msg = "Exception in file {$e->getFile()} on line {$e->getLine()} with msg : {$e->getMessage()}";

        return $this->save(self::LEVEL_EXCEPTION, $msg);
    }


    protected function getCurrentDate()
    {
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $t));
        $result = $d->format('Y-m-d H:i:s.u');

        return $result;
    }

    protected function getJournalPath()
    {
        if (null === $this->journalFolder) {
            return null;
        }

        return $this->journalFolder . date('Y-m-d') . '.log';
    }

    protected function save($level, $msg)
    {
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }

        $points = str_repeat('. ', (int) $level);
        $prefix = $this->getCurrentDate() . ' : ' . $points;

        $line = $prefix . $msg . PHP_EOL;

        $journalPath = $this->getJournalPath();

        if ($this->journalLevel >= $level && null !== $journalPath) {
            file_put_contents($journalPath, $line, FILE_APPEND);
        }

        if ($this->consoleLevel >= $level) {
            echo $line;
        }

        return $this;
    }

}