<?php

use Composer\InstalledVersions;

class SomeClass
{
    public function __construct()
    {
        InstalledVersions::getInstalledPackages();
        json_decode();
    }
}
