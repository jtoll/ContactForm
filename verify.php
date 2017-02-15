<?php

class GoogleRecaptcha {

    /* Google recaptcha API url */
    private $google_url = "https://www.google.com/recaptcha/api/siteverify";
    private $secret = 'your_secret_key';

    public function VerifyCaptcha($response, $remoteIp) {
        $url = $this->google_url."?secret=".$this->secret.
               "&response=".$response."&remoteIp=".$remoteIp;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $curlData = curl_exec($curl);

        curl_close($curl);

        $res = json_decode($curlData, TRUE);
        if($res['success'] == 'true')
            return TRUE;
        else
            return FALSE;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {

    $response = $_POST['g-recaptcha-response'];
    $remoteIp = $_SERVER['REMOTE_ADDR'];
    $host = $_SERVER['HTTP_HOST'];
    $error_page = 'error.html';

    if(!empty($response)) {

          $cap = new GoogleRecaptcha();
          $verified = $cap->VerifyCaptcha($response, $remoteIp);

          if($verified) {
            // code to handle a successful verification
            date_default_timezone_set('America/Chicago');
            $to = 'to@example.com';
            $from = 'from@example.com';
            $subject = 'Contact Form Submission';
            $success_page = 'success.html';
            $ip = $_SERVER['REMOTE_ADDR'];

            $headers = array (
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 7bit',
                'Date: ' . date('r', $_SERVER['REQUEST_TIME']),
                'Message-ID: <' . md5($_SERVER['REQUEST_TIME']) . '@' . $_SERVER['SERVER_NAME'] . '>',
                'From: ' . $from,
                'X-Mailer: PHP-' . phpversion(),
            );

            // basic sanitization of form data using strip_tags() and trim()
            $body = array (
                'Remote Address: ' . $ip,
                'Name: ' . trim(strip_tags($_POST['contact_name'])),
                'Email: ' . trim(strip_tags($_POST['contact_email'])),
                'Phone: ' . trim(strip_tags($_POST['contact_phone'])),
                'Message: ' . trim(strip_tags($_POST['contact_message']))
            );

            if (mail($to, $subject, implode("\n", $body), implode("\n", $headers))) {
              header('Location: http://' . $host . '/' . $success_page);
            } else {
              # catch email failure
              header('Location: http://' . $host . '/' . $error_page);
            }
            // end code for successful verification
          } else {
            // catch a $response but CAPTCHA is not verified?
            header('Location: http://' . $host . '/' . $error_page);
            exit;
          }
    } else {
        // catch a bad/empty $response
        header('Location: http://' . $host . '/' . $error_page);
        exit;
    }
}

?>
