<?php
/**
 * Standalone Simple SMTP Socket Client for PHP
 * Handles SSL/TLS secure connections, AUTH LOGIN authentication, and sending HTML emails.
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $secure;

    public function __construct($host, $port, $username, $password, $secure = 'ssl') {
        $this->host = $host;
        $this->port = (int)$port;
        $this->username = $username;
        $this->password = $password;
        $this->secure = strtolower($secure);
    }

    public function send($to, $subject, $body, $fromName = 'FleetElite') {
        $transport = ($this->secure === 'ssl') ? 'ssl://' . $this->host : $this->host;
        
        // Open socket connection
        $socket = fsockopen($transport, $this->port, $errno, $errstr, 15);
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        $this->readSocket($socket); // Read server greeting

        // Say Hello
        $this->writeSocket($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?: 'localhost'));
        $this->readSocket($socket);

        // Handle TLS upgrade if specified
        if ($this->secure === 'tls') {
            $this->writeSocket($socket, "STARTTLS");
            $res = $this->readSocket($socket);
            if (strpos($res, '220') === false) {
                fclose($socket);
                throw new Exception("TLS negotiation failed: " . $res);
            }
            
            // Enable stream crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new Exception("Failed to enable TLS encryption.");
            }
            
            // Say Hello again over TLS encrypted tunnel
            $this->writeSocket($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?: 'localhost'));
            $this->readSocket($socket);
        }

        // Authenticate
        $this->writeSocket($socket, "AUTH LOGIN");
        $this->readSocket($socket);

        $this->writeSocket($socket, base64_encode($this->username));
        $this->readSocket($socket);

        $this->writeSocket($socket, base64_encode($this->password));
        $res = $this->readSocket($socket);
        if (strpos($res, '235') === false) {
            fclose($socket);
            throw new Exception("SMTP Authentication failed: " . $res);
        }

        // Set Mail Envelope
        $this->writeSocket($socket, "MAIL FROM: <" . $this->username . ">");
        $this->readSocket($socket);

        $this->writeSocket($socket, "RCPT TO: <" . $to . ">");
        $this->readSocket($socket);

        // Prepare Data body
        $this->writeSocket($socket, "DATA");
        $this->readSocket($socket);

        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=UTF-8",
            "From: $fromName <" . $this->username . ">",
            "To: <$to>",
            "Subject: $subject",
            "Date: " . date('r'),
            "Content-Transfer-Encoding: 8bit"
        ];

        // Format message data and signify end of message transmission using a dot . on its own line
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $this->writeSocket($socket, $data);
        $this->readSocket($socket);

        // Say goodbye
        $this->writeSocket($socket, "QUIT");
        fclose($socket);
        return true;
    }

    private function writeSocket($socket, $data) {
        fwrite($socket, $data . "\r\n");
    }

    private function readSocket($socket) {
        $data = "";
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === " ") {
                break;
            }
        }
        return $data;
    }
}
?>
