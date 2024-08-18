<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$db = new SQLite3('db.sqlite');

// 處理各種操作請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];
    $postData = json_decode(file_get_contents('php://input'), true);
    
    // 新增使用者
    if (isset($postData['action']) && $postData['action'] === 'add_user') {
        $username = $postData['username'];
        // $password = password_hash($postData['password'], PASSWORD_BCRYPT);
        $password = $postData['password'];
        $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $stmt->execute();
        $response['message'] = 'User added successfully';
    }

    // 刪除使用者
    if (isset($postData['action']) && $postData['action'] === 'delete_user') {
        $username = $postData['username'];
        $stmt = $db->prepare('DELETE FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->execute();
        $response['message'] = 'User deleted successfully';
    }

    // 變更使用者密碼
    if (isset($postData['action']) && $postData['action'] === 'change_password') {
        $username = $postData['username'];
        // $new_password = password_hash($postData['new_password'], PASSWORD_BCRYPT);
        $new_password = $postData['new_password'];
        $stmt = $db->prepare('UPDATE users SET password = :password WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $new_password, SQLITE3_TEXT);
        $stmt->execute();
        $response['message'] = 'Password changed successfully';
    }

    // 瀏覽使用者簽到記錄
    if (isset($postData['action']) && $postData['action'] === 'view_records') {
        $username = $postData['username'];
        $stmt = $db->prepare('SELECT * FROM records WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $records = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $records[] = $row;
        }
        $response['records'] = $records;
    }

    // 修改簽到記錄（新增/更新）
    if (isset($postData['action']) && ($postData['action'] === 'add_record' || $postData['action'] === 'modify_record')) {
        $record_id = $postData['record_id'] ?? null;
        $username = $postData['username'];
        $time = $postData['time'];
        $action = $postData['action_type'];
        $stmt = $db->prepare('REPLACE INTO records (id, username, time, action, ip, longitude, latitude, location) VALUES (:id, :username, :time, :action, :ip, :longitude, :latitude, :location)');
        $stmt->bindValue(':id', $record_id, SQLITE3_INTEGER);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':time', $time, SQLITE3_TEXT);
        $stmt->bindValue(':action', $action, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $postData['ip'], SQLITE3_TEXT);
        $stmt->bindValue(':longitude', $postData['longitude'], SQLITE3_FLOAT);
        $stmt->bindValue(':latitude', $postData['latitude'], SQLITE3_FLOAT);
        $stmt->bindValue(':location', $postData['location'], SQLITE3_TEXT);
        $stmt->execute();
        $response['message'] = 'Record added/updated successfully';
    }

    // 修改簽到記錄（刪除）
    if (isset($postData['action']) && $postData['action'] === 'delete_record') {
        $record_id = $postData['record_id'];
        $stmt = $db->prepare('DELETE FROM records WHERE id = :id');
        $stmt->bindValue(':id', $record_id, SQLITE3_INTEGER);
        $stmt->execute();
        $response['message'] = 'Record deleted successfully';
    }

    // 檢索所有使用者
    if (isset($postData['action']) && $postData['action'] === 'get_users') {
        $users = [];
        $stmt = $db->prepare('SELECT * FROM users');
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $response['users'] = $users;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 計算每月工作時數
function calculateMonthlyHours($username, $db) {
    $stmt = $db->prepare('SELECT * FROM records WHERE username = :username ORDER BY time');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $monthlyHours = [];
    $lastCheckIn = null;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $date = new DateTime($row['time']);
        $monthKey = $date->format('Y-m');
        if ($row['action'] == 'CheckIn') {
            $lastCheckIn = $date;
        } elseif ($row['action'] == 'CheckOut' && $lastCheckIn) {
            $hoursWorked = $date->getTimestamp() - $lastCheckIn->getTimestamp();
            $hoursWorked /= 3600; // convert to hours
            if (!isset($monthlyHours[$monthKey])) {
                $monthlyHours[$monthKey] = 0;
            }
            $monthlyHours[$monthKey] += $hoursWorked;
            $lastCheckIn = null;
        }
    }
    return $monthlyHours;
}

// 下載 CSV
if (isset($_GET['download_csv'])) {
    $username = $_GET['username'];
    $stmt = $db->prepare('SELECT * FROM records WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="records.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'Time', 'Action', 'IP', 'Longitude', 'Latitude', 'Location']);
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
<div id="app" class="container">
    <h1>Admin Panel</h1>
    <h2>Manage Users</h2>
    <hr>
    <h3>Modify Account and Password</h3>
    <table border="1">
        <tr>
            <th>Username</th>
            <th>Actions</th>
        </tr>
        <tr v-for="user in users" :key="user.id">
            <td>{{ user.username }}</td>
            <td>
                <button @click="deleteUser(user.username)">Delete</button>
                <input type="password" v-model="user.new_password" placeholder="New Password" required>
                <button @click="changePassword(user.username, user.new_password)">Change Password</button>
            </td>
        </tr>
    </table>
    <h3>Add User</h3>
    <form @submit.prevent="addUser">
        <label for="username">Username:</label>
        <input type="text" v-model="newUser.username" required>
        <label for="password">Password:</label>
        <input type="password" v-model="newUser.password" required>
        <button type="submit">Add User</button>
    </form>

    <h2>View Records</h2>
    <hr>
    <form @submit.prevent="viewRecords">
        <label for="username">Username:</label>
        <input type="text" v-model="recordUsername" required placeholder="Enter username">
        <button type="submit">View Records</button>
    </form>

    <table v-if="records.length" border="1">
        <h3>Records for {{ recordUsername }}</h3>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Time</th>
            <th>Action</th>
            <th>IP</th>
            <th>Longitude</th>
            <th>Latitude</th>
            <th>Location</th>
            <th>Modify</th>
            <th>Delete</th>
        </tr>
        <tr v-for="record in records" :key="record.id">
            <td>{{ record.id }}</td>
            <td><input type="text" v-model="record.username" required></td>
            <td><input type="text" v-model="record.time" required></td>
            <td><input type="text" v-model="record.action" required></td>
            <td><input type="text" v-model="record.ip" required></td>
            <td><input type="text" v-model="record.longitude" required></td>
            <td><input type="text" v-model="record.latitude" required></td>
            <td><input type="text" v-model="record.location" required></td>
            <td><button @click="modifyRecord(record)">Modify</button></td>
            <td><button @click="deleteRecord(record.id)">Delete</button></td>
        </tr>
    </table>

    <h3>Add Record</h3>
    <form @submit.prevent="addRecord">
        <input type="text" v-model="newRecord.username" placeholder="Username" required>
        <input type="text" v-model="newRecord.time" placeholder="Time (YYYY-MM-DD HH:MM:SS)" required>
        <input type="text" v-model="newRecord.action" placeholder="Action" required>
        <input type="text" v-model="newRecord.ip" placeholder="IP Address" required>
        <input type="text" v-model="newRecord.longitude" placeholder="Longitude" required>
        <input type="text" v-model="newRecord.latitude" placeholder="Latitude" required>
        <input type="text" v-model="newRecord.location" placeholder="Location" required>
        <button type="submit">Add Record</button>
    </form>

    <form @submit.prevent="downloadCsv">
        <input type="hidden" v-model="recordUsername">
        <button type="submit" class='SubmitBtn'>Download CSV</button>
    </form>

    <h2>Calculate Monthly Hours</h2>
    <hr>
    <form @submit.prevent="calculateHours">
        <label for="username">Username:</label>
        <input type="text" v-model="recordUsername" required>
        <button type="submit">Calculate Hours</button>
    </form>

    <div v-if="monthlyHours.length">
        <h3>Monthly Hours for {{ recordUsername }}</h3>
        <ul>
            <li v-for="(hours, month) in monthlyHours" :key="month">{{ month }}: {{ hours }} hours</li>
        </ul>
    </div>
</div>

<script>
new Vue({
    el: '#app',
    data: {
        users: [],
        newUser: {
            username: '',
            password: ''
        },
        recordUsername: '',
        records: [],
        newRecord: {
            username: '',
            time: '',
            action: '',
            ip: '',
            longitude: '',
            latitude: '',
            location: ''
        },
        monthlyHours: {}
    },
    methods: {
        async fetchData(action, data = {}) {
            const response = await axios.post('admin.php', { action, ...data });
            return response.data;
        },
        async getUsers() {
            const data = await this.fetchData('get_users');
            this.users = data.users || [];
        },
        async addUser() {
            await this.fetchData('add_user', this.newUser);
            this.newUser.username = '';
            this.newUser.password = '';
            this.getUsers();
        },
        async deleteUser(username) {
            await this.fetchData('delete_user', { username });
            this.getUsers();
        },
        async changePassword(username, new_password) {
            await this.fetchData('change_password', { username, new_password });
            this.getUsers();
        },
        async viewRecords() {
            const data = await this.fetchData('view_records', { username: this.recordUsername });
            this.records = data.records || [];
        },
        async modifyRecord(record) {
            await this.fetchData('modify_record', record);
            this.viewRecords();
        },
        async deleteRecord(record_id) {
            await this.fetchData('delete_record', { record_id });
            this.viewRecords();
        },
        async addRecord() {
            await this.fetchData('add_record', this.newRecord);
            this.newRecord.username = '';
            this.newRecord.time = '';
            this.newRecord.action = '';
            this.newRecord.ip = '';
            this.newRecord.longitude = '';
            this.newRecord.latitude = '';
            this.newRecord.location = '';
            this.viewRecords();
        },
        async calculateHours() {
            const data = await this.fetchData('calculate_hours', { username: this.recordUsername });
            this.monthlyHours = data.monthlyHours || {};
        },
        downloadCsv() {
            window.location.href = `admin.php?download_csv=1&username=${this.recordUsername}`;
        }
    },
    mounted() {
        this.getUsers();
    }
});
</script>

<style>
@font-face {
    font-family: 'Cyber';
    src: url('./assets/Valorax-lg25V.otf');
}
@font-face {
    font-family: 'Console';
    src: url('./assets/Consolas.ttf');
}
body{
    background-color: black;
    color: #2388d2;
    padding: 3rem;
    font-family: 'Console';
}
.container {
    display: flex;
    flex-direction: column;
    align-items: start;
    width: 100%;
}
h1 {
    font-family: 'Cyber';
    color: #f6e837;
    font-size: 3rem;
    margin-bottom: 1rem;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}
th, td {
    border: 1px solid #f6e837;
    padding: 0.5rem;
    text-align: left;
}
button {
    border: solid 1px #f6e837;
    background-color: black;
    color: white;
    width: 160px;
    height: 5vh;
    font-family: 'Console';
}
input{
    height: 5vh;
    width: 160px;
    font-family: 'Console';
    color: white;
    border: solid 1px #f6e837;
    background-color: black;
}
@keyframes animate {
    0% {
        background-color: black;
        color: #808184;
    }   
    100% {
        background-color: white;
        color: black;
    }
}
button:hover {
    background-color: white;
    color: black;
    animation: animate 0.5s ease-in;
}
a {
    color: #2388d2;
}
hr{
    border: 1px solid #808184;
    width: 100%;
    margin-bottom: 1rem;
}
.SubmitBtn{
    margin-top: 1rem;
}
</style>
</body>
</html>
