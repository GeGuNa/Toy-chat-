$attempts = $cache->get('login_attempts_' . $_SERVER['REMOTE_ADDR']);
if ($attempts > 5) {
    http_response_code(429);
    die("Too many attempts. Try again later.");
}
