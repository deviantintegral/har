<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP7x1Migration' => true,
        '@PHPUnit7x5Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'protected_to_private' => false,
        'native_constant_invocation' => ['strict' => false],
        'nullable_type_declaration_for_default_null_value' => true,
        'modernize_strpos' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->append([__FILE__])
            ->notPath('#/Fixtures/#')
    )
    ->setCacheFile('.php-cs-fixer.cache')
;
