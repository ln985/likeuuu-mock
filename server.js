const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(cors());
app.use(express.json());

// ============================================================
// 数据模型 (从 classes2.dex 反编译推断)
// ============================================================

// 公告类型枚举
const AnnouncementType = {
  NORMAL: 'NORMAL',
  IMPORTANT: 'IMPORTANT',
  URGENT: 'URGENT',
};

// 示例公告数据
let announcements = [
  {
    id: 1,
    type: AnnouncementType.IMPORTANT,
    title: '系统维护通知',
    content: '服务器将于今晚22:00-23:00进行例行维护，届时服务可能短暂不可用，请提前做好准备。',
    active: true,
    isPopup: true,
    order: 1,
    startTime: '2026-04-01T00:00:00Z',
    endTime: '2026-12-31T23:59:59Z',
    createdAt: '2026-04-01T10:00:00Z',
    updatedAt: '2026-04-15T08:30:00Z',
  },
  {
    id: 2,
    type: AnnouncementType.NORMAL,
    title: '新版本发布',
    content: 'v2.5.0 已发布，新增多项功能优化，建议尽快更新。',
    active: true,
    isPopup: false,
    order: 2,
    startTime: '2026-04-10T00:00:00Z',
    endTime: '2026-06-30T23:59:59Z',
    createdAt: '2026-04-10T09:00:00Z',
    updatedAt: '2026-04-10T09:00:00Z',
  },
];

// 应用配置
const appConfig = {
  linkConfig: {
    mainUrl: 'https://zq.likeuuu.top/',
  },
  appLinkConfig: {
    communityGroup: 'https://qm.qq.com/cgi-bin/qm/qr?k=example123456',
  },
};

// 行政区划数据 (省 -> 市 -> 区)
const regions = [
  {
    adcode: '110000',
    name: '北京市',
    children: [
      {
        adcode: '110100',
        name: '北京市',
        children: [
          { adcode: '110101', name: '东城区', children: [] },
          { adcode: '110102', name: '西城区', children: [] },
          { adcode: '110105', name: '朝阳区', children: [] },
          { adcode: '110106', name: '丰台区', children: [] },
          { adcode: '110107', name: '石景山区', children: [] },
          { adcode: '110108', name: '海淀区', children: [] },
        ],
      },
    ],
  },
  {
    adcode: '440000',
    name: '广东省',
    children: [
      {
        adcode: '440300',
        name: '深圳市',
        children: [
          { adcode: '440303', name: '罗湖区', children: [] },
          { adcode: '440304', name: '福田区', children: [] },
          { adcode: '440305', name: '南山区', children: [] },
          { adcode: '440306', name: '宝安区', children: [] },
          { adcode: '440307', name: '龙岗区', children: [] },
          { adcode: '440308', name: '盐田区', children: [] },
        ],
      },
      {
        adcode: '440100',
        name: '广州市',
        children: [
          { adcode: '440103', name: '荔湾区', children: [] },
          { adcode: '440104', name: '越秀区', children: [] },
          { adcode: '440105', name: '海珠区', children: [] },
          { adcode: '440106', name: '天河区', children: [] },
          { adcode: '440111', name: '白云区', children: [] },
          { adcode: '440112', name: '黄埔区', children: [] },
        ],
      },
    ],
  },
  {
    adcode: '310000',
    name: '上海市',
    children: [
      {
        adcode: '310100',
        name: '上海市',
        children: [
          { adcode: '310101', name: '黄浦区', children: [] },
          { adcode: '310104', name: '徐汇区', children: [] },
          { adcode: '310105', name: '长宁区', children: [] },
          { adcode: '310106', name: '静安区', children: [] },
          { adcode: '310107', name: '普陀区', children: [] },
          { adcode: '310110', name: '杨浦区', children: [] },
        ],
      },
    ],
  },
  {
    adcode: '510000',
    name: '四川省',
    children: [
      {
        adcode: '510100',
        name: '成都市',
        children: [
          { adcode: '510104', name: '锦江区', children: [] },
          { adcode: '510105', name: '青羊区', children: [] },
          { adcode: '510106', name: '金牛区', children: [] },
          { adcode: '510107', name: '武侯区', children: [] },
          { adcode: '510108', name: '成华区', children: [] },
        ],
      },
    ],
  },
];

