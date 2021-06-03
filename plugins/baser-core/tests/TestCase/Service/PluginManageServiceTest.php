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

namespace BaserCore\Test\TestCase\Service;

use BaserCore\Service\PluginManageService;
use BaserCore\TestSuite\BcTestCase;
use Cake\Filesystem\Folder;
use Cake\Core\App;

/**
 * Class PluginManageServiceTest
 * @package BaserCore\Test\TestCase\Service
 * @property PluginManageService $Plugins
 */
class PluginManageServiceTest extends BcTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BaserCore.Plugins',
    ];

    /**
     * @var PluginManageService|null
     */
    public $Plugins = null;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->PluginManage = new PluginManageService();
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->PluginManage);
        parent::tearDown();
    }

    /**
     * Test getIndex
     * @dataProvider indexDataprovider
     */
    public function testGetIndex($sortMode, $expected)
    {
        $plugins = $this->PluginManage->getIndex($sortMode);
        $this->assertEquals(count($plugins), $expected);
    }
    public function indexDataprovider()
    {
        return [
            // 普通の場合
            ["0", "4"],
            // ソートモードの場合
            ["1", "3"],
        ];
    }

    /**
     * testGetPluginConfig
     */
    public function testGetPluginConfig()
    {
        $plugin = $this->PluginManage->getPluginConfig('BaserCore');
        $this->assertEquals('BaserCore', $plugin->name);
    }

    /**
     * testIsInstallable
     */
    public function testIsInstallable()
    {
        $this->expectExceptionMessage('既にインストール済のプラグインです。');
        $this->PluginManage->isInstallable('BcBlog');
        $this->expectExceptionMessage('インストールしようとしているプラグインのフォルダが存在しません。');
        $this->PluginManage->isInstallable('BcTest');
        $pluginPath = App::path('plugins')[0] . DS . 'BcTest';
        $folder = new Folder($pluginPath);
        $folder->create($pluginPath, 0777);
        $this->assertEquals(true, $this->Plugins->isInstallable('BcTest'));
        $folder->delete($pluginPath);
    }

}