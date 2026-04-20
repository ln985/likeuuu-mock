# likeuuu 本地化部署工具

## 文件清单

| 文件 | 说明 |
|------|------|
| `server.js` | Mock API 服务器 (Node.js + Express) |
| `patch_dex.py` | DEX 文件补丁工具 (替换服务器地址) |
| `districts.json` | 行政区划数据文件 |
| `package.json` | Node.js 依赖配置 |

## 快速开始

### 1. 启动本地服务器

```bash
cd likeuuu-mock
npm install
node server.js
```

服务器默认监听 `0.0.0.0:8080`，可通过环境变量修改：

```bash
PORT=3000 node server.js   # 监听 3000 端口
```

### 2. 修改 DEX 文件指向本地服务器

```bash
# 用法
python3 patch_dex.py <原始classes2.dex> <本地服务器URL> [输出文件]

# 示例 - 使用 localhost
python3 patch_dex.py classes2.dex http://127.0.0.1:8080/ classes2_patched.dex

# 示例 - 使用局域网 IP
python3 patch_dex.py classes2.dex http://192.168.1.1:80/ classes2_patched.dex
```

> ⚠️ **URL 长度限制**: 新 URL 不能超过 23 字节（原 URL `https://zq.likeuuu.top/` 的长度）。
> 请使用短 IP 地址或短域名。

可用示例：
```
http://192.168.1.1:80/       ✓ 22 bytes
http://10.0.0.1:8080/        ✓ 21 bytes
http://127.0.0.1:8080/       ✓ 22 bytes
http://localhost:80/         ✓ 20 bytes
http://192.168.1.100:8080/   ✗ 26 bytes (超长)
```

### 3. 替换 APK 中的 DEX 并重新打包

```bash
# 解包 APK
apktool d original.apk -o app_unpacked

# 替换 classes2.dex
cp classes2_patched.dex app_unpacked/classes2.dex

# 重新打包
apktool b app_unpacked -o patched.apk

# 签名 (需要你的 keystore)
apksigner sign --ks your-keystore.jks patched.apk
```

## API 接口说明

### 公共接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/app/config` | 获取应用配置（主页URL、社群链接） |
| GET | `/api/app/announcement/active` | 获取活跃公告列表 |
| GET | `/api/app/announcement/active?limit=N` | 获取限量公告 |
| GET | `/api/location` | 获取所有省份列表 |
| GET | `/api/location/:adcode` | 根据行政区划代码获取详情 |
| GET | `/api/location?adcode=XXX` | 同上 (query 参数方式) |
| GET | `/api/location/provinces` | 获取省份列表 |
| GET | `/api/location/cities?provinceAdcode=XXX` | 获取指定省份的城市列表 |
| GET | `/api/location/districts?cityAdcode=XXX` | 获取指定城市的区县列表 |
| GET | `/api/health` | 健康检查 |

### 管理接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/admin/announcements` | 获取所有公告 |
| POST | `/api/admin/announcement` | 添加公告 |
| DELETE | `/api/admin/announcement/:id` | 删除公告 |

## 数据模型

### 公告 (Announcement)
```json
{
  "id": 1,
  "type": "IMPORTANT",
  "title": "标题",
  "content": "内容",
  "active": true,
  "isPopup": true,
  "order": 1,
  "startTime": "2026-04-01T00:00:00Z",
  "endTime": "2026-12-31T23:59:59Z",
  "createdAt": "2026-04-01T10:00:00Z",
  "updatedAt": "2026-04-15T08:30:00Z"
}
```

### 应用配置 (AppConfig)
```json
{
  "linkConfig": {
    "mainUrl": "https://zq.likeuuu.top/"
  },
  "appLinkConfig": {
    "communityGroup": "https://qm.qq.com/cgi-bin/qm/qr?k=example"
  }
}
```

### 行政区划 (RegionNode)
```json
{
  "adcode": "440305",
  "name": "南山区",
  "children": []
}
```

### 位置结果 (LocationResult)
```json
{
  "adcode": "440305",
  "provinceName": "广东省",
  "provinceAdcode": "440000",
  "cityName": "深圳市",
  "cityAdcode": "440300",
  "districtName": "南山区"
}
```

## 反编译分析摘要

从 `classes2.dex` 中提取的信息：

- **包名**: `com.lucky.aazq`
- **框架**: Kotlin + Jetpack Compose
- **网络**: Retrofit + OkHttp + NetBare (网络代理框架)
- **API 域名**: `https://zq.likeuuu.top/`
- **API 路径**:
  - `/api/app/config`
  - `/api/app/announcement/active`
  - `/api/app/announcement/active?limit=`
  - `/api/location`
  - `/api/location/`
- **本地数据**: `districts.json` (内置行政区划)
- **安全**: GeoService 中存在 `trustAllCerts`，即信任所有证书

## 注意事项

1. DEX 补丁会修改 checksum，但 **不会更新 SHA-1 签名**，需要重新签名 APK
2. 新 URL 必须 ≤ 23 字节，建议使用短 IP 或短域名
3. 如果 APP 使用了证书固定 (Certificate Pinning)，还需要额外处理
4. APP 内置了 `trustAllCerts`，所以本地服务器用 HTTP 也可以正常工作
