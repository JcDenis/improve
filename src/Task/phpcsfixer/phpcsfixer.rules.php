<?php
/**
 * @brief improve, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
/** @phpstan-ignore-next-line */
$finder = PhpCsFixer\Finder::create()
    ->exclude('node_modules')
    ->exclude('vendor')
    ->in(__DIR__);

/** @phpstan-ignore-next-line */
$config = new PhpCsFixer\Config();

/* @phpstan-ignore-next-line */
return $config
    ->setRules([
        '@PSR12'                 => true,
        '@PHP81Migration'        => true,
        'array_indentation'      => true,
        'binary_operator_spaces' => [
            'default'   => 'align_single_space_minimal',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
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
        'phpdoc_indent'                         => true,
        'phpdoc_to_comment'                     => true,
        'phpdoc_trim'                           => true,
        'single_quote'                          => true,
        'trim_array_spaces'                     => true,

        //'no_multiline_whitespace_around_double_arrow' => true,
        //'use_arrow_functions'                         => true,
    ])
    ->setFinder($finder);
