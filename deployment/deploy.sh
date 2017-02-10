#!/bin/bash -ex

STAGE="$1"
if [ "$STAGE" == "develop" ] || [ "$STAGE" == "prod" ]; then
    cd "/var/www/$STAGE/html/"
    git pull
    cd themes/circle
    npm install
    ./node_modules/.bin/gulp dist
    cd ../../
    /usr/local/bin/drush cr
fi
