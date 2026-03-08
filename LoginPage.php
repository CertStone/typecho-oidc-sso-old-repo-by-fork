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
$keepNativePasswordLogin = empty($pluginConfig->keepNativePasswordLogin) || $pluginConfig->keepNativePasswordLogin === '1';
$loginAction = $options->loginAction;
$referer = $options->adminUrl;
$loginUrl = Common::url('/oidc/login', $options->index);
$cdnBase = !empty($pluginConfig->uiCdnBase) ? rtrim($pluginConfig->uiCdnBase, '/') : 'https://s4.zstatic.net';
$backgroundUrl = !empty($pluginConfig->loginBackgroundUrl) ? $pluginConfig->loginBackgroundUrl : '';
$logoUrl = !empty($pluginConfig->loginLogoUrl) ? trim($pluginConfig->loginLogoUrl) : '';
$rememberName = htmlspecialchars(\Typecho\Cookie::get('__typecho_remember_name', ''));
\Typecho\Cookie::delete('__typecho_remember_name');

$noticeType = trim((string) \Typecho\Cookie::get('__typecho_notice_type', ''));
$noticeRaw = (string) \Typecho\Cookie::get('__typecho_notice', '');
$noticeMessages = [];
if ($noticeRaw !== '') {
    $decoded = json_decode($noticeRaw, true);
    if (is_array($decoded)) {
        $noticeMessages = $decoded;
    }
}
\Typecho\Cookie::delete('__typecho_notice');
\Typecho\Cookie::delete('__typecho_notice_type');
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
</head>

<body class="min-h-screen">
    <div class="hero min-h-screen bg-base-200"<?php if (!empty($backgroundUrl)) { ?> style="background-image: url('<?php echo htmlspecialchars($backgroundUrl); ?>'); background-size: cover; background-position: center;"<?php } ?> >
        <?php if (!empty($backgroundUrl)) { ?>
            <div class="hero-overlay bg-base-100/30"></div>
        <?php } ?>
        <div class="hero-content w-full px-3 py-8 sm:px-6 md:px-8">
            <div class="w-full max-w-[33rem]">
                <div class="card w-full rounded-3xl border border-base-300/60 bg-base-100 shadow-2xl backdrop-blur-sm">
                    <div class="card-body space-y-5 p-6 sm:p-8 md:p-10">
                        <div class="border-b border-base-300/70 pb-6 text-center space-y-3">
                            <?php if (!empty($logoUrl)) { ?>
                                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($systemName); ?>" class="mx-auto h-12 w-auto max-w-[220px] object-contain" />
                            <?php } ?>
                            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-base-content">
                                <?php _e('登录你的账号'); ?>
                            </h1>
                            <p class="text-sm md:text-base text-base-content/75">
                                <?php _e('建议使用 %s', $systemName); ?>
                            </p>
                        </div>

                        <div class="mx-auto w-full max-w-[19rem] space-y-4">
                        <?php if (!empty($noticeMessages)) { ?>
                            <div class="alert <?php echo $noticeType === 'error' ? 'alert-error' : ($noticeType === 'success' ? 'alert-success' : 'alert-info'); ?>">
                                <span><?php echo htmlspecialchars((string) $noticeMessages[0]); ?></span>
                            </div>
                        <?php } ?>

                        <div id="oidc-login-progress" class="alert alert-info hidden">
                            <span><?php _e('正在登录，请稍候...'); ?></span>
                        </div>

                        <a class="btn btn-neutral btn-lg w-full rounded-2xl normal-case text-base font-semibold shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg" href="<?php echo $loginUrl; ?>">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M10.75 2a.75.75 0 0 1 0 1.5H6A2.5 2.5 0 0 0 3.5 6v12A2.5 2.5 0 0 0 6 20.5h4.75a.75.75 0 0 1 0 1.5H6A4 4 0 0 1 2 18V6a4 4 0 0 1 4-4h4.75ZM15 7.25a.75.75 0 0 1 .53.22l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06l3.22-3.22H8a.75.75 0 0 1 0-1.5h9.69l-3.22-3.22A.75.75 0 0 1 15 7.25Z" />
                            </svg>
                            <?php _e('从 %s 登录/注册', $systemName); ?>
                        </a>

                        <?php if ($keepNativePasswordLogin) { ?>
                            <div class="divider text-xs text-base-content/50 my-1 h-6"><?php _e('或使用本地账户'); ?></div>

                            <form id="oidc-local-login-form" action="<?php echo htmlspecialchars($loginAction); ?>" method="post" name="login" role="form" class="space-y-3 text-left">
                                <?php if (isset($this->security) && isset($this->request)) { ?>
                                    <input type="hidden" name="_" value="<?php echo htmlspecialchars($this->security->getToken($this->request->getRequestUrl())); ?>" />
                                <?php } ?>
                                <div class="form-control">
                                    <label class="input input-bordered input-lg w-full rounded-xl flex items-center gap-2">
                                        <svg class="w-4 h-4 text-base-content/60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" />
                                        </svg>
                                        <input type="text" id="name" name="name" value="<?php echo $rememberName; ?>" class="grow text-sm" placeholder="用户名" autocomplete="username" required />
                                    </label>
                                </div>
                                <div class="form-control">
                                    <label class="input input-bordered input-lg w-full rounded-xl flex items-center gap-2">
                                        <svg class="w-4 h-4 text-base-content/60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 6.73V17a1 1 0 1 0 2 0v-1.27a2 2 0 1 0-2 0ZM10 9V7a2 2 0 0 1 4 0v2Z" />
                                        </svg>
                                        <input type="password" id="password" name="password" class="grow text-sm" placeholder="密码" autocomplete="current-password" required />
                                    </label>
                                </div>
                                <input type="hidden" name="referer" value="<?php echo htmlspecialchars($referer); ?>" />
                                <div class="form-control">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <input type="checkbox" class="checkbox checkbox-sm" name="remember" value="1" id="remember" />
                                        <span class="label-text"><?php _e('记住我'); ?></span>
                                    </label>
                                </div>
                                <button id="oidc-local-login-submit" type="submit" class="btn btn-outline btn-neutral btn-lg w-full rounded-xl normal-case text-base font-medium">
                                    <?php _e('登录'); ?>
                                </button>
                            </form>
                        <?php } ?>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var form = document.getElementById('oidc-local-login-form');
            var submitBtn = document.getElementById('oidc-local-login-submit');
            var progressAlert = document.getElementById('oidc-login-progress');

            if (!form || !submitBtn) {
                return;
            }

            form.addEventListener('submit', function () {
                submitBtn.classList.add('opacity-70', 'cursor-wait');
                submitBtn.textContent = '登录中...';
                submitBtn.disabled = true;
                if (progressAlert) {
                    progressAlert.classList.remove('hidden');
                }
            });
        })();
    </script>
</body>

</html>
