# multibasebox

Serve multiple docker container with the help of haproxy from one vagrant-host.

## How it works

Multibasebox will provision a vagrant based virtual machine and install docker and docker-compose.

For serving the different containers a separate docker-container named [haproxy-config](https://github.com/factorial-io/haproxy-config) will be pulled and started.

haproxy is listening on port 80 of the virtual machine and will forward all requests to a specific docker-image running inside the vm. It uses the hostname to distinguish the containers.

How does haproxy know about the running docker-containers? There's a python script based on work of Bastian Hoyer which rewrites the haproxy-configuration on request. It will scan all running docker-containers and get the hostname and port from all running containers via environment-variables. The container set the environment-variable `VHOST` and (optionally) `VPORT` to their needs, the configuration utility parses this information and the internal IP of the docker-container and constructs a suitable haproxy-configuration file and restarts haproxy.

If you want to recreate the haproxy-configuraion just touch `/tmp/haproxy`, the script will rewrite the configuration and restart haproxy.


## Installation

1. Clone this repository,
2. Clone the submodules via `git submodule update --init`
3. Make sure you have ansible installed.
4. Install needed plugins:

    ```
    vagrant plugin install vagrant-dns # only for OS X
    vagrant plugin install vagrant-fabric
    ```

5. If you want to use vmware, make sure you have installed the corresponding plugin and applied a valid license (More info [here](https://www.vagrantup.com/vmware/))
6. Start your terminal, cd into the multibasebox-folder
7. Install vagrant-dns with `vagrant dns --install`
8. Run `vagrant up` (if you are using vmware, append `--provider=vmware_fusion`)
9. Wait
10. Visit `http://multibasebox.test:1936` This will show you the haproxy status page.

## Linux-specific remarks

`vagrant-dns` is not supported on Linux, you'll need to add your hosts to `/etc/hosts` manually:

```
33.33.33.33 multibasebox.test <your-other-hosts.test>
```


## Local modifications

The `Vagrantfile` includes `Vagrant.local` if the file exists. This is suitable to change the configuration to your needs without committing the changes back to a repository.

Here`s an example:
```
Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.provider "vmware_fusion" do |v|
    v.vmx["memsize"] = "4096"
    v.vmx["numvcpus"] = "4"
  end
end
```


## Setup a new project

Move your project-folder into `projects` or clone a repository into that folder. The `projects`-folder get mapped to `/vagrant` inside your vm.

Your docker-container should use the environment-variables ``VHOST`` and ``VPORT`` (defaults to 80) to signalize haproxy which hostname should be used and on which port the http-service is listening inside the container. There's no need to expose the ports to the host.

Fabalicious may help you to administrate your project and docker-setup. For more info visit [fabalicious](https://github.com/factorial-io/fabalicious)

For scaffolding new projects have a look at our generator [jaMann](https://github.com/factorial-io/generator-jaMann)


## Usage

1. start your vagrant-box with

    ```
    vagrant up
    ```

2. if you are using fabalicious as a deployment-helper, cd into your project and run

    ```
    fab config:mbb docker:run
    ```
    (this will work only if your have a suitable configuration in your fabfile.yaml)

## Status

You can see the status of haproxy at [http://multibasebox.test:1936/](http://multibasebox.test:1936/)

## Troubleshooting

#### I can't connect to `http://mutibasebox.test`

Check if vagrant-dns is running. On a mac try `dscacheutil -q host -a name multibasebox.test`. If the result is not `33.33.33.33`try reinstalling vagrant-dns via `vagrant dns --install`.



