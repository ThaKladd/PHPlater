<?php

/**
 * This function gives quick access to the phplater class
 */
$php_later_instances = [];
function phplater(?string $template = null, ?array $plates = null, ?int $instance = null): PHPlater {
    if($instance !== null){
        if(!isset($php_later_instances[$instance])){
            $php_later_instances[$instance] = phplater($template, $plates);
        }
        return $php_later_instances[$instance];
    }
    return (new PHPlater($template))->plates($plates);
}