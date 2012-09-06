<?php
define('SRC_ROOT', realpath(dirname(__FILE__) . '/../../src'));

function get_botobor_checks()
{
    $conf = new ReflectionProperty('Botobor', 'conf');
    $conf->setAccessible(true);
    $list = $conf->getValue('Botobor');
    $checks = array();
    foreach ($list as $key => $value)
    {
        if (strpos($key, 'check.') === 0)
        {
            $checks[substr($key, strlen('check.'))] = $value;
        }
    }
    return $checks;
}