// ============================================================
// API 路由
// ============================================================

// 1. 获取应用配置
// GET /api/app/config
app.get('/api/app/config', (req, res) => {
  console.log('[GET] /api/app/config');
  res.json({
    status: 'ok',
    data: appConfig,
  });
});

// 2. 获取活跃公告列表
// GET /api/app/announcement/active
// GET /api/app/announcement/active?limit=N
app.get('/api/app/announcement/active', (req, res) => {
  const limit = req.query.limit ? parseInt(req.query.limit, 10) : null;
  console.log(`[GET] /api/app/announcement/active${limit ? '?limit=' + limit : ''}`);

  let result = announcements
    .filter((a) => a.active)
    .sort((a, b) => a.order - b.order);

  if (limit && limit > 0) {
    result = result.slice(0, limit);
  }

  res.json({
    status: 'ok',
    data: result,
  });
});

// 3. 获取位置信息 (通过行政区划代码)
// GET /api/location?adcode=XXXXXX
// GET /api/location/XXXXXX
app.get('/api/location', (req, res) => {
  const adcode = req.query.adcode;
  console.log(`[GET] /api/location${adcode ? '?adcode=' + adcode : ''}`);
  handleLocationRequest(adcode, res);
});

app.get('/api/location/:adcode', (req, res) => {
  const adcode = req.params.adcode;
  console.log(`[GET] /api/location/${adcode}`);
  handleLocationRequest(adcode, res);
});

app.get('/api/location/:adcode/', (req, res) => {
  const adcode = req.params.adcode;
  console.log(`[GET] /api/location/${adcode}/`);
  handleLocationRequest(adcode, res);
});

function handleLocationRequest(adcode, res) {
  if (!adcode) {
    // 返回所有省份列表
    const provinces = regions.map((r) => ({
      adcode: r.adcode,
      name: r.name,
    }));
    return res.json({
      status: 'ok',
      data: { provinces },
    });
  }

  // 查找指定 adcode 的区域信息
  const result = findRegionByAdcode(adcode);
  if (!result) {
    return res.status(404).json({
      status: 'error',
      message: `未找到行政区划代码: ${adcode}`,
    });
  }

  // 构建 LocationResult
  const locationResult = buildLocationResult(result, adcode);
  res.json({
    status: 'ok',
    data: locationResult,
  });
}

function findRegionByAdcode(adcode) {
  for (const province of regions) {
    if (province.adcode === adcode) return province;
    for (const city of province.children || []) {
      if (city.adcode === adcode) return city;
      for (const district of city.children || []) {
        if (district.adcode === adcode) return district;
      }
    }
  }
  return null;
}

function findParentChain(adcode) {
  for (const province of regions) {
    if (province.adcode === adcode) {
      return { province, city: null, district: null };
    }
    for (const city of province.children || []) {
      if (city.adcode === adcode) {
        return { province, city, district: null };
      }
      for (const district of city.children || []) {
        if (district.adcode === adcode) {
          return { province, city, district };
        }
      }
    }
  }
  return null;
}

function buildLocationResult(region, adcode) {
  const chain = findParentChain(adcode);
  if (!chain) return null;

  return {
    adcode: adcode,
    provinceName: chain.province ? chain.province.name : '',
    provinceAdcode: chain.province ? chain.province.adcode : '',
    cityName: chain.city ? chain.city.name : '',
    cityAdcode: chain.city ? chain.city.adcode : '',
    districtName: chain.district ? chain.district.name : '',
  };
}

// 4. 获取省份列表
// GET /api/location/provinces
app.get('/api/location/provinces', (req, res) => {
  console.log('[GET] /api/location/provinces');
  const provinces = regions.map((r) => ({
    adcode: r.adcode,
    name: r.name,
  }));
  res.json({
    status: 'ok',
    data: provinces,
  });
});

// 5. 获取城市列表 (按省份)
// GET /api/location/cities?provinceAdcode=XXXXXX
app.get('/api/location/cities', (req, res) => {
  const provinceAdcode = req.query.provinceAdcode;
  console.log(`[GET] /api/location/cities?provinceAdcode=${provinceAdcode}`);

  const province = regions.find((r) => r.adcode === provinceAdcode);
  if (!province) {
    return res.status(404).json({
      status: 'error',
      message: `未找到省份: ${provinceAdcode}`,
    });
  }

  const cities = (province.children || []).map((c) => ({
    adcode: c.adcode,
    name: c.name,
  }));
  res.json({
    status: 'ok',
    data: cities,
  });
});

