import http from 'k6/http';
import { check, sleep } from 'k6';

// Simulasi: 50 user langsung hit server sejak detik pertama sampai detik akhir.
export const options = {
    vus: 50,           // 50 concurrent users
    duration: '30s',   // for 30 seconds
    // duration: '5m',   // for 5 minutes
};

// // Simulasi: Trafik Dinamis (comment "options" diatas untuk diterapkan di simulasi testing)
// export const options = {
//     stages: [
//         { duration: '30s', target: 20 }, // Ramp-up: dari 1 ke 20 user dalam 30 detik
//         { duration: '1m', target: 50 }, // Ramp-up: dari 20 ke 50 user dalam 1 menit
//         { duration: '3m', target: 50 }, // Stay: bertahan di 50 user selama 3 menit
//         { duration: '30s', target: 0 },  // Ramp-down: turun perlahan ke 0 (graceful stop)
//     ],
// };

export default function () {
    const url = 'http://localhost:8000/api/v1/dashboard'; // Change to your endpoint
    const payload = JSON.stringify({
        title: 'JSON data <script>document.print</script>',
        tag_html: {div: '<div onclick=\'submitError()\'>Submit</div>'},
        page: '1',
        offset: '0',
        limit: 1000, // Change Limit to real apps paging system (1000 just for stress testing, usually between 10 - 100)
        float: 1.091,
        age: '18',
        username: 'lutvi',
        email: 'lutvi@demo.local',
        website: 'http://demo.local:8000/dashboard/'
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Accept-Encoding': 'gzip', // Ask server to handle compressed response
            'X-API-KEY': 'sswrSrFtV1VkYz0ikG4dpouo1uEqEvS9cZ3QfwgTxdc=', // Test your Zero-Trust layer
        },
    };

    const res = http.request('GET', url, payload, params);
    // const res = http.post(url, payload, params);

    // // Debug
    // if (res.status >= 200 && res.status < 300) {
    //     console.log(`Error! Status: ${res.status}, Body: ${res.body}`);
    // }

    // This checks if your Go-sanitizer and PHP logic are working
    check(res, {
        // 'is status 200': (r) => r.status === 200,
        'is status success': (r) => r.status >= 200 && r.status < 300,
        'body has statusCode': (r) => r.json().hasOwnProperty('statusCode'),
    });

    sleep(0.1); // Small pause to simulate real-world behavior
}
