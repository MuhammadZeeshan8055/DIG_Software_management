<?php

/**
 * Fallback entry point when the host document root is the project folder
 * (not /public). Prefer pointing the domain/subfolder to /public when possible.
 */
require __DIR__.'/public/index.php';
