<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@auto' => true,
        '@auto:risky' => true,
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
            ->exclude([
                'vendor',
                'var',
                'Stubs',
                'cache',
                'DOCS',
            ])
    );