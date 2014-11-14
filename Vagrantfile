# this vagrant-file needs the following plugins:
# * vagrant-hostmaster

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"
Vagrant.require_version ">= 1.6"
require 'yaml'

path = "#{File.dirname(__FILE__)}"
ip_address = "33.33.33.33"
local_http_port = 8080
sitename = "multibasebox.dev"



# Check hostmanager required plugin
REQUIRED_PLUGINS = %w(vagrant-hostmanager vagrant-fabric)
exit unless REQUIRED_PLUGINS.all? do |plugin|
  Vagrant.has_plugin?(plugin) || (
    puts "The #{plugin} plugin is required. Please install it with:"
    puts "$ vagrant plugin install #{plugin}"
    false
  )
end


$script = <<SCRIPT
echo "Install packages..."
wget -q -O - https://get.docker.io/gpg | apt-key add -
rm -f /etc/apt/sources.list.d/docker.list
echo deb http://get.docker.io/ubuntu docker main > /etc/apt/sources.list.d/docker.list
apt-get update -qq
apt-get install -q -y --force-yes lxc-docker python-setuptools software-properties-common python-software-properties
usermod -a -G docker vagrant

echo "Installing haproxy 1.5"
apt-add-repository  ppa:vbernat/haproxy-1.5
apt-get update -qq; apt-get install -q -y --force-yes haproxy

echo "Installing python and pip..."
easy_install pip
pip install --upgrade pip virtualenv virtualenvwrapper

echo "Copying haproxy_config..."
pip install virtualenv
mkdir -p /home/vagrant/bin
cp -R /tmp/provisioning/haproxy_config /home/vagrant/bin/
cp /tmp/provisioning/haproxy_config.conf /etc/init/

echo "Provisioning haproxy_config..."
cd /home/vagrant/bin/haproxy_config
pip install -r requirements.txt

SCRIPT



Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "phusion-open-ubuntu-14.04-amd64"
  config.vm.box_url = "https://oss-binaries.phusionpassenger.com/vagrant/boxes/latest/ubuntu-14.04-amd64-vbox.box"
  # Or, for Ubuntu 12.04:
  #config.vm.box = "phusion-open-ubuntu-12.04-amd64"
  #config.vm.box_url = "https://oss-binaries.phusionpassenger.com/vagrant/boxes/latest/ubuntu-12.04-amd64-vbox.box"

  config.vm.provider :vmware_fusion do |f, override|
    override.vm.box_url = "https://oss-binaries.phusionpassenger.com/vagrant/boxes/latest/ubuntu-14.04-amd64-vmwarefusion.box"
    #override.vm.box_url = "https://oss-binaries.phusionpassenger.com/vagrant/boxes/latest/ubuntu-12.04-amd64-vmwarefusion.box"
  end

  config.vm.network :private_network, ip: ip_address

  if local_http_port
    config.vm.network "forwarded_port", guest: 80, host: local_http_port
  end


  # Use hostonly network with a static IP Address and enable
  # hostmanager so we can have a custom domain for the server
  # by modifying the host machines hosts file
  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true
  config.vm.hostname = sitename
  config.hostmanager.aliases = [ "www." + sitename ]
  config.vm.provision :hostmanager

  config.ssh.forward_agent = true

  config.vm.synced_folder "./projects", "/vagrant", nfs: true
  config.vm.synced_folder "./bin", "/tmp/provisioning", nfs: true

  #increase memory
  config.vm.provider "virtualbox" do |v|
    v.customize ["modifyvm", :id, "--cpuexecutioncap", "80"]
    v.customize ["modifyvm", :id, "--memory", 4536]
    v.cpus = 2
  end

  config.vm.provider "vmware_fusion" do |v|
    v.vmx["memsize"] = "1536"
    v.vmx["numvcpus"] = "2"
  end

  # shell provisioner
  config.vm.provision "shell", inline: $script


  if File.file?('vagrant.local')
    load "./vagrant.local"
  end
end
