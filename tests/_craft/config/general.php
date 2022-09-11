<?php

return [
    'devMode' => true,

    # Disable csrf in case if there no possibility to get csrf in tests
    'enableCsrfProtection' => false,

    'securityKey' => getenv('SECURITY_KEY'),
];
