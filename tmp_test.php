<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test';
require 'C:/xampp/htdocs/TWITTER_CLONE/pages/index.php';
