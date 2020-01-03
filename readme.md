# multibasebox

Serve multiple docker container with the help of haproxy. Requires a running docker-environment

## How it works

Multibasebox consists of a simple shell script setting up name resolution and a haproxy container on your local environment.

For serving the different containers a separate docker-container named [haproxy-config](https://github.com/factorial-io/haproxy-config) will be pulled and started.

haproxy is listening on port 80 and 443 and will forward all requests to a specific docker-image. It uses the hostname to distinguish the containers.


## Installation

### With phabalicious

[Phabalicious](https://github.com/factorial-io/phabalicious) is nicely integrated with multibasebox and has a dedicated command to setup multibasebox since version 3.3. Run

```bash
phab workspace:create
```

and answer the questions. The scaffolder will create a `fabfile.local.yaml` suited to your local setup.

That's it!

### Manual setup

1. Clone this repository,
2. Run `bash ./setup-docker.sh`
3. Visit `http://multibasebox.test:1936` This will show you the haproxy status page.

### Integration with phabalicious

If you installed multibasebox with phabalicious, the following part is just for your information, as the phabalicious created this file already.  Here's a short example for `fabfile.local.yaml`, adapt it to your setup and it should work:

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

The script won't run the dnsmasq-container for linux, but there are some workarounds:

### Fedora

1. Create `/etc/NetworkManager/conf.d/00-use-dnsmaq.conf` with the following content

      ```
      dns=dnsmasq
      ```
2. Create `/etc/NetworkManager/dnsmasq.d/test.conf` with

      ```
      local=/test/
      address=/.test/127.0.0.1
      addn-hosts=/etc/hosts
      ```
3. Restart the NetworkManager with `sudo systemctl restart NetworkManager`

### Other distributions

Add your hosts to `/etc/hosts` similar to

```
127.0.0.1 multibasebox.test <your-other-hosts.test>
```

dnsmasq is available for a lot of linux distributions and it should be easy to install. Adapt your local config to sth similar.

## Setup a new project

1. Move your project-folder into `projects` or clone a repository into that folder.
2. Your docker-container should use the environment-variables ``VHOST`` and ``VPORT`` (defaults to 80) to signalize haproxy which hostname should be used and on which port the http-service is listening inside the container. There's no need to expose the ports to the host. See the documentation of [haproxy-config](https://github.com/factorial-io/haproxy-config)

Phabalicious may help you to administrate your project and docker-setup. For more info visit [fabalicious](https://github.com/factorial-io/phabalicious) It can also new projects.



## Usage

The containers are setup to restart automatically. If you encounter problems just run

```
bash setup-docker.sh
```


## Status

You can see the status of haproxy at [http://multibasebox.test:1936](http://multibasebox.test:1936/)


## Troubleshooting (Mac)

### container are not reachable, Host is down messages

Test if name resolution is working:

```
dscacheutil -q host -a name multibasebox.test
```

If you get an error message, try restarting mDNSResponder with

```
sudo killall mDNSResponder
```
or reboot the machine.

Still not working? Then try the `/etc/hosts`-route.

