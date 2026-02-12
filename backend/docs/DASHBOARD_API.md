# 仪表盘API文档

## 概述

仪表盘API提供系统关键数据统计功能，用于管理后台的数据展示。

**验证需求:** 1.1, 1.2, 1.3, 1.4

## 认证要求

所有仪表盘API端点都需要：
- JWT令牌认证（通过Authorization头）
- 管理员角色权限（role = 'admin'）

## API端点

### 获取仪表盘统计数据

获取系统的关键数据统计，包括总订单数、总销售额、总用户数、总商品数、订单状态统计和最近订单列表。

**端点:** `GET /api/dashboard/stats`

**认证:** 需要（管理员）

**请求头:**
```
Authorization: Bearer {jwt_token}
```

**响应示例:**

```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "totalOrders": 150,
        "totalSales": 45000.00,
        "totalUsers": 320,
        "totalProducts": 85,
        "orderStatusCounts": {
            "pending": 12,
            "paid": 8,
            "shipped": 15,
            "completed": 110,
            "cancelled": 5
        },
        "recentOrders": [
            {
                "id": 150,
                "order_no": "ORD20240115001",
                "user_id": 25,
                "total_amount": 299.00,
                "status": "paid",
                "created_at": "2024-01-15 14:30:25",
                "user": {
                    "id": 25,
                    "username": "zhangsan",
                    "email": "zhangsan@example.com"
                }
            },
            {
                "id": 149,
                "order_no": "ORD20240115002",
                "user_id": 30,
                "total_amount": 599.00,
                "status": "completed",
                "created_at": "2024-01-15 13:20:10",
                "user": {
                    "id": 30,
                    "username": "lisi",
                    "email": "lisi@example.com"
                }
            }
            // ... 最多10条订单
        ]
    }
}
```

**响应字段说明:**

| 字段 | 类型 | 说明 |
|------|------|------|
| totalOrders | integer | 总订单数（所有状态） |
| totalSales | float | 总销售额（仅已完成订单） |
| totalUsers | integer | 总用户数 |
| totalProducts | integer | 总商品数 |
| orderStatusCounts | object | 各订单状态的数量统计 |
| orderStatusCounts.pending | integer | 待支付订单数 |
| orderStatusCounts.paid | integer | 待发货订单数 |
| orderStatusCounts.shipped | integer | 已发货订单数 |
| orderStatusCounts.completed | integer | 已完成订单数 |
| orderStatusCounts.cancelled | integer | 已取消订单数 |
| recentOrders | array | 最近10条订单记录 |
| recentOrders[].id | integer | 订单ID |
| recentOrders[].order_no | string | 订单号 |
| recentOrders[].user_id | integer | 用户ID |
| recentOrders[].total_amount | float | 订单金额 |
| recentOrders[].status | string | 订单状态 |
| recentOrders[].created_at | string | 创建时间 |
| recentOrders[].user | object | 用户信息 |
| recentOrders[].user.id | integer | 用户ID |
| recentOrders[].user.username | string | 用户名 |
| recentOrders[].user.email | string | 邮箱 |

**错误响应:**

1. 未认证（401）
```json
{
    "code": 401,
    "message": "未授权访问",
    "data": null
}
```

2. 无权限（403）
```json
{
    "code": 403,
    "message": "无权限访问",
    "data": null
}
```

3. 系统错误（500）
```json
{
    "code": 500,
    "message": "系统错误",
    "data": null
}
```

## 性能要求

根据需求1.4，仪表盘API应在2秒内返回最新的统计数据。

## 使用示例

### JavaScript (Axios)

```javascript
// 获取仪表盘统计数据
async function getDashboardStats() {
    try {
        const response = await axios.get('/api/dashboard/stats', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const { data } = response.data;
        console.log('总订单数:', data.totalOrders);
        console.log('总销售额:', data.totalSales);
        console.log('总用户数:', data.totalUsers);
        console.log('总商品数:', data.totalProducts);
        console.log('订单状态统计:', data.orderStatusCounts);
        console.log('最近订单:', data.recentOrders);
        
        return data;
    } catch (error) {
        if (error.response) {
            console.error('错误:', error.response.data.message);
        } else {
            console.error('网络错误');
        }
        throw error;
    }
}
```

### PHP (cURL)

```php
<?php

function getDashboardStats($token) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost/api/dashboard/stats',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['data'];
    } else {
        throw new Exception('获取仪表盘数据失败');
    }
}

// 使用示例
try {
    $stats = getDashboardStats($jwtToken);
    echo "总订单数: " . $stats['totalOrders'] . "\n";
    echo "总销售额: " . $stats['totalSales'] . "\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
```

## 注意事项

1. **认证要求**: 必须使用有效的JWT令牌，且用户角色必须为管理员
2. **性能**: API响应时间应在2秒内，如果数据量过大可能需要优化查询
3. **数据准确性**: 统计数据实时计算，确保数据准确性
4. **订单排序**: 最近订单列表按创建时间降序排列
5. **销售额统计**: 仅统计已完成状态的订单金额

## 相关需求

- **需求 1.1**: 显示总订单数、总销售额、总用户数和总商品数
- **需求 1.2**: 显示各订单状态的数量统计
- **需求 1.3**: 显示最近10条订单记录
- **需求 1.4**: 在2秒内返回最新的统计数据
