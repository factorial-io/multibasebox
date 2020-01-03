# Helloworld-example

This is a minimal example, showing how to run docker-images with the help of [fabalicious](http://github.com/stmh/fablicious) and inside a multibasebox:

## Usage:

Make sure your vagrant box is up and running. Open a terminal, cd into the helloworld-folder

    phab --config mbb deploy

When the job is finished you should be able to visit the site at http://helloworld.test

## What happens under the hood

When you run ``phab deploy`` fabalicious will run the deploy-task from the fabfile, which will run a docker-task, which will lookup the docker-configuration for the host-configuration ``local`` and run the tasks specified there:

* stop an existing helloworld-container
* remove an existing helloworld-container
* run docker, pull a nginx-container and link some local paths into the box, so nginx can deliver an index.html at http://helloworld.test
* A helper script running as a sidekick with haproxy will reload the configuration automatically if a container gets started or stopped
