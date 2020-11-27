<?php

namespace Example\Library;

use Composer\InstalledVersions;

class SomeClass
{
    public function __construct()
    {
        InstalledVersions::getInstalledPackages();
    }
}
