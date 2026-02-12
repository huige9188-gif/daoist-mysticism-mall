# 道家玄学商城系统 - 前端

基于Vue.js 3.x + Element Plus开发的单页应用。

## 环境要求

- Node.js >= 16.0
- npm >= 8.0

## 安装步骤

1. 安装依赖
```bash
npm install
```

2. 启动开发服务器
```bash
npm run dev
```

应用将在 http://localhost:3000 启动

3. 构建生产版本
```bash
npm run build
```

## 项目结构

```
frontend/
├── src/
│   ├── views/              # 页面组件
│   ├── components/         # 通用组件
│   ├── router/             # 路由配置
│   ├── store/              # Vuex状态管理
│   ├── api/                # API接口封装
│   ├── utils/              # 工具函数
│   ├── layouts/            # 布局组件
│   └── assets/             # 静态资源
├── public/                 # 公共文件
└── index.html              # HTML模板
```

## 技术栈

- Vue.js 3.x - 渐进式JavaScript框架
- Vue Router - 官方路由管理器
- Vuex - 状态管理
- Element Plus - UI组件库
- Axios - HTTP客户端
- Socket.io-client - WebSocket客户端
- Vite - 构建工具

## 功能模块

### 管理后台
- 仪表盘 - 数据统计展示
- 用户管理 - 用户CRUD操作
- 商品分类 - 分类管理
- 商品管理 - 商品CRUD和上下架
- 订单管理 - 订单处理和发货
- 视频管理 - 教学视频管理
- 资讯管理 - 文章发布管理
- 风水师管理 - 风水师信息管理
- 支付配置 - 支付网关配置
- 客服管理 - 实时聊天管理

### 用户界面
- 商品展示 - 商品浏览和搜索
- 购物车 - 购物车管理
- 订单结算 - 下单和支付
- 内容浏览 - 视频、文章、风水师信息
- 在线客服 - 实时聊天咨询

## 开发规范

- 使用组合式API (Composition API)
- 遵循Vue.js风格指南
- 使用ESLint进行代码检查
- 组件命名使用PascalCase
- 文件命名使用kebab-case

## 测试

运行测试：
```bash
npm run test
```

## 许可证

MIT
