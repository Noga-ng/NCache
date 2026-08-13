<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],

        'no_unused_imports' => true,

        'single_quote' => true,

        'trailing_comma_in_multiline' => [
            'elements' => [
                'arrays',
                'arguments',
                'parameters',
            ],
        ],

        // Important for PHP 8.1 compatibility.
        'new_expression_parentheses' => [
            'use_parentheses' => true,
        ],
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