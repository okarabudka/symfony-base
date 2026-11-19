<?php

/**
 * Applies the template's changes to files that belong to the Symfony skeleton and its Flex recipes.
 * Each change is anchored on recipe text; a missing anchor stops generation, so a recipe change
 * surfaces in CI instead of producing a half-configured project. Runs in the generated project root.
 */

declare(strict_types=1);

function replaceLine(string $file, string $pattern, string $line): void
{
    $content = (string) file_get_contents($file);
    $content = preg_replace($pattern, $line, $content, 1, $count);
    if (1 !== $count) {
        fwrite(STDERR, "patch-skeleton: {$pattern} not found in {$file}\n");
        exit(1);
    }
    file_put_contents($file, $content);
}

// Development defaults for the Docker setup: SQLite, Mailpit and the variables docker compose reads.
replaceLine('.env', '/^DATABASE_URL=.*$/m', 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"');
replaceLine('.env', '/^MAILER_DSN=.*$/m', 'MAILER_DSN=smtp://mail:1025');
file_put_contents('.env', <<<'ENV'

###> symfony-base ###
APP_NAME=symfony-base
TZ=UTC
HTTP_PORT=8080
HTTPS_PORT=8443
MAILPIT_PORT=8025
# MariaDB (docker compose --profile full):
# DATABASE_URL="mysql://app:secret@db:3306/app?serverVersion=11.4.0-MariaDB&charset=utf8mb4"
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root
###< symfony-base ###

ENV, FILE_APPEND);

// The recipe writes a random dev secret into a committed file; `make env` puts one into .env.local instead.
replaceLine('.env.dev', '/^APP_SECRET=.*$/m', 'APP_SECRET=');

// Composer scripts behind make test/coverage/mutate/lint/fix.
$composer = json_decode((string) file_get_contents('composer.json'), true, flags: JSON_THROW_ON_ERROR);
$composer['name'] = 'app/symfony-base';
$composer['description'] = 'A Symfony application.';
$composer['scripts']['test'] = ['@php bin/phpunit'];
$composer['scripts']['coverage'] = [
    '@php -d pcov.enabled=1 bin/phpunit --coverage-clover var/coverage.xml --coverage-text --only-summary-for-coverage-text',
    // Single quotes keep the shell away from the PHP variables.
    "@php -r '\$m = simplexml_load_file(\"var/coverage.xml\")->project->metrics; \$p = (int) \$m[\"statements\"] > 0 ? 100 * (int) \$m[\"coveredstatements\"] / (int) \$m[\"statements\"] : 100; printf(\"Line coverage %.1f%% (minimum 80%%)\\n\", \$p); exit(\$p < 80 ? 1 : 0);'",
];
$composer['scripts']['mutate'] = ['@php -d pcov.enabled=1 vendor/bin/infection --threads=max --no-progress --show-mutations=0'];
$composer['scripts']['lint'] = [
    'php-cs-fixer check --diff',
    'phpstan analyse --no-progress --memory-limit=1G',
    '@php bin/console lint:container',
    '@php bin/console lint:yaml config --parse-tags',
    '@php bin/console lint:twig templates',
    '@php bin/console doctrine:schema:validate --skip-sync',
];
$composer['scripts']['fix'] = ['php-cs-fixer fix'];
$composer['config']['process-timeout'] = 0;
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
