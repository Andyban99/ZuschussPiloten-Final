<?php
/**
 * Zuschuss Piloten - Kunden Logout
 */

require_once 'auth.php';

doKundeLogout();

header('Location: login.php');
exit;
