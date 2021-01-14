<?php //Simple email send example

namespace Ability\Warehouse;

$to = "matt.chandleraz@gmail.com";
//$to = "michael@keelsconsulting.com";
$subject = "Working now";
$message = "I can read this<a href=#>click me</a>";
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: <inventory@warehousedashboard.com>";//192.168.0.10

echo "Sending mail to $to ...<br>$to,$subject,$message, $headers";


mail($to,$subject,$message,$headers);

echo "Mail sent : $message";
