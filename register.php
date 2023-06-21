<?php

include './config.php';

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Register</title>


</head>

<body>

    <div class="containe-form">
        <form action="#" method="POST">
            <img src="image source" class="img" alt="image">
            <?php

            if (isset($error)) {
                foreach ($error as $error) {
                    echo '<span class="msg-error">' . $error . '</span>';
                }
            }

            ?>
            <h3>Register Now</h3>
            <div class="field-input">
                <p>Your Name <sup>*</sup></p>
                <input type="text" name="username" required placeholder="Please Enter Your Name">
            </div>
            <div class="field-input">
                <p>Your Email <sup>*</sup></p>
                <input type="email" name="email" required placeholder="Please Enter Your Email">
            </div>
            <div class="field-input">
                <p>Your Password <sup>*</sup></p>
                <input type="password" name="password" required placeholder="Please Enter Your password">
            </div>
            <div class="field-input">
                <p>Confirm Your Password<sup>*</sup></p>
                <input type="password" name="confirm" required placeholder="Please confirm Your password">
            </div>
            <div class="field-input">
                <p>User Type<sup>*</sup></p>
                <select name="type-user" >
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <input type="submit" name="sub" value="Register Now" class=""btn-form>
            <p>Already Have A Account <a href="./login.php">Login Now</a></p>
        </form>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>