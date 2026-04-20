# likeuuu Mock Server - PHP 兼容版

## 上传结构

```
你的域名根目录/
├── admin.php           ← 管理面板
├── data/               ← 数据 (自动创建,需可写)
└── api/
    ├── .htaccess       ← 路由配置
    ├── debug.php       ← 环境诊断
    ├── health.php      ← /api/health
    ├── app/
    │   ├── config.php              ← /api/app/config
    │   └── announcement/
    │       └── active/
    │           └── index.php       ← /api/app/announcement/active
    └── location/
        ├── .htaccess
        └── index.php               ← /api/location, /api/location/{adcode}
```

## 部署步骤

### 1. 创建子域名

在虚拟主机面板创建子域名 `zq.你的域名.com`

> URL 长度 ≤ 23 字节，如 `https://zq.abc.com/` = 22 bytes ✓

### 2. 上传文件

把本目录所有文件上传到子域名根目录。

### 3. 先诊断

访问 `https://zq.你的域名.com/api/debug.php`

检查 mod_rewrite、目录权限等是否正常。

### 4. 测试 API

```
https://zq.你的域名.com/api/health.php
https://zq.你的域名.com/api/app/config.php
https://zq.你的域名.com/api/app/announcement/active/index.php
https://zq.你的域名.com/api/location/index.php
```

### 5. 修补 DEX

```bash
python3 patch_dex.py classes2.dex https://zq.你的域名.com/ classes2_patched.dex
```

### 6. 重新打包 APK

```bash
apktool d original.apk -o app
cp classes2_patched.dex app/classes2.dex
apktool b app -o patched.apk
apksigner sign --ks your.keystore patched.apk
```

## 如果 .htaccess 不生效

大部分虚拟主机支持 mod_rewrite。如果 404:

1. 确认 `.htaccess` 已上传到 `api/` 目录
2. 联系虚拟主机商开启 `mod_rewrite`
3. 确认 `AllowOverride All` 已设置
4. 试试在 `.htaccess` 开头加 `Options +FollowSymLinks`

## API 接口

| URL | 文件 |
|-----|------|
| `/api/health` | `api/health.php` |
| `/api/app/config` | `api/app/config.php` |
| `/api/app/announcement/active` | `api/app/announcement/active/index.php` |
| `/api/app/announcement/active?limit=N` | 同上 (带参数) |
| `/api/location` | `api/location/index.php` |
| `/api/location/{adcode}` | `api/location/index.php` |
| `/api/location?adcode=X` | 同上 (带参数) |
