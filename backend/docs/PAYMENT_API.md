# 支付API文档

## 概述

本文档描述了道家玄学商城系统的支付相关API端点。系统支持支付宝、微信支付和PayPal三种支付方式。

## 基础信息

- **基础URL**: `/api`
- **认证方式**: JWT Token (除支付回调外)
- **请求格式**: JSON
- **响应格式**: JSON

## 响应格式

所有API响应遵循统一格式：

```json
{
    "code": 200,
    "message": "成功信息",
    "data": {}
}
```

## API端点

### 1. 获取支付配置列表

获取所有支付网关的配置信息（管理员权限）。

**端点**: `GET /api/payment-configs`

**权限**: 管理员

**请求头**:
```
Authorization: Bearer {token}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "gateway": "alipay",
            "config": {
                "app_id": "2021001234567890",
                "private_key": "...",
                "public_key": "...",
                "notify_url": "https://example.com/notify"
            },
            "status": 1,
            "created_at": "2024-01-01 00:00:00",
            "updated_at": "2024-01-01 00:00:00"
        }
    ]
}
```

**验证需求**: 9.1, 9.2, 9.3, 9.4

---

### 2. 保存支付配置

创建或更新支付网关配置（管理员权限）。

**端点**: `POST /api/payment-configs`

**权限**: 管理员

**请求头**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**请求体**:

#### 支付宝配置
```json
{
    "gateway": "alipay",
    "config": {
        "app_id": "2021001234567890",
        "private_key": "MIIEvQIBADANBgkqhkiG9w0BAQE...",
        "public_key": "MIIBIjANBgkqhkiG9w0BAQEFAAO...",
        "notify_url": "https://example.com/api/payments/callback"
    },
    "status": 1
}
```

**必填字段**:
- `app_id`: 支付宝应用ID
- `private_key`: 应用私钥
- `public_key`: 支付宝公钥
- `notify_url`: 异步通知地址

#### 微信支付配置
```json
{
    "gateway": "wechat",
    "config": {
        "app_id": "wx1234567890abcdef",
        "mch_id": "1234567890",
        "api_key": "32characterslong1234567890abcd",
        "notify_url": "https://example.com/api/payments/callback"
    },
    "status": 1
}
```

**必填字段**:
- `app_id`: 微信应用ID
- `mch_id`: 商户号
- `api_key`: API密钥
- `notify_url`: 异步通知地址

#### PayPal配置
```json
{
    "gateway": "paypal",
    "config": {
        "client_id": "AeB1234567890abcdef",
        "secret": "EFG1234567890abcdef",
        "notify_url": "https://example.com/api/payments/callback"
    },
    "status": 1
}
```

**必填字段**:
- `client_id`: PayPal客户端ID
- `secret`: PayPal密钥
- `notify_url`: 异步通知地址

**响应示例**:
```json
{
    "code": 200,
    "message": "保存成功",
    "data": {
        "id": 1,
        "gateway": "alipay",
        "config": {...},
        "status": 1,
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-01 00:00:00"
    }
}
```

**错误响应**:
```json
{
    "code": 400,
    "message": "缺少必填字段: app_id, private_key",
    "data": null
}
```

**验证需求**: 9.1, 9.2, 9.3, 9.4

---

### 3. 创建支付

为订单创建支付请求。

**端点**: `POST /api/payments`

**权限**: 已认证用户

**请求头**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**请求体**:
```json
{
    "order_id": 123,
    "gateway": "alipay"
}
```

**参数说明**:
- `order_id` (必填): 订单ID
- `gateway` (必填): 支付网关，可选值: `alipay`, `wechat`, `paypal`

**响应示例**:
```json
{
    "code": 200,
    "message": "支付创建成功",
    "data": {
        "payment_url": "https://openapi.alipay.com/gateway.do?...",
        "order_no": "20240101123456789",
        "gateway": "alipay"
    }
}
```

**错误响应**:

订单不存在:
```json
{
    "code": 400,
    "message": "订单不存在",
    "data": null
}
```

支付方式未启用:
```json
{
    "code": 400,
    "message": "支付方式未启用",
    "data": null
}
```

订单状态不正确:
```json
{
    "code": 400,
    "message": "订单状态不正确",
    "data": null
}
```

