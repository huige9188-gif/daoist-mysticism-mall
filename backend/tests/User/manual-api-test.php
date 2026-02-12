<?php
/**
 * 手动测试用户管理API端点
 * 
 * 使用方法：
 * php backend/tests/User/manual-api-test.php
 */

require __DIR__ . '/../../vendor/autoload.php';

// 初始化应用
$app = new think\App();
$app->initialize();

use app\model\User;
use app\service\UserService;
use app\common\Jwt;

echo "=== 用户管理API端点测试 ===\n\n";

// 1. 创建测试管理员用户
echo "1. 创建测试管理员用户...\n";
$adminUser = User::where('username', 'test_admin_manual')->find();
if (!$adminUser) {
    $adminUser = User::create([
        'username' => 'test_admin_manual',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'test_admin_manual@test.com',
        'phone' => '13800138000',
        'role' => 'admin',
        'status' => 1
    ]);
    echo "   ✓ 管理员用户创建成功 (ID: {$adminUser->id})\n";
} else {
    echo "   ✓ 管理员用户已存在 (ID: {$adminUser->id})\n";
}

// 2. 生成JWT令牌
echo "\n2. 生成JWT令牌...\n";
$token = Jwt::generateToken([
    'id' => $adminUser->id,
    'username' => $adminUser->username,
    'email' => $adminUser->email,
    'role' => $adminUser->role
]);
echo "   ✓ Token: " . substr($token, 0, 50) . "...\n";

// 3. 测试UserService方法
echo "\n3. 测试UserService方法...\n";
$userService = new UserService();

// 3.1 创建用户
echo "   3.1 创建用户...\n";
try {
    $newUser = $userService->createUser([
        'username' => 'test_user_' . time(),
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'email' => 'test_user_' . time() . '@test.com',
        'phone' => '13900139000',
        'role' => 'user',
        'status' => 1
    ]);
    echo "       ✓ 用户创建成功 (ID: {$newUser->id}, 用户名: {$newUser->username})\n";
} catch (\Exception $e) {
    echo "       ✗ 创建失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 3.2 获取用户列表
echo "   3.2 获取用户列表...\n";
try {
    $users = $userService->getUserList(1, 10);
    echo "       ✓ 获取成功，共 {$users->total()} 个用户\n";
} catch (\Exception $e) {
    echo "       ✗ 获取失败: " . $e->getMessage() . "\n";
}

// 3.3 更新用户
echo "   3.3 更新用户...\n";
try {
    $updatedUser = $userService->updateUser($newUser->id, [
        'phone' => '13900139999'
    ]);
    echo "       ✓ 更新成功，新手机号: {$updatedUser->phone}\n";
} catch (\Exception $e) {
    echo "       ✗ 更新失败: " . $e->getMessage() . "\n";
}

// 3.4 更新用户状态
echo "   3.4 更新用户状态...\n";
try {
    $statusUser = $userService->updateStatus($newUser->id, 0);
    echo "       ✓ 状态更新成功，新状态: {$statusUser->status}\n";
} catch (\Exception $e) {
    echo "       ✗ 状态更新失败: " . $e->getMessage() . "\n";
}

// 3.5 搜索用户
echo "   3.5 搜索用户...\n";
try {
    $searchResults = $userService->getUserList(1, 10, 'test_user');
    echo "       ✓ 搜索成功，找到 {$searchResults->total()} 个用户\n";
} catch (\Exception $e) {
    echo "       ✗ 搜索失败: " . $e->getMessage() . "\n";
}

// 3.6 删除用户（软删除）
echo "   3.6 删除用户（软删除）...\n";
try {
    $userService->deleteUser($newUser->id);
    echo "       ✓ 删除成功\n";
    
    // 验证软删除
    $deletedUser = User::withTrashed()->find($newUser->id);
    if ($deletedUser && $deletedUser->deleted_at) {
        echo "       ✓ 验证软删除成功，deleted_at: {$deletedUser->deleted_at}\n";
    } else {
        echo "       ✗ 软删除验证失败\n";
    }
} catch (\Exception $e) {
    echo "       ✗ 删除失败: " . $e->getMessage() . "\n";
}

// 4. 测试验证规则
echo "\n4. 测试验证规则...\n";

// 4.1 测试用户名重复
echo "   4.1 测试用户名重复...\n";
try {
    $userService->createUser([
        'username' => $adminUser->username,
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'email' => 'duplicate@test.com',
        'phone' => '13900139001',
        'role' => 'user',
        'status' => 1
    ]);
    echo "       ✗ 应该拒绝重复用户名\n";
} catch (\Exception $e) {
    echo "       ✓ 正确拒绝: " . $e->getMessage() . "\n";
}

// 4.2 测试邮箱格式
echo "   4.2 测试邮箱格式...\n";
try {
    $userService->createUser([
        'username' => 'test_invalid_email',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'email' => 'invalid-email',
        'phone' => '13900139002',
        'role' => 'user',
        'status' => 1
    ]);
    echo "       ✗ 应该拒绝无效邮箱\n";
} catch (\Exception $e) {
    echo "       ✓ 正确拒绝: " . $e->getMessage() . "\n";
}

// 5. 清理测试数据
echo "\n5. 清理测试数据...\n";
if (isset($newUser)) {
    User::destroy($newUser->id, true);
    echo "   ✓ 测试用户已清理\n";
}

echo "\n=== 所有测试完成 ===\n";
echo "\n提示：API端点已创建，路由配置如下：\n";
echo "  GET    /api/users              - 获取用户列表\n";
echo "  POST   /api/users              - 创建用户\n";
echo "  PUT    /api/users/:id          - 更新用户\n";
echo "  DELETE /api/users/:id          - 删除用户\n";
echo "  PATCH  /api/users/:id/status   - 更新用户状态\n";
echo "\n所有端点需要管理员权限（auth + admin中间件）\n";
