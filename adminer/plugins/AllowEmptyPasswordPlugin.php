<?php

class AllowEmptyPasswordPlugin
{
    function login($login, $password)
    {
        return true;
    }
}
