<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function kc_include($path){
    include $path;
}

function kc_include_once($path){
    include_once $path;
}

function kc_file_exists($filename){
    return file_exists($filename);
}


?>
