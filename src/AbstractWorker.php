<?php

namespace Cronario;

use Cronario\Exception\ResultException;
use Cronario\Exception\WorkerException;

abstract class AbstractWorker
{

    /**
     * @param $workerClass
     *
     * @return mixed|self
     * @throws WorkerException
     */
    public static function factory($workerClass)
    {
        if (!class_exists($workerClass)) {
            throw new Exception\WorkerException("Worker {$workerClass} is not exists");
        }

        $worker = new $workerClass;

        if (!$worker instanceof AbstractWorker) {
            throw new Exception\WorkerException("Worker {$workerClass} is not instanceof AbstractWorker");
        }

        return $worker;
    }

    //region CONFIG **************************************************

    const CONFIG_P_MANAGERS_LIMIT = 'managers_limit';
    const CONFIG_P_MANAGER_LIFETIME = 'manager_lifetime';
    const CONFIG_P_MANAGER_POOL_SIZE = 'manager_pool_size';
    const CONFIG_P_MANAGER_IDLE_DIE_DELAY = 'manager_idle_die_delay';
    const CONFIG_P_MANAGER_JOB_DONE_DELAY = 'manager_job_done_delay';
    const CONFIG_P_JOBS_DONE_LIMIT = 'jobs_done_limit';
    const CONFIG_P_JOBS_SUCCESS_LIMIT = 'jobs_success_limit';
    const CONFIG_P_JOBS_FAIL_LIMIT = 'jobs_fail_limit';
    const CONFIG_P_JOBS_RETRY_LIMIT = 'jobs_retry_limit';
    const CONFIG_P_JOBS_ERROR_LIMIT = 'jobs_error_limit';
    const CONFIG_P_JOBS_REDIRECT_LIMIT = 'jobs_redirect_limit';

    const DEFAULT_CONFIG_FILENAME = 'config.php';

    protected static $configDefault
        = [
            self::CONFIG_P_MANAGERS_LIMIT         => 4, // max thread count
            self::CONFIG_P_MANAGER_POOL_SIZE      => 5, // job count to increase threads
            self::CONFIG_P_MANAGER_JOB_DONE_DELAY => 0, // seconds
            self::CONFIG_P_MANAGER_IDLE_DIE_DELAY => 5, // seconds

            self::CONFIG_P_JOBS_DONE_LIMIT        => 999, // limit ... count
            self::CONFIG_P_JOBS_SUCCESS_LIMIT     => 999, // limit ... count
            self::CONFIG_P_JOBS_FAIL_LIMIT        => 999, // limit ... count
            self::CONFIG_P_JOBS_RETRY_LIMIT       => 999, // limit ... count
            self::CONFIG_P_JOBS_ERROR_LIMIT       => 999, // limit ... count
            self::CONFIG_P_JOBS_REDIRECT_LIMIT    => 999, // limit ... count
            self::CONFIG_P_MANAGER_LIFETIME       => 0,   // seconds (0 = forever)
        ];



    /*
     * [
     *      'Xxx\Worker' => [
     *          '...' => '...',
     *      ],
     *      'Yyy\Worker' => [
     *          '...' => '...',
     *      ]
     * ]
     */
    private static $loadedConfigSet = [];
    protected static $config = [];
    protected static $configFile; // __DIR__ . '/' . self::DEFAULT_CONFIG_FILENAME;

    /**
     * @return array|mixed|string
     * @throws WorkerException
     */
    protected static function loadConfig()
    {
        if (!empty(static::$configFile) && is_readable(static::$configFile)) {
            return static::loadConfigFile(static::$configFile);
        }

        return static::$config;
    }

    /**
     * @param $path
     *
     * @return mixed|string
     * @throws WorkerException
     */
    protected static function loadConfigFile($path)
    {
        if (!is_file($path)) {
            throw new Exception\WorkerException("Configuration file not exist '{$path}'");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ('php' === $extension) {
            $config = require_once($path);
        } elseif ('json' === $extension) {
            $config = file_get_contents($path);
            $config = json_decode($config, true);
        } else {
            throw new Exception\WorkerException("Configuration file must be PHP or JSON : '{$path}'");
        }

        if (!is_array($config)) {
            throw new Exception\WorkerException("Configuration file must return array inside '{$path}'");
        }

        return $config;
    }

    /**
     * @param null $key
     *
     * @return mixed
     */
    final public static function getConfig($key = null)
    {
        $calledClass = get_called_class();

        // Load config
        if (null === self::$loadedConfigSet[$calledClass]) {
            self::$loadedConfigSet[$calledClass] = array_merge(self::$configDefault, static::loadConfig());
        }

        // Get config property
        return (null === $key)
            ? self::$loadedConfigSet[$calledClass]
            : self::$loadedConfigSet[$calledClass][$key];
    }

    //endregion **************************************************

    //endregion JOB CALLBACKS **************************************************

    /**
     * @param AbstractJob $job
     *
     * @return $this
     */
    protected function invokeCallbacks(AbstractJob $job)
    {
        $result = $job->getResult();

        if ($result->isRedirect() || $result->isRetry()) {
            return $this;
        }

        if ($result->isError()) {
            $callbackJobs = $job->getCallback(AbstractJob::P_CALLBACK_T_ERROR);
        } else {
            $type = ($result->isSuccess())
                ? AbstractJob::P_CALLBACK_T_SUCCESS
                : AbstractJob::P_CALLBACK_T_FAILURE;

            $callbackJobs = array_merge(
                $job->getCallback(AbstractJob::P_CALLBACK_T_ERROR),
                $job->getCallback($type)
            );
        }

        foreach ($callbackJobs as $index => $callbackJob) {
            /** @var $callbackJob AbstractJob */
            $callbackJob($job);
        }

        return $this;
    }

    //endregion **************************************************

    //endregion MAIN **************************************************

    /**
     * @param AbstractJob $job
     *
     * @throws ResultException
     */
    abstract protected function doJob(AbstractJob $job);

    /**
     * @param AbstractJob $job
     *
     * @return ResultException|\Exception|null
     * @throws Exception\JobException
     * @throws \Exception
     */
    public function __invoke(AbstractJob $job)
    {
        try {
            $result = $this->doJob($job);
        } catch (\Exception $ex) {
            $result = $ex;
        }

        $job->setResult($result);
        $job->save();

        $this->invokeCallbacks($job);

        return $job->getResult();
    }

    //endregion **************************************************

}