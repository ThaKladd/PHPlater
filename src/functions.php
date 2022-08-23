<?php

/**
 * This function gives quick access to the phplater class
 */
function phplater(?string $template = null, ?array $plates = null, ?int $instance = null): PHPlater {
    if($instance !== null){
        if (!isset(PHPlaterBase::$function_instances[$instance])) {
            PHPlaterBase::$function_instances[$instance] = phplater($template, $plates);
        }
        return PHPlaterBase::$function_instances[$instance];
    }
    return (new PHPlater($template))->plates($plates);
}