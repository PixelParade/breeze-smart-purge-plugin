<?php
/**
 * Staging only — mu-plugin loader for BSP test CPT (Smart Purge QA).
 *
 * Install: scripts/install-staging-test-mu-plugin.ps1
 * Never ship in agency / wp.org plugin zips or MainWP client deploys.
 *
 * @package Breeze_Smart_Purge
 */

$cpt_paths = array(
	__DIR__ . '/bsp-staging-test-cpt/bsp-test-cpt.php',
	WP_CONTENT_DIR . '/novamira-sandbox/bsp-test-cpt.php',
);

foreach ($cpt_paths as $cpt_file) {
	if (is_readable($cpt_file)) {
		require_once $cpt_file;
		return;
	}
}
