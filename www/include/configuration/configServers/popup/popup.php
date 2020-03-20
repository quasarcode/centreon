<?php

/*
 * Copyright 2016-2020 Centreon (http://www.centreon.com/)
 *
 * Centreon is a full-fledged industry-strength solution that meets
 * the needs in IT infrastructure and application monitoring for
 * service performance.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,*
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once realpath(__DIR__ . "/../../../../../config/centreon.config.php");
require_once _CENTREON_PATH_ . 'www/class/centreonDB.class.php';
require_once _CENTREON_PATH_ . 'bootstrap.php';
require_once _CENTREON_PATH_ . "www/include/common/common-Func.php";
require_once _CENTREON_PATH_ . "/www/class/centreonRestHttp.class.php";

$pearDB = $dependencyInjector['configuration_db'];

$tpl = new Smarty();
$tpl = initSmartyTpl(null, $tpl);

// get remote server ip
$query = 'SELECT ip FROM remote_servers';
$dbResult = $pearDB->query($query);
$remotesServerIPs = $dbResult->fetchAll(PDO::FETCH_COLUMN);
$dbResult->closeCursor();

//get poller informations
$query = "
SELECT ns.`id`, ns.`name`, ns.`gorgone_port`, ns.`ns_ip_address`, ns.`localhost`,ns.remote_id, 
cn.`command_file`, GROUP_CONCAT( pr.`remote_server_id` ) AS list_remote_server_id , 
CASE
    WHEN (ns.localhost = '1') THEN 'central'
    WHEN (rs.id > '0') THEN 'remote'
    ELSE 'poller' 
END  AS poller_type 
FROM nagios_server AS ns 
LEFT JOIN remote_servers AS rs ON (rs.ip = ns.ns_ip_address) 
LEFT JOIN cfg_nagios AS cn ON (cn.`nagios_id` = ns.`id` ) 
LEFT JOIN rs_poller_relation AS pr ON (pr.`poller_server_id` = ns.`id` ) 
WHERE ns.ns_activate = '1' 
AND ns.`id` =" . (int)$_GET['id'];

$dbResult = $pearDB->query($query);
$server = $dbResult->fetch();

//get gorgone api informations
$gorgoneApi = [];
$dbResult = $pearDB->query('SELECT * from options WHERE `key` LIKE "gorgone%"');
while ($row = $dbResult->fetch()) {
    $gorgoneApi[$row['key']] = $row['value'];
}

$tpl->assign('serverIp', $server['ns_ip_address']);
if (empty($server['remote_id']) && empty($server['list_remote_server_id'])) {
    //parent is the central
    $query = "SELECT `id` FROM nagios_server WHERE ns_activate = '1' AND localhost = '1'";
    $dbResult = $pearDB->query($query);
    $parents = $dbResult->fetchAll(\PDO::FETCH_COLUMN);
} else {
    $dbResult = $pearDB->query($query);
    $parents = [$server['remote_id']];
    if (!empty($server['list_remote_server_id'])) {
        $remote = explode(',', $server['list_remote_server_id']);
        $parents = array_merge($parents, $remote);
    }
    $query = 'SELECT `id` FROM nagios_server WHERE `ns_activate` = "1" AND `id` IN (' . implode(',', $parents) . ')';
    $dbResult = $pearDB->query($query);
    $parents = $dbResult->fetchAll(\PDO::FETCH_COLUMN);
}

$kernel = App\Kernel::createForWeb();
/**
 * @var $gorgoneService \Centreon\Domain\Gorgone\Interfaces\GorgoneServiceInterface
 */
$gorgoneService = $kernel->getContainer()->get(\Centreon\Domain\Gorgone\Interfaces\GorgoneServiceInterface::class);
$thumbprints = '';
$dataError = '';
$gorgoneError = false;
$timeout = 0;

try {
    foreach ($parents as $serverId) {
        $lastActionLog = null;
        $thumbprintCommand = new \Centreon\Domain\Gorgone\Command\Internal\ThumbprintCommand($serverId);
        $gorgoneResponse = $gorgoneService->send($thumbprintCommand);
        // check if we have log for 30 s every 2s
        do {
            $lastActionLog = $gorgoneResponse->getLastActionLog();
            sleep(2);
            $timeout += 2;
        } while (
            ($lastActionLog == null || $lastActionLog->getCode() === \Centreon\Domain\Gorgone\Response::STATUS_BEGIN) &&
            $timeout <= 30
        );

        if ($timeout > 30) {
            // add 10 s for the next server
            $timeout -= 10;
            $gorgoneError = true;
            $dataError .= '
    - error : TimeOut error for poller ' . $serverId . ' We can\'t get log';
            continue;
        }

        $thummprintReponse = json_decode($lastActionLog->getData(), true);
        if ($lastActionLog->getCode() === \Centreon\Domain\Gorgone\Response::STATUS_OK) {
            $thumbprints .= '
      - key: ' . $thummprintReponse['data']['thumbprint'];
        } else {
            $gorgoneError = true;
            $dataError .= '
      - error : Poller ' . $serverId . ' : ' . $thummprintReponse['message'];
        }
    }
} catch (\Exception $ex) {
    $gorgoneError = true;
    $dataError = $ex->getMessage();
}

if (!empty($dataError)) {
    $config = $dataError;
} elseif (in_array($server['ns_ip_address'], $remotesServerIPs)) {
    //config for remote
    $config = file_get_contents('./remote.yaml');
    $config = str_replace(
        [
            '__SERVERNAME__',
            '__SERVERID__',
            '__GORGONEPORT__',
            '__THUMBPRINT__',
            '__COMMAND__',
        ],
        [
            $server['name'],
            $server['id'],
            $server['gorgone_port'],
            $thumbprints,
            $server['command_file'],
        ],
        $config
    );
} else {
    //config for poller
    $config = file_get_contents('./poller.yaml');
    $config = str_replace(
        [
            '__SERVERNAME__',
            '__SERVERID__',
            '__GORGONEPORT__',
            '__THUMBPRINT__',
            '__COMMAND__',
        ],
        [
            $server['name'],
            $server['id'],
            $server['gorgone_port'],
            $thumbprints,
            $server['command_file'],
        ],
        $config
    );
}

$tpl->assign('args', $config);
$tpl->assign('gorgoneError', $gorgoneError);
$tpl->display("popup.ihtml");