<?php

use VnBiz\VnBiz;
use VnBiz\VnBizError;
use PHPMailer\PHPMailer\PHPMailer;

function vnbiz_init_module_email() {
    vnbiz_model_add('email')
        ->ui([
            'icon' => 'mail',
            'title' => 'subject',
            'subtitle' => 'to_name'
        ])
        ->slug('subject_template_name', 'content_template_name')
        ->string('from_email', 'to_email')
        ->string('from_name', 'to_name')
        ->string('subject')
        ->string('content')
        ->string('language')
        ->int('view_count')
        ->author()
        ->require('from_email', 'to_email')
        ->no_update()
        ->index('index_from_email', ['from_email'])
        ->index('index_to_email', ['to_email'])
        ->text_search('subject_template_name', 'content_template_name', 'from_email', 'to_email', 'subject', 'content')
        ->db_before_create(function (&$context) {
            $model = &$context['model'];
            $subject_template_name = vnbiz_get_key($model, 'subject_template_name');
            $content_template_name = vnbiz_get_key($model, 'content_template_name');
            $language = vnbiz_get_key($model, 'language');
            $subject = vnbiz_get_key($model, 'subject');
            $content = vnbiz_get_key($model, 'content');
            $data = vnbiz_get_key($model, 'data', []);
            if ($subject_template_name) {
                $subject = vnbiz_render($subject_template_name, $language, $data);
            }
            
            if ($content_template_name) {
                $content = vnbiz_render($content_template_name, $language, $data);
            }

            $model['view_count'] = 0;
            $model['subject'] = $subject;
            $model['content'] = $content;

        })
        ->require('subject', 'content')
        ->db_before_create(function (&$context) {
            $model = &$context['model'];
            $from_name = vnbiz_get_key($model, 'from_name');
            $to_name = vnbiz_get_key($model, 'to_name');
            $from_email = vnbiz_get_key($model, 'from_email');
            $to_email = vnbiz_get_key($model, 'to_email');
            $subject = vnbiz_get_key($model, 'subject');
            $content = vnbiz_get_key($model, 'content');
            
            if (defined('MAILER_SMTP_HOST')) {
                $mailer = new PHPMailer(true);
                $mailer->isSMTP();                                            //Send using SMTP
                $mailer->Host       = MAILER_SMTP_HOST;                     //Set the SMTP server to send through
                $mailer->SMTPAuth   = true;//defined('MAILER_SMTP_USERNAME');                                   //Enable SMTP authentication
                $mailer->Username   = MAILER_SMTP_USERNAME;                     //SMTP username
                $mailer->Password   = MAILER_SMTP_PASSWORD;                               //SMTP password
                            //Enable implicit TLS encryption
                $mailer->Port       = MAILER_SMTP_PORT;    
                if ($mailer->Port === 587) {
                    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                if ($mailer->Port === 465) {
                    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
                $mailer->isHTML(true);
                $mailer->Subject = $subject;
                $mailer->Body = $content;
                $mailer->setFrom($from_email, $from_name);
                $mailer->addAddress($to_email, $to_name);
                $mailer->send();
            } else {
                $from = $from_name ? "$from_name <$from_email>" : $from_email;
                $to = $to_name ? "$to_name <$to_email>" : $to_email;

                $headers =[
                    'From' => $from,
                    'Content-type' => 'text/html; charset=UTF-8'
                ];
                $success = mail($to, $subject, $content, $headers);
                if (!$success) {
                    $errorMessage = error_get_last();
                    if (isset($errorMessage) && isset($errorMessage['message'])) {
                         $errorMessage = error_get_last()['message'];
                    } else {
                        $errorMessage = 'UNKNOWN';
                    }
                    throw new VnBizError($errorMessage, 'email_error');
                }
            }
        })
        ->write_permission('super', 'email_write')
        ->read_permission('super', 'email_read')
    ;
}

function vnbiz_mail_content_template($from_email, $to_email, $subject, $content_template_name, $language, $data, $from_name = '', $to_name = '') {
    return vnbiz_model_create('email', [
        'from_email' => $from_email,
        'to_email' => $to_email,
        'from_name' => $from_name,
        'to_name' => $to_name,
        'subject' => $subject,
        'language' => $language,
        'content_template_name' => $content_template_name,
        'data' => $data
    ]);
}

function vnbiz_mail_template_to_user($from_email, $user_id, $subject, $content_template_name, $data, $from_name = '') {
    $user = vnbiz_model_find_one('user', ['id' => $user_id]);
    if (!$user ) {
        throw new VnBizError('Sending email Error: no such user id, ' . $user_id, 'email_error');
    }
    if (!isset($user['email'])) {
        throw new VnBizError('Sending email Error: The user do not have email address, ' . $user_id, 'email_error');
    }
    $data['USER'] = $user;
    return vnbiz_model_create('email', [
        'from_email' => $from_email,
        'to_email' => $user['email'],
        'from_name' => $from_name,
        'to_name' => vnbiz_get_key($user, 'alias', vnbiz_get_key($user, 'first_name')),
        'subject' => $subject,
        'content_template_name' => $content_template_name,
        'language' => vnbiz_get_key($user, 'language', 'en'),
        'data' => $data
    ]);
}