<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief       improve backend class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!App::blog()->isDefined()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs): void {
                $favs->register(
                    My::id(),
                    [
                        'title'      => My::name(),
                        'url'        => My::manageUrl(),
                        'small-icon' => My::icons(),
                        'large-icon' => My::icons(),
                        //'permissions' => null,
                    ]
                );
            },

            // Add taks to improve
            'improveTaskAdd' => function (Tasks $tasks): void {
                $tasks
                    ->add(new Task\CssHeader())
                    ->add(new Task\DcDeprecated())
                    ->add(new Task\DcStore())
                    ->add(new Task\EndOfFile())
                    ->add(new Task\GitShields())
                    ->add(new Task\LicenseFile())
                    ->add(new Task\NewLine())
                    ->add(new Task\PhpCsFixer())
                    ->add(new Task\PhpHeader())
                    ->add(new Task\PhpStan())
                    ->add(new Task\Po2Php())
                    ->add(new Task\Tab())
                    ->add(new Task\Zip())
                ;
            },
        ]);

        return true;
    }
}
