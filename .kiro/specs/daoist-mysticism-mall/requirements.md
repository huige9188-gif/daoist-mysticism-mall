# 需求文档 - 道家玄学商城系统

## 简介

道家玄学商城系统是一个专门为道家玄学产品和服务设计的电子商务平台。系统采用前后端分离架构，后端使用ThinkPHP 6.x框架，前端使用Vue.js + Element UI，提供完整的商城功能、内容管理、客服支持和支付集成。

## 术语表

- **System**: 道家玄学商城系统
- **Admin**: 系统管理员
- **User**: 注册用户/客户
- **Product**: 商品（道家玄学相关产品）
- **Category**: 商品分类
- **Order**: 订单
- **Video**: 教学视频
- **Article**: 资讯文章
- **Feng_Shui_Master**: 风水师
- **Chat_Session**: 客服会话
- **Payment_Gateway**: 支付网关（支付宝、微信支付、PayPal）
- **Dashboard**: 仪表盘
- **Backend_API**: 后端API服务
- **Frontend_UI**: 前端用户界面

## 需求

### 需求 1: 仪表盘数据展示

**用户故事:** 作为管理员，我想查看系统的关键数据统计，以便快速了解商城运营状况。

#### 验收标准

1. WHEN Admin访问仪表盘页面，THEN THE System SHALL显示总订单数、总销售额、总用户数和总商品数
2. WHEN Admin访问仪表盘页面，THEN THE System SHALL显示各订单状态的数量统计（待支付、待发货、已发货、已完成、已取消）
3. WHEN Admin访问仪表盘页面，THEN THE System SHALL显示最近10条订单记录，包含订单号、用户名、金额、状态和创建时间
4. WHEN Admin刷新仪表盘，THEN THE System SHALL在2秒内返回最新的统计数据

### 需求 2: 用户管理

**用户故事:** 作为管理员，我想管理系统用户，以便维护用户信息和控制用户访问权限。

#### 验收标准

1. WHEN Admin访问用户管理页面，THEN THE System SHALL显示所有用户列表，包含用户ID、用户名、邮箱、手机号、状态和注册时间
2. WHEN Admin创建新用户，THEN THE System SHALL验证用户名唯一性、邮箱格式和手机号格式
3. WHEN Admin编辑用户信息，THEN THE System SHALL保存修改并更新用户记录
4. WHEN Admin删除用户，THEN THE System SHALL标记用户为已删除状态而非物理删除
5. WHEN Admin启用或禁用用户，THEN THE System SHALL更新用户状态，禁用用户无法登录系统
6. WHEN Admin搜索用户，THEN THE System SHALL支持按用户名、邮箱或手机号进行模糊搜索

### 需求 3: 商品管理

**用户故事:** 作为管理员，我想管理商城商品，以便控制商品信息和销售状态。

#### 验收标准

1. WHEN Admin创建商品，THEN THE System SHALL要求填写商品名称、分类、价格、库存、描述和上传商品图片
2. WHEN Admin创建商品时价格为负数或零，THEN THE System SHALL拒绝创建并返回错误提示
3. WHEN Admin创建商品时库存为负数，THEN THE System SHALL拒绝创建并返回错误提示
4. WHEN Admin编辑商品信息，THEN THE System SHALL保存修改并更新商品记录
5. WHEN Admin删除商品，THEN THE System SHALL标记商品为已删除状态而非物理删除
6. WHEN Admin上架商品，THEN THE System SHALL将商品状态设置为上架，商品在前台可见
7. WHEN Admin下架商品，THEN THE System SHALL将商品状态设置为下架，商品在前台不可见
8. WHEN Admin搜索商品，THEN THE System SHALL支持按商品名称或分类进行搜索

### 需求 4: 商品分类管理

**用户故事:** 作为管理员，我想管理商品分类，以便组织商品结构和方便用户浏览。

#### 验收标准

1. WHEN Admin创建分类，THEN THE System SHALL要求填写分类名称、排序值和状态
2. WHEN Admin创建分类时名称为空，THEN THE System SHALL拒绝创建并返回错误提示
3. WHEN Admin编辑分类信息，THEN THE System SHALL保存修改并更新分类记录
4. WHEN Admin删除分类，THEN THE System SHALL检查该分类下是否有商品，如有商品则拒绝删除
5. WHEN Admin调整分类排序值，THEN THE System SHALL按排序值从小到大显示分类列表
6. WHEN Admin启用或禁用分类，THEN THE System SHALL更新分类状态，禁用分类在前台不显示

