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
$cdnBase = !empty($pluginConfig->uiCdnBase) ? rtrim($pluginConfig->uiCdnBase, '/') : 'https://s4.zstatic.net/ajax/libs/daisyui/5.1.25';
$backgroundUrl = !empty($pluginConfig->loginBackgroundUrl) ? $pluginConfig->loginBackgroundUrl : '';
?>
<!DOCTYPE HTML>
<html data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php _e('%s 登录', $systemName); ?> - <?php echo htmlspecialchars($options->title); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo $cdnBase; ?>/full.min.css">
    <link rel="stylesheet" href="<?php echo $cdnBase; ?>/themes.min.css">
    <style>
        .oidc-hero {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .oidc-glass {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.7);
        }

        .oidc-brand-dot {
            width: 8px;
            height: 8px;
        }
    </style>
</head>

<body>
    <div class="min-h-screen hero bg-base-200 oidc-hero"<?php if (!empty($backgroundUrl)) { ?> style="background-image: url('<?php echo htmlspecialchars($backgroundUrl); ?>');"<?php } ?> >
        <div class="hero-overlay bg-base-200/60"></div>
        <div class="hero-content w-full px-6 py-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-6">
                    <div class="flex items-center justify-center gap-2 text-sm text-base-content/60">
                        <span class="oidc-brand-dot rounded-full bg-primary"></span>
                        <span><?php _e('统一认证'); ?></span>
                    </div>
                    <h1 class="text-3xl font-semibold text-base-content mt-3">
                        <?php _e('登录你的账号'); ?>
                    </h1>
                    <p class="text-sm text-base-content/60 mt-2">
                        <?php _e('使用 %s 账户或本地账号登录', $systemName); ?>
                    </p>
                </div>

                <div class="card shadow-2xl oidc-glass">
                    <div class="card-body space-y-5">
                        <a class="btn btn-primary w-full" href="<?php echo $loginUrl; ?>">
                            <?php _e('从 %s 登录/注册', $systemName); ?>
                        </a>

                        <div class="divider text-xs text-base-content/50"><?php _e('或使用本地账户'); ?></div>

                        <form action="<?php echo $loginAction; ?>" method="post" name="login" role="form" class="space-y-3">
                            <div class="form-control">
                                <label class="label" for="name">
                                    <span class="label-text"><?php _e('用户名或邮箱'); ?></span>
                                </label>
                                <input type="text" id="name" name="name" class="input input-bordered bg-base-100" placeholder="<?php _e('请输入用户名或邮箱'); ?>" />
                            </div>
                            <div class="form-control">
                                <label class="label" for="password">
                                    <span class="label-text"><?php _e('密码'); ?></span>
                                </label>
                                <input type="password" id="password" name="password" class="input input-bordered bg-base-100" placeholder="<?php _e('请输入密码'); ?>" required />
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
