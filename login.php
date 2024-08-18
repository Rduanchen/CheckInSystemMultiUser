<?php
session_start();
// 建立資料庫連接並檢查錯誤
try {
    $db = new SQLite3('db.sqlite');
} catch (Exception $e) {
    echo 'dberror';
    exit;
}

// 處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['username']) && isset($_GET['password'])) {
    // $username = trim($_POST['username']);
    // $password = trim($_POST['password']);
    $username = $_GET['username'];
    $password = $_GET['password'];
    // 檢查輸入是否為空
    if (empty($username) || empty($password)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Username and password are required']);
        exit;
    }

    // 查詢用戶資料
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray();
    // 驗證密碼
    if ($row['password'] == $password) {
        // 設置 Cookie，添加 HttpOnly 和 Secure 標誌
        setcookie('username', $username, time() + (3600 * 24 * 3), '/', '', isset($_SERVER['HTTPS']), true);
        // 設置 Session
        $_SESSION['username'] = $username;
        echo 'suceess';
        exit;
    } else {
        // 返回 JSON 錯誤消息
        echo "fail";
    }
} else {
    // 返回 JSON 錯誤消息
    echo "fail";
}
?>