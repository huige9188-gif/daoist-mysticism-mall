# 设计文档 - 道家玄学商城系统

## 概述

道家玄学商城系统采用前后端分离架构，后端使用ThinkPHP 6.x框架提供RESTful API服务，前端使用Vue.js + Element UI构建单页应用。系统支持商品管理、订单处理、内容发布、实时客服和多种支付方式集成。

### 技术栈

**后端:**
- ThinkPHP 6.x
- MySQL 8.0
- Redis（缓存和会话管理）
- JWT（身份认证）
- WebSocket（实时聊天）

**前端:**
- Vue.js 3.x
- Vue Router
- Vuex（状态管理）
- Element UI
- Axios（HTTP客户端）
- Socket.io-client（WebSocket客户端）

## 架构

### 系统架构图

```
┌─────────────────────────────────────────────────────────┐
│                      前端层 (Vue.js)                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ 管理后台  │  │ 用户界面  │  │ 商品展示  │  │ 客服聊天 │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          │ HTTP/WebSocket
┌─────────────────────────────────────────────────────────┐
│                   API网关层 (ThinkPHP)                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ 路由控制  │  │ 中间件    │  │ 认证授权  │  │ 异常处理 │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          │
┌─────────────────────────────────────────────────────────┐
│                      业务逻辑层                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ 用户服务  │  │ 商品服务  │  │ 订单服务  │  │ 支付服务 │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ 内容服务  │  │ 客服服务  │  │ 文件服务  │  │ 统计服务 │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          │
┌─────────────────────────────────────────────────────────┐
│                      数据访问层                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │ MySQL    │  │ Redis    │  │ 文件存储  │              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
```

### 目录结构

**后端目录结构 (ThinkPHP):**
```
backend/
├── app/
│   ├── controller/          # 控制器
│   │   ├── admin/          # 管理后台控制器
│   │   └── api/            # API控制器
│   ├── model/              # 模型
│   ├── service/            # 业务逻辑服务
│   ├── middleware/         # 中间件
│   └── validate/           # 验证器
├── config/                 # 配置文件
├── route/                  # 路由定义
└── public/                 # 公共资源
```

**前端目录结构 (Vue.js):**
```
frontend/
├── src/
│   ├── views/              # 页面组件
│   ├── components/         # 通用组件
│   ├── router/             # 路由配置
│   ├── store/              # Vuex状态管理
│   ├── api/                # API接口封装
│   ├── utils/              # 工具函数
│   └── assets/             # 静态资源
└── public/                 # 公共文件
```

## 组件和接口

### 核心组件

#### 1. 认证中间件 (AuthMiddleware)


**职责:** 验证JWT令牌，提取用户信息，检查权限

**接口:**
```php
class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 从请求头获取token
        token = request.header('Authorization')
        
        // 验证token有效性
        if not isValidToken(token):
            return response(401, "未授权访问")
        
        // 解析token获取用户信息
        user = decodeToken(token)
        
        // 将用户信息注入请求
        request.user = user
        
        return next(request)
    }
}
```

#### 2. 用户服务 (UserService)

**职责:** 处理用户相关业务逻辑

**接口:**
```php
class UserService
{
    public function createUser(data)
    {
        // 验证用户名唯一性
        if User.exists(username = data.username):
            throw Exception("用户名已存在")
        
        // 验证邮箱格式
        if not isValidEmail(data.email):
            throw Exception("邮箱格式不正确")
        
        // 加密密码
        data.password = hashPassword(data.password)
        
        // 创建用户
        user = User.create(data)
        return user
    }
    
    public function updateUser(id, data)
    {
        user = User.find(id)
        if not user:
            throw Exception("用户不存在")
        
        user.update(data)
        return user
    }
    
    public function deleteUser(id)
    {
        user = User.find(id)
        if not user:
            throw Exception("用户不存在")
        
        // 软删除
        user.deleted_at = now()
        user.save()
    }
    
    public function getUserList(page, pageSize, search)
    {
        query = User.query()
        
        if search:
            query.where('username', 'like', '%' + search + '%')
                 .orWhere('email', 'like', '%' + search + '%')
        
        return query.paginate(page, pageSize)
    }
}
```

