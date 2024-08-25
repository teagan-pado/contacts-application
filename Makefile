# Variables
DOCKER_COMPOSE_BACKEND = docker-compose -f ./backend/docker-compose.yml
APP_CONTAINER = laravel_app

# Rebuild everything from scratch for the backend
backend-rebuild: backend-clean backend-build backend-install

# Stop and remove all containers, volumes, and images related to the backend app
backend-clean-all: backend-clean
	$(DOCKER_COMPOSE_BACKEND) down --rmi all --volumes --remove-orphans

# Stop and remove all containers and volumes related to the backend app
backend-clean:
	$(DOCKER_COMPOSE_BACKEND) down -v --remove-orphans

# Build everything from scratch without using cache for the backend
backend-build:
	$(DOCKER_COMPOSE_BACKEND) build --no-cache

# Install the backend app (start services, install dependencies, run migrations)
backend-install: backend-start
	sleep 5  # Wait for services to initialize
	$(DOCKER_COMPOSE_BACKEND) exec $(APP_CONTAINER) composer install
	$(DOCKER_COMPOSE_BACKEND) exec $(APP_CONTAINER) php artisan migrate

# Start the backend app (Docker containers)
backend-start:
	$(DOCKER_COMPOSE_BACKEND) up -d

# Stop the backend app (Docker containers)
backend-stop:
	$(DOCKER_COMPOSE_BACKEND) down

# Execute a command as normal user inside the backend app container
backend-exec:
	$(DOCKER_COMPOSE_BACKEND) exec $(APP_CONTAINER) $(cmd)

# Execute a command as root inside the backend app container
backend-exec-root:
	$(DOCKER_COMPOSE_BACKEND) exec --user root $(APP_CONTAINER) $(cmd)

# SSH into the backend app container
backend-ssh:
	$(DOCKER_COMPOSE_BACKEND) exec -it $(APP_CONTAINER) /bin/sh

.PHONY: backend-rebuild backend-clean-all backend-clean backend-build backend-install backend-start backend-stop backend-exec backend-exec-root backend-ssh
