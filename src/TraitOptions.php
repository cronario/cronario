<?php

namespace Cronario;

trait TraitOptions
{

    final private function setOptions(array $options = [])
    {
        if (empty($options)) {
            return $this;
        }

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if ($method == 'setOptions') {
                continue;
            }
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }
}