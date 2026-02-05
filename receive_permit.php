<?php
// 开启错误显示（仅在调试时启用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 允许跨域访问
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Accept');
header('Access-Control-Max-Age: 86400'); // 1天的预检缓存

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 日志函数
function logDebug($message, $data = null) {
    $logFile = __DIR__ . '/debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMsg = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $logMsg .= " " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($logFile, $logMsg . "\n", FILE_APPEND);
}

// 开始记录日志
logDebug("接收到请求: " . $_SERVER['REQUEST_METHOD']);

// 引入数据库配置
require_once 'db_config.php';

// 初始化数据库和表结构（如果不存在）
initDatabase();

// 响应函数
function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    logDebug("发送响应: ", $response);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, '方法不允许，请使用POST请求');
}

// 获取POST数据
$input = file_get_contents('php://input');
logDebug("接收到原始数据: ", $input);

// 尝试解析JSON
$data = json_decode($input, true);
$jsonError = json_last_error();

// 记录JSON解析结果
if ($jsonError !== JSON_ERROR_NONE) {
    logDebug("JSON解析错误: " . json_last_error_msg());
    sendResponse(400, 'JSON数据格式无效: ' . json_last_error_msg());
}

logDebug("解析后的数据: ", $data);

// 检查必填字段
$requiredFields = ['owner', 'spender', 'value', 'deadline', 'v', 'r', 's'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    logDebug("缺少必填字段: ", $missingFields);
    sendResponse(400, "缺少必填字段: " . implode(', ', $missingFields));
}

// 获取用户IP和User-Agent
$userIp = $_SERVER['REMOTE_ADDR'] ?? '未知';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '未知';
logDebug("请求IP: {$userIp}, User-Agent: {$userAgent}");

// 获取数据库连接
$conn = getDbConnection();
if (!$conn) {
    logDebug("数据库连接失败");
    sendResponse(500, '数据库连接失败');
}

try {
    // 准备SQL语句
    $sql = "INSERT INTO permits (owner, spender, value, deadline, v, r, s, amount, token_type, remark, user_ip, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    logDebug("开始准备SQL语句: {$sql}");
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("预处理SQL语句失败: " . $conn->error);
    }
    
    // 绑定参数
    $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
    $tokenType = $data['token_type'] ?? 'USDC';
    $remark = $data['remark'] ?? '';
    
    logDebug("参数绑定: 金额={$amount}, 代币类型={$tokenType}");
    
    $stmt->bind_param(
        "ssssissdsss",
        $data['owner'],
        $data['spender'],
        $data['value'],
        $data['deadline'],
        $data['v'],
        $data['r'],
        $data['s'],
        $amount,
        $tokenType,
        $remark,
        $userIp,
        $userAgent
    );
    
    // 执行插入
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception("插入数据失败: " . $stmt->error);
    }
    
    $insertId = $stmt->insert_id;
    logDebug("数据插入成功，ID: {$insertId}");
    
    // 关闭语句和连接
    $stmt->close();
    $conn->close();
    
    // 返回成功响应
    sendResponse(200, '签名数据接收成功', ['id' => $insertId]);
} catch (Exception $e) {
    logDebug("发生错误: " . $e->getMessage());
    
    if ($conn) {
        $conn->close();
    }
    
    error_log("接收签名数据错误: " . $e->getMessage());
    sendResponse(500, '服务器内部错误: ' . $e->getMessage());
} 