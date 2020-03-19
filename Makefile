UID=$(shell id -u)
SERVICE=car-insurance-data

help:  ## Display command list
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

privilege: ## add user to docker group. After run "exec su -l $USER"
	sudo gpasswd -a ${USER} docker

install: build run uid deps stop ## build project with deps

build: ## build project
	docker-compose build

run: ## run project
	docker-compose up -d

stop: ## stop project
	docker-compose stop

deps: ## install dependency
	docker-compose exec ${SERVICE} sh -c "cd /var/www/ && composer install && chown ${UID}:${UID} vendor/ -R"

uid: ## install dependency
	docker-compose exec ${SERVICE} sh -c 'usermod -u ${UID} www-data'
