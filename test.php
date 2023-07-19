<?php

// GoIP configuration
$goipIp = '192.168.1.110';
$goipPort = 10991;//port 1
$goipUsername = 'admin';
$goipPassword = 'admin';

// Message configuration
$message = 'Your authentication code is: 1234';
$numbers = ['+213656181996', '+213656181996', '+213656181996']; // List of numbers to send messages to

// Connect to GoIP
$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if ($socket === false) {
    throw new Exception("error while init");
}

$socketSetOptions = socket_set_option(
    $socket,
    SOL_SOCKET,
    SO_RCVTIMEO,
    [
        'sec' => 20,
        'usec' => 0
    ]
);

if (!$socketSetOptions) {
    throw new Exception("Error while socket options");
}

socket_connect($socket, $goipIp, $goipPort);

$command = "LOGIN admin admin";
$socketSendTo = socket_sendto($socket, $command, strlen($command), 0, $goipIp, $goipPort);
$socketRecvFrom = socket_recvfrom($socket, $buffer, 2048, 0, $goipIp, $goipPort);
die($socketRecvFrom);
if (strpos($buffer, 'Login Success') === false) {
    die('Login failed.');
}

$command = "GET SESSION";
$socketSendTo = socket_sendto($socket, $command, strlen($command), 0, $goipIp, $goipPort);
$socketRecvFrom = socket_recvfrom($socket, $buffer, 2048, 0, $goipIp, $goipPort);
preg_match('/^SESSION:(\d+)/', $buffer, $matches);
$sessionId = $matches[1];



// Authenticate with GoIP
$command = "PASSWORD 444 {$goipPassword}";
echo $command."\n";
$socketSendTo = socket_sendto($socket, $message, strlen($message), 0, $goipIp, $goipPort);
if ($socketSendTo === false) {
    throw new Exception("Error while sending");
}
//socket_send($socket, $command, strlen($command), 0);
$socketRecvFrom = socket_recvfrom($socket, $buffer, 2048, 0, $goipIp, $goipPort);
echo $socketRecvFrom;
if ($socketRecvFrom === false) {
    throw new Exception("Erro while receiving");
}
echo $buffer."\n";

if (strpos($buffer, 'Password is OK') === false) {
    die('Authentication failed.');
}

// Send messages to all numbers
/* foreach ($numbers as $number) {
    $command = "MSG {$number} {$message}\r\n";
    socket_send($socket, $command, strlen($command), 0);
    $response = socket_read($socket, 1024);
    echo "Message sent to {$number}: {$response}\n";
}
 */
// Close connection to GoIP
socket_close($socket);