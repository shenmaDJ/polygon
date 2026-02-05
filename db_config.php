<?php
// 数据库连接配置
$dbHost = 'localhost';  // 数据库主机地址
$dbUser = 'root';       // 数据库用户名，请修改为您的实际用户名
$dbPass = '';           // 数据库密码，请修改为您的实际密码
$dbName = 'permit_db';  // 数据库名称

// 日志函数（如果不存在）
if (!function_exists('logDebug')) {
    function logDebug($message, $data = null) {
        $logFile = __DIR__ . '/debug_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMsg = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $logMsg .= " " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        file_put_contents($logFile, $logMsg . "\n", FILE_APPEND);
    }
}

// 创建数据库连接
function getDbConnection() {
    global $dbHost, $dbUser, $dbPass, $dbName;
    
    try {
        if (function_exists('logDebug')) {
            logDebug("尝试连接数据库: {$dbHost}, 用户: {$dbUser}, 数据库: {$dbName}");
        }
        
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        
        // 检查连接是否成功
        if ($conn->connect_error) {
            if (function_exists('logDebug')) {
                logDebug("数据库连接失败: " . $conn->connect_error);
            }
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 设置字符集
        $conn->set_charset("utf8mb4");
        
        if (function_exists('logDebug')) {
            logDebug("数据库连接成功");
        }
        
        return $conn;
    } catch (Exception $e) {
        // 记录错误日志
        error_log("数据库连接错误: " . $e->getMessage());
        if (function_exists('logDebug')) {
            logDebug("数据库连接异常: " . $e->getMessage());
        }
        return null;
    }
}

// 初始化数据库
function initDatabase() {
    global $dbHost, $dbUser, $dbPass, $dbName;
    
    try {
        if (function_exists('logDebug')) {
            logDebug("初始化数据库开始");
        }
        
        // 连接MySQL（不指定数据库）
        $conn = new mysqli($dbHost, $dbUser, $dbPass);
        
        if ($conn->connect_error) {
            if (function_exists('logDebug')) {
                logDebug("连接MySQL服务器失败: " . $conn->connect_error);
            }
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 创建数据库（如果不存在）
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$conn->query($sql)) {
            if (function_exists('logDebug')) {
                logDebug("创建数据库失败: " . $conn->error);
            }
            throw new Exception("创建数据库失败: " . $conn->error);
        }
        
        // 选择数据库
        $conn->select_db($dbName);
        
        if (function_exists('logDebug')) {
            logDebug("开始创建permits表");
        }
        
        // 创建permits表
        $sql = "CREATE TABLE IF NOT EXISTS `permits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `owner` VARCHAR(255) NOT NULL,
            `spender` VARCHAR(255) NOT NULL,
            `value` TEXT NOT NULL,
            `deadline` BIGINT NOT NULL,
            `v` INT NOT NULL,
            `r` TEXT NOT NULL,
            `s` TEXT NOT NULL,
            `amount` DECIMAL(18,6) DEFAULT 0,
            `token_type` VARCHAR(50) DEFAULT 'USDC',
            `remark` TEXT,
            `user_ip` VARCHAR(50),
            `user_agent` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `status` TINYINT DEFAULT 0 COMMENT '0:待处理, 1:已处理, -1:失败'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($sql)) {
            if (function_exists('logDebug')) {
                logDebug("创建permits表失败: " . $conn->error);
            }
            throw new Exception("创建表失败: " . $conn->error);
        }
        
        if (function_exists('logDebug')) {
            logDebug("数据库初始化成功");
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("初始化数据库错误: " . $e->getMessage());
        if (function_exists('logDebug')) {
            logDebug("初始化数据库异常: " . $e->getMessage());
        }
        return false;
    }
} 