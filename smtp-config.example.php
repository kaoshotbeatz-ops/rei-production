<?php
/* Copy this file to  smtp-config.php  (same folder) and fill in the real values.
   smtp-config.php is gitignored so deploys never overwrite it and the password never hits git. */
return [
  'host' => 'smtp.office365.com',
  'port' => 587,
  'user' => 'forms@racialequityinstitute.org',   // the M365 mailbox that sends (Authenticated SMTP ON)
  'pass' => 'APP-PASSWORD-HERE',                  // app password from Microsoft 365
  'from' => 'forms@racialequityinstitute.org',    // usually same as user
  'to'   => 'info@racialequityinstitute.org, omar@dbaomarhuertasllc.com',  // who receives (comma-separated)
];
