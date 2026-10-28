#!/bin/bash
set -euo pipefail # para se houver erro, variavel indefinida ou falha em um pipe
#-e: se um comando falhar, para.
#-u: se usar uma variável que não foi definida, para.
#pipefail: detecta tbm a falha dos comandos anteriores

# o Vagrantfile ja rodou apt-get update antes de chamar este script
apt-get install -y apache2 php libapache2-mod-php php-mysql mysql-server

mkdir -p /var/lib/projeto2/envs # pasta onde vao ficar os ambientes
chown www-data:www-data /var/lib/projeto2/envs

# cria um atalho para chamar o envctl
ln -sfn /opt/projeto2/bin/envctl /usr/local/bin/envctl
if [ -f /opt/projeto2/bin/envctl ]; then
	chmod +x /opt/projeto2/bin/envctl
	# +x permissao de execucao
fi

# confere a regra, copia e permite apenas leitura para root e seu grupo.
visudo -cf /opt/projeto2/provision/sudoers/projeto2 #confere/indica o arquivo
cp /opt/projeto2/provision/sudoers/projeto2 /etc/sudoers.d/projeto2 #copia a regra p o sudo poder usar
chmod 0440 /etc/sudoers.d/projeto2 #protecao apenas dono e grupo podem ler, ninguem pode escrever ou executar

# instala o servico que prepara os cgroups a cada boot
cp /opt/projeto2/provision/systemd/projeto2-init.service /etc/systemd/system/
systemctl daemon-reload # le o arquivo do servico
systemctl enable projeto2-init.service # habilita nos proximos boots
systemctl restart projeto2-init.service # executa agora

if [ -f /opt/projeto2/provision/db.sh ]; then #verifica se o arquivo existe
	bash /opt/projeto2/provision/db.sh
fi

cp /opt/projeto2/provision/apache/projeto2.conf /etc/apache2/sites-available/
a2ensite projeto2     # liga o nosso site
a2dissite 000-default # desliga a pagina padrao do Ubuntu, senao ela aparece no lugar da nossa
systemctl reload apache2 # aplica a configuracao nova

echo "provisionamento concluido"
