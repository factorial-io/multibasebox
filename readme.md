# multibasebox

Serve multiple docker container with the help of haproxy from one vagrant-host.


## Setup

Move your project-folder into projects. Every project-folder may contain a fabfile.yaml,
which get parsed by the vagrantfile to setup /etc/hosts etc.

Your docker-container should use the environment-variables ``VHOST`` and ``VPORT`` (defaults to 80) to signalize haproxy which domain-name should be used and on which port the http-service is listening inside the container. There's no need to expose the ports to the host.


## TODO
- implement everything
- better readme
