<?php

/**
 * Validation messages that differ between CodeIgniter 4 and CodeIgniter 3:
 * the sitepanel keeps the CodeIgniter 3 wording. Every other rule message is
 * already the same in both versions (system/Language/en/Validation.php).
 */
return [
    'alpha_dash'          => 'The {field} field may only contain alpha-numeric characters, underscores, and dashes.',
    'alpha_numeric'       => 'The {field} field may only contain alpha-numeric characters.',
    'alpha_numeric_space' => 'The {field} field may only contain alpha-numeric characters and spaces.',
    'valid_base64'        => 'The {field} field must contain a valid Base64 string.',
];
