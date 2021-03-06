# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure("2") do |config|
	config.vm.box = "ubuntu/bionic64"

	config.vm.network "forwarded_port", guest: 8000, host: 8000
	config.vm.network "forwarded_port", guest: 3306, host: 3006

	config.vm.provider "virtualbox" do |vb|
		vb.memory = "2048"
	end

	config.vm.provision "bootstrap", type: "shell", inline: <<-SHELL
		fallocate -l 4G /swapfile
		chmod 600 /swapfile

		mkswap /swapfile
		swapon /swapfile

		if grep -Fqvx "^/swapfile" /etc/fstab; then
			echo -e '/swapfile\tnone\tswap\tsw\t0\t0' >> /etc/fstab
		fi

		apt-get update -y
		apt-get remove apache2 -y

		add-apt-repository -y ppa:ondrej/php

		apt-get install -y ntp build-essential software-properties-common php7.2 php7.2-mysqlnd php7.2-curl \
			php7.2-zip php7.2-mbstring php7.2-xml php7.2-xdebug php7.2-memcached php7.2-gd
		apt-get install -y composer

		if grep -Fqvx "xdebug.remote_enable" /etc/php/7.2/mods-available/xdebug.ini; then
			echo "xdebug.remote_enable = on" >> /etc/php/7.2/mods-available/xdebug.ini
			echo "xdebug.remote_connect_back = on" >> /etc/php/7.2/mods-available/xdebug.ini
			echo "xdebug.idekey = application" >> /etc/php/7.2/mods-available/xdebug.ini
			echo "xdebug.remote_autostart = on" >> /etc/php/7.2/mods-available/xdebug.ini
			echo "xdebug.remote_host = 10.0.2.2" >> /etc/php/7.2/mods-available/xdebug.ini
		fi

		apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
		add-apt-repository 'deb [arch=amd64,arm64,ppc64el] http://mirrors.accretive-networks.net/mariadb/repo/10.3/ubuntu bionic main'
		DEBIAN_FRONTEND=noninteractive apt-get install -yq mariadb-server

		update-rc.d mysql defaults
		sed -i 's/^bind-address/#bind-address/' /etc/mysql/my.cnf
		systemctl restart mariadb

		mysql -e "DROP SCHEMA IF EXISTS application;"
		mysql -e "CREATE SCHEMA application;"
		mysql -e "CREATE USER 'application'@'%';"
		mysql -e "GRANT ALL ON application.* TO 'application'@'%';"
	SHELL

	config.vm.provision "install", type: "shell", privileged: false, inline: <<-SHELL
		echo "----------------------------------------------"
		echo "Configuring 'vagrant' user..."

		echo "[client]" > ~/.my.cnf
		echo "user=application" >> ~/.my.cnf
		echo "database=application" >> ~/.my.cnf

		echo '... Done!'

		echo "Initializing project..."

		cd /vagrant

		openssl genrsa -out config/jwt/private.pem 4096
		openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

		composer install
		composer db:reset
	SHELL

	config.vm.provision "admin-run", type: "shell", run: "always", inline: <<-SHELL
		systemctl stop ntp
		ntpd -gq > /dev/null
		systemctl start ntp
	SHELL

	config.vm.provision "run", type: "shell", run: "always", privileged: false, inline: <<-SHELL
		echo ""
		echo "Installed packages:"
		echo "  -> PHP 7.2 (with extensions: mysqlnd, curl, zip, mbstring, xml, xdebug, memcached, gd)"
		echo "  -> Composer"
		echo "  -> MariaDB"
		echo ""
		echo "Mapped Ports:"
		echo "  -> VM:8000 > Host:8000"
		echo "  -> VM:3306 > Host:3006"
		echo ""
		echo "XDebug Configuration:"
		echo "  -> IDE Key: application"
		echo "  -> Remote Autostart: Yes"
		echo "  -> Remote Connectback: Yes"
		echo ""
		echo "----------------------------------------------"
		echo "Starting Symfony server at http://localhost:8000"
		echo "If it doesn't come up, check the ~/symfony-server.log file for any error messages"

		cd /vagrant


		php bin/console server:stop > /dev/null
		php bin/console server:start 0.0.0.0 &> ~/symfony-server.log &
	SHELL
end