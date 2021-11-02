<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('node_modules')
    ->exclude('vendor')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12'                 => true,
        'array_indentation'      => true,
        'array_syntax'           => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default'   => 'align_single_space_minimal',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ]
        ],
        'blank_line_before_statement'           => true,
        'braces'                                => ['allow_single_line_closure' => true],
        'cast_spaces'                           => true,
        'combine_consecutive_unsets'            => true,
        'concat_space'                          => ['spacing' => 'one'],
        'linebreak_after_opening_tag'           => true,
        'no_blank_lines_after_phpdoc'           => true,
        'no_break_comment'                      => false,
        'no_extra_blank_lines'                  => true,
        'no_spaces_around_offset'               => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports'                     => true,
        'no_useless_else'                       => true,
        'no_useless_return'                     => true,
        'no_whitespace_before_comma_in_array'   => true,
        'normalize_index_brace'                 => true,
        'phpdoc_indent'                         => true,
        'phpdoc_to_comment'                     => true,
        'phpdoc_trim'                           => true,
        'return_type_declaration'               => ['space_before' => 'none'],
        'single_quote'                          => true,
        'ternary_to_null_coalescing'            => true,
        'trailing_comma_in_multiline'           => false,
        'trim_array_spaces'                     => true,
    ])
    ->setFinder($finder);
