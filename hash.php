<?php

$input = "admin";

$hash = password_hash($input, PASSWORD_BCRYPT);

echo $hash;
