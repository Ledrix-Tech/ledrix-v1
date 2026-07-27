<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Unauthorized Access</title>
    <style>
        body {
            background-color: black;
            color: #00ff00;
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        .line {
            white-space: pre;
        }

        .ip {
            position: absolute;
            color: #ff0000;
            font-size: 12px;
            opacity: 0.6;
            animation: fadeout 5s forwards;
        }

        @keyframes fadeout {
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .alert-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 0, 0, 0.9);
            color: white;
            padding: 30px 50px;
            font-size: 1.8rem;
            font-weight: bold;
            border: 3px solid white;
            z-index: 9999;
            text-align: center;
            animation: pulse 1s infinite;
            display: none;
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.2);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div id="terminal"></div>
    <div class="alert-box" id="alertBox">🚨 INTRUSION DETECTED<br>Redirecting...</div>

    <script>
        const lines = [
            "ACCESS DENIED...",
            "Unauthorized attempt detected...",
            "IP Logging...",
            "Geo-locating user...",
            "Reverse tracing route...",
            "Flagged by Internal Security Protocol...",
            "Reporting to Admin...",
            "Session terminating..."
        ];

        const terminal = document.getElementById('terminal');
        let index = 0;

        function typeLine() {
            if (index >= lines.length) {
                setTimeout(showDangerAlert, 300);
                return;
            }

            const line = document.createElement('div');
            line.className = 'line';
            terminal.appendChild(line);

            let charIndex = 0;

            function typeChar() {
                if (charIndex < lines[index].length) {
                    line.textContent += lines[index][charIndex++];
                    setTimeout(typeChar, 50);
                } else {
                    index++;
                    setTimeout(typeLine, 500);
                }
            }

            typeChar();
        }

        function generateRandomIPs() {
            for (let i = 0; i < 20; i++) {
                const ip = `${rand(1, 255)}.${rand(0, 255)}.${rand(0, 255)}.${rand(0, 255)}`;
                const span = document.createElement('span');
                span.className = 'ip';
                span.textContent = `Tracing IP: ${ip}`;
                span.style.top = rand(10, window.innerHeight - 30) + 'px';
                span.style.left = rand(10, window.innerWidth - 200) + 'px';
                document.body.appendChild(span);
            }
        }

        function rand(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        function showDangerAlert() {
            const alert = document.createElement('div');
            alert.className = 'alert-screen';
            alert.innerHTML = '🚨 Suspicious Activity Detected!<br>Redirecting...';
            document.body.appendChild(alert);

            setTimeout(() => {
                window.location.href = "{{ route('seller.login.get') }}";
            }, 5000);
        }

        typeLine();
        setInterval(generateRandomIPs, 1500); // keep generating fake IPs



        // const terminal = document.getElementById('terminal');
        // const alertBox = document.getElementById('alertBox');

        // function getRandomIP() {
        //     return `${rand(1, 255)}.${rand(0, 255)}.${rand(0, 255)}.${rand(1, 255)}`;
        // }

        // function rand(min, max) {
        //     return Math.floor(Math.random() * (max - min + 1)) + min;
        // }

        // function addLine(content) {
        //     const line = document.createElement('div');
        //     line.className = 'line';
        //     line.textContent = content;
        //     terminal.appendChild(line);

        //     // Keep only latest 50 lines
        //     if (terminal.children.length > 50) {
        //         terminal.removeChild(terminal.firstChild);
        //     }
        // }

        // let count = 0;
        // const interval = setInterval(() => {
        //     const ip = getRandomIP();
        //     const traceMsg = `> Tracing IP: ${ip}`;
        //     addLine(traceMsg);
        //     count++;

        //     if (count >= 30) {
        //         clearInterval(interval);
        //         showAlert();
        //     }
        // }, 80); // fast scrolling like hacking terminal

        // function showAlert() {
        //     alertBox.style.display = 'block';
        //     setTimeout(() => {
        //         window.location.href = "{{ route('admin.login.get') }}";
        //     }, 5000);
        // }
    </script>
</body>

</html>
