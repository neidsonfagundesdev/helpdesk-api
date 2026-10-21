<?php

require_once __DIR__ . "/../vendor/autoload.php";

header("Content-Type: application/json; charset=UTF-8");

echo json_encode(["mensagem" => "Help desk API"]);