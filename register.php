<?php
require_once 'config/constants.php';
require_once 'includes/functions.php';

// Public registration disabled — only agents and admins can add retailers
redirect(BASE_URL . '/index.php');
