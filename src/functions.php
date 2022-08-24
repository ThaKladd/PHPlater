<?php

/**
 * This function gives quick access to the phplater class
 * @param string $template The template to use
 * @param array<mixed> $plates All the plates
 * @param ?int $instance To have a reference point to your instance
 * @return PHPlater The PHPlater object
 */
function phplater(string $template = '', array $plates = [], ?int $instance = null): PHPlater {
    if($instance !== null){
        if (!isset(PHPlaterBase::$function_instances[$instance])) {
            PHPlaterBase::$function_instances[$instance] = phplater($template, $plates);
        }
        return PHPlaterBase::$function_instances[$instance];
    }
    $phplater = (new PHPlater($template));
    $phplater->setPlates($plates);
    return $phplater;
}