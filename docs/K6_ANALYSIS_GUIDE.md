# 📑 k6 Load Test Result Analysis Guide
*Reference Documentation: JSON Streaming & Gzip Optimization (April 2026)*

This guide serves as a manual reference for reading and analyzing the output of k6 load testing, specifically for monitoring backend performance, network efficiency, and system stability.

---

## 1. CORE METRICS: HTTP (Responsiveness)
This section measures how quickly and reliably the server handles user requests.

| Metric | Technical Meaning | Analysis Example (Reference Data) |
| :--- | :--- | :--- |
| **`http_req_duration`** | Total server response time (from request sent to response finished). | **Avg=4.62s**: On average, users wait 4.6 seconds for the data. |
| **`med` (Median)** | The middle value of all request durations. | **Med=4.61s**: If Avg and Med are close, the server performance is consistent. |
| **`p(90) / p(95)`** | The time threshold for the fastest 90% or 95% of requests. | **p(95)=5.02s**: 95% of users received data in under 5 seconds. This is a real-world UX indicator. |
| **`http_req_failed`** | Percentage of failed requests (e.g., Error 500, 502, 504). | **0.00%**: The system is highly reliable with no functional failures. |
| **`http_reqs`** | Throughput or the number of successful requests per second. | **10.47/s**: The server consistently serves ~10 large data fetches per second. |

---

## 2. CORE METRICS: NETWORK (Efficiency)
Measures the data traffic load passing through the communication channel between client and server.

* **`data_received` (79 MB / 258 kB/s)**
    * **Efficiency Analysis:** If the raw data size is expected to be ~400MB but only 79MB is recorded, **Gzip/Compression** is active and effective.
    * **Indicator:** A speed of 258 kB/s shows very lean bandwidth usage for high-volume data.
* **`data_sent` (1.6 MB)**
    * Data sent from the client (Headers, API Keys, Params). If this number is unexpectedly high, check for bloated request payloads.

---

## 3. CORE METRICS: EXECUTION (Load Intensity)
Describes the scale of the load applied during the testing duration.

* **`vus` (Virtual Users):** Number of simulated users active at the same time. 
    * *Reference: Max 50 VUs.*
* **`iteration_duration` (Avg=4.73s):** Total time to complete one full script cycle. A small gap between this and `http_req_duration` indicates minimal overhead on the client (k6) side.

---

## 4. COMPARISON GUIDE (CHEAT SHEET)

Use this table to determine if your test results pass the **Healthy** criteria:

| Indicator | HEALTHY | CRITICAL |
| :--- | :--- | :--- |
| **Error Rate** | Exactly 0.00% | Above 1.00% (Server is starting to struggle). |
| **p(95) Stability** | Gap between `p(95)` and `Avg` < 1s. | `p(95)` is significantly higher than `Avg` (Server jitter). |
| **Network Payload** | `data_received` is low (Gzip ON). | `data_received` is very large (Bandwidth wastage). |
| **Max Latency** | Remains controlled (e.g., < 7s). | `Max` value increases over time (Potential Memory Leak). |

---

## 5. QUICK ANALYSIS (3 STEPS)

1.  **Check for Failures:** If `http_req_failed` > 0, stop the test and inspect server logs (Error 500/502).
2.  **Check Latency:** Focus on `p(95)`. If it is under your SLA (e.g., 5-6 seconds), the performance is acceptable.
3.  **Check Efficiency:** Inspect `data_received`. Ensure compression is working to save server costs and speed up data delivery to the end-user.

---