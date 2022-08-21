<?php

/**
 * This function gives quick access to the phplater class
 */
$phplater_instances = [];
function phplater(?string $template = null, ?array $plates = null, ?int $instance = null): PHPlater {
    if($instance !== null){
        if(!isset($phplater_instances[$instance])){
            $phplater_instances[$instance] = phplater($template, $plates);
        }
        return $phplater_instances[$instance];
    }
    return (new PHPlater($template))->plates($plates);
}