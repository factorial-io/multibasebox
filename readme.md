# multibasebox

Serve multiple docker container with the help of haproxy. Requires a running docker-environment

## How it works

Multibasebox consists of a simple shell script setting up name resolution and a haproxy container on your local environment.

For serving the different containers a separate docker-container named [haproxy-config](https://github.com/factorial-io/haproxy-config) will be pulled and started.

haproxy is listening on port 80 ond 443 and will forward all requests to a specific docker-image running inside the vm. It uses the hostname to distinguish the containers.


## Installation

1. Clone this repository,
2. Clone the submodules via `git submodule update --init`
3. Run `bash ./setup-docker.sh`
4. Visit `http://multibasebox.test:1936` This will show you the haproxy status page.

## Fabalicious setup

Most likely you'll need a specialized `fabfile.local.yaml` if you are using fabalicious. Here's a short example, adpat it to your setup and it should work:

```yaml
dockerHosts:
  mbb:
    runLocally: true
    rootFolder: <absolute-path-to-projects-folder>


hosts:
  mbb:
    xdebug:
      remote_host: docker.for.mac.localhost
    blueprint:
      xdebug:
        remote_host: docker.for.mac.localhost
```

## Linux-specific remarks

automatic dns resolution does not work on linux, only on os x. Add your hosts to `/etc/hosts` similar to

```
127.0.0.1 multibasebox.test <your-other-hosts.test>
```

## Setup a new project

Move your project-folder into `projects` or clone a repository into that folder.

Your docker-container should use the environment-variables ``VHOST`` and ``VPORT`` (defaults to 80) to signalize haproxy which hostname should be used and on which port the http-service is listening inside the container. There's no need to expose the ports to the host. See the documentation of [haproxy-config](https://github.com/factorial-io/haproxy-config)

Fabalicious may help you to administrate your project and docker-setup. For more info visit [fabalicious](https://github.com/factorial-io/fabalicious)

For scaffolding new projects have a look at our generator [jaMann](https://github.com/factorial-io/generator-jaMann)


## Usage

The containers are setup to restart automatically. If you encounter problems just run `bash setup-docker.sh`


## Status

You can see the status of haproxy at [http://multibasebox.test:1936/](http://multibasebox.test:1936/)





