<?php 
/* SendMail();
** SmtpDebug();
*/
class MailSender
{
	public function SendMail($to, $subject, $message) {

		$responce = false;
		$header  = "Date: ".date("D, j M Y G:i:s")." +0700\r\n" ;
		$header .= "From: =?".SENDING_EMAIL_CHARSET."?Q?".str_replace("+","_",str_replace("%","=",urlencode(SENDER_NAME)))."?= <".SMTP_SERVER_USER.">\r\n" ;
		$header .= "X-Mailer: The Bat! (v3.99.3) Professional\r\n" ;        
		$header .= "Reply-To: =?".SENDING_EMAIL_CHARSET."?Q?".str_replace("+","_",str_replace("%","=",urlencode(SENDER_NAME)))."?= <".SMTP_SERVER_USER.">\r\n" ;
		$header .= "X-Priority: 3 (Normal)\r\n" ;
		$header .= "Message-ID: <172562218.".date("YmjHis")."@mail.ru>\r\n" ;
		$header .= "To: <".$to.">\r\n" ;
		$header .= "Subject: =?".SENDING_EMAIL_CHARSET."?Q?".str_replace("+","_",str_replace("%","=",urlencode($subject)))."?=\r\n" ;
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: text/html; charset=".SENDING_EMAIL_CHARSET."\r\n" ;
		$header .= "Content-Transfer-Encoding: 8bit\r\n" ;

		if( !USE_EXTERNAL_SMTP_SERVER ) {
			//$success = mail($to, $subject, $message, $header);
			$success = true;
			if ($success) {
				$responce = 'Ok';
			} else {
				$responce = error_get_last()['message'];
			}
			sleep(2);
		} else {
			try {
				$connection = fsockopen('ssl://'.SMTP_SERVER_HOST, SMTP_SERVER_PORT) ;

				fputs($connection, 'EHLO '.SMTP_SERVER_HOST."\r\n") ;
				$this->SmtpDebug($connection);
				fputs($connection, "AUTH LOGIN\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, base64_encode(SMTP_SERVER_USER)."\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, base64_encode(SMTP_SERVER_PASSWORD)."\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, 'MAIL FROM: '.SMTP_SERVER_USER."\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, 'RCPT TO: '.$to."\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, "DATA\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, $header."\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, $message."\r\n.\r\n");
				$this->SmtpDebug($connection);
				fputs($connection, "QUIT\r\n");
				$this->SmtpDebug($connection);
				fclose($connection);
				$responce = 'Ok';
			} catch (\Exception $ex) {
				$responce = $ex->getMessage();
			}
		}
		return $responce;
	}

	public function SmtpDebug($connection) {

		$responce = ''; 
		while( $str = fgets($connection, 512) ) {
			$responce .= $str ;
			if( substr($str, 3, 1) == ' ' ) {
				break ;
			}
		}
		if( SMTP_DEBUG_OUTPUT ) { 
			Logger::info("$responce<br/>\r\n");
			Logger::dump_to_file(LOG_FILE);
			Logger::clear_log();
		}
		return $responce;
	}
}
?>