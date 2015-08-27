<?php

namespace Cronario\Storage;

use Cronario\AbstractJob;

interface StorageInterface
{

    /**
     * @param AbstractJob $job
     *
     * @return mixed
     */
    public function save(AbstractJob $job);

    /**
     * @param $jobId
     *
     * @return AbstractJob|null
     */
    public function find($jobId);

}