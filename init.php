<?php
include_once __DIR__ . '/saf/framework/functions/constants.php';
include_once __DIR__ . '/saf/framework/functions/string_functions.php';

if ($argc < 3) {
	die('Arguments attendus : nickname helloworld' . LF);
}

$project_name = strtolower($argv[2]);
$project_password = uniqid();
$project_dir = __DIR__ . SL . strtolower($argv[1] . SL . $argv[2]);
$application_file = $project_dir . SL . 'Application.php';
$config_file = __DIR__ . SL . strtolower($argv[2]) . '.php';
$namespace = ucfirst($argv[1]) . BS . ucfirst($argv[2]);
$config_name = str_replace(BS, SL, $namespace);
$helloworld_template = $project_dir . SL . 'Application_home.html';
$alias_script = lLastParse(__DIR__, SL) . SL . strtolower($argv[2]) . '.php';
$application_dir = rLastParse(__DIR__, SL);
$password_file = __DIR__ . SL . 'pwd.php';
$cache_dir = __DIR__ . SL . 'cache';
$tmp_dir = __DIR__ . SL . 'tmp';
$update_file = __DIR__ . SL . 'update';
$vendor_dir = __DIR__ . SL . 'vendor';
echo 'Initialization of your project ' . $namespace . '...' . LF;

echo '- Create directory ' . $project_dir . LF;
if (!is_dir($project_dir)) mkdir($project_dir, 0755, true);

echo '- Create application class file ' . $application_file . LF;
file_put_contents($application_file, <<<EOT
<?php
namespace {$namespace};

use SAF\Framework;

/**
 * The {$argv[2]} application
 */
class Application extends Framework\Application
{

}

EOT
);

echo '- Create password file ' . $password_file . LF;
file_put_contents($password_file, <<<EOT
<?php
\$pwd = [
	'{$project_name}' => '{$project_password}',
	'saf_demo' => '',
];

EOT
);

echo '- Create application configuration file ' . $config_file . LF;
file_put_contents($config_file, <<<EOT
<?php
namespace {$namespace};

use SAF\Framework;

global \$pwd;
require 'pwd.php';
require 'saf.php';

\$config['{$config_name}'] = [
	'app'     => Application::class,
	'extends' => 'SAF/Framework',

	'normal' => [
		Framework\Dao::class => [
			'database' => '{$project_name}',
			'login'    => '{$project_name}',
			'password' => \$pwd['{$project_name}']
		]
	]
];

EOT
);

echo '- Create helloworld home template file ' . $helloworld_template . LF;
file_put_contents($helloworld_template, <<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Hello, world !</title>
</head>
<body>
<!--BEGIN-->

Hello, world !

<!--END-->
</body>
</html>

EOT
);

echo '- Create alias script ' . $alias_script . LF;
file_put_contents($alias_script, <<<EOT
<?php
\$_SERVER['ENV'] = 'DEV';
chdir('$application_dir');
require 'index.php';

EOT
);

echo '- create cache directory ' . $cache_dir . LF;
if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);
exec('chown -R www-data.www-data ' . $cache_dir);

echo '- create temporary directory ' . $tmp_dir . LF;
if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, true);
exec('chown -R www-data.www-data ' . $tmp_dir);

echo '- create update file ' . $update_file . LF;
touch($update_file);
exec('chown www-data.www-data ' . $update_file);

echo '- download dependencies into ' . $vendor_dir . LF;
if (!is_dir($vendor_dir)) mkdir($vendor_dir, 0755, true);
chdir($vendor_dir);
if (!is_dir($vendor_dir . SL . 'textile')) exec('git clone https://github.com/textile/php-textile.git textile -b2.5');
if (!is_dir($vendor_dir . SL . 'reset5')) mkdir($vendor_dir . SL . 'reset5', 0755, true);
copy('http://reset5.googlecode.com/hg/reset.css', $vendor_dir . SL . 'reset5/reset.css');
if (!is_dir($vendor_dir . SL . 'jquery')) mkdir($vendor_dir . SL . 'jquery', 0755, true);
copy('http://code.jquery.com/jquery-1.8.3.js', $vendor_dir . SL . 'jquery/jquery-1.8.3.js');
copy('http://code.jquery.com/jquery-1.8.3.min.js', $vendor_dir . SL . 'jquery/jquery-1.8.3.min.js');
//exec('wget http://saf.re/dev/projects/saf/vendor/jquery-ui -r');
exec('chown -R www-data.www-data ' . $vendor_dir);
chdir(__DIR__);

echo '- create mysql database and user ' . $project_name . LF;
file_put_contents(__DIR__ . '/tmp/init.sql', <<<EOT
CREATE DATABASE IF NOT EXISTS {$project_name};
DELETE FROM mysql.user WHERE User = '{$project_name}';
DELETE FROM mysql.db WHERE User = '{$project_name}';
INSERT INTO mysql.user (Host, User, Password)
VALUES ('localhost', '{$project_name}', PASSWORD('{$project_password}'));
INSERT INTO mysql.db (Host, User, Db, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, References_priv, Index_priv, Alter_priv, Create_tmp_table_priv, Lock_tables_priv)
VALUES ('localhost', '{$project_name}', '{$project_name}', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');
FLUSH PRIVILEGES;
EOT
);
exec('mysql -uroot -p <' . __DIR__ . '/tmp/init.sql');

echo 'Your application is initialized' . LF;

