<?php
function adminer_object() {
	// Load plugin support
	require_once __DIR__ . '/plugins/plugin.php';
	
	// Load enabled plugins
	$plugins = [];
	foreach (glob(__DIR__ . '/plugins-enabled/*.php') as $plugin) {
		include_once $plugin;
	}
	
	// Get all plugin instances - only from plugins-enabled directory
	$instances = [];
	// Note: We're not using get_declared_classes() because that would include
	// Adminer's internal classes. Instead, plugins should return their instances.
	
	return new AdminerPlugin($instances);
}

// CSS handling
if (basename($_SERVER['DOCUMENT_URI'] ?? $_SERVER['REQUEST_URI']) === 'adminer.css' && is_readable('adminer.css')) {
	header('Content-Type: text/css');
	readfile('adminer.css');
	exit;
}

// Load Adminer
require __DIR__ . '/adminer.php';
