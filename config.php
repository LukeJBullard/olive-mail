<?php
    /**
     * Config file for OliveWeb Mail module
     * 
     * @author Luke Bullard
     */

    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }

    $mail_config = array(
        "preset1" => array(
            "hostname" => "",
            "username" => "",
            "password" => ""
        )
    );
?>