#### 3. 商品服务 (ProductService)

**职责:** 处理商品相关业务逻辑

**接口:**
```php
class ProductService
{
    public function createProduct(data)
    {
        // 验证价格
        if data.price <= 0:
            throw Exception("价格必须大于0")
        
        // 验证库存
        if data.stock < 0:
            throw Exception("库存不能为负数")
        
        // 验证分类存在
        if not Category.exists(id = data.category_id):
            throw Exception("分类不存在")
        
        product = Product.create(data)
        return product
    }
    
    public function updateProduct(id, data)
    {
        product = Product.find(id)
        if not product:
            throw Exception("商品不存在")
        
        product.update(data)
        return product
    }
    
    public function deleteProduct(id)
    {
        product = Product.find(id)
        if not product:
            throw Exception("商品不存在")
        
        product.deleted_at = now()
        product.save()
    }
    
    public function updateStatus(id, status)
    {
        product = Product.find(id)
        if not product:
            throw Exception("商品不存在")
        
        product.status = status
        product.save()
        return product
    }
}
```

#### 4. 订单服务 (OrderService)

**职责:** 处理订单相关业务逻辑

**接口:**
```php
class OrderService
{
    public function createOrder(userId, items, address)
    {
        // 开始事务
        beginTransaction()
        
        try:
            // 计算总金额
            totalAmount = 0
            for item in items:
                product = Product.find(item.product_id)
                
                // 检查库存
                if product.stock < item.quantity:
                    throw Exception("商品库存不足")
                
                totalAmount += product.price * item.quantity
                
                // 减少库存
                product.stock -= item.quantity
                product.save()
            
            // 创建订单
            order = Order.create({
                user_id: userId,
                total_amount: totalAmount,
                status: 'pending',
                address: address
            })
            
            // 创建订单明细
            for item in items:
                OrderItem.create({
                    order_id: order.id,
                    product_id: item.product_id,
                    quantity: item.quantity,
                    price: Product.find(item.product_id).price
                })
            
            commit()
            return order
            
        catch Exception as e:
            rollback()
            throw e
    }
    
    public function shipOrder(id, logistics)
    {
        order = Order.find(id)
        if not order:
            throw Exception("订单不存在")
        
        if order.status != 'paid':
            throw Exception("订单状态不正确")
        
        order.status = 'shipped'
        order.logistics_company = logistics.company
        order.logistics_number = logistics.number
        order.shipped_at = now()
        order.save()
        
        return order
    }
    
    public function cancelOrder(id)
    {
        order = Order.find(id)
        if not order:
            throw Exception("订单不存在")
        
        beginTransaction()
        
        try:
            // 恢复库存
            items = OrderItem.where('order_id', id).get()
            for item in items:
                product = Product.find(item.product_id)
                product.stock += item.quantity
                product.save()
            
            // 更新订单状态
            order.status = 'cancelled'
            order.save()
            
            // 如果已支付，触发退款
            if order.paid_at:
                PaymentService.refund(order)
            
            commit()
            return order
            
        catch Exception as e:
            rollback()
            throw e
    }
}
```

#### 5. 支付服务 (PaymentService)

**职责:** 处理支付相关业务逻辑

