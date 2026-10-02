<?php
require __DIR__ . '/includes/bootstrap.php';
if (!is_post()) redirect('page.php?slug=contact');
verify_csrf();
$name = mb_substr((string) input('name'), 0, 120); $email = (string) input('email'); $msg = mb_substr((string) input('message'), 0, 5000);
if (input('website') !== '') redirect('');   // honeypot
if ($name === '' || $msg === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Please fill in your name, a valid email and a message.');
} else {
    insert('contact_messages', ['name' => $name, 'email' => $email, 'message' => $msg]);
    flash('success', 'Thanks! Your message has been sent.');
}
redirect('page.php?slug=contact');
