<?php

function get_fields($vars)
{
    /*  in your view just call this in your small 
            @php 
             $myvariables = get_fields(get_defined_vars());
            @endphp
    */
    
    $vars = $vars['__data'];
    unset($vars['__env']);
    unset($vars['app']);
    unset($vars['errors']);
    return $vars;
}