// 6. 获取区县列表 (按城市)
// GET /api/location/districts?cityAdcode=XXXXXX
app.get('/api/location/districts', (req, res) => {
  const cityAdcode = req.query.cityAdcode;
  console.log(`[GET] /api/location/districts?cityAdcode=${cityAdcode}`);

  for (const province of regions) {
    const city = (province.children || []).find((c) => c.adcode === cityAdcode);
    if (city) {
      const districts = (city.children || []).map((d) => ({
        adcode: d.adcode,
        name: d.name,
      }));
      return res.json({
        status: 'ok',
        data: districts,
      });
    }
  }

  res.status(404).json({
    status: 'error',
    message: `未找到城市: ${cityAdcode}`,
  });
});

// ============================================================
// 管理接口 (用于管理公告等)
// ============================================================

// 添加公告
app.post('/api/admin/announcement', (req, res) => {
  const { type, title, content, isPopup, order, startTime, endTime } = req.body;
  const newId = Math.max(...announcements.map((a) => a.id), 0) + 1;
  const now = new Date().toISOString();
  const announcement = {
    id: newId,
    type: type || AnnouncementType.NORMAL,
    title,
    content,
    active: true,
    isPopup: isPopup || false,
    order: order || newId,
    startTime: startTime || now,
    endTime: endTime || '2099-12-31T23:59:59Z',
    createdAt: now,
    updatedAt: now,
  };
  announcements.push(announcement);
  console.log(`[POST] /api/admin/announcement -> id=${newId}`);
  res.json({ status: 'ok', data: announcement });
});

// 删除公告
app.delete('/api/admin/announcement/:id', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const idx = announcements.findIndex((a) => a.id === id);
  if (idx === -1) {
    return res.status(404).json({ status: 'error', message: '公告不存在' });
  }
  announcements.splice(idx, 1);
  console.log(`[DELETE] /api/admin/announcement/${id}`);
  res.json({ status: 'ok', message: '已删除' });
});

// 获取所有公告
app.get('/api/admin/announcements', (req, res) => {
  res.json({ status: 'ok', data: announcements });
});

// ============================================================
// 健康检查
// ============================================================
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// ============================================================
// 启动服务
// ============================================================
const PORT = process.env.PORT || 443;
const HOST = process.env.HOST || '0.0.0.0';

// 如果 443 需要 root，回退到 8080
const actualPort = PORT === 443 && process.getuid && process.getuid() !== 0 ? 8080 : PORT;

app.listen(actualPort, HOST, () => {
  console.log(`\n========================================`);
  console.log(`  likeuuu mock server 已启动`);
  console.log(`  http://${HOST}:${actualPort}`);
  console.log(`========================================`);
  console.log(`\n可用 API:`);
  console.log(`  GET  /api/app/config                    - 应用配置`);
  console.log(`  GET  /api/app/announcement/active        - 活跃公告`);
  console.log(`  GET  /api/app/announcement/active?limit=N - 限量公告`);
  console.log(`  GET  /api/location                       - 省份列表`);
  console.log(`  GET  /api/location/:adcode               - 行政区划详情`);
  console.log(`  GET  /api/location?adcode=XXX            - 行政区划详情`);
  console.log(`  GET  /api/location/provinces             - 省份列表`);
  console.log(`  GET  /api/location/cities?provinceAdcode=XXX - 城市列表`);
  console.log(`  GET  /api/location/districts?cityAdcode=XXX  - 区县列表`);
  console.log(`  GET  /api/health                         - 健康检查`);
  console.log(`\n管理接口:`);
  console.log(`  POST   /api/admin/announcement           - 添加公告`);
  console.log(`  DELETE /api/admin/announcement/:id       - 删除公告`);
  console.log(`  GET    /api/admin/announcements          - 所有公告`);
  console.log(`\n安卓客户端修改:`);
  console.log(`  将 classes2.dex 中的 https://zq.likeuuu.top/`);
  console.log(`  替换为 http://YOUR_SERVER_IP:${actualPort}/`);
  console.log(`========================================\n`);
});
