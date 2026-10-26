<?php
/**
 * Admin login session check.
 * Include at the top of every protected admin page.
 */
require_once dirname(__DIR__, 2) . '/includes/db.php';
requireAuth();
