<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS User Community <https://basercms.net/community/>
 *
 * @copyright     Copyright (c) baserCMS User Community
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Controller\Admin;

/**
 * Class PluginsController
 * @package BaserCore\Controller\Admin
 */
class PluginsController extends BcAdminAppController {
    /**
     * プラグイン一覧
     */
    public function index() {
        $this->set('title', 'プラグイン一覧');
    }
}
