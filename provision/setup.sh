#!/bin/bash
set -e # se qualquer comando der erro, para tudo na hora

# o Vagrantfile ja rodou apt-get update antes de chamar este script
apt-get install -y apache2 php libapache2-mod-php php-mysql mysql-server

mkdir -p /var/lib/projeto2/envs # pasta onde vao ficar os ambientes

if [ -f /opt/projeto2/provision/db.sh ]; then
	bash /opt/projeto2/provision/db.sh
fi

cp /opt/projeto2/provision/apache/projeto2.conf /etc/apache2/sites-available/
a2ensite projeto2     # liga o nosso site
a2dissite 000-default # desliga a pagina padrao do Ubuntu, senao ela aparece no lugar da nossa
systemctl reload apache2 # aplica a configuracao nova

echo "provisionamento concluido"