**接口:**
```php
class PaymentService
{
    public function createPayment(orderId, gateway)
    {
        order = Order.find(orderId)
        if not order:
            throw Exception("订单不存在")
        
        // 根据支付网关创建支付
        if gateway == 'alipay':
            return AlipayGateway.createPayment(order)
        else if gateway == 'wechat':
            return WechatGateway.createPayment(order)
        else if gateway == 'paypal':
            return PaypalGateway.createPayment(order)
        else:
            throw Exception("不支持的支付方式")
    }
    
    public function handleCallback(gateway, data)
    {
        // 验证回调签名
        if not verifySignature(gateway, data):
            throw Exception("签名验证失败")
        
        // 更新订单状态
        order = Order.find(data.order_id)
        order.status = 'paid'
        order.paid_at = now()
        order.save()
        
        return order
    }
    
    public function refund(order)
    {
        // 调用支付网关退款接口
        gateway = order.payment_gateway
        
        if gateway == 'alipay':
            return AlipayGateway.refund(order)
        else if gateway == 'wechat':
            return WechatGateway.refund(order)
        else if gateway == 'paypal':
            return PaypalGateway.refund(order)
    }
}
```

#### 6. 客服服务 (ChatService)

**职责:** 处理实时聊天业务逻辑

**接口:**
```php
class ChatService
{
    public function createSession(userId)
    {
        // 创建聊天会话
        session = ChatSession.create({
            user_id: userId,
            status: 'active',
            started_at: now()
        })
        
        // 通知在线客服
        notifyAdmins(session)
        
        return session
    }
    
    public function sendMessage(sessionId, senderId, content)
    {
        session = ChatSession.find(sessionId)
        if not session:
            throw Exception("会话不存在")
        
        // 创建消息
        message = ChatMessage.create({
            session_id: sessionId,
            sender_id: senderId,
            content: content,
            created_at: now()
        })
        
        // 通过WebSocket推送消息
        WebSocketServer.broadcast(sessionId, message)
        
        // 更新会话最后活跃时间
        session.last_activity_at = now()
        session.save()
        
        return message
    }
    
    public function closeSession(sessionId)
    {
        session = ChatSession.find(sessionId)
        if not session:
            throw Exception("会话不存在")
        
        session.status = 'closed'
        session.closed_at = now()
        session.save()
    }
    
    public function checkInactiveSessions()
    {
        // 查找30分钟无活动的会话
        threshold = now() - 30 minutes
        sessions = ChatSession.where('status', 'active')
                              .where('last_activity_at', '<', threshold)
                              .get()
        
        for session in sessions:
            session.status = 'inactive'
            session.save()
    }
}
```

#### 7. 文件服务 (FileService)

**职责:** 处理文件上传和管理

**接口:**
```php
class FileService
{
    public function uploadImage(file)
    {
        // 验证文件类型
        allowedTypes = ['jpg', 'jpeg', 'png', 'gif']
        if file.extension not in allowedTypes:
            throw Exception("不支持的文件类型")
        
        // 验证文件大小（5MB）
        if file.size > 5 * 1024 * 1024:
            throw Exception("文件大小超过限制")
        
        // 生成唯一文件名
        filename = generateUniqueFilename(file.extension)
        
        // 按日期组织目录
        directory = 'uploads/' + date('Y/m/d')
        
        // 保存文件
        path = directory + '/' + filename
        file.save(path)
        
        // 返回访问URL
        return {
            url: baseUrl + '/' + path,
            path: path
        }
    }
}
```

#### 8. 统计服务 (StatisticsService)

**职责:** 提供数据统计功能

**接口:**
```php
class StatisticsService
{
    public function getDashboardData()
    {
        return {
            totalOrders: Order.count(),
            totalSales: Order.where('status', 'completed').sum('total_amount'),
            totalUsers: User.count(),
            totalProducts: Product.count(),
            orderStatusCounts: {
                pending: Order.where('status', 'pending').count(),
                paid: Order.where('status', 'paid').count(),
                shipped: Order.where('status', 'shipped').count(),
                completed: Order.where('status', 'completed').count(),
                cancelled: Order.where('status', 'cancelled').count()
            },
            recentOrders: Order.orderBy('created_at', 'desc').limit(10).get()
        }
    }
}
```

