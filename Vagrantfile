# Projeto 2 - Computacao em Nuvem
# I1: criar a VM e clonar o projeto automaticamente.

REPO = "https://github.com/Itonsow/projeto2-cloud.git"
BRANCH = ENV.fetch("P2_BRANCH", "main")

Vagrant.configure("2") do |config|
	config.vm.box = "ubuntu/jammy64" # Ubuntu 22.04

	config.vm.define "projeto2" do |projeto2|
		projeto2.vm.hostname = "projeto2"
		projeto2.vm.network "forwarded_port", guest: 80, host: 8080

		projeto2.vm.synced_folder ".", "/vagrant", disabled: true

		projeto2.vm.provider "virtualbox" do |vb|
			vb.gui = false
			vb.memory = "4096"
			vb.cpus = 2
			vb.name = "projeto2-cloud"
		end

		projeto2.vm.provision "shell", args: [REPO, BRANCH], inline: <<-SHELL
			set -eu

			REPO="$1"
			BRANCH="$2"
			DEST="/opt/projeto2"

			export DEBIAN_FRONTEND=noninteractive
			sudo apt-get update -y
			sudo apt-get install -y git

			# no primeiro vagrant up, clona o repositorio dentro da VM
			# no prox, atualiza o codigo e roda o setup novamente
			if [ -d "$DEST/.git" ]; then
				sudo git -C "$DEST" fetch origin
				sudo git -C "$DEST" checkout "$BRANCH"
				sudo git -C "$DEST" pull --ff-only origin "$BRANCH"
			else
				sudo git clone --branch "$BRANCH" "$REPO" "$DEST"
			fi

			sudo bash "$DEST/provision/setup.sh"
		SHELL
	end
end
