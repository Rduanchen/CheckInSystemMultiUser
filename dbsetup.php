<?php
    $db = new SQLite3('db.sqlite');
    $db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT, password TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS records (id INTEGER PRIMARY KEY, username TEXT, time TEXT, action TEXT, ip TEXT, latitude TEXT, longitude FLOAT, location TEXT)');
    // ADD admin user
    $db->exec("INSERT INTO users (username, password) VALUES ('admin', 'admin')");
    // Read all userinfo
    $stmt = $db->prepare('SELECT * FROM users');
    $result = $stmt->execute();
    while ($row = $result->fetchArray()) {
        echo 'User: ' . $row['username'] . ' Password: ' . $row['password'] . '<br>';
    }
    echo 'Database setup completed.';
?>