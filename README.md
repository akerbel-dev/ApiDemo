# User API
A demo API made with Symfony 8.1.

## Symfony Docker

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

Coding-agents ready: ships with a [Dev Container](https://containers.dev/) and a [one-page guide](docs/agents.md)
to run [OpenCode](https://opencode.ai), [Claude Code](https://claude.ai/claude-code), or any AI coding assistant,
against a local or a remote model, with an optional network sandbox.

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up --wait` to set up and start a fresh Symfony project
4. Enter php container `docker exec -it apidemo-php-1 bash`
5. Run composer `composer install`
6. Create certificates for JWT tokens `bin/console lexik:jwt:generate-keypair --skip-if-exists`
7. Open Swagger `https://localhost/api/doc` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
8. If you need some demo users run: `bin/console app:create-demo-users`
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## TESTS

Test are made with PHPUnit and zenstruck/foundry

To run all tests, execute inside php container:
`bin/phpunit`

For unit tests separated
`bin/phpunit --testsuite=Unit`

For functional tests separated
`bin/phpunit --testsuite=Functional`

## PHPStan

For static analisys run inside php container
`php ./vendor/bin/phpstan analyse -c phpstan.dist.neon`

To update PhpStan exceptions, run inside php container
`php ./vendor/bin/phpstan analyse -c phpstan.dist.neon --generate-baseline`

## CS-FIX

To check code style of your changes run inside php container
`php ./vendor/bin/php-cs-fixer check`

To fix conde style of your changes run inside php container
`php ./vendor/bin/php-cs-fixer fix`

## SSL keys

run inside php container
`bin/console lexik:jwt:generate-keypair --skip-if-exists`

## Promote user to admin
run inside php container
`bin/console app:user:promote %email%`

## Create demo users (only for dev environment!)
run inside php container
`bin/console app:create-demo-users`

## License

Symfony Docker is available under the MIT License.
