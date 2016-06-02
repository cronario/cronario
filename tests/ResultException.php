<?php

namespace Cronario\Test;

class ResultException extends \Cronario\Exception\ResultException
{

    const FAILURE_XXX = 201;
    const ERROR_XXX = 401;
    const ERROR_PARAM_EXPECTED_RESULT = 402;
    const RETRY_XXX = 601;
    const REDIRECT_XXX = 701;

    /**
     * @var array
     */
    public static $results
        = array(
            self::FAILURE_XXX  => array(
                self::P_MESSAGE => 'FAILURE_XXX ...',
                self::P_STATUS  => self::STATUS_FAILURE,
            ),
            self::ERROR_XXX    => array(
                self::P_MESSAGE => 'ERROR_XXX ...',
                self::P_STATUS  => self::STATUS_ERROR,
            ),
            self::ERROR_PARAM_EXPECTED_RESULT    => array(
                self::P_MESSAGE => 'ERROR_PARAM_EXPECTED_RESULT ...',
                self::P_STATUS  => self::STATUS_ERROR,
            ),
            self::RETRY_XXX    => array(
                self::P_MESSAGE => 'RETRY_XXX ...',
                self::P_STATUS  => self::STATUS_RETRY,
            ),
            self::REDIRECT_XXX => array(
                self::P_MESSAGE => 'REDIRECT_XXX ...',
                self::P_STATUS  => self::STATUS_REDIRECT,
            ),
        );
}