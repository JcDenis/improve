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
return [
    'php' => [
        ['Clearbricks::lib\(', 'Clearbricks::lib()', __('namespaces'), '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['Clearbricks::add\(', 'Clearbricks::add()', __('namespaces'), '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['toExtStatic\(', 'dcRecord::toExtStatic()', 'Dotclear\Database\MetaRecord::toStatic()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        //['(themes|plugins)->setModules\(', 'adminModulesList::setModules()', 'ModulesList::setDefine()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        //['(themes|plugins)->getModules\(', 'adminModulesList::getModules()', 'ModulesList::getDefines()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['->sanitizeModule\(', 'adminModulesList::sanitizeModule()', 'ModulesList::fillSanitizeModule()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['->fillSanitizeModule\(', 'adminModulesList::fillSanitizeModule()', 'ModulesList::fillSanitizeModule()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['->isDistributedModule\(', 'adminModulesList::isDistributedModule()', 'ModulesList::getDefine($id)->distributed', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->getModules\(', 'dcModules::getModules()', 'dcModules::getDefines()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->getAnyModules\(', 'dcModules::getAnyModules()', 'dcModules::getDefines()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->getDisabledModules\(', 'dcModules::getDisabledModules()', "dcModules::getDefines(['state' => '!' . dcModuleDefine::STATE_ENABLED])", '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->getHardDisabledModules\(', 'dcModules::getHardDisabledModules()', "dcModules::getDefines(['state' => dcModuleDefine::STATE_HARD_DISABLED])", '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->getSoftDisabledModules\(', 'dcModules::getSoftDisabledModules()', "dcModules::getDefines(['state' => dcModuleDefine::STATE_SOFT_DISABLED])", '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->moduleRoot\(', 'dcModules::moduleRoot()', "dcModules::getDefine(\$id, ['state' => dcModuleDefine::STATE_ENABLED])->root", '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['(themes|plugins)->loadNsFiles\(', 'dcModules::loadNsFiles()', __('nothing'), '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['store->getModules\(', 'dcStoreParser::getModules()', 'dcStoreParser::getDefines()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['store->get\(', 'dcStoreParser::get()', 'dcStoreParser::getDefine()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['store->search\(', 'dcStoreParser::search()', 'dcStoreParser::searchDefines()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
        ['dcUtils::removeDiacritics\(', 'dcUtils::removeDiacritics()', 'Text::removeDiacritics()', '2.27', 'https://dotclear.watch/Billet/Release-2.26'],
    ],
];
