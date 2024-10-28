<?php 
session_start();
require "connection.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer-6.9.2/src/Exception.php';
require 'PHPMailer-6.9.2/src/PHPMailer.php';
require 'PHPMailer-6.9.2/src/SMTP.php';


$email = "";
$name = "";
$errors = array();

// Initialize PHPMailer
$mail = new PHPMailer(true);

// Signup button
if (isset($_POST['signup'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $age = mysqli_real_escape_string($con, $_POST['age']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);
    $division = mysqli_real_escape_string($con, $_POST['division']);
    $district = mysqli_real_escape_string($con, $_POST['district']);
    $address = mysqli_real_escape_string($con, $_POST['address_t']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $blood = mysqli_real_escape_string($con, $_POST['blood']);
    
    if ($password !== $cpassword) {
        $errors['password'] = "Confirm password not matched!";
    }
    
    $email_check = "SELECT * FROM users WHERE email = '$email'";
    $res = mysqli_query($con, $email_check);
    if (mysqli_num_rows($res) > 0) {
        $errors['email'] = "Email that you have entered already exists!";
    }
    
    $mobile_check = "SELECT * FROM users WHERE mobile = '$mobile'";
    $res = mysqli_query($con, $mobile_check);
    if (mysqli_num_rows($res) > 0) {
        $errors['mobile'] = "Phone number that you have entered already exists!";
    }
    
    if (count($errors) === 0) {
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $code = rand(999999, 111111);
        $status = "notverified";
        
        $insert_data = "INSERT INTO users (name, mobile, email, age, password, division, district, address, gender, blood, code, status) 
                        VALUES ('$name', '$mobile', '$email', '$age', '$encpass', '$division', '$district', '$address', '$gender', '$blood', '$code', '$status')";
        
        $data_check = mysqli_query($con, $insert_data);
        if ($data_check) {
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Specify your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'rockshox913@gmail.com'; // SMTP username
                $mail->Password = 'scnzphfeaplhwwnc'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('rockshox913@gmail.com', 'Blood Donation');
                $mail->addAddress($email); // Add a recipient
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification Code';
                $mail->Body    = "Your verification code is $code";

                $mail->send();
                $info = "We've sent a verification code to your email - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                header('location: user-otp.php');
                exit();
            } catch (Exception $e) {
                $errors['otp-error'] = "Failed while sending code: {$mail->ErrorInfo}";
            }
        } else {
            $errors['db-error'] = "Failed while inserting data into database!";
        }
    }
}

// Verification code submit button
if (isset($_POST['check'])) {
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
    $check_code = "SELECT * FROM users WHERE code = $otp_code";
    $code_res = mysqli_query($con, $check_code);
    if (mysqli_num_rows($code_res) > 0) {
        $fetch_data = mysqli_fetch_assoc($code_res);
        $fetch_code = $fetch_data['code'];
        $email = $fetch_data['email'];
        $code = 0;
        $status = 'verified';
        
        $update_otp = "UPDATE users SET code = $code, status = '$status' WHERE code = $fetch_code";
        $update_res = mysqli_query($con, $update_otp);
        if ($update_res) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header('location: home.php');
            exit();
        } else {
            $errors['otp-error'] = "Failed while updating code!";
        }
    } else {
        $errors['otp-error'] = "You've entered incorrect code!";
    }
}

// Login button
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $check_email = "SELECT * FROM users WHERE email = '$email' AND admin_control=0";
    $res = mysqli_query($con, $check_email);
    if (mysqli_num_rows($res) > 0) {
        $fetch = mysqli_fetch_assoc($res);
        $fetch_pass = $fetch['password'];
        if (password_verify($password, $fetch_pass)) {
            $_SESSION['email'] = $email;
            $status = $fetch['status'];
            if ($status == 'verified') {
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                header('location: home.php');
            } else {
                $info = "It looks like you haven't verified your email - $email";
                $_SESSION['info'] = $info;
                header('location: user-otp.php');
            }
        } else {
            $errors['email'] = "Incorrect email or password!";
        }
    } else {
        $errors['email'] = "It looks like you're not yet a member! Click on the bottom link to signup.";
    }
}

// Forgot password form
if (isset($_POST['check-email'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $check_email = "SELECT * FROM users WHERE email='$email'";
    $run_sql = mysqli_query($con, $check_email);
    if (mysqli_num_rows($run_sql) > 0) {
        $code = rand(999999, 111111);
        $insert_code = "UPDATE users SET code = $code WHERE email = '$email'";
        $run_query = mysqli_query($con, $insert_code);
        if ($run_query) {
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Specify your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'rockshox913@gmail.com'; // SMTP username
                $mail->Password = 'scnzphfeaplhwwnc'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('rockshox913@gmail.com', 'Blood Donation');
                $mail->addAddress($email); // Add a recipient
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body    = "Your password reset code is $code";

                $mail->send();
                $info = "We've sent a password reset OTP to your email - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                header('location: reset-code.php');
                exit();
            } catch (Exception $e) {
                $errors['otp-error'] = "Failed while sending code: {$mail->ErrorInfo}";
            }
        } else {
            $errors['db-error'] = "Something went wrong!";
        }
    } else {
        $errors['email'] = "This email address does not exist!";
    }
}

// Reset OTP button
if (isset($_POST['check-reset-otp'])) {
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
    $check_code = "SELECT * FROM users WHERE code = $otp_code";
    $code_res = mysqli_query($con, $check_code);
    if (mysqli_num_rows($code_res) > 0) {
        $fetch_data = mysqli_fetch_assoc($code_res);
        $email = $fetch_data['email'];
        $_SESSION['email'] = $email;
        $info = "Please create a new password that you don't use on any other site.";
        $_SESSION['info'] = $info;
        header('location: new-password.php');
        exit();
    } else {
        $errors['otp-error'] = "You've entered incorrect code!";
    }
}

// Change password button
if (isset($_POST['change-password'])) {
    $_SESSION['info'] = "";
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);
    if ($password !== $cpassword) {
        $errors['password'] = "Confirm password not matched!";
    } else {
        $code = 0;
        $email = $_SESSION['email'];
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $update_pass = "UPDATE users SET code = $code, password = '$encpass' WHERE email = '$email'";
        $run_query = mysqli_query($con, $update_pass);
        if ($run_query) {
            $info = "Your password has been changed. Now you can login with your new password.";
            $_SESSION['info'] = $info;
            header('Location: password-changed.php');
        } else {
            $errors['db-error'] = "Failed to change your password!";
        }
    }
}

// Login button click
if (isset($_POST['login-now'])) {
    header('Location: login-user.php');
}
?>
