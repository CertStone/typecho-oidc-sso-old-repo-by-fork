<?php
namespace TypechoPlugin\Oidc;

use Typecho\Common;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$options = Options::alloc();
$pluginConfig = $options->plugin('Oidc');
$systemName = !empty($pluginConfig->oidcSystemName) ? $pluginConfig->oidcSystemName : 'OIDC';
$loginAction = $options->loginAction();
$referer = Common::url('admin/', $options->index);
$loginUrl = Common::url('/oidc/login', $options->index);
?>
<!DOCTYPE HTML>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php _e('%s 登录', $systemName); ?> - <?php echo htmlspecialchars($options->title); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "PingFang SC",
                "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            background: linear-gradient(135deg, #f5f7ff 0%, #f7f8fb 45%, #eef1ff 100%);
            color: #1f1f1f;
        }

        a {
            color: inherit;
        }

        .oidc-login-wrap {
            display: table;
            height: 100%;
            width: 100%;
        }

        .oidc-login {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 40px 20px;
        }

        .oidc-login h1 {
            margin: 0 0 20px;
            font-size: 24px;
            font-weight: normal;
            color: #1b1f3b;
        }

        .oidc-card {
            width: 420px;
            max-width: 90%;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.12);
            padding: 28px 32px;
            text-align: left;
        }

        .oidc-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            background: #2f54eb;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 6px 14px rgba(47, 84, 235, 0.3);
        }

        .oidc-primary:hover {
            background: #1d39c4;
        }

        .oidc-divider {
            margin: 20px 0;
            text-align: center;
            font-size: 12px;
            color: #999;
            position: relative;
        }

        .oidc-divider::before,
        .oidc-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #eee;
        }

        .oidc-divider::before {
            left: 0;
        }

        .oidc-divider::after {
            right: 0;
        }

        .oidc-form .input {
            width: 100%;
            margin-bottom: 12px;
            border: 1px solid #d0d7e2;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
            outline: none;
        }

        .oidc-form .input:focus {
            border-color: #2f54eb;
            box-shadow: 0 0 0 3px rgba(47, 84, 235, 0.15);
        }

        .oidc-form .submit {
            margin-top: 12px;
        }

        .oidc-form .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            background: #0f172a;
            color: #fff;
            font-size: 14px;
        }

        .oidc-form .btn:hover {
            background: #111827;
        }

        .oidc-hint {
            margin-top: 16px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="oidc-login-wrap">
        <div class="oidc-login">
            <h1><?php _e('欢迎使用 %s', $systemName); ?></h1>
            <div class="oidc-card">
                <a class="oidc-primary" href="<?php echo $loginUrl; ?>">
                    <?php _e('从 %s 登录/注册', $systemName); ?>
                </a>

                <div class="oidc-divider"><?php _e('或使用本地账户'); ?></div>

                <form action="<?php echo $loginAction; ?>" method="post" name="login" class="oidc-form" role="form">
                    <p>
                        <label for="name" class="sr-only"><?php _e('用户名或邮箱'); ?></label>
                        <input type="text" id="name" name="name" class="input" placeholder="<?php _e('用户名或邮箱'); ?>" />
                    </p>
                    <p>
                        <label for="password" class="sr-only"><?php _e('密码'); ?></label>
                        <input type="password" id="password" name="password" class="input" placeholder="<?php _e('密码'); ?>" required />
                    </p>
                    <p class="submit">
                        <button type="submit" class="btn primary"><?php _e('登录'); ?></button>
                        <input type="hidden" name="referer" value="<?php echo $referer; ?>" />
                    </p>
                    <p>
                        <label for="remember">
                            <input type="checkbox" name="remember" value="1" id="remember" />
                            <?php _e('记住我'); ?>
                        </label>
                    </p>
                </form>

                <p class="oidc-hint">
                    <?php _e('注册功能已在后台禁用'); ?>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
