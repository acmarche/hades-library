<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret'         => "%env(APP_SECRET)%",
        'mailer'         => [
            'dsn' => '%env(MAILER_DSN)%',
        ],
    ]);
};