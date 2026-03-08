<?php
namespace TypechoPlugin\Oidc;

use Typecho\Common;
use Typecho\Db;
use Typecho\Plugin as TypechoPlugin;
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
 * @author CertStone和uy/sun
 * @version 0.4.0
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
        // 后台入口统一拦截（原生登录注册页 + 账户中心接管）
        TypechoPlugin::factory('admin/common.php')->begin = array(__CLASS__, 'onAdminCommonBegin');
        // 兼容部分版本/别名写法
        TypechoPlugin::factory('admin/common.php')->call_begin = array(__CLASS__, 'onAdminCommonBegin');

        // 账户中心接管个人资料页
        TypechoPlugin::factory('admin/profile.php')->bottom = array(__CLASS__, 'renderProfileTakeoverHint');

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

        $enablePlugin = new Form\Element\Radio(
            'enablePlugin',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '1',
            _t('插件功能开关'),
            _t('禁用后将停止 OIDC 登录与回调，但保留配置数据')
        );
        $form->addInput($enablePlugin);


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

        $autoRegisterGroup = new Form\Element\Select(
            'autoRegisterGroup',
            array(
                'subscriber' => _t('subscriber（订阅者）'),
                'contributor' => _t('contributor（贡献者）'),
                'editor' => _t('editor（编辑）')
            ),
            'subscriber',
            _t('OIDC 自动注册用户组'),
            _t('当启用自动注册时，新建用户将使用此用户组（默认不提供 administrator，建议通过后台人工提升）')
        );
        $form->addInput($autoRegisterGroup);

        $disableNativeAuthPages = new Form\Element\Radio(
            'disableNativeAuthPages',
            array('0' => _t('否'), '1' => _t('是')),
            '0',
            _t('是否禁用 Typecho 原生登录和注册页'),
            _t('启用后访问原生 login.php / register.php 会重定向到 /oidc/login-page（兼容自定义后台目录）。为避免首次登录死锁，建议同时开启自动注册')
        );
        $form->addInput($disableNativeAuthPages);

        $keepNativePasswordLogin = new Form\Element\Radio(
            'keepNativePasswordLogin',
            array('1' => _t('保留'), '0' => _t('不保留')),
            '1',
            _t('是否保留 Typecho 原生账号登录功能'),
            _t('“保留”：在 /oidc/login-page 显示本地账号登录表单；“不保留”：访问 /oidc/login-page 时直接跳转到单点登录')
        );
        $form->addInput($keepNativePasswordLogin);

        $accountCenterUrl = new Form\Element\Text(
            'accountCenterUrl',
            null,
            '',
            _t('账户中心 URL'),
            _t('例如：https://idp.example.com/account')
        );
        $form->addInput($accountCenterUrl);

        $enableAccountCenterTakeover = new Form\Element\Radio(
            'enableAccountCenterTakeover',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('使用独立账户中心接管用户个人资料设置'),
            _t('启用后将隐藏 Typecho 原生“个人资料/密码修改”并显示“前往账户中心设置”。<br>请谨慎开启，且必须满足：<br>1) 禁用 Typecho 原生登录和注册页=是<br>2) 保留 Typecho 原生账号登录功能=不保留<br>3) 允许用户解绑 OIDC 账户=否<br>4) 已填写账户中心 URL')
        );
        $enableAccountCenterTakeover->addRule(
            array(__CLASS__, 'validateAccountCenterTakeoverPrerequisite'),
            _t('启用接管前需先开启“禁用 Typecho 原生登录和注册页”'),
            'disableNativeAuthPages',
            '1'
        );
        $enableAccountCenterTakeover->addRule(
            array(__CLASS__, 'validateAccountCenterTakeoverPrerequisite'),
            _t('启用接管前，“是否保留 Typecho 原生账号登录功能”必须为“不保留”'),
            'keepNativePasswordLogin',
            '0'
        );
        $enableAccountCenterTakeover->addRule(
            array(__CLASS__, 'validateAccountCenterTakeoverPrerequisite'),
            _t('启用接管前，“是否允许用户解绑 OIDC 账户”必须为“否”'),
            'allowUserUnbind',
            '0'
        );
        $enableAccountCenterTakeover->addRule(
            array(__CLASS__, 'validateAccountCenterTakeoverUrl'),
            _t('启用接管前，必须填写合法的“账户中心 URL”')
        );
        $form->addInput($enableAccountCenterTakeover);

        $allowUserUnbind = new Form\Element\Radio(
            'allowUserUnbind',
            array('0' => _t('否'), '1' => _t('是')),
            '0',
            _t('是否允许用户解绑 OIDC 账户'),
            _t('默认不允许。关闭后，用户在绑定页面将无法执行解绑操作')
        );
        $form->addInput($allowUserUnbind);

        $enablePkce = new Form\Element\Radio(
            'enablePkce',
            array('0' => _t('关闭'), '1' => _t('开启')),
            '0',
            _t('PKCE 支持'),
            _t('是否在授权码流程中启用 PKCE')
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

        $uiCdnBase = new Form\Element\Text(
            'uiCdnBase',
            null,
            'https://s4.zstatic.net',
            _t('前端 UI CDN Base URL'),
            _t('用于加载 daisyUI 与 Tailwind Browser 资源。默认会拼接：/ajax/libs/daisyui/5.1.25 与 /npm/@tailwindcss/browser@4')
        );
        $form->addInput($uiCdnBase);

        $loginLogoUrl = new Form\Element\Text(
            'loginLogoUrl',
            null,
            '',
            _t('登录页 Logo URL'),
            _t('可选，留空则不显示 Logo。建议使用透明背景 PNG/SVG')
        );
        $form->addInput($loginLogoUrl);

        $loginBackgroundUrl = new Form\Element\Text(
            'loginBackgroundUrl',
            null,
            '',
            _t('登录页背景图片 URL'),
            _t('可选，留空则使用默认背景色')
        );
        $form->addInput($loginBackgroundUrl);

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

    /**
     * 后台公共入口拦截
     */
    public static function onAdminCommonBegin()
    {
        self::interceptNativeAuthPages();
        self::interceptProfilePageForAccountCenterTakeover();
    }

    /**
     * 账户中心接管：访问 profile 页面时触发同步
     */
    public static function interceptProfilePageForAccountCenterTakeover()
    {
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($requestPath)) {
            $requestPath = '';
        }

        $isProfilePage = $scriptName === 'profile.php' || preg_match('#/profile\.php$#i', $requestPath) === 1;
        if (!$isProfilePage) {
            return;
        }

        $options = Options::alloc();
        $pluginConfig = $options->plugin('Oidc');

        $pluginEnabled = empty($pluginConfig->enablePlugin) || $pluginConfig->enablePlugin === '1';
        if (!$pluginEnabled) {
            return;
        }

        if (empty($pluginConfig->enableAccountCenterTakeover) || $pluginConfig->enableAccountCenterTakeover !== '1') {
            return;
        }

        if (!self::isAccountCenterTakeoverReady($pluginConfig)) {
            return;
        }

        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $profileUrl = Common::url('profile.php', $options->adminUrl);

        if ($requestMethod === 'POST') {
            \Widget\Notice::alloc()->set(_t('个人资料和密码由账户中心管理，请前往账户中心修改'), 'notice');
            $separator = strpos($profileUrl, '?') === false ? '?' : '&';
            header('Location: ' . $profileUrl . $separator . 'oidc_synced=1');
            exit;
        }

        $synced = isset($_GET['oidc_synced']) ? (string) $_GET['oidc_synced'] : '';
        if ($synced === '1') {
            return;
        }

        $currentUrl = self::buildCurrentAbsoluteUrl($requestUri, $options->adminUrl);
        if ($currentUrl === '') {
            $currentUrl = $profileUrl;
        }

        $loginUrl = Common::url('/oidc/login', $options->index);
        $target = $loginUrl . '?sync_profile=1&return_to=' . rawurlencode($currentUrl);
        header('Location: ' . $target);
        exit;
    }

    /**
     * 账户中心接管：在 profile 页面渲染按钮和提示
     */
    public static function renderProfileTakeoverHint()
    {
        $options = Options::alloc();
        $pluginConfig = $options->plugin('Oidc');

        $pluginEnabled = empty($pluginConfig->enablePlugin) || $pluginConfig->enablePlugin === '1';
        if (!$pluginEnabled) {
            return;
        }

        if (empty($pluginConfig->enableAccountCenterTakeover) || $pluginConfig->enableAccountCenterTakeover !== '1') {
            return;
        }

        $errors = self::getAccountCenterTakeoverErrors($pluginConfig);
        if (!empty($errors)) {
            $listHtml = '<ul style="margin:8px 0 0 18px;">';
            foreach ($errors as $item) {
                $listHtml .= '<li>' . htmlspecialchars($item) . '</li>';
            }
            $listHtml .= '</ul>';
            echo '<div class="message error"><p><strong>' . _t('账户中心接管未生效，原因：') . '</strong></p>' . $listHtml . '</div>';
            return;
        }

        $accountCenterUrl = self::sanitizeAccountCenterUrl((string) $pluginConfig->accountCenterUrl);
        if ($accountCenterUrl === '') {
            return;
        }

        $accountCenterUrlJson = json_encode($accountCenterUrl);
        $title = _t('账户中心已接管个人资料设置');
        $desc = _t('请前往账户中心修改个人资料和密码，修改后请重新登录以生效。');
        $buttonText = _t('前往账户中心设置');
        ?>
        <script>
            (function () {
                var panel = document.querySelector('.typecho-content-panel');
                if (!panel) {
                    return;
                }

                var sections = panel.querySelectorAll('section');
                sections.forEach(function (section) {
                    var h3 = section.querySelector('h3');
                    var title = h3 ? (h3.textContent || '').trim() : '';
                    if (section.id === 'change-password' || section.id === 'writing-option') {
                        section.style.display = 'none';
                    }
                });

                var firstSection = panel.querySelector('section');
                if (firstSection) {
                    firstSection.style.display = 'none';
                }

                var wrapper = document.createElement('div');
                wrapper.className = 'message notice';
                wrapper.innerHTML = '<p><strong><?php echo addslashes($title); ?></strong></p>'
                    + '<p style="margin-top:6px;"><?php echo addslashes($desc); ?></p>'
                    + '<p style="margin-top:10px;"><a class="btn primary" href=' + <?php echo $accountCenterUrlJson; ?> + '><?php echo addslashes($buttonText); ?></a></p>';

                panel.insertBefore(wrapper, panel.firstChild);
            })();
        </script>
        <?php
    }

    /**
     * 拦截 Typecho 原生登录/注册页
     */
    public static function interceptNativeAuthPages()
    {
        if (!self::shouldDisableNativeAuthPages()) {
            return;
        }

        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($requestPath)) {
            $requestPath = '';
        }

        $isNativeLoginPage = $scriptName === 'login.php' || preg_match('#/login\.php$#i', $requestPath) === 1;
        $isNativeRegisterPage = $scriptName === 'register.php' || preg_match('#/register\.php$#i', $requestPath) === 1;

        if (!$isNativeLoginPage && !$isNativeRegisterPage) {
            return;
        }

        $options = Options::alloc();
        $target = Common::url('/oidc/login-page', $options->index);
        header('Location: ' . $target);
        exit;
    }

    /**
     * 是否启用原生登录/注册页拦截
     */
    private static function shouldDisableNativeAuthPages()
    {
        try {
            $options = Options::alloc();
            $pluginConfig = $options->plugin('Oidc');

            $pluginEnabled = empty($pluginConfig->enablePlugin) || $pluginConfig->enablePlugin === '1';
            if (!$pluginEnabled) {
                return false;
            }

            return !empty($pluginConfig->disableNativeAuthPages) && $pluginConfig->disableNativeAuthPages === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 账户中心接管是否满足生效条件
     */
    private static function isAccountCenterTakeoverReady($pluginConfig)
    {
        return empty(self::getAccountCenterTakeoverErrors($pluginConfig));
    }

    /**
     * 返回账户中心接管未满足的条件
     */
    private static function getAccountCenterTakeoverErrors($pluginConfig)
    {
        $errors = array();

        if (empty($pluginConfig->disableNativeAuthPages) || $pluginConfig->disableNativeAuthPages !== '1') {
            $errors[] = _t('未开启“禁用 Typecho 原生登录和注册页”');
        }

        if (empty($pluginConfig->keepNativePasswordLogin) || $pluginConfig->keepNativePasswordLogin !== '0') {
            $errors[] = _t('“是否保留 Typecho 原生账号登录功能”未设置为“不保留”');
        }

        if (!empty($pluginConfig->allowUserUnbind) && $pluginConfig->allowUserUnbind !== '0') {
            $errors[] = _t('“是否允许用户解绑 OIDC 账户”未设置为“否”');
        }

        $accountCenterUrl = self::sanitizeAccountCenterUrl(!empty($pluginConfig->accountCenterUrl) ? (string) $pluginConfig->accountCenterUrl : '');
        if ($accountCenterUrl === '') {
            $errors[] = _t('“账户中心 URL”未配置或格式无效');
        }

        return $errors;
    }

    /**
     * 校验并清理账户中心 URL
     */
    private static function sanitizeAccountCenterUrl($url)
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        return $url;
    }

    /**
     * 构建当前请求绝对 URL
     */
    private static function buildCurrentAbsoluteUrl($requestUri, $siteUrl)
    {
        $requestUri = trim((string) $requestUri);
        if ($requestUri === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $requestUri)) {
            return $requestUri;
        }

        $base = rtrim((string) $siteUrl, '/');
        if ($base === '') {
            return '';
        }

        return $base . '/' . ltrim($requestUri, '/');
    }

    /**
     * 配置保存校验：账户中心接管前置条件
     *
     * @param string|null $value
     * @param string $field
     * @param string $expected
     * @return bool
     */
    public static function validateAccountCenterTakeoverPrerequisite($value, $field, $expected)
    {
        if ((string) $value !== '1') {
            return true;
        }

        $actual = isset($_POST[$field]) ? (string) $_POST[$field] : '';
        if ($actual === '') {
            $options = Options::alloc();
            $pluginConfig = $options->plugin('Oidc');
            $actual = isset($pluginConfig->{$field}) ? (string) $pluginConfig->{$field} : '';
        }

        return $actual === (string) $expected;
    }

    /**
     * 配置保存校验：账户中心 URL
     *
     * @param string|null $value
     * @return bool
     */
    public static function validateAccountCenterTakeoverUrl($value)
    {
        if ((string) $value !== '1') {
            return true;
        }

        $url = isset($_POST['accountCenterUrl']) ? trim((string) $_POST['accountCenterUrl']) : '';
        if ($url === '') {
            $options = Options::alloc();
            $pluginConfig = $options->plugin('Oidc');
            $url = isset($pluginConfig->accountCenterUrl) ? trim((string) $pluginConfig->accountCenterUrl) : '';
        }

        return self::sanitizeAccountCenterUrl($url) !== '';
    }

}