## 数据模型

### 数据库表设计

#### 用户表 (users)

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status TINYINT DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 商品分类表 (categories)

```sql
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    status TINYINT DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_sort_order (sort_order),
    INDEX idx_status (status)
);
```

#### 商品表 (products)

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    images JSON COMMENT '商品图片数组',
    status ENUM('on_sale', 'off_sale') DEFAULT 'off_sale',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 订单表 (orders)

```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(50) UNIQUE NOT NULL,
    user_id BIGINT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    payment_gateway VARCHAR(20),
    address JSON COMMENT '收货地址',
    logistics_company VARCHAR(50),
    logistics_number VARCHAR(100),
    paid_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_order_no (order_no),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

#### 订单明细表 (order_items)

```sql
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order_id (order_id)
);
```

#### 视频表 (videos)

```sql
CREATE TABLE videos (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    cover_image VARCHAR(500),
    status TINYINT DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 文章表 (articles)

```sql
CREATE TABLE articles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    cover_image VARCHAR(500),
    author VARCHAR(100),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 风水师表 (feng_shui_masters)

```sql
CREATE TABLE feng_shui_masters (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio TEXT,
    specialty VARCHAR(200),
    contact VARCHAR(100),
    avatar VARCHAR(500),
    status TINYINT DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
);
```

#### 支付配置表 (payment_configs)

```sql
CREATE TABLE payment_configs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    gateway VARCHAR(20) UNIQUE NOT NULL COMMENT 'alipay, wechat, paypal',
    config JSON NOT NULL COMMENT '支付配置信息',
    status TINYINT DEFAULT 1 COMMENT '1:启用 0:禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gateway (gateway)
);
```

#### 聊天会话表 (chat_sessions)

```sql
CREATE TABLE chat_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_last_activity_at (last_activity_at)
);
```

#### 聊天消息表 (chat_messages)

```sql
CREATE TABLE chat_messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    session_id BIGINT NOT NULL,
    sender_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
);
```

### 数据模型关系

```
User 1 ─── N Order
User 1 ─── N ChatSession
Category 1 ─── N Product
Product N ─── N Order (through OrderItem)
Order 1 ─── N OrderItem
ChatSession 1 ─── N ChatMessage
```


## 正确性属性

*属性是一个特征或行为，应该在系统的所有有效执行中保持为真——本质上是关于系统应该做什么的形式化陈述。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 仪表盘统计属性

**属性 1: 统计数据准确性**
*对于任意* 数据库状态，仪表盘显示的总订单数应该等于数据库中订单表的记录总数，总用户数应该等于用户表的记录总数，总商品数应该等于商品表的记录总数
**验证需求: 1.1**

**属性 2: 订单状态统计准确性**
*对于任意* 数据库状态，每个订单状态的统计数量应该等于该状态在订单表中的实际记录数，且所有状态的数量之和应该等于总订单数
**验证需求: 1.2**

**属性 3: 最近订单列表正确性**
*对于任意* 包含N个订单的数据库（N >= 10），最近订单列表应该返回最新的10条订单，且按创建时间降序排列
**验证需求: 1.3**

### 用户管理属性

**属性 4: 用户名唯一性约束**
*对于任意* 已存在的用户名，尝试创建具有相同用户名的新用户应该被拒绝并返回错误
**验证需求: 2.2**

**属性 5: 邮箱格式验证**
*对于任意* 不符合邮箱格式的字符串，使用该字符串作为邮箱创建用户应该被拒绝
**验证需求: 2.2**

**属性 6: 用户更新幂等性**
*对于任意* 用户和更新数据，多次使用相同数据更新该用户应该产生相同的结果
**验证需求: 2.3**

**属性 7: 软删除不变量**
*对于任意* 用户，删除操作后该用户记录应该仍然存在于数据库中，但deleted_at字段应该被设置为当前时间戳
**验证需求: 2.4**

**属性 8: 禁用用户无法登录**
*对于任意* 被禁用的用户，使用正确的用户名和密码尝试登录应该被拒绝
**验证需求: 2.5**

**属性 9: 用户搜索完整性**
*对于任意* 搜索关键词，搜索结果应该包含所有用户名、邮箱或手机号中包含该关键词的用户
**验证需求: 2.6**

### 商品管理属性

**属性 10: 商品价格正值约束**
*对于任意* 价格小于或等于零的商品数据，创建商品操作应该被拒绝并返回错误
**验证需求: 3.2**

**属性 11: 商品库存非负约束**
*对于任意* 库存为负数的商品数据，创建商品操作应该被拒绝并返回错误
**验证需求: 3.3**

**属性 12: 商品软删除不变量**
*对于任意* 商品，删除操作后该商品记录应该仍然存在于数据库中，但deleted_at字段应该被设置
**验证需求: 3.5**

**属性 13: 上架商品可见性**
*对于任意* 上架状态的商品，该商品应该出现在前台商品列表查询结果中
**验证需求: 3.6**

**属性 14: 下架商品不可见性**
*对于任意* 下架状态的商品，该商品不应该出现在前台商品列表查询结果中
**验证需求: 3.7**

### 商品分类属性

**属性 15: 分类名称非空约束**
*对于任意* 空字符串或仅包含空白字符的分类名称，创建分类操作应该被拒绝
**验证需求: 4.2**

**属性 16: 分类删除约束**
*对于任意* 包含商品的分类，删除操作应该被拒绝并返回错误
**验证需求: 4.4**

**属性 17: 分类排序正确性**
*对于任意* 分类列表，返回的分类应该按照sort_order字段从小到大排序
**验证需求: 4.5**

**属性 18: 禁用分类不可见性**
*对于任意* 禁用状态的分类，该分类不应该出现在前台分类列表中
**验证需求: 4.6**

### 订单管理属性

**属性 19: 订单创建库存扣减**
*对于任意* 订单和订单商品，成功创建订单后，每个商品的库存应该减少相应的订单数量
**验证需求: 5.5**

**属性 20: 库存不足拒绝订单**
*对于任意* 商品和数量，如果数量大于商品当前库存，创建包含该商品的订单应该被拒绝
**验证需求: 5.5**

**属性 21: 发货状态转换**
*对于任意* 待发货状态的订单，执行发货操作后订单状态应该变为已发货，且shipped_at字段应该被设置
**验证需求: 5.3**

**属性 22: 发货物流信息必填**
*对于任意* 发货操作，如果缺少物流公司或物流单号，操作应该被拒绝
**验证需求: 5.4**

**属性 23: 取消订单库存恢复**
*对于任意* 订单，取消订单后每个订单商品的库存应该增加相应的订单数量
**验证需求: 5.5**

**属性 24: 已支付订单取消触发退款**
*对于任意* 已支付的订单，取消操作应该触发退款流程
**验证需求: 5.6**

### 内容管理属性

**属性 25: 视频标题非空约束**
*对于任意* 空字符串作为标题，创建视频操作应该被拒绝
**验证需求: 6.2**

**属性 26: 视频URL非空约束**
*对于任意* 空字符串作为视频URL，创建视频操作应该被拒绝
**验证需求: 6.3**

**属性 27: 文章标题非空约束**
*对于任意* 空字符串作为标题，创建文章操作应该被拒绝
**验证需求: 7.2**

**属性 28: 文章内容非空约束**
*对于任意* 空字符串作为内容，创建文章操作应该被拒绝
**验证需求: 7.3**

**属性 29: 已发布文章可见性**
*对于任意* 已发布状态的文章，该文章应该出现在前台文章列表中
**验证需求: 7.6**

**属性 30: 草稿文章不可见性**
*对于任意* 草稿状态的文章，该文章不应该出现在前台文章列表中
**验证需求: 7.7**

### 风水师管理属性

**属性 31: 风水师姓名非空约束**
*对于任意* 空字符串作为姓名，创建风水师操作应该被拒绝
**验证需求: 8.2**

**属性 32: 禁用风水师不可见性**
*对于任意* 禁用状态的风水师，该风水师不应该出现在前台风水师列表中
**验证需求: 8.5**

### 支付配置属性

**属性 33: 支付配置必填字段验证**
*对于任意* 支付网关配置，如果缺少该网关要求的必填字段，保存操作应该被拒绝
**验证需求: 9.1, 9.2, 9.3, 9.4**

**属性 34: 启用支付方式可见性**
*对于任意* 启用状态的支付方式，该支付方式应该出现在前台支付页面的支付选项中
**验证需求: 9.5**

**属性 35: 禁用支付方式不可见性**
*对于任意* 禁用状态的支付方式，该支付方式不应该出现在前台支付页面的支付选项中
**验证需求: 9.6**

### 客服管理属性

**属性 36: 聊天会话创建**
*对于任意* 用户发起的聊天请求，系统应该创建一个新的活跃状态的聊天会话
**验证需求: 10.1**

**属性 37: 活跃会话列表正确性**
*对于任意* 数据库状态，活跃会话列表应该只包含状态为active的会话
**验证需求: 10.4**

**属性 38: 会话聊天记录完整性**
*对于任意* 会话，该会话的聊天记录应该包含所有属于该会话的消息，且按时间顺序排列
**验证需求: 10.5**

**属性 39: 结束会话状态转换**
*对于任意* 活跃会话，执行结束操作后会话状态应该变为closed，且closed_at字段应该被设置
**验证需求: 10.6**

**属性 40: 超时会话自动转换**
*对于任意* 最后活动时间超过30分钟的活跃会话，系统应该将其状态设置为inactive
**验证需求: 10.7**

### 认证和授权属性

**属性 41: 正确凭证生成令牌**
*对于任意* 有效的用户名和正确的密码，登录操作应该返回有效的JWT令牌
**验证需求: 11.2**

**属性 42: 错误凭证拒绝登录**
*对于任意* 用户名和错误的密码，登录操作应该返回401错误
**验证需求: 11.3**

**属性 43: 有效令牌访问授权**
*对于任意* 有效的JWT令牌，访问受保护的API端点应该被允许
**验证需求: 11.4**

**属性 44: 无效令牌拒绝访问**
*对于任意* 无效或过期的JWT令牌，访问受保护的API端点应该返回401错误
**验证需求: 11.5**

**属性 45: Admin角色权限验证**
*对于任意* 具有admin角色的用户，访问管理功能应该被允许
**验证需求: 11.6**

**属性 46: 普通用户权限限制**
*对于任意* 具有user角色的用户，访问管理功能应该返回403错误
**验证需求: 11.7**

### 数据持久化属性

**属性 47: 事务回滚一致性**
*对于任意* 在事务中执行的操作序列，如果任何操作失败，所有操作的效果应该被回滚
**验证需求: 12.1, 12.2**

**属性 48: 更新时间戳自动更新**
*对于任意* 数据记录的更新操作，updated_at字段应该被自动更新为当前时间
**验证需求: 12.4**

**属性 49: 软删除数据保留**
*对于任意* 数据记录的删除操作，该记录应该仍然存在于数据库中，但deleted_at字段应该被设置
**验证需求: 12.5**

### API接口属性

**属性 50: JSON响应格式**
*对于任意* API请求，响应应该是有效的JSON格式
**验证需求: 13.2**

**属性 51: 统一响应结构**
*对于任意* API请求，响应应该包含code、message和data字段
**验证需求: 13.3**

**属性 52: 成功响应状态码**
*对于任意* 成功的API请求，响应状态码应该是200，且code字段应该表示成功
**验证需求: 13.4**

**属性 53: 失败响应状态码**
*对于任意* 失败的API请求，响应状态码应该是相应的HTTP错误码（如400、401、403、404、500），且message字段应该包含错误描述
**验证需求: 13.5**

**属性 54: CORS头存在性**
*对于任意* API请求，响应头应该包含Access-Control-Allow-Origin字段
**验证需求: 13.6**

**属性 55: Content-Type头正确性**
*对于任意* API请求，响应头应该包含Content-Type: application/json
**验证需求: 13.7**

### 文件上传属性

**属性 56: 文件类型验证**
*对于任意* 文件扩展名不是jpg、jpeg、png或gif的文件，上传操作应该被拒绝
**验证需求: 15.1**

**属性 57: 文件大小验证**
*对于任意* 大小超过5MB的文件，上传操作应该被拒绝
**验证需求: 15.2**

**属性 58: 成功上传返回URL**
*对于任意* 通过验证的文件，上传成功后应该返回可访问的文件URL
**验证需求: 15.3**

**属性 59: 文件名唯一性**
*对于任意* 两次上传操作（即使是相同文件），生成的文件名应该不同
**验证需求: 15.5**

**属性 60: 文件目录组织**
*对于任意* 上传的文件，文件路径应该包含上传日期的年/月/日目录结构
**验证需求: 15.6**

## 错误处理

### 错误类型

系统定义以下错误类型：

1. **验证错误 (ValidationError)**: 输入数据不符合验证规则
2. **认证错误 (AuthenticationError)**: 用户身份验证失败
3. **授权错误 (AuthorizationError)**: 用户无权限执行操作
4. **资源不存在错误 (NotFoundError)**: 请求的资源不存在
5. **业务逻辑错误 (BusinessError)**: 违反业务规则
6. **系统错误 (SystemError)**: 系统内部错误

### 错误响应格式

所有错误响应遵循统一格式：

```json
{
    "code": 错误码,
    "message": "错误描述",
    "data": null,
    "errors": {
        "field": ["具体错误信息"]
    }
}
```

### HTTP状态码映射

- 200: 成功
- 400: 验证错误、业务逻辑错误
- 401: 认证错误
- 403: 授权错误
- 404: 资源不存在错误
- 500: 系统错误

### 错误处理策略

**后端错误处理:**
```php
class ExceptionHandler
{
    public function handle(Exception $e)
    {
        if e instanceof ValidationException:
            return response(400, {
                code: 400,
                message: "验证失败",
                errors: e.errors
            })
        
        if e instanceof AuthenticationException:
            return response(401, {
                code: 401,
                message: "未授权访问"
            })
        
        if e instanceof AuthorizationException:
            return response(403, {
                code: 403,
                message: "无权限访问"
            })
        
        if e instanceof NotFoundException:
            return response(404, {
                code: 404,
                message: "资源不存在"
            })
        
        if e instanceof BusinessException:
            return response(400, {
                code: 400,
                message: e.message
            })
        
        // 系统错误
        logError(e)
        return response(500, {
            code: 500,
            message: "系统错误"
        })
    }
}
```

**前端错误处理:**
```javascript
// Axios拦截器
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            const { status, data } = error.response
            
            if (status === 401) {
                // 跳转到登录页
                router.push('/login')
                Message.error('请先登录')
            } else if (status === 403) {
                Message.error('无权限访问')
            } else if (status === 404) {
                Message.error('资源不存在')
            } else if (status === 400) {
                Message.error(data.message || '请求失败')
            } else {
                Message.error('系统错误，请稍后重试')
            }
        } else {
            Message.error('网络错误，请检查网络连接')
        }
        
        return Promise.reject(error)
    }
)
```

## 测试策略

### 双重测试方法

系统采用单元测试和基于属性的测试相结合的方法：

- **单元测试**: 验证特定示例、边界条件和错误情况
- **基于属性的测试**: 验证跨所有输入的通用属性
- 两者互补，共同确保全面覆盖

### 单元测试平衡

单元测试应该专注于：
- 演示正确行为的特定示例
- 组件之间的集成点
- 边界条件和错误情况

基于属性的测试应该专注于：
- 对所有输入都成立的通用属性
- 通过随机化实现全面的输入覆盖

### 基于属性的测试配置

**测试库选择:**
- PHP后端: 使用 **Eris** 库进行基于属性的测试
- JavaScript前端: 使用 **fast-check** 库进行基于属性的测试

**测试配置:**
- 每个属性测试最少运行100次迭代
- 每个测试必须引用其设计文档属性
- 标签格式: **Feature: daoist-mysticism-mall, Property {number}: {property_text}**

**示例属性测试 (PHP):**
```php
use Eris\Generator;

