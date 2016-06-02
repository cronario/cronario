# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|

  config.vm.box = "deb/wheezy-amd64"
  config.vm.box_check_update = true

  config.vm.network "private_network", ip: "192.168.56.15"

  config.vm.synced_folder "./", "/var/www"


  config.vm.provider "virtualbox" do |vb|
    vb.gui = false
    vb.memory = "1024"
  end

  if Vagrant.has_plugin?('vagrant-cachier')
    config.cache.scope = :box
  end

   config.vm.provision "ansible" do |ansible|
      ansible.playbook = "playbook.yml"
    end
end
