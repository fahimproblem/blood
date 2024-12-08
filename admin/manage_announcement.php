<?php
require('top.inc.php');
isAdmin();
require __DIR__ . '/../vendor/autoload.php';  // Adjust the path if your file is inside a subdirectory

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = '';
$title = '';
$text = '';
$msg = '';

if (isset($_GET['id']) && $_GET['id'] != '') {
    $id = get_safe_value($con, $_GET['id']);
    $res = mysqli_query($con, "SELECT * FROM announcement WHERE id='$id'");
    $check = mysqli_num_rows($res);
    if ($check > 0) {
        $row = mysqli_fetch_assoc($res);
        $id = $row['id'];
        $title = $row['head'];
        $text = $row['body'];
    } else {
        header('location: announcement.php');
        die();
    }
}

if (isset($_POST['submit'])) {
    $id = get_safe_value($con, $_POST['id']);
    $title = get_safe_value($con, $_POST['head']);
    $text = get_safe_value($con, $_POST['body']);

    // Check if the announcement already exists
    $res = mysqli_query($con, "SELECT * FROM announcement WHERE id='$id'");
    $check = mysqli_num_rows($res);
    if ($check > 0) {
        if (isset($_GET['id']) && $_GET['id'] != '') {
            $getData = mysqli_fetch_assoc($res);
            if ($id != $getData['id']) {
                $msg = "ANNOUNCEMENT ALREADY EXISTS";
            }
        } else {
            $msg = "ANNOUNCEMENT ALREADY EXISTS";
        }
    }

    // If no errors, insert or update the announcement
    if ($msg == '') {
        if (isset($_GET['id']) && $_GET['id'] != '') {
            $update_sql = "UPDATE announcement SET head='$title', body='$text' WHERE id='$id'";
            mysqli_query($con, $update_sql);
        } else {
            $insert_sql = "INSERT INTO announcement (id, head, body, status) VALUES ('$id', '$title', '$text', 1)";
            mysqli_query($con, $insert_sql);
        }

        // Send the announcement to all users via email
        sendAnnouncementEmail($title, $text);

        header('location: announcement.php');
        die();
    }
}

// Function to send announcement to all users
function sendAnnouncementEmail($title, $text) {
    global $con;

    // Fetch all users' email addresses from the database
    $sql = "SELECT email FROM users";
    $result = mysqli_query($con, $sql);

    // Set up PHPMailer
   // require 'vendor/autoload.php'; // Assuming you have installed PHPMailer using Composer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Specify your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'rockshox913@gmail.com'; // Your Gmail address
        $mail->Password = 'scnzphfeaplhwwnc'; // Use the app password here (16 characters)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; // SMTP port (use 465 for SSL)

        // Sender's email
        $mail->setFrom('rockshox913@gmail.com', 'Blood Donation');

        // Loop through all users and send the announcement
        while ($row = mysqli_fetch_assoc($result)) {
            $email = $row['email'];

            // Add recipient
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "New Blood Donation Announcement: $title";
            $mail->Body    = "Dear User,<br><br>We have an important announcement regarding blood donation:<br><br><strong>$title</strong><br><br>$text<br><br>Best regards,<br>Blood Donation System";

            // Send email
            if (!$mail->send()) {
                echo "Failed to send announcement to $email<br>";
            }

            // Clear recipients for the next email
            $mail->clearAddresses();
        }

        echo "Announcement sent to all users!";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

?>

<div class="content pb-0">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header"><strong>Announcement Form</strong><small> </small></div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="card-body card-block">
                            <div class="form-group">
                                <label for="categories" class="form-control-label">ID</label>
                                <input type="text" name="id" placeholder="Enter ID" class="form-control" required value="<?php echo $id ?>">
                            </div>
                            <div class="form-group">
                                <label for="categories" class="form-control-label">Title</label>
                                <input type="text" name="head" placeholder="Enter Title" class="form-control" required value="<?php echo $title ?>">
                            </div>
                            <div class="form-group">
                                <label for="categories" class="form-control-label">Text</label>
                                <textarea type="text" name="body" placeholder="Enter Text" class="form-control" required><?php echo $text ?></textarea>
                            </div>
                            <button id="payment-button" name="submit" type="submit" class="btn btn-lg btn-info btn-block">
                                <span id="payment-button-amount">Submit</span>
                            </button>
                            <div class="field_error"><?php echo $msg ?></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>