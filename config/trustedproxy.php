<?php

return [
    // Reverse proxies allowed to send X-Forwarded-For / -Host / -Proto:
    // comma separated IPs or ranges (e.g. 10.0.0.0/8), or * when the app is ONLY reachable through
    // the proxy (ngrok, cloud load balancer). Empty = no proxy trusted.
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
