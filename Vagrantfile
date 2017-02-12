# this vagrant-file needs the following plugins:
# * vagrant-dns
# * vagrant-fabric

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"
Vagrant.require_version ">= 1.6"

path = "#{File.dirname(__FILE__)}"
ip_address = "33.33.33.33"
sitename = "multibasebox.dev"

# Check required plugins
REQUIRED_PLUGINS = %w(vagrant-dns vagrant-fabric)
exit unless REQUIRED_PLUGINS.all? do |plugin|
  Vagrant.has_plugin?(plugin) || (
    puts "The #{plugin} plugin is required. Please install it with:"
    puts "$ vagrant plugin install #{plugin}"
    false
  )
end


# Run dns.
system('./vagrant dns --start')

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
  config.vm.hostname = sitename

  config.dns.tld = "dev"
  config.dns.patterns = [/^.*.dev$/]

  config.ssh.forward_agent = true

  config.vm.synced_folder "./projects", "/vagrant", nfs: true, mount_options: ["nolock", "vers=3", "tcp", "fsc"]

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
  #config.vm.provision "shell", inline: $script
  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "provision/playbook.yml"
  end
  config.vm.provision "shell", path: "startup.sh", run: "always"
end

if File.file?('vagrant.local')
  load "./vagrant.local"
end
