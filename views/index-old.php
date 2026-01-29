<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Using HTMX for frontend -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <title>Cron Simulator</title>
    <style>
        #log-container {
            height: 350px;         /* Tentukan tinggi maksimal */
            overflow-y: auto;      /* Munculkan scrollbar jika konten penuh */
            background: #1e1e1e;   /* Warna gelap ala terminal */
            color: #00ff00;        /* Teks hijau terminal */
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', Courier, monospace;
            border: 2px solid #333;
        }
    </style>
</head>
<body>

    <h1>Real-time Cron Log</h1>

    <div id="log-container" 
         hx-get="get_logs.php" 
         hx-trigger="every 5s" 
         hx-swap="innerHTML"
         hx-on::after-settle="this.scrollTop = this.scrollHeight">
        Menunggu log...
    </div>

    <p><small>Log akan diperbarui otomatis setiap 5 detik.</small></p>

</body>
</html>