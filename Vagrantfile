# -*- mode: ruby -*-
# vi: set ft=ruby :

require 'yaml'
require 'mkmf'
require 'fileutils'

DIR = File.dirname(__FILE__)

config_file = File.join(DIR, 'config.yml')
sample_file = File.join(DIR, 'config-sample.yml')

unless File.exists?(config_file)
  # Use sample instead
  FileUtils.copy sample_file, config_file
  puts '==> default: config.yml was not found. Copying from sample configs..'
end

site_config = YAML.load_file(config_file)

Vagrant.require_version '>= 1.5.1'

Vagrant.configure('2') do |config|

  # Use host-machine ssh-key so we can log into production
  config.ssh.forward_agent = true

  # Use precompiled box
  config.vm.box = 'seravo/wordpress'

  # Required for NFS to work, pick any local IP
  config.vm.network :private_network, ip: '192.168.50.10'

  config.vm.hostname = site_config['name']+"-wp-dev"

  if Vagrant.has_plugin? 'vagrant-hostsupdater'
    domains = get_domains(site_config)

    config.hostsupdater.aliases = domains - [config.vm.hostname]
  else
    puts 'vagrant-hostsupdater missing, please install the plugin:'
    puts 'vagrant plugin install vagrant-hostsupdater'
    exit 1
  end

  # We only need to sync this project folder with /data/wordpress/
  config.vm.synced_folder DIR, '/data/wordpress/', owner: 'vagrant', group: 'vagrant', mount_options: ['dmode=776', 'fmode=775']

  # Disable default vagrant share
  config.vm.synced_folder ".", "/vagrant", disabled: true

  # For Self-signed ssl-certificate
  ssl_cert_path = File.join(DIR,'.vagrant','ssl')
  unless File.exists? File.join(ssl_cert_path,'development.crt')
    config.vm.provision :shell, :inline => "wp-generate-ssl"
  end

  # Add SSH Public Key from developer home folder into vagrant
  if File.exists? File.join(Dir.home, ".ssh", "id_rsa.pub")
    id_rsa_ssh_key_pub = File.read(File.join(Dir.home, ".ssh", "id_rsa.pub"))
    config.vm.provision :shell, :inline => "echo '#{id_rsa_ssh_key_pub }' >> /home/vagrant/.ssh/authorized_keys && chmod 600 /home/vagrant/.ssh/authorized_keys"
  end

  # WP-Palvelu uses Https-domain-alias plugin heavily. For debugging add HTTPS_DOMAIN_ALIAS to envs
  unless site_config['name'].nil?
    config.vm.provision :shell, :inline => "echo 'export HTTPS_DOMAIN_ALIAS=#{site_config['name']}.seravo.dev' >> /etc/container_environment.sh"
    config.vm.provision :shell, :inline => "echo 'fastcgi_param  HTTPS_DOMAIN_ALIAS   #{site_config['name']}.seravo.dev;' >> /etc/nginx/fastcgi_params"
  end

  # Some useful triggers with better workflow
  if Vagrant.has_plugin? 'vagrant-triggers'
    # TODO: Create/Sync database with vagrant up
    config.trigger.after :up do

      #Run all system commands inside project root
      Dir.chdir(DIR)

      if confirm "Install composer packages?"
        #Run locally if possible
        if find_executable 'composer' and system "composer validate > /dev/null"
          system "composer install"
        else
          run_remote "composer install --working-dir=/data/wordpress"
        end
      end

      # Database imports
      if site_config['production'] != nil && site_config['production']['ssh_port'] != nil and confirm("Pull database from production?",false)
        ##
        # Wordpress palvelu customers can pull the production database here
        ##
        run_remote("wp-pull-production-db")
      elsif File.exists?(File.join(DIR,'.vagrant','shutdown-dump.sql'))
        #Return the state where we last left
        run_remote("wp-vagrant-import-db")
      else
        # If nothing else was specified just install basic wordpress
        run_remote("wp core install --url=http://#{site_config['name']}.dev --title=#{site_config['name'].capitalize}\
         --admin_email=vagrant@#{site_config['name']}.dev --admin_user=vagrant --admin_password=vagrant")
        notice "Installed default wordpress with user:vagrant password:vagrant"
      end

      unless Vagrant::Util::Platform.windows?
        if  confirm "Activate git hooks in scripts/hooks?"
          # symlink git on remote
          run_remote "wp-activate-git-hooks"

          # create hook folder (if not exists) and symlink git on host
          git_hooks_dir = File.join(DIR,".git","hooks")
          Dir.mkdir(git_hooks_dir) unless File.exists?(git_hooks_dir)
          Dir.chdir git_hooks_dir
          system "ln -sf ../../scripts/hooks/* ."
          system "chmod +x ../../scripts/hooks/*"
        end
      end

      case RbConfig::CONFIG['host_os']
      when /darwin/
        # Do OS X specific things
        unless File.exists?(File.join(ssl_cert_path,'trust.lock'))
          if File.exists?(File.join(ssl_cert_path,'development.crt')) and confirm "Trust the generated ssl-certificate in OS-X keychain?"
            system "sudo security add-trusted-cert -d -r trustRoot -k '/Library/Keychains/System.keychain' #{ssl_cert_path}/development.crt"
            # Write lock file so we can remove it too
            touch_file File.join(ssl_cert_path,'trust.lock')
          end
        end
      when /linux/
        # Do linux specific things
      end

      # File system might not have been ready when nginx started.
      run_remote("sudo service nginx restart")

      notice "Visit your site: http://#{site_config['name']}.dev"
    end

    config.trigger.before :halt do
      # dump database when closing vagrant
      dump_wordpress_database
    end

    config.trigger.before :destroy do
      # dump database when destroying vagrant
      dump_wordpress_database
    end

    # TODO: Activate .git commit hooks with vagrant up
    # - git commit to master should activate all tests
  else
    puts 'vagrant-triggers missing, please install the plugin:'
    puts 'vagrant plugin install vagrant-triggers'
    exit 1
  end

  config.vm.provider 'virtualbox' do |vb|
    # Give VM access to all cpu cores on the host
    cpus = case RbConfig::CONFIG['host_os']
      when /darwin/ then `sysctl -n hw.ncpu`.to_i
      when /linux/ then `nproc`.to_i
      else 2
    end

    # Customize memory in MB
    vb.customize ['modifyvm', :id, '--memory', 1024]
    vb.customize ['modifyvm', :id, '--cpus', cpus]

    # Fix for slow external network connections
    vb.customize ['modifyvm', :id, '--natdnshostresolver1', 'on']
    vb.customize ['modifyvm', :id, '--natdnsproxy1', 'on']
  end

