<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP71Migration' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'ordered_imports' => [
            'imports_order' => null,
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_order' => true,
        'phpdoc_align' => true,
        'phpdoc_no_access' => true,
        'phpdoc_separation' => true,
        'increment_style' => ['style' => 'pre'],
        'single_quote' => true,
        'trim_array_spaces' => true,
        'yoda_style' => false,
        'php_unit_method_casing' => false,
        // risky -->
        'strict_param' => true,
    ])
    ->setFinder($finder)
;
