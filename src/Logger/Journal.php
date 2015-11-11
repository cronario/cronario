<?php

namespace Cronario\Logger;

class Journal implements \Psr\Log\LoggerInterface
{
    use \Psr\Log\LoggerTrait;

    protected $journalFolder;
    protected $journalRecord;

    /**
     * @param string $folder
     * @param array  $record
     */
    public function __construct($folder = __DIR__, array $record = ['emergency', 'alert', 'critical', 'error'])
    {
        $this->journalFolder = rtrim($folder, '/') . '/';
        $this->journalRecord = $record;
    }


    /**
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {

        if (!in_array($level, $this->journalRecord)) {
            return null;
        }

        $line = sprintf("%s: %s %s %s",
                $this->getCurrentDate(),
                str_repeat('. ', $this->levelToPoints[$level]),
                $message,
                '[ ' . implode(' ; ', $context) . ' ]'
            ) . PHP_EOL;

        file_put_contents($this->getJournalPath(), $line, FILE_APPEND);

        return null;
    }

    /**
     * @var array
     */
    protected $levelToPoints
        = [
            'emergency' => 0,
            'alert'     => 1,
            'critical'  => 2,
            'error'     => 3,
            'warning'   => 4,
            'notice'    => 5,
            'info'      => 6,
            'debug'     => 7,
        ];

    /**
     * @return string
     */
    protected function getCurrentDate()
    {
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $t));
        $result = $d->format('Y-m-d H:i:s.u');

        return $result;
    }

    /**
     * @return string
     */
    protected function getJournalPath()
    {
        return $this->journalFolder . date('Y-m-d') . '.log';
    }

}