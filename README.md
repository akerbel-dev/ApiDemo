# User API
A demo API made with Symfony 8.1.

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up --wait` to set up and start a fresh Symfony project
4. Create certificates for JWT tokens `bin/console lexik:jwt:generate-keypair --skip-if-exists`
5. Open Swagger `https://localhost/api/doc` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
6. If you need some demo users run: `bin/console app:create-demo-users`
7. Run `docker compose down --remove-orphans` to stop the Docker containers.

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

## Github actions

1. PHPStan - runs PhpStan check
2. PHP-CS-Fixer - runs code style check

## SSL keys

run inside php container
`bin/console lexik:jwt:generate-keypair --skip-if-exists`

## Promote user to admin
run inside php container
`bin/console app:user:promote %email%`

## Create demo users (only for dev environment!)
run inside php container
`bin/console app:create-demo-users`

## Improvement suggestions:

1. Make production ready. 
    1. Rid of docker/mysql-init/01-grant.sql . It adds rights to default DB user to create new database for tests. Find safer way to create test database.
    2. Create real database credentials and store them in .env.%env%.local or in k8s.
    3. Double check if there is something else unsafe.
2. Make a github action for running tests.
3. Organize a proper GitFlow:
    1. Make "main" branch forbidden for changes. Changes can come only via merge requests.
    2. Make mandatory for merge request:
        1. Approve from maintainers
        2. Sucessful run of all GitHub Actions.
4. Make "cursor" pagination for /user/list and /user/search for case of big data.

-------
-------
-------

## [Symfony Docker](https://github.com/dunglas/symfony-docker)

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

Coding-agents ready: ships with a [Dev Container](https://containers.dev/) and a [one-page guide](docs/agents.md)
to run [OpenCode](https://opencode.ai), [Claude Code](https://claude.ai/claude-code), or any AI coding assistant,
against a local or a remote model, with an optional network sandbox.

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## License

Symfony Docker is available under the MIT License.
