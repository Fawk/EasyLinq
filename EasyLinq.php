<?php

require_once("EasyLinqBase.php");

function in($list)
{
    return new EasyLinqBase($list);
}