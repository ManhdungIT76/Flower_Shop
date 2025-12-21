<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function forbid_admin_buying(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') return;

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ADMIN_CANNOT_BUY';
    exit;
}