### 需求 5: 订单管理

**用户故事:** 作为管理员，我想管理订单，以便处理客户购买请求和跟踪订单状态。

#### 验收标准

1. WHEN Admin访问订单管理页面，THEN THE System SHALL显示所有订单列表，包含订单号、用户名、商品信息、金额、状态和创建时间
2. WHEN Admin查看订单详情，THEN THE System SHALL显示完整的订单信息，包括收货地址、支付方式和订单商品明细
3. WHEN Admin对待发货订单执行发货操作，THEN THE System SHALL更新订单状态为已发货并记录发货时间
4. WHEN Admin对待发货订单执行发货操作，THEN THE System SHALL要求填写物流公司和物流单号
5. WHEN Admin取消订单，THEN THE System SHALL更新订单状态为已取消并恢复商品库存
6. WHEN Admin取消已支付订单，THEN THE System SHALL触发退款流程
7. WHEN Admin搜索订单，THEN THE System SHALL支持按订单号、用户名或订单状态进行搜索
8. WHEN Admin筛选订单，THEN THE System SHALL支持按日期范围筛选订单

### 需求 6: 视频管理

**用户故事:** 作为管理员，我想管理教学视频，以便为用户提供道家玄学相关的教学内容。

#### 验收标准

1. WHEN Admin创建视频，THEN THE System SHALL要求填写视频标题、描述、视频URL、封面图片和状态
2. WHEN Admin创建视频时标题为空，THEN THE System SHALL拒绝创建并返回错误提示
3. WHEN Admin创建视频时视频URL为空，THEN THE System SHALL拒绝创建并返回错误提示
4. WHEN Admin编辑视频信息，THEN THE System SHALL保存修改并更新视频记录
5. WHEN Admin删除视频，THEN THE System SHALL标记视频为已删除状态而非物理删除
6. WHEN Admin启用或禁用视频，THEN THE System SHALL更新视频状态，禁用视频在前台不显示

### 需求 7: 资讯管理

**用户故事:** 作为管理员，我想管理资讯文章，以便发布道家玄学相关的新闻和知识内容。

#### 验收标准

1. WHEN Admin创建文章，THEN THE System SHALL要求填写文章标题、内容、封面图片、作者和状态
2. WHEN Admin创建文章时标题为空，THEN THE System SHALL拒绝创建并返回错误提示
3. WHEN Admin创建文章时内容为空，THEN THE System SHALL拒绝创建并返回错误提示
4. WHEN Admin编辑文章信息，THEN THE System SHALL保存修改并更新文章记录
5. WHEN Admin删除文章，THEN THE System SHALL标记文章为已删除状态而非物理删除
6. WHEN Admin发布文章，THEN THE System SHALL将文章状态设置为已发布，文章在前台可见
7. WHEN Admin撤回文章，THEN THE System SHALL将文章状态设置为草稿，文章在前台不可见

### 需求 8: 风水师管理

**用户故事:** 作为管理员，我想管理风水师信息，以便展示专业风水师资料供用户查看和咨询。

#### 验收标准

1. WHEN Admin创建风水师，THEN THE System SHALL要求填写姓名、简介、专长、联系方式、头像和状态
2. WHEN Admin创建风水师时姓名为空，THEN THE System SHALL拒绝创建并返回错误提示
3. WHEN Admin编辑风水师信息，THEN THE System SHALL保存修改并更新风水师记录
4. WHEN Admin删除风水师，THEN THE System SHALL标记风水师为已删除状态而非物理删除
5. WHEN Admin启用或禁用风水师，THEN THE System SHALL更新风水师状态，禁用风水师在前台不显示

### 需求 9: 支付配置管理

**用户故事:** 作为管理员，我想配置支付网关，以便系统支持多种支付方式。

#### 验收标准

1. WHEN Admin配置支付宝，THEN THE System SHALL要求填写App ID、私钥、公钥和回调URL
2. WHEN Admin配置微信支付，THEN THE System SHALL要求填写App ID、商户号、API密钥和回调URL
3. WHEN Admin配置PayPal，THEN THE System SHALL要求填写Client ID、Secret和回调URL
4. WHEN Admin保存支付配置，THEN THE System SHALL验证必填字段完整性
5. WHEN Admin启用支付方式，THEN THE System SHALL将该支付方式在前台支付页面显示
6. WHEN Admin禁用支付方式，THEN THE System SHALL将该支付方式在前台支付页面隐藏

### 需求 10: 客服管理

