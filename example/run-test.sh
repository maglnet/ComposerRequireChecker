#!/bin/sh

set -x

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

mkdir -p "$DIR/test-data/zend-feed"
git clone git@github.com:zendframework/zend-feed.git "$DIR/test-data/zend-feed"
cd "$DIR/test-data/zend-feed"

# checking out a release that is known to have "soft" (broken) dependencies:
git checkout release-2.5.0

curl -sS https://getcomposer.org/installer | php
./composer.phar install

cd "$DIR"

php test.php
