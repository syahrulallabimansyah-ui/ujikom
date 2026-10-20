# -*- coding: utf-8 -*-
"""
Capture live screens of AKSA NOVA from local Laragon server
"""
import os
import subprocess
import time

def setup_session_helper():
    # Helper PHP script to establish session for headless Chrome
    php_code = """<?php
session_start();
$role = $_GET['role'] ?? 'member';
if ($role === 'admin') {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrator';
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    echo "SESSION_ADMIN_OK";
} else {
    $_SESSION['user_id'] = 26;
    $_SESSION['user_name'] = 'Tsaritsa Anastasia';
    $_SESSION['username'] = 'tsaritsaanastasia';
    $_SESSION['role'] = 'member';
    echo "SESSION_MEMBER_OK";
}
"""
    with open("set_dev_session.php", "w", encoding="utf-8") as f:
        f.write(php_code)

if __name__ == '__main__':
    setup_session_helper()
    print("set_dev_session.php created.")
