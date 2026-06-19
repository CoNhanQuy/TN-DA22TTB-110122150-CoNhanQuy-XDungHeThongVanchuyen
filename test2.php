<?php
require 'backend/cauhinh.php';
require 'backend/xacthuc_dangnhap.php';

$endpoints = ['users', 'vehicles', 'routes', 'delivery_persons', 'pricing', 'customers', 'orders'];
foreach($endpoints as $ep) {
    $_GET['action'] = $ep;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    echo "--- $ep ---\n";
    
    // Simulate what api.php does roughly or just use cURL to localhost if apache is running?
    // Let's use curl to localhost to see the EXACT output including HTTP headers!
}
