<?php
include('./classes/MailSender.php');
include('./classes/Logger.php');
include('./config.php');

$db       = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$letter   = ['subject' => 'subject', 'message' => 'message'];
$cursor   = $db->query('SELECT email FROM users WHERE DATE(end_date) = DATE_ADD(CURDATE(),INTERVAL 4 DAY)');

if ($cursor->num_rows ?? false) 
{
	Logger::time();
	Logger::info( 'Start newsletter ' . date("Y-m-d") . '. Emails quantity:' . $cursor->num_rows );
	Logger::dump_to_file(LOG_FILE);
	Logger::clear_log();

	fwrite(STDOUT, 'Start newsletter ' . date("Y-m-d") . '. Emails quantity:' . $cursor->num_rows . "\n");

	startJobs($cursor, function($f) use ($letter) { sendEmailWorker($letter, $f); });
}

function sendEmailWorker($letter, $row)
{
	$sender   = new MailSender();
	$response = $sender->SendMail($row[0], $letter['subject'], $letter['message']);
	fwrite(STDOUT, 'Letter send to ' . $row[0] . ', response:' . $response . "\n");

	Logger::info('Letter send to ' . $row[0] . ', response:' . $response);
	Logger::dump_to_file(LOG_FILE);
	Logger::clear_log();
}

function startJobs($cursor, $func)
{
	for ($proc_num = 0; $proc_num < PROCESSES_NUM; $proc_num++) 
	{
		$pid = pcntl_fork();
		if ($pid < 0) {
			fwrite(STDERR, "Cannot fork\n");
			exit(1);
		}
		if ($pid == 0) break;
	}

	if ($pid) {
		for ($i = 0; $i < PROCESSES_NUM; $i++) 
		{
			pcntl_wait($status);
			$exitcode = pcntl_wexitstatus($status);
			if ($exitcode) exit(1);
		}
		return;
	}

	$l = $cursor->num_rows;
	for ($i = $proc_num; $i < $l; $i += PROCESSES_NUM) 
	{
		$cursor->data_seek($i);
		$func($cursor->fetch_row());
	}
	exit(0);
}