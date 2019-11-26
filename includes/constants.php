<?php

/**
 * Database Constants - these constants are required in order for there to be a 
 * successful connection to the database. Make sure the information is correct.
 */
define("DB_TYPE", "mysql");
define("DB_HOST", "localhost");
define("DB_USER", "ipay");
define("DB_PASS", "0725313208Evance!");
define("DB_NAME", "slashdotpro_ipay");


/**
 * Special Name Constants - It's important that the ADMIN_NAME constant matches
 * the Super Admin username (case not important) who is level 10.
 */
define("ADMIN_NAME", "Evance");
define("GUEST_NAME", "Guest");


/**
 * Level Constants - Best not to change these unless you know what you are doing.
 * Use groups to assign levels.
 */
define("SUPER_ADMIN_LEVEL", 10); // Super Admin - There can be only one!
define("ADMIN_LEVEL", 9); // Other Admins - Promoted by the Super Admin 
define("REGUSER_LEVEL", 3); // Normal Registered User
define("ADMIN_ACT", 2); // Awaiting Admin activation
define("ACT_EMAIL", 1); // Awaiting Email Activation
define("GUEST_LEVEL", 0);
