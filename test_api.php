<?php
// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 引入数据库配置
require_once 'db_config.php';

// 测试数据库连接
echo "<h2>测试数据库连接</h2>";
$conn = getDbConnection();
if ($conn) {
    echo "<p style='color:green'>✅ 数据库连接成功</p>";
    $conn->close();
} else {
    echo "<p style='color:red'>❌ 数据库连接失败</p>";
}

// 测试数据库初始化
echo "<h2>测试数据库初始化</h2>";
if (initDatabase()) {
    echo "<p style='color:green'>✅ 数据库初始化成功</p>";
} else {
    echo "<p style='color:red'>❌ 数据库初始化失败</p>";
}

// 测试API endpoint
echo "<h2>测试API Endpoint</h2>";
$testData = [
    'owner' => '0xtest1234567890abcdef1234567890abcdef12345',
    'spender' => '0x134aF0E6Da1F0b8d4ebc1dD5f163a99242D31429',
    'value' => '115792089237316195423570985008687907853269984665640564039457584007913129639935',
    'deadline' => time() + 3600,
    'v' => 28,
    'r' => '0xtest15a97962bc28df68a019598073ce6caca9ec6a928212c46a3c8253ad5a6d',
    's' => '0xtest25af9ec71e9bba4fd757fcb9996573e7c90d6a42d8c0cb3bd0fe714cc276',
    'amount' => 10.5,
    'token_type' => 'USDC',
    'remark' => '测试数据',
    'timestamp' => time()
];

try {
    $ch = curl_init('http://localhost/receive_permit.php');
    if ($ch === false) {
        throw new Exception("无法初始化cURL");
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception("cURL请求失败: " . $error);
    }
    
    echo "<p><strong>HTTP状态码:</strong> {$info['http_code']}</p>";
    echo "<p><strong>响应内容:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $responseData = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($responseData['status']) && $responseData['status'] === 200) {
        echo "<p style='color:green'>✅ API测试成功</p>";
    } else {
        echo "<p style='color:orange'>⚠️ API响应成功但数据异常</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ API测试失败: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 检查日志文件
echo "<h2>测试日志文件</h2>";
$logFile = __DIR__ . '/debug_log.txt';
if (file_exists($logFile)) {
    echo "<p style='color:green'>✅ 日志文件存在</p>";
    echo "<p><strong>最新日志内容:</strong></p>";
    
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, max(0, count($lines) - 10)); // 显示最后10行
    
    echo "<pre>" . htmlspecialchars(implode("\n", $lastLines)) . "</pre>";
} else {
    echo "<p style='color:red'>❌ 日志文件不存在</p>";
}

// 检查表中的记录数
echo "<h2>检查数据库记录</h2>";
$conn = getDbConnection();
if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM permits");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>表中共有 <strong>{$row['total']}</strong> 条记录</p>";
    } else {
        echo "<p style='color:red'>❌ 无法查询记录数: " . $conn->error . "</p>";
    }
    $conn->close();
} 