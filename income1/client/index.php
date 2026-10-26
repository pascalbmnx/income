<?php
/**
 * Open the home page when browsing /client/. 
 * The client area hosts auth pages only; there is no dashboard here.
 */
require_once dirname(__DIR__) . '/includes/db.php';
header('Location: ' . baseUrl() . '/index.php');
exit;