**用户故事:** 作为管理员或客服人员，我想与用户进行实时聊天，以便解答用户疑问和提供咨询服务。

#### 验收标准

1. WHEN User发起聊天请求，THEN THE System SHALL创建新的聊天会话并通知在线客服
2. WHEN Admin或客服发送消息，THEN THE System SHALL在1秒内将消息推送给User
3. WHEN User发送消息，THEN THE System SHALL在1秒内将消息推送给Admin或客服
4. WHEN Admin访问客服管理页面，THEN THE System SHALL显示所有活跃会话列表，包含用户名、最后消息和会话状态
5. WHEN Admin选择会话，THEN THE System SHALL显示该会话的完整聊天记录
6. WHEN Admin结束会话，THEN THE System SHALL更新会话状态为已结束
7. WHEN 会话超过30分钟无消息，THEN THE System SHALL自动将会话状态设置为不活跃

### 需求 11: 用户认证和授权

**用户故事:** 作为系统，我需要验证用户身份和权限，以便保护系统资源和数据安全。

#### 验收标准

1. WHEN User提交登录请求，THEN THE System SHALL验证用户名和密码的正确性
2. WHEN 登录凭证正确，THEN THE System SHALL生成JWT令牌并返回给User
3. WHEN 登录凭证错误，THEN THE System SHALL返回401错误和错误提示
4. WHEN User访问受保护的API端点，THEN THE System SHALL验证JWT令牌的有效性
5. WHEN JWT令牌无效或过期，THEN THE System SHALL返回401错误并要求重新登录
6. WHEN Admin访问管理功能，THEN THE System SHALL验证Admin角色权限
7. WHEN User尝试访问Admin功能，THEN THE System SHALL返回403错误拒绝访问

### 需求 12: 数据持久化

**用户故事:** 作为系统，我需要可靠地存储和检索数据，以便保证数据完整性和一致性。

#### 验收标准

1. WHEN System保存数据到数据库，THEN THE System SHALL使用事务确保数据一致性
2. WHEN 数据库操作失败，THEN THE System SHALL回滚事务并返回错误信息
3. WHEN System查询数据，THEN THE System SHALL返回最新的数据记录
4. WHEN System更新数据，THEN THE System SHALL记录更新时间戳
5. WHEN System删除数据，THEN THE System SHALL使用软删除标记而非物理删除

### 需求 13: API接口规范

**用户故事:** 作为前端开发者，我需要清晰的API接口规范，以便正确调用后端服务。

#### 验收标准

1. THE Backend_API SHALL遵循RESTful设计原则
2. THE Backend_API SHALL使用JSON格式进行数据交换
3. THE Backend_API SHALL返回统一的响应格式，包含code、message和data字段
4. WHEN API请求成功，THEN THE Backend_API SHALL返回200状态码和成功标识
5. WHEN API请求失败，THEN THE Backend_API SHALL返回相应的HTTP状态码和错误信息
6. THE Backend_API SHALL支持跨域请求（CORS）
7. THE Backend_API SHALL在响应头中包含Content-Type: application/json

### 需求 14: 前端用户界面

**用户故事:** 作为用户，我需要友好的用户界面，以便方便地使用系统功能。

#### 验收标准

1. THE Frontend_UI SHALL使用Vue.js框架构建单页应用
2. THE Frontend_UI SHALL使用Element UI组件库提供一致的视觉体验
3. WHEN User执行操作，THEN THE Frontend_UI SHALL显示加载状态指示器
4. WHEN API返回错误，THEN THE Frontend_UI SHALL显示友好的错误提示消息
5. WHEN User执行成功操作，THEN THE Frontend_UI SHALL显示成功提示消息
6. THE Frontend_UI SHALL支持响应式布局，适配不同屏幕尺寸
7. THE Frontend_UI SHALL在表单中提供客户端验证，减少无效请求

### 需求 15: 文件上传管理

**用户故事:** 作为管理员，我需要上传图片和文件，以便为商品、文章和其他内容添加媒体资源。

#### 验收标准

1. WHEN Admin上传图片文件，THEN THE System SHALL验证文件类型为jpg、jpeg、png或gif
2. WHEN Admin上传图片文件，THEN THE System SHALL验证文件大小不超过5MB
3. WHEN 文件验证通过，THEN THE System SHALL保存文件到服务器并返回文件访问URL
4. WHEN 文件验证失败，THEN THE System SHALL返回错误信息并拒绝上传
5. THE System SHALL为上传的文件生成唯一的文件名以避免冲突
6. THE System SHALL将文件按日期组织到不同的目录中
