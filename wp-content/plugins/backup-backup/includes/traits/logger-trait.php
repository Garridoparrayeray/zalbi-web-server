<?php

namespace BMI\Plugin\Traits;

if (!defined('ABSPATH')) exit;

use BMI\Plugin\BMI_Logger as Logger;

trait LoggerTrait
{
    public function log($message)
    {
        Logger::log(get_called_class() . ': ' . json_encode($message));
    }

    public function error($message)
    {
        Logger::error(get_called_class() . ': ' . json_encode($message));
    }

    public function debug($message)
    {
        Logger::debug(get_called_class() . ': ' . json_encode($message));
    }

}