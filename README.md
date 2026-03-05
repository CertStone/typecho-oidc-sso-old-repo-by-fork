# OIDC

OpenID Connect 的认证插件

## 安装

```bash
cd typecho/usr/plugins
git clone https://github.com/CertStone/typecho-oidc.git Oidc
```

## 使用

启用插件并配置好后，在需要的位置添加指向 `oidc/login` 的按钮即可。
如果需要完整的自定义登录页，可使用 `oidc/login-page`。

比如 `sidebar.php`

```php
<li><a href="<?php $this->options->index('oidc/login'); ?>"><?php _e('单点登录'); ?></a></li>
```

或 `login.php`

```php
<a href="<?php $options->index('oidc/login'); ?>"><?php _e('单点登录'); ?></a>
```

自定义登录页示例：

```php
<a href="<?php $options->index('oidc/login-page'); ?>"><?php _e('统一认证登录'); ?></a>
```

## IdP 配置说明

根据插件代码，当前 OIDC 回调地址固定为：

```
<你的站点地址>/oidc/callback
```

请将该地址加入 IdP 的 **Redirect URIs**。

本插件目前 **未实现 OIDC 退出登录（end_session_endpoint）**，因此 **Post Logout Redirect URIs** 不会被使用。
如果你在 IdP 中需要填写，可使用以下地址作为默认回跳：

```
<你的站点地址>/
```

自定义登录页入口（用于展示现代化登录页）：

```
<你的站点地址>/oidc/login-page
```
