<?php
    session_start();
    if (isset($_SESSION['username']) && isset($_COOKIE['username'])){
        if ( $_COOKIE['username'] == $_SESSION['username'] ){
            // $html = file_get_contents('mainpage.html');
            // echo $html;
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTWA CheckIn System</title>
    <script src="https://apis.google.com/js/api.js"></script>
    <script>
        ip = null;
        latitude = null;
        longitude = null;
        function calculateWorkingHours(records) {
            const monthlyHours = {};
            let lastCheckIn = null;

            records.forEach(record => {
                const [time, action] = record;
                const date = new Date(time);
                const monthKey = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;

                if (action === 'CheckIn') {
                    lastCheckIn = date;
                } else if (action === 'CheckOut' && lastCheckIn) {
                    const hoursWorked = (date - lastCheckIn) / (1000 * 60 * 60);
                    if (!monthlyHours[monthKey]) {
                        monthlyHours[monthKey] = 0;
                    }
                    monthlyHours[monthKey] += hoursWorked;
                    lastCheckIn = null;
                }
            });

            const workingHoursDiv = document.getElementById('workingHours');
            workingHoursDiv.innerHTML += '<h2>Monthly Working Hours:</h2>' ;
            for (const [month, hours] of Object.entries(monthlyHours)) {
                workingHoursDiv.innerHTML += `<p>${month}: ${hours.toFixed(2)} hours</p>`;
            }
            return monthlyHours;
        }
        async function sendRequest(action) {
            try {
                let response;
                if (action == 'CheckIn' || action == 'CheckOut') {
                    console.log(`./index2.php?action=${action}&ip=${ip}&latitude=${latitude}&longitude=${longitude}`);
                    response = await fetch(`./index2.php?action=${action}&ip=${ip}&latitude=${latitude}&longitude=${longitude}`);
                }
                else{
                    response = await fetch(`./index2.php?action=${action}`);
                }
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                let result = await response.text();
                console.log(result);
                if (action === 'GetInfo') {
                    calculateWorkingHours(JSON.parse(result));
                }
                else if (action === 'Read'){
                    document.getElementById('response').innerHTML += result + '<br>';
                }
                else {
                    document.getElementById('response').innerHTML += result + '<br>';            
                }
            } catch (error) {
                console.error('There was a problem with the fetch operation:', error);
            }
        }
        async function getip() {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            document.getElementById('Info').innerHTML += data.ip;
            console.log(data.ip);
            ip = data.ip;
        }
        document.addEventListener('DOMContentLoaded', function() {
            getip();
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    console.log("Latitude is :", position.coords.latitude);
                    console.log("Longitude is :", position.coords.longitude);
                    latitude = position.coords.latitude;
                    longitude = position.coords.longitude;
                    document.getElementById('Pos').innerHTML += `Latitude: ${position.coords.latitude}, Longitude: ${position.coords.longitude}`;
                    // console.log(position.coords.latitude > 21.991)
                    // console.log(position.coords.latitude > 21.992)
                    // console.log(position.coords.longitude > 120.217)
                    // console.log(position.coords.longitude < 120.218)
                    // if (position.coords.latitude < 21.991 && position.coords.latitude < 21.992 && position.coords.longitude < 120.217 && position.coords.longitude > 120.218) {
                    //     console.log("Available");
                    //     document.body.innerHTML = "<h1>Access Denied</h1><br><p>Not in the right location</p>";
                    //     document.body.style.color = "red";
                    //     document.body.style.backgroundColor = "black";
                    //     document.body.style.fontFamily = 'Console';
                    //     return;
                    // }
                    // else {
                    //     console.log("Not Available");
                    // }
                });
            } else {
                console.log("Not Available");
            }
            document.getElementById('CheckIn').addEventListener('click', function() {
                sendRequest('CheckIn');
            });

            document.getElementById('CheckOut').addEventListener('click', function() {
                sendRequest('CheckOut');
            });

            document.getElementById('Download').addEventListener('click', function() {
                sendRequest('Read');
            });
            
            document.getElementById('GetInfo').addEventListener('click', function() {
                sendRequest('GetInfo');
            });
        });
       
    </script>
</head>
<body>
<div class="container">
    <h1>PTWA CheckIn System</h1>
    <div class="BtnGroup">
        <button id="CheckIn">CheckIn</button>
        <button id="CheckOut">CheckOut</button>
        <button id="Download">Download</button>
        <button id="GetInfo">GetInfo</button>
    </div>
    <p id="Info">User: Rduan @ </p>
    <p id="Pos">Position: </p>
    <p>Response Info:</p>
    <div id="response"></div>
    <div id="workingHours"></div>
</div>
</body>
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
    padding: 1rem;
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
.BtnGroup {
    @media screen and (max-width: 600px){
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 200px;
        gap: 20px;
    }
    display: flex;
    flex-direction: row;
    gap: 20px;
    width: 100%;
}
.BtnGroup button {
    border: solid 1px #f6e837;
    background-color: black;
    color: white;
    width: 160px;
    height: 5vh;
    font-family: 'Console';
    @media screen and (max-width: 600px){
        width: 100%;
    }
}
@keyframes animate {
    0% {
        background-color: black;
        color: white;
    }   
    100% {
        background-color: white;
        color: black;
    }
}
.BtnGroup button:hover {
    background-color: white;
    color: black;
    animation: animate 0.5s ease-in;
}
a {
    color: #2388d2;
}
</style>
</html>

<?php
        }
        else{
            $html = file_get_contents('login.html');
            echo $html;
        }
    }
    else{
        $html = file_get_contents('login.html');
        echo $html;
    }
?>