class ProductServiceTest extends TestCase
{
    /**
     * Feature: daoist-mysticism-mall, Property 10: 商品价格正值约束
     * 对于任意价格小于或等于零的商品数据，创建商品操作应该被拒绝
     */
    public function testProductPriceMustBePositive()
    {
        $this->forAll(
            Generator\choose(-1000, 0)  // 生成负数和零
        )->then(function ($price) {
            $this->expectException(ValidationException::class);
            
            $service = new ProductService();
            $service->createProduct([
                'name' => 'Test Product',
                'category_id' => 1,
                'price' => $price,
                'stock' => 10
            ]);
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 23: 取消订单库存恢复
     * 对于任意订单，取消订单后每个订单商品的库存应该增加相应的订单数量
     */
    public function testCancelOrderRestoresStock()
    {
        $this->forAll(
            Generator\choose(1, 100),  // 初始库存
            Generator\choose(1, 10)    // 订单数量
        )->then(function ($initialStock, $orderQuantity) {
            // 创建商品
            $product = Product::create([
                'name' => 'Test Product',
                'price' => 100,
                'stock' => $initialStock
            ]);
            
            // 创建订单
            $order = Order::create([
                'user_id' => 1,
                'total_amount' => 100 * $orderQuantity
            ]);
            
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $orderQuantity,
                'price' => 100
            ]);
            
            // 减少库存
            $product->stock -= $orderQuantity;
            $product->save();
            
            // 取消订单
            $service = new OrderService();
            $service->cancelOrder($order->id);
            
            // 验证库存恢复
            $product->refresh();
            $this->assertEquals($initialStock, $product->stock);
        });
    }
}
```

**示例属性测试 (JavaScript):**
```javascript
import fc from 'fast-check'

describe('UserService', () => {
    /**
     * Feature: daoist-mysticism-mall, Property 4: 用户名唯一性约束
     * 对于任意已存在的用户名，尝试创建具有相同用户名的新用户应该被拒绝
     */
    it('should reject duplicate usernames', () => {
        fc.assert(
            fc.property(
                fc.string({ minLength: 3, maxLength: 20 }),
                async (username) => {
                    // 创建第一个用户
                    await userService.createUser({
                        username,
                        email: `${username}@test.com`,
                        password: 'password123'
                    })
                    
                    // 尝试创建相同用户名的用户
                    await expect(
                        userService.createUser({
                            username,
                            email: `${username}2@test.com`,
                            password: 'password456'
                        })
                    ).rejects.toThrow('用户名已存在')
                }
            ),
            { numRuns: 100 }
        )
    })
})
```

### 测试覆盖目标

- 单元测试代码覆盖率: >= 80%
- 所有正确性属性必须有对应的属性测试
- 所有API端点必须有集成测试
- 关键业务流程必须有端到端测试

### 测试环境

- 开发环境: 使用SQLite内存数据库进行快速测试
- CI环境: 使用Docker容器运行MySQL进行完整测试
- 测试数据: 使用工厂模式和Faker库生成测试数据