**验证需求**: 9.1, 9.2, 9.3, 9.5

---

### 4. 获取可用支付方式

获取当前启用的支付方式列表。

**端点**: `GET /api/payments/gateways`

**权限**: 已认证用户

**请求头**:
```
Authorization: Bearer {token}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "gateway": "alipay",
            "name": "支付宝"
        },
        {
            "gateway": "wechat",
            "name": "微信支付"
        },
        {
            "gateway": "paypal",
            "name": "PayPal"
        }
    ]
}
```

**验证需求**: 9.5

---

### 5. 支付回调处理

处理支付网关的异步通知回调。

**端点**: `POST /api/payments/callback`

**权限**: 无需认证（由支付网关调用）

**请求头**:
```
Content-Type: application/json
```

**请求体**:

#### 支付宝回调
```json
{
    "gateway": "alipay",
    "out_trade_no": "20240101123456789",
    "trade_no": "2024010122001234567890",
    "trade_status": "TRADE_SUCCESS",
    "total_amount": "100.00",
    "gmt_payment": "2024-01-01 12:00:00",
    "sign": "...",
    "sign_type": "RSA2"
}
```

#### 微信支付回调
```json
{
    "gateway": "wechat",
    "out_trade_no": "20240101123456789",
    "transaction_id": "4200001234567890",
    "result_code": "SUCCESS",
    "total_fee": "10000",
    "time_end": "20240101120000",
    "sign": "..."
}
```

#### PayPal回调
```json
{
    "gateway": "paypal",
    "invoice_id": "20240101123456789",
    "payment_id": "PAYID-1234567890",
    "payment_status": "Completed",
    "mc_gross": "100.00",
    "payment_date": "2024-01-01 12:00:00"
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "回调处理成功",
    "data": {
        "id": 123,
        "order_no": "20240101123456789",
        "status": "paid",
        "paid_at": "2024-01-01 12:00:00",
        ...
    }
}
```

**错误响应**:

签名验证失败:
```json
{
    "code": 400,
    "message": "签名验证失败",
    "data": null
}
```

订单不存在:
```json
{
    "code": 400,
    "message": "订单不存在",
    "data": null
}
```

**验证需求**: 9.1, 9.2, 9.3

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误或业务逻辑错误 |
| 401 | 未授权访问（未登录或token无效） |
| 403 | 无权限访问（非管理员） |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 支付流程

### 用户支付流程

1. 用户创建订单 (`POST /api/orders`)
2. 用户选择支付方式并创建支付 (`POST /api/payments`)
3. 系统返回支付URL，用户跳转到支付页面
4. 用户完成支付
5. 支付网关异步通知系统 (`POST /api/payments/callback`)
6. 系统更新订单状态为已支付

### 管理员配置流程

1. 管理员登录系统
2. 访问支付配置页面
3. 填写支付网关配置信息 (`POST /api/payment-configs`)
4. 启用或禁用支付方式
5. 用户可以使用已启用的支付方式

## 安全注意事项

1. **密钥保护**: 支付网关的私钥和密钥必须妥善保管，不要泄露
2. **签名验证**: 所有支付回调必须验证签名，确保请求来自支付网关
3. **HTTPS**: 生产环境必须使用HTTPS协议
4. **回调URL**: 回调URL必须是公网可访问的地址
5. **幂等性**: 支付回调可能重复发送，系统需要保证幂等性

## 测试

### 运行测试

```bash
cd backend
vendor/bin/phpunit tests/Payment/PaymentApiTest.php
```

### 测试覆盖

- ✅ 获取支付配置列表（管理员权限）
- ✅ 获取支付配置列表（普通用户无权限）
- ✅ 保存支付宝配置
- ✅ 保存微信支付配置
- ✅ 保存PayPal配置
- ✅ 保存配置缺少必填字段
- ✅ 保存配置不支持的支付网关
- ✅ 创建支付成功
- ✅ 创建支付订单不存在
- ✅ 创建支付方式未启用
- ✅ 获取可用支付方式
- ✅ 处理支付回调

## 相关文档

- [订单API文档](./ORDER_API.md)
- [用户API文档](./USER_API.md)
- [支付网关配置指南](./PAYMENT_GATEWAY_SETUP.md)
