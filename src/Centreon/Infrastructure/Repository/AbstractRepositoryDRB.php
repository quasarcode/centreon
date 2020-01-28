<?php
/*
 * Copyright 2005 - 2019 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */
declare(strict_types=1);

namespace Centreon\Infrastructure\Repository;

use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\Security\AccessGroup;
use Centreon\Infrastructure\DatabaseConnection;

class AbstractRepositoryDRB
{
    /**
     * @var DatabaseConnection
     */
    protected $db;

    /**
     * @var AccessGroup[] List of access group used to filter the requests
     */
    protected $accessGroups = [];

    /**
     * Replace all instances of :dbstg and :db by the real db names.
     * The table names of the database are defined in the services.yaml
     * configuration file.
     *
     * @param string $request Request to translate
     * @return string Request translated
     */
    protected function translateDbName(string $request): string
    {
        return str_replace(
            array(':dbstg', ':db'),
            array($this->db->getStorageDbName(), $this->db->getCentreonDbName()),
            $request
        );
    }

    /**
     * Formats the access group ids in string. (values are separated by coma)
     *
     * @param AccessGroup[] $accessGroups
     * @return string
     */
    protected function accessGroupIdToString(array $accessGroups): string
    {
        $ids = [];
        foreach ($accessGroups as $accessGroup) {
            $ids[] = $accessGroup->getId();
        }
        return implode(',', $ids);
    }

    /**
     * Check if a contact is admin
     *
     * @return boolean admin or not
     */
    protected function isAdmin(): bool
    {
        return ($this->contact !== null)
            ? $this->contact->isAdmin()
            : false;
    }

    /**
     * @return bool Return FALSE if the contact is an admin or has at least one access group.
     */
    protected function hasNotEnoughRightsToContinue(): bool
    {
        return ($this->contact !== null)
            ? !($this->contact->isAdmin() || count($this->accessGroups) > 0)
            : count($this->accessGroups) == 0;
    }
}
