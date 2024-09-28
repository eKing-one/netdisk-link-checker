# NetDiskLinkChecker

检查百度网盘、阿里网盘、夸克网盘和115网盘分享链接是否有效的 PHP 库。

## 安装

使用 Composer 安装：

```bash
composer require eking/netdisk-link-checker
```

## 使用

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use eking-one\netdisk\LinkChecker;

$checker = new LinkChecker();
if ($checker->checkUrl('https://www.aliyundrive.com/s/someshareid')) {
    echo "链接有效";
} else {
    echo "链接无效";
}
```
## 贡献

欢迎贡献！提交 Pull Request 或创建 Issue。

## 版权

© [eKing], 2024.
