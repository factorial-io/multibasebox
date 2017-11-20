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
    /usr/local/bin/drush config-import staging --source=config/staging/ -y
    if [ "$STAGE" == "prod" ]; then
        /usr/local/bin/drush config-import --partial --source=config/prod -y
    fi
    /usr/local/bin/drush updb -y
fi
