<?php
/**
 * Generates a hash to use with the php password_verify function
 *  Run this to get the hash then delete from your server of course!!
 */
$options = [
    'cost' => 12,
];
echo password_hash("R8hG5aE-k5619GxPee3", PASSWORD_BCRYPT, $options);
?>

$2y$12$6R155FQWKe0Z2FUZxm/.ruRF8LxAvIq5g1i5gz8eeuDFnZfRX4etu