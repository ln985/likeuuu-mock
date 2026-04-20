# likeuuu Mock Server - PHP 虚拟主机版

## 部署步骤

### 1. 创建子域名

在你的虚拟主机面板 (cPanel/Plesk 等) 创建子域名，例如：

```
zq.你的域名.com
```

> 💡 用短子域名，确保替换后的 URL ≤ 23 字节。
> 例如 `https://zq.abc.com/` = 22 字节 ✓

### 2. 上传文件

把本目录所有文件上传到子域名的根目录：

```
zq.你的域名.com/
├── .htaccess         ← URL 重写规则
├── index.php         ← API 入口
├── admin.php         ← 管理面板
└── data/             ← 数据目录 (首次访问自动创建)
```

### 3. 设置目录权限

`data/` 目录需要可写权限（首次访问会自动创建，如果失败手动创建并 `chmod 755`）。

### 4. 测试

浏览器访问：

```
https://zq.你的域名.com/api/health
```

应返回：`{"status":"ok","time":"..."}`

### 5. 修改 DEX 文件

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

## 管理面板

访问 `https://zq.你的域名.com/admin.php` 可以：

- 修改应用配置（主页 URL、社群链接）
- 添加/删除/启用/禁用公告
- 查看 API 接口列表

## API 接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/app/config` | 应用配置 |
| GET | `/api/app/announcement/active` | 活跃公告 |
| GET | `/api/app/announcement/active?limit=N` | 限量公告 |
| GET | `/api/location` | 省份列表 |
| GET | `/api/location/:adcode` | 行政区划详情 |
| GET | `/api/location/provinces` | 省份列表 |
| GET | `/api/location/cities?provinceAdcode=XXX` | 城市列表 |
| GET | `/api/location/districts?cityAdcode=XXX` | 区县列表 |
| GET | `/api/health` | 健康检查 |

## 常见问题

**Q: 访问返回 500 错误？**
- 检查 PHP 版本 ≥ 7.4
- 检查 `data/` 目录是否可写
- 查看虚拟主机的 error_log

**Q: 访问返回 404？**
- 确认 `.htaccess` 已上传
- 确认虚拟主机支持 `mod_rewrite`
- 尝试在 `.htaccess` 顶部加 `Options +FollowSymLinks`

**Q: DEX 替换后 URL 超长？**
- 使用更短的子域名，如 `https://zq.a.com/`
- 最长不能超过 23 字节

**Q: 数据存放在哪里？**
- 首次访问自动在 `data/` 目录生成 JSON 文件
- 可以直接编辑 `data/config.json` 等文件
- 管理面板修改也会保存到 `data/` 目录
