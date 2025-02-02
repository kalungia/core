<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\UserStatusLogGateway;

require getcwd().'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$remoteCLIKey = $container->get(SettingGateway::class)->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() || ($remoteCLIKey != '' && $remoteCLIKey == $remoteCLIKeyInput))) {
    echo __('This script cannot be run from a browser, only via CLI.');
} else {

    $sql = 'UPDATE gibbonPerson SET failCount=0 WHERE gibbonPersonID=0000000001 OR gibbonPersonID=0000002931 OR gibbonPersonID=0000002932';
    $result = $pdo->statement($sql);

    // Output the result to terminal
    echo $pdo->getQuerySuccess();
}
