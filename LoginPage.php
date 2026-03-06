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
$logoUrl = !empty($pluginConfig->loginLogoUrl) ? trim($pluginConfig->loginLogoUrl) : '';
$daisyCssUrl = $cdnBase . '/npm/daisyui@5/daisyui.css';
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

        .oidc-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 14px;
        }

        .oidc-input-wrap {
            position: relative;
        }

        .oidc-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: color-mix(in oklab, var(--color-base-content) 45%, transparent);
            pointer-events: none;
        }

        .oidc-input {
            width: 100%;
            padding-left: 2.5rem;
            border-radius: 1rem;
            min-height: 2.75rem;
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
                <div class="flex items-center justify-center gap-2 text-sm text-base-content/60 mb-2">
                    <?php if (!empty($logoUrl)) { ?>
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($systemName); ?>" class="oidc-logo" />
                    <?php } else { ?>
                        <span class="oidc-brand-dot rounded-full bg-primary"></span>
                    <?php } ?>
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
                            <?php if (isset($this->security) && isset($this->request)) { ?>
                                <input type="hidden" name="_" value="<?php echo $this->security->getToken($this->request->getRequestUrl()); ?>" />
                            <?php } ?>
                            <div class="form-control">
                                <div class="oidc-input-wrap">
                                    <svg class="oidc-input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" />
                                    </svg>
                                    <input type="text" id="name" name="name" class="input input-bordered oidc-input" placeholder="Username" autocomplete="username" required />
                                </div>
                            </div>
                            <div class="form-control">
                                <div class="oidc-input-wrap">
                                    <svg class="oidc-input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 6.73V17a1 1 0 1 0 2 0v-1.27a2 2 0 1 0-2 0ZM10 9V7a2 2 0 0 1 4 0v2Z" />
                                    </svg>
                                    <input type="password" id="password" name="password" class="input input-bordered oidc-input" placeholder="password" autocomplete="current-password" required />
                                </div>
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

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