end

##
# Custom helpers
##
def notice(text)
  puts "==> trigger: #{text}"
end

def dump_wordpress_database
  notice "dumping the database into: .vagrant/shutdown-dump.sql"
  run_remote "wp-vagrant-dump-db"
end

def touch_file(path)
  File.open(path, "w") {}
end

def get_domains(config)
  unless config['development'].nil?
    domains = config['development']['domains'] || []
    domains << config['development']['domain'] unless config['development']['domain'].nil?
  else
    domains = []
  end

  domains << "www."+config['name']+".dev"
  domains << config['name']+".seravo.dev" #test https-domain-alias locally
  domains << "webgrind."+config['name']+".dev" #For xdebug
  domains << "adminer."+config['name']+".dev" #For adminer
  domains << "mailcatcher."+config['name']+".dev" #For mailcatcher
  domains << "terminal."+config['name']+".dev" #For ad-hoc terminal commands (and links into terminal)
  domains << "info."+config['name']+".dev" #For info page
  domains.uniq #remove duplicates
end

def confirm(question,default=true)
  if default
    default = "yes"
  else
    default = "no"
  end

  confirm = nil
  until ["Y","N","YES","NO",""].include?(confirm)
    ask "#{question} (default: #{default}): "

    if (confirm.nil? or confirm.empty?)
      confirm = default
    end

    confirm.strip!
    confirm.upcase!
  end
  if confirm.empty? or confirm == "Y" or confirm == "YES"
    return true
  end
  return false
end

