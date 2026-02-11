<?php
/**
 * Zuschuss Piloten - Logout
 */

require_once 'auth.php';
doLogout();
header('Location: login.php');
exit;
