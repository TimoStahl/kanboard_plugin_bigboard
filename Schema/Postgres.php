<?php

namespace Kanboard\Plugin\Bigboard\Schema;

use PDO;

const VERSION = 4;

function version_4(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bigboard_selected (
          id SERIAL PRIMARY KEY,
          user_id INTEGER NOT NULL,
          project_id INTEGER NOT NULL,
          UNIQUE(user_id, project_id)
        )
    ");	
	$pdo->exec("
        CREATE TABLE IF NOT EXISTS bigboard_collapsed (
          id SERIAL PRIMARY KEY,
          user_id INTEGER NOT NULL,
          project_id INTEGER NOT NULL,
          UNIQUE(user_id, project_id)
        )
    ");	

}
