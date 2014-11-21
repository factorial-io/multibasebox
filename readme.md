# multibasebox

Serve multiple docker container with the help of haproxy from one vagrant-host.


## Setup

Move your project-folder into projects. Every project-folder may contain a fabfile.yaml,
which get parsed by the vagrantfile to setup /etc/hosts etc.

Your docker-container should use the environment-variables ``VHOST`` and ``VPORT`` (defaults to 80) to signalize haproxy which domain-name should be used and on which port the http-service is listening inside the container. There's no need to expose the ports to the host.

The Vagrantfile parses all fabfile.yaml-files for hostnames of the configuration ``local`` and ``mbb`` and use them as alias for the domain-name.

For more info see [fabalicious](https://github.com/stmh/fabalicious)

If you add a new project, do not forget to provision your vagrantbox again, as this will setup the hostnames. (or edit your /etc/hosts manually)

## Usage

1. start your vagrant-box with

    vagrant up
   
2. if you are using fabalicious as a deployment-helper, cd into your project and run

    fab config:mbb docker:run

## Status

You can see the status of haproxy at http://multibasebox.dev:1936/

## TODO
- better readme
