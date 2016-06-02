<?php

namespace Cronario\Exception;

use \Result\ResultException as IkResultException;

/**
 * Class ResultException
 *
 * @package Cronario\Exception
 */
class ResultException extends IkResultException
{

    /******************************************************************************
     * STATUS
     ******************************************************************************/

    const STATUS_RETRY = 'retry';
    const STATUS_REDIRECT = 'redirect';
    const STATUS_QUEUED = 'queued';

    /******************************************************************************
     * RESULTS
     ******************************************************************************/

    const R_QUEUED = 1;
    const R_RETRY = 6;
    const R_REDIRECT = 7;
    const FAILURE_MAX_ATTEMPTS = 8;

    /******************************************************************************
     * LOGIC
     *
     * COMMON                   codes 0xx
     *
     * FAILURE                  codes 2xx
     * ERROR                    codes 4xx
     *
     * RETRIES                  codes 6xx
     * REDIRECTS                codes 7xx
     * RETRIES + REDIRECTS      codes 8xx
     ******************************************************************************/

    /**
     * Common results scope with message and status
     *
     * @var array
     */
    public static $results
        = [
            self::R_QUEUED             => array(
                self::P_MESSAGE => 'queued ...',
                self::P_STATUS  => self::STATUS_QUEUED,
            ),
            self::R_REDIRECT           => array(
                self::P_MESSAGE => 'redirect ...',
                self::P_STATUS  => self::STATUS_REDIRECT,
            ),
            self::R_RETRY              => array(
                self::P_MESSAGE => 'retry ...',
                self::P_STATUS  => self::STATUS_RETRY,
            ),
            self::FAILURE_MAX_ATTEMPTS => array(
                self::P_MESSAGE => 'failure max retries ...',
                self::P_STATUS  => self::STATUS_FAILURE,
            ),
        ];


    /**
     * Check for 'queued' status
     *
     * @return boolean
     */
    public function isQueued()
    {
        return ($this->status === self::STATUS_QUEUED);
    }

    /**
     * Check for 'retry' status
     *
     * @return boolean
     */
    public function isRetry()
    {
        return ($this->status === self::STATUS_RETRY);
    }

    /**
     * Check for 'redirect' status
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return ($this->status === self::STATUS_REDIRECT);
    }

}


