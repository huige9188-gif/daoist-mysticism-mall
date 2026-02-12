<?php

namespace Tests\User;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use app\service\UserService;
use PDO;

/**
 * 用户管理属性测试
 * 
 * 使用Eris库进行基于属性的测试
 * 验证需求: 2.2
 */
class UserPropertyTest extends TestCase
{
    use TestTrait;
    
    private static ?PDO $pdo = null;
    private UserService $userService;
    
    public static function setUpBeforeClass(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'daoist_mall_test';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '123456';

        try {
            self::$pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (\PDOException $e) {
            self::fail("无法连接到数据库: " . $e->getMessage());
        }
    }
    
    protected function setUp(): void
    {
        $this->userService = new UserService();
        
        // 禁用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        // 清空用户表
        self::$pdo->exec("TRUNCATE TABLE users");
        // 启用外键检查
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    protected function tearDown(): void
    {
        // 每次测试后清理数据库
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        self::$pdo->exec("TRUNCATE TABLE users");
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 4: 用户名唯一性约束
     * 
     * **Validates: Requirements 2.2**
     * 
     * 对于任意已存在的用户名，尝试创建具有相同用户名的新用户应该被拒绝并返回错误
     * 
     * 此属性测试验证：
     * 1. 对于任意有效的用户名，第一次创建用户应该成功
     * 2. 使用相同用户名创建第二个用户应该失败
     * 3. 失败时应该抛出异常并包含"用户名已存在"或类似的错误消息
     * 4. 数据库中只应该存在一个该用户名的用户记录
     * 5. 即使其他字段（邮箱、密码等）不同，只要用户名相同就应该被拒绝
     */
    public function testUsernameUniquenessConstraint(): void
    {
        $this->forAll(
            $this->generateUsername(),
            $this->generateEmail(),
            $this->generateEmail(), // 生成不同的邮箱
            $this->generatePassword()
        )
        ->hook(\Eris\Listener\collectFrequencies())
        ->then(function ($username, $email1, $email2, $password) {
            // 清理数据库（每次迭代前）
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            self::$pdo->exec("TRUNCATE TABLE users");
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // 确保两个邮箱不同
            if ($email1 === $email2) {
                $email2 = 'different' . $email2;
            }
            
            try {
                // 创建第一个用户
                $user1Data = [
                    'username' => $username,
                    'email' => $email1,
                    'password' => $password,
                    'role' => 'user',
                    'status' => 1
                ];
                
                $user1 = $this->userService->createUser($user1Data);
                
                // 验证第一个用户创建成功
                $this->assertNotNull($user1, 
                    "第一个用户应该创建成功");
                $this->assertEquals($username, $user1['username'], 
                    "创建的用户名应该与输入一致");
                
                // 验证数据库中存在该用户
                $stmt = self::$pdo->prepare("
                    SELECT COUNT(*) as count FROM users 
                    WHERE username = :username AND deleted_at IS NULL
                ");
                $stmt->execute(['username' => $username]);
                $result = $stmt->fetch();
                $this->assertEquals(1, $result['count'], 
                    "数据库中应该只有一个该用户名的用户");
                
                // 尝试创建具有相同用户名但不同邮箱的第二个用户
                $user2Data = [
                    'username' => $username, // 相同的用户名
                    'email' => $email2,      // 不同的邮箱
                    'password' => $password . '_different',
                    'role' => 'user',
                    'status' => 1
                ];
                
                // 验证创建第二个用户应该失败
                $exceptionThrown = false;
                $exceptionMessage = '';
                
                try {
                    $this->userService->createUser($user2Data);
                } catch (\Exception $e) {
                    $exceptionThrown = true;
                    $exceptionMessage = $e->getMessage();
                }
                
                // 验证抛出了异常
                $this->assertTrue($exceptionThrown, 
                    "尝试创建重复用户名的用户应该抛出异常");
                
                // 验证异常消息包含"用户名"和"存在"相关的提示
                $this->assertMatchesRegularExpression(
                    '/用户名.*存在|用户名.*重复|duplicate.*username/i',
                    $exceptionMessage,
                    "异常消息应该明确指出用户名已存在"
                );
                
                // 验证数据库中仍然只有一个该用户名的用户
                $stmt->execute(['username' => $username]);
                $result = $stmt->fetch();
                $this->assertEquals(1, $result['count'], 
                    "失败后数据库中应该仍然只有一个该用户名的用户");
                
                // 验证第二个用户没有被创建
                $stmt2 = self::$pdo->prepare("
                    SELECT COUNT(*) as count FROM users 
                    WHERE email = :email AND deleted_at IS NULL
                ");
                $stmt2->execute(['email' => $email2]);
                $result2 = $stmt2->fetch();
                $this->assertEquals(0, $result2['count'], 
                    "第二个用户不应该被创建到数据库中");
            } catch (\Exception $e) {
                // 如果在测试过程中发生意外异常，记录并重新抛出
                // 但不计入评估比率（使用when来过滤）
                throw $e;
            }
        });
    }
    
    /**
     * Feature: daoist-mysticism-mall, Property 5: 邮箱格式验证
     * 
     * **Validates: Requirements 2.2**
     * 
     * 对于任意不符合邮箱格式的字符串，使用该字符串作为邮箱创建用户应该被拒绝
     * 
     * 此属性测试验证：
     * 1. 对于任意无效的邮箱格式，创建用户应该失败
     * 2. 失败时应该抛出异常并包含"邮箱格式"或类似的错误消息
     * 3. 无效的邮箱不应该被保存到数据库中
     * 4. 各种类型的无效邮箱格式都应该被正确识别和拒绝
     */
    public function testEmailFormatValidation(): void
    {
        $this->forAll(
            $this->generateUsername(),
            $this->generateInvalidEmail(),
            $this->generatePassword()
        )->then(function ($username, $invalidEmail, $password) {
            // 尝试使用无效邮箱创建用户
            $userData = [
                'username' => $username,
                'email' => $invalidEmail,
                'password' => $password,
                'role' => 'user',
                'status' => 1
            ];
            
            // 验证创建用户应该失败
            $exceptionThrown = false;
            $exceptionMessage = '';
            
            try {
                $this->userService->createUser($userData);
            } catch (\Exception $e) {
                $exceptionThrown = true;
                $exceptionMessage = $e->getMessage();
            }
            
            // 验证抛出了异常
            $this->assertTrue($exceptionThrown, 
                "使用无效邮箱格式创建用户应该抛出异常，邮箱: {$invalidEmail}");
            
            // 验证异常消息包含"邮箱"和"格式"相关的提示
            $this->assertMatchesRegularExpression(
                '/邮箱.*格式|email.*format|email.*invalid|invalid.*email/i',
                $exceptionMessage,
                "异常消息应该明确指出邮箱格式不正确"
            );
            
            // 验证用户没有被创建到数据库中
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as count FROM users 
                WHERE username = :username AND deleted_at IS NULL
            ");
            $stmt->execute(['username' => $username]);
            $result = $stmt->fetch();
            $this->assertEquals(0, $result['count'], 
                "使用无效邮箱的用户不应该被创建到数据库中");
        });
    }
    
    /**
     * 测试有效邮箱格式应该被接受
     * 
     * **Validates: Requirements 2.2**
     * 
     * 对于任意符合邮箱格式的字符串，使用该字符串作为邮箱创建用户应该成功
     */
    public function testValidEmailFormatAccepted(): void
    {
        $this->forAll(
            $this->generateUsername(),
            $this->generateValidEmail(),
            $this->generatePassword()
        )
        ->hook(\Eris\Listener\collectFrequencies())
        ->then(function ($username, $validEmail, $password) {
            // 清理数据库（每次迭代前）
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            self::$pdo->exec("TRUNCATE TABLE users");
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            try {
                // 使用有效邮箱创建用户
                $userData = [
                    'username' => $username,
                    'email' => $validEmail,
                    'password' => $password,
                    'role' => 'user',
                    'status' => 1
                ];
                
                // 创建用户应该成功
                $user = $this->userService->createUser($userData);
                
                // 验证用户创建成功
                $this->assertNotNull($user, 
                    "使用有效邮箱格式创建用户应该成功");
                $this->assertEquals($validEmail, $user['email'], 
                    "创建的用户邮箱应该与输入一致");
                
                // 验证用户被保存到数据库中
                $stmt = self::$pdo->prepare("
                    SELECT * FROM users 
                    WHERE username = :username AND deleted_at IS NULL
                ");
                $stmt->execute(['username' => $username]);
                $dbUser = $stmt->fetch();
                
                $this->assertNotFalse($dbUser, 
                    "用户应该被保存到数据库中");
                $this->assertEquals($validEmail, $dbUser['email'], 
                    "数据库中的邮箱应该与输入一致");
            } catch (\Exception $e) {
                // 如果在测试过程中发生意外异常，记录并重新抛出
                throw $e;
            }
        });
    }
    
    /**
     * 生成用户名生成器
     * 
     * 生成3-20个字符的字母数字用户名（符合alphaNum规则）
     */
    private function generateUsername()
    {
        return \Eris\Generator\map(
            function ($n) {
                // 生成基于数字的用户名，只包含字母和数字，确保唯一性
                return 'user' . $n . substr(md5((string)$n . microtime()), 0, 8);
            },
            \Eris\Generator\choose(1, 100000)
        );
    }
    
    /**
     * 生成邮箱生成器
     * 
     * 生成有效的邮箱地址
     */
    private function generateEmail()
    {
        return \Eris\Generator\map(
            function ($n) {
                // 生成基于数字的邮箱，确保唯一性和有效性
                return 'user' . $n . substr(md5((string)$n . microtime()), 0, 8) . '@example.com';
            },
            \Eris\Generator\choose(1, 100000)
        );
    }
    
    /**
     * 生成有效邮箱生成器
     * 
     * 生成各种有效的邮箱格式
     */
    private function generateValidEmail()
    {
        return \Eris\Generator\oneOf(
            // 标准邮箱格式
            \Eris\Generator\map(
                function ($n) {
                    return 'user' . $n . '_' . substr(md5(microtime()), 0, 8) . '@example.com';
                },
                \Eris\Generator\choose(1, 100000)
            ),
            // 带点号的邮箱
            \Eris\Generator\map(
                function ($n) {
                    return 'user.' . $n . '@example.com';
                },
                \Eris\Generator\choose(1, 100000)
            ),
            // 带加号的邮箱
            \Eris\Generator\map(
                function ($n) {
                    return 'user+' . $n . '@example.com';
                },
                \Eris\Generator\choose(1, 100000)
            ),
            // 带下划线的邮箱
            \Eris\Generator\map(
                function ($n) {
                    return 'user_' . $n . '@example.com';
                },
                \Eris\Generator\choose(1, 100000)
            ),
            // 带连字符的邮箱
            \Eris\Generator\map(
                function ($n) {
                    return 'user-' . $n . '@example.com';
                },
                \Eris\Generator\choose(1, 100000)
            )
        );
    }
    
    /**
     * 生成无效邮箱生成器
     * 
     * 生成各种无效的邮箱格式：
     * - 缺少@符号
     * - 缺少域名
     * - 缺少用户名
     * - 多个@符号
     * - 空字符串
     * - 只有空格
     * - 特殊字符
     */
    private function generateInvalidEmail()
    {
        return \Eris\Generator\oneOf(
            // 缺少@符号
            \Eris\Generator\map(
                function ($str) {
                    $str = preg_replace('/[^a-z0-9]/', '', strtolower($str));
                    return substr($str, 0, 20) . 'example.com';
                },
                \Eris\Generator\string()
            ),
            // 缺少域名
            \Eris\Generator\map(
                function ($str) {
                    $str = preg_replace('/[^a-z0-9]/', '', strtolower($str));
                    return substr($str, 0, 20) . '@';
                },
                \Eris\Generator\string()
            ),
            // 缺少用户名
            \Eris\Generator\elements(
                '@example.com',
                '@domain.com',
                '@test.org'
            ),
            // 多个@符号
            \Eris\Generator\map(
                function ($str) {
                    $str = preg_replace('/[^a-z0-9]/', '', strtolower($str));
                    return substr($str, 0, 10) . '@@' . substr($str, 10, 10) . '.com';
                },
                \Eris\Generator\string()
            ),
            // 空字符串和无效格式
            \Eris\Generator\elements(
                '',
                ' ',
                '   ',
                'not-an-email',
                'invalid',
                'test',
                'user@',
                '@domain',
                'user @domain.com',
                'user@ domain.com',
                'user@domain .com',
                'user..name@domain.com',
                '.username@domain.com',
                'username.@domain.com',
                'user@domain',
                'user@.com',
                'user@domain..com'
            ),
            // 包含非法字符
            \Eris\Generator\map(
                function ($str) {
                    return 'user<>' . substr($str, 0, 5) . '@domain.com';
                },
                \Eris\Generator\string()
            )
        );
    }
    
    /**
     * 生成密码生成器
     * 
     * 生成6-50个字符的密码
     */
    private function generatePassword()
    {
        return \Eris\Generator\map(
            function ($n) {
                // 生成简单但有效的密码
                return 'password' . $n;
            },
            \Eris\Generator\choose(1, 100000)
        );
    }
}
