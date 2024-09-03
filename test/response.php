<?php
$sleep = rand(1, 5);
echo json_encode($_GET), PHP_EOL;
echo "Sleep random: ", $sleep, PHP_EOL;
sleep($sleep);

echo "Cost: ".(time() - $_SERVER['REQUEST_TIME']).' seconds';
