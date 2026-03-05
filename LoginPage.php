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
$loginAction = $options->loginAction;
$referer = Common::url('admin/', $options->index);
$loginUrl = Common::url('/oidc/login', $options->index);
$cdnBase = !empty($pluginConfig->uiCdnBase) ? rtrim($pluginConfig->uiCdnBase, '/') : 'https://s4.zstatic.net';
$backgroundUrl = !empty($pluginConfig->loginBackgroundUrl) ? $pluginConfig->loginBackgroundUrl : '';
$daisyCssUrl = $cdnBase . '/npm/daisyui@5';
$daisyThemeUrl = $cdnBase . '/npm/daisyui@5/themes.css';
$tailwindBrowserUrl = $cdnBase . '/npm/@tailwindcss/browser@4';
?>
<!DOCTYPE HTML>
<html data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php _e('%s 登录', $systemName); ?> - <?php echo htmlspecialchars($options->title); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo $daisyCssUrl; ?>" type="text/css" />
    <link rel="stylesheet" href="<?php echo $daisyThemeUrl; ?>" type="text/css" />
    <script src="<?php echo $tailwindBrowserUrl; ?>"></script>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "PingFang SC",
                "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
        }

        .oidc-brand-dot {
            width: 8px;
            height: 8px;
        }
    </style>
</head>

<body>
    <div class="hero min-h-screen bg-base-200"<?php if (!empty($backgroundUrl)) { ?> style="background-image: url('<?php echo htmlspecialchars($backgroundUrl); ?>'); background-size: cover; background-position: center;"<?php } ?> >
        <?php if (!empty($backgroundUrl)) { ?>
            <div class="hero-overlay bg-base-200/70"></div>
        <?php } ?>
        <div class="hero-content flex-col text-center">
            <div class="max-w-md">
                <div class="flex items-center justify-center gap-2 text-sm text-base-content/60">
                    <span class="oidc-brand-dot rounded-full bg-primary"></span>
                    <span><?php _e('统一认证'); ?></span>
                </div>
                <h1 class="text-4xl font-semibold text-base-content mt-3">
                    <?php _e('登录你的账号'); ?>
                </h1>
                <p class="py-3 text-sm text-base-content/60">
                    <?php _e('使用 %s 账户或本地账号登录', $systemName); ?>
                </p>

                <div class="card bg-base-100 shadow-2xl">
                    <div class="card-body space-y-4">
                        <a class="btn btn-primary w-full" href="<?php echo $loginUrl; ?>">
                            <?php _e('从 %s 登录/注册', $systemName); ?>
                        </a>

                        <div class="divider text-xs text-base-content/50"><?php _e('或使用本地账户'); ?></div>

                        <form action="<?php echo $loginAction; ?>" method="post" name="login" role="form" class="space-y-3 text-left">
                            <div class="form-control">
                                <label class="label" for="name">
                                    <span class="label-text"><?php _e('用户名或邮箱'); ?></span>
                                </label>
                                <input type="text" id="name" name="name" class="input input-bordered" placeholder="<?php _e('请输入用户名或邮箱'); ?>" />
                            </div>
                            <div class="form-control">
                                <label class="label" for="password">
                                    <span class="label-text"><?php _e('密码'); ?></span>
                                </label>
                                <input type="password" id="password" name="password" class="input input-bordered" placeholder="<?php _e('请输入密码'); ?>" required />
                            </div>
                            <input type="hidden" name="referer" value="<?php echo $referer; ?>" />
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" class="checkbox checkbox-sm" name="remember" value="1" id="remember" />
                                    <span class="label-text"><?php _e('记住我'); ?></span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-neutral w-full">
                                <?php _e('登录'); ?>
                            </button>
                        </form>

                        <p class="text-xs text-center text-base-content/50">
                            <?php _e('注册功能已在后台禁用'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
