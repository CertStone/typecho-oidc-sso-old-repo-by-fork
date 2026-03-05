<?php
namespace TypechoPlugin\Oidc;

use Typecho\Common;
use Typecho\Db;
use Typecho\Plugin\Exception;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Utils\Helper;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * OpenID Connect 插件
 *
 * @package Oidc
 * @author uy/sun和CertStone
 * @version 0.3.1
 * @since 1.2.0
 * @link https://github.com/CertStone/typecho-oidc
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法
     *
     * @access public
     * @return string
     * @throws Exception
     */
    public static function activate()
    {
        // 注册 Action 路由（用于 unbind 等管理操作）
        Helper::addAction('oidc', 'Oidc_Action');

        // 注册公开路由
        Helper::addRoute('oidc_login', '/oidc/login', 'Oidc_Action', 'login');
        Helper::addRoute('oidc_callback', '/oidc/callback', 'Oidc_Action', 'callback');
        Helper::addRoute('oidc_login_page', '/oidc/login-page', 'Oidc_Action', 'loginPage');

        // 添加管理面板
        Helper::addPanel(1, 'Oidc/Panel.php', _t('OIDC 绑定'), _t('管理 OIDC 账户绑定'), 'subscriber');

        // 创建 OIDC 绑定表
        self::createBindingTable();

        return _t('插件已激活，请配置 OIDC 参数');
    }

    /**
     * 创建 OIDC 绑定表
     *
     * @access private
     * @throws Exception
     */
    private static function createBindingTable()
    {
        $db = Db::get();
        $prefix = $db->getPrefix();
        $adapter = $db->getAdapterName();

        // 检查表是否已存在
        $tableName = $prefix . 'oidc_bindings';

        // 根据不同的数据库适配器创建表
        if (strpos($adapter, 'Mysql') !== false || strpos($adapter, 'Pdo_Mysql') !== false) {
            // MySQL
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `uid` int(10) unsigned NOT NULL,
                `iss` varchar(255) NOT NULL COMMENT 'OIDC Issuer（身份提供商标识）',
                `sub` varchar(255) NOT NULL COMMENT 'OIDC Subject（用户标识）',
                `created_at` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `iss_sub` (`iss`, `sub`),
                KEY `uid` (`uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        } elseif (strpos($adapter, 'SQLite') !== false || strpos($adapter, 'Pdo_SQLite') !== false) {
            // SQLite
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uid` INTEGER NOT NULL,
                `iss` TEXT NOT NULL,
                `sub` TEXT NOT NULL,
                `created_at` INTEGER NOT NULL,
                UNIQUE(`iss`, `sub`)
            );";
        } elseif (strpos($adapter, 'Pgsql') !== false || strpos($adapter, 'Pdo_Pgsql') !== false) {
            // PostgreSQL
            $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
                id SERIAL PRIMARY KEY,
                uid INTEGER NOT NULL,
                iss VARCHAR(255) NOT NULL,
                sub VARCHAR(255) NOT NULL,
                created_at INTEGER NOT NULL,
                UNIQUE(iss, sub)
            );";
        } else {
            throw new Exception(_t('不支持的数据库类型'));
        }

        try {
            $db->query($sql);
        } catch (\Exception $e) {
            throw new Exception(_t('创建 OIDC 绑定表失败: ') . $e->getMessage());
        }
    }

    /**
     * 禁用插件方法
     *
     * @access public
     * @return string
     */
    public static function deactivate()
    {
        // 移除 Action
        Helper::removeAction('oidc');

        // 移除公开路由
        Helper::removeRoute('oidc_login');
        Helper::removeRoute('oidc_callback');
        Helper::removeRoute('oidc_login_page');

        // 移除管理面板
        Helper::removePanel(1, 'Oidc/Panel.php');

        // 注意：不删除绑定表，以保留用户绑定数据
        // 如需删除，可在此添加删除表的代码

        return _t('插件已禁用');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        $options = Options::alloc();
        $callbackUrl = Common::url('/oidc/callback', $options->index);
        $loginPageUrl = Common::url('/oidc/login-page', $options->index);
        $logoutRedirectUrl = Common::url('/', $options->index);

        // 添加 OIDC 发现文档 URL 配置
        $discoveryUrl = new Form\Element\Text(
            'discoveryUrl',
            null,
            '',
            _t('OIDC 发现文档 URL'),
            _t('例如: https://your-oidc-provider/.well-known/openid_configuration<br/>回调地址: %s<br/>自定义登录页: %s<br/>退出登录后重定向: %s', $callbackUrl, $loginPageUrl, $logoutRedirectUrl)
        );
        $form->addInput($discoveryUrl);


        $oidcSystemName = new Form\Element\Text(
            'oidcSystemName',
            null,
            '单点登录',
            _t('OIDC 系统名称'),
            _t('例如: IAM、CAS')
        );
        $form->addInput($oidcSystemName->addRule('required', _t('请输入 OIDC 系统名称')));

        $clientId = new Form\Element\Text(
            'clientId',
            null,
            '',
            _t('Client ID'),
            _t('OIDC 客户端 ID')
        );
        $form->addInput($clientId->addRule('required', _t('请输入 Client ID')));

        $clientSecret = new Form\Element\Text(
            'clientSecret',
            null,
            '',
            _t('Client Secret'),
            _t('OIDC 客户端密钥')
        );
        $form->addInput($clientSecret->addRule('required', _t('请输入 Client Secret')));

        $scope = new Form\Element\Text(
            'scope',
            null,
            'openid email profile',
            _t('Scope'),
            _t('OIDC 作用域')
        );
        $form->addInput($scope);

        $enableAutoRegister = new Form\Element\Radio(
            'enableAutoRegister',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('自动注册'),
            _t('当未绑定用户时，允许自动创建 Typecho 账户（仅在邮箱已验证时）')
        );
        $form->addInput($enableAutoRegister);

        $enablePkce = new Form\Element\Radio(
            'enablePkce',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('PKCE 支持'),
            _t('是否在授权码流程中启用 PKCE（推荐开启）')
        );
        $form->addInput($enablePkce);

        $nicknameClaim = new Form\Element\Text(
            'nicknameClaim',
            null,
            'name',
            _t('昵称 Claim'),
            _t('用于填充 Typecho 昵称（screenName）的 Claim 名称，可留空')
        );
        $form->addInput($nicknameClaim);

        $homepageClaim = new Form\Element\Text(
            'homepageClaim',
            null,
            'website',
            _t('主页 Claim'),
            _t('用于填充 Typecho 个人主页（url）的 Claim 名称，可留空')
        );
        $form->addInput($homepageClaim);

        $emailClaim = new Form\Element\Text(
            'emailClaim',
            null,
            'email',
            _t('邮箱 Claim'),
            _t('用于填充 Typecho 邮箱（mail）的 Claim 名称，可留空')
        );
        $form->addInput($emailClaim);

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }
}
