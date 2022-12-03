<?php

return [
    'php' => [
        ['(\$core|\$GLOBALS\[(\'|")core(\'|")\]|\$this->core)', '$core', 'dcCore::app()', '2.23', 'https://open-time.net/post/2022/10/21/Adapter-son-code-pour-la-224-n-2'],
        ['(\$_ctx|\$GLOBALS\[(\'|")_ctx(\'|")\])', '$_ctx', 'dcCore::app()->ctx', '2.23', 'https://open-time.net/post/2022/10/22/Adapter-son-code-pour-la-224-n-3'],
        ['(\$_lang|\$GLOBALS\[(\'|")_lang(\'|")\])', '$_lang', 'dcCore::app()->lang', '2.23', 'https://open-time.net/post/2022/10/23/Adapter-son-code-pour-la-224-n-4'],
        ['(\$_menu|\$GLOBALS\[(\'|")_menu(\'|")\])', '$_menu', 'dcCore::app()->menu', '2.23', 'https://open-time.net/post/2022/10/24/Adapter-son-code-pour-la-224-n-5'],
        ['(\$__resources|\$GLOBALS\[(\'|")__resources(\'|")\])', '$__resources', 'dcCore::app()->resources', '2.23', 'https://open-time.net/post/2022/10/26/Adapter-son-code-pour-la-224-n-6'],
        ['(\$__widgets|\$GLOBALS\[(\'|")__widgets(\'|")\])', '$__widgets', 'dcCore::app()->widgets', '2.23', 'https://open-time.net/post/2022/10/31/Adapter-son-code-pour-la-224-n-11'],
        ['(\$_page_number|\$GLOBALS\[(\'|")_page_number(\'|")\])', '$_page_number', 'dcCore::app()->public->getPageNumber()', '2.23', 'https://open-time.net/post/2022/11/01/Adapter-son-code-pour-la-224-n-12'],
        ['(\$_search|\$GLOBALS\[(\'|")_search(\'|")\])', '$_search', 'dcCore::app()->public->search', '2.23', 'https://open-time.net/post/2022/11/02/Adapter-son-code-pour-la-224-n-13'],
        ['(\$_search_count|\$GLOBALS\[(\'|")_search_count(\'|")\])', '$_search_count', 'dcCore::app()->public->search_count', '2.23', 'https://open-time.net/post/2022/11/02/Adapter-son-code-pour-la-224-n-13'],
        ['(\$__theme|\$GLOBALS\[(\'|")__theme(\'|")\])', '$__theme', 'dcCore::app()->public->theme', '2.23', 'https://open-time.net/post/2022/11/03/Adapter-son-code-pour-la-224-n-14'],
        ['(\$__parent_theme|\$GLOBALS\[(\'|")__parent_theme(\'|")\])', '$__parent_theme', 'dcCore::app()->public->parent_theme', '2.23', 'https://open-time.net/post/2022/11/03/Adapter-son-code-pour-la-224-n-14'],
        ['(\$__smilies|\$GLOBALS\[(\'|")__smilies(\'|")\])', '$__smilies', 'dcCore::app()->public->smilies', '2.23', 'https://open-time.net/post/2022/11/04/Adapter-son-code-pour-la-224-n-15'],
        ['(\$__autoload|\$GLOBALS\[(\'|")__autoload(\'|")\])', '$__autoload', 'Clearbricks::lib()->autoload()', '2.23', 'https://open-time.net/post/2022/11/05/Adapter-son-code-pour-la-224-n-16'],
        ['(\$p_url|\$GLOBALS\[(\'|")p_url(\'|")\])', '$p_url', 'dcCore::app()->admin->getPageURL()', '2.23', 'https://open-time.net/post/2022/11/13/Adapter-son-code-pour-la-224-n-24'],
    ],
];
