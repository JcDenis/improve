dev
- [ ] add global config for file size limit
- [ ] add module to check directory structure
- [ ] add module to create full README file
- [ ] add module to minify .js
- [ ] write documentation of php class

1.3 - 2023.07.16
- require dotclear 2.27
- require PHP 8.1+
- use latest dotclear namespaces
- add deprecated for Dotclear 2.26 and 2.27

1.2 - 2023.04.29
- require dotclear 2.26
- require PHP 8.1+
- move / rename / split class and methods
- add plugin Uninstaller features
- fix phpstan on modules that requires other modules

1.1.3 - 2023.04.06
- disable phpstan if option nodetails is active
- add option to show distributed modules
- add option to sort modules by name or id
- use dcModulesDefine rather than properties array
- use latest dotclear Helper
- use dotclear autoloader rather than clearbricks one

1.1.2 - 2023.03.20
- add option to phpstan to clear cache
- add option to phpstan not ot use ignored errors
- fix init check

1.1.1 - 2023.03.18
- require php 8.0
- use new Zip helper
- use Html form for tools configuration
- check php version
- update to phpstan 1.10.7
- update to php-cs-fixer 3.15.1

1.1 - 2023.03.05 (pre)
- require Dotclear 2.26-dev
- use PHP namespace
- use Html Form
- use json rather than serialize functions
- use new open/close module
- add cssheader tool (experimental)
- update to phpstan 1.10.3
- update to php-cs-fixer 3.14.4

1.0.2 - 2023.01.16
- cleanup namespace usage
- fix namespace on disabled plugin
- update to phpstan 1.9.11
- update to php-cs-fixer 3.13.2

1.0.1 - 2023.01.03
- fix lost of settings between themes and plugins (thx brol)
- fix false positive on global_filters deprecated
- remove unnecessary settings namespace declaration

1.0 - 2022.12.21
- update phpstan and php-cs-fixer to lastest releases
- add module to compile .po file into .lang.php file
- add some deprecated (splitted by dc version)
- add ability to improve disabled modules
- use svg icon
- use constant for tables names
- use anonymous functions
- use abstract plugin id
- change plugin files structure and namespace
- fix install
- fix translations

0.10 - 2022.12.03
- allow to improve disabled modules
- split deprecated list by dotclear versions
- fix translation
- use svg icon
- various code improvments

0.9 - 2022.11.20
- update to Dotclear 2.24
- update to php-cs-fixer.phar 3.13.0
- update to phpstan.phar 1.9.2

0.8.2 - 2021.11.18
- update php-cs-fixer.phar to 3.3.2
- update phpstan.phar to 1.2.0

0.8.1 - 2021.11.15
- fix unknow preferences on new install

0.8 - 2021.11.13
- update structure to php namespace
- update php cs fixer rules to dc2.21 / php7.4
- update phpstan to 1.1.2

0.7.1 - 2021.11.08
- Fix php < 8 _ thanks @Gvx- _ closes #5

0.7 - 2021.11.08
- add phpstan module
- fix some errors from phpstan analyze

0.6 - 2021.11.06
- add header for modules configuration
- add options to hide details of executed actions
- show configuration file in phpcsfixer module
- use table in ui

0.5 - 2021.11.05
- add settings to disable (hide) modules
- fix dcstore xml rendering (thanks Franck Paul)

0.4 - 2021.11.02
- add module to use php-cs-fixer

0.3 - 2021.10.29
- use of xmlTag to generate dcstore.xml contents
- complete readme file
- fix module update (thanks Gvx)

0.2 - 2021.09.25
- add module to check deprecated Dotclear function

0.1.2
- add logs / report systeme
- add DA badge to module _gitshields_
- change function args, less is better
- change interface, lighter names
- add function to summarize by action what was done

0.1.1
- fix php < 8.0

0.1 - 2021.09.11
- First release

0.0.1 - 2021.09.11
- First pre-release