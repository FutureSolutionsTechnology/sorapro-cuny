<?php
class electronic_mail {
	public function send_message_private( $subject , $html ){
		global $database;
		$email_list = explode(",",NOTIFICATION_LIST);

		global $appliance;

		$narrative = "<h2>Narrative</h2><ol>";
		foreach( $appliance->narrative as $key => $value ){
			$narrative .= $value;
		}
		$narrative .= "</ol>";		
		
		$mail = new COM("Persits.MailSender");
			foreach( $email_list as $key => $value){
				$mail->AddAddress( $value );
			}

			$mail->FromName		=	"Jason McKinley";
			$mail->From			=	"jason@verdantautomation.com";
			$mail->Subject		=	$subject;
			$mail->Body			=	$html . $narrative;
			$mail->IsHTML		=	true;
			$mail->Queue		=	true;
			$mail->Send();
		$mail = null;
	}
	public function send_message_public( $to_email , $from_name , $from_email , $subject , $html ){
		$mail = new COM("Persits.MailSender");
			$mail->AddAddress( $to_email );

			$mail->FromName		=	$from_name;
			$mail->From			=	$from_email;
			$mail->Subject		=	$subject;
			$mail->Body			=	$html;
			$mail->IsHTML		=	true;
			$mail->Queue		=	true;
			$mail->Send();
		$mail = null;
	}
}
?>