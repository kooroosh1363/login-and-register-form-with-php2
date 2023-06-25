<?php

include './config.php';
session_start();

if (isset($_POST['sub'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);
    $confirm = md5($_POST['confirm']);
    $userType = $_POST['userType'];

    $selectUser = "SELECT * FROM users WHERE email= '$email' && password = '$password'" ;
    $result = mysqli_query($conn, $selectUser);
    if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_array($result);

        if ($row['userType']=='admin') {
            $_SESSION['name'] = $row['name'];
            header('location:admin.php');
        }elseif($row['userType'] == 'user'){
            $_SESSION['name'] = $row['name'];
            header('location:page_user.php');

        }else{
            $error[]='incorrect password or email';
        }
    }
    
}
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

    <div class="container-form">
        <form action="#" method="POST">
           
            <?php

            if (isset($error)) {
                foreach ($error as $error) {
                    echo '<span class="msg-error">' . $error . '</span>';
                }
            }

            ?>
            <h3>Login</h3>
            <div class="field-input">
                <p>Enter Your Email <sup>*</sup></p>
                <input type="email" name="email" required placeholder="Please Enter Your Email">
            </div>
            <div class="field-input">
                <p>Enter Your Password <sup>*</sup></p>
                <input type="password" name="password" required placeholder="Please Enter Your password">
            </div>
            <input type="submit" name="sub" value="Login" class="btn-form">
            <p>Already Have A Account <a href="./register.php">Login Now</a></p>
        </form>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>