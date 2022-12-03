<?php

namespace Example\Library;

use Composer\InstalledVersions;

class Great
{
    public function __construct()
    {
        InstalledVersions::getInstalledPackages();
    }
